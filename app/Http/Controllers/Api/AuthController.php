<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate(['username' => ['required'], 'password' => ['required'], 'device_name' => ['nullable', 'string', 'max:100']]);
        $user = User::where('username', $data['username'])->where('is_active', true)->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Username atau password tidak sesuai.'], 422);
        }

        $token = $user->createToken($data['device_name'] ?? 'api-client')->plainTextToken;
        AuditLog::create(['user_id' => $user->id, 'event' => 'api_login', 'created_at' => now()]);

        return response()->json(['token' => $token, 'token_type' => 'Bearer', 'must_change_password' => $user->must_change_password, 'user' => $this->userData($user)]);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $this->userData($request->user())]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        AuditLog::create(['user_id' => $request->user()->id, 'event' => 'api_logout', 'created_at' => now()]);

        return response()->noContent();
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', 'min:8']]);
        $request->user()->update(['password' => $data['password'], 'must_change_password' => false]);
        AuditLog::create(['user_id' => $request->user()->id, 'event' => 'password_changed', 'created_at' => now()]);

        return response()->json(['message' => 'Password berhasil diubah.']);
    }

    private function userData(User $user): array
    {
        return $user->loadMissing('student.classRoom', 'teacher', 'parentProfile')->only(['id', 'name', 'username', 'email', 'role', 'must_change_password', 'is_active']) + [
            'student' => $user->student,
            'teacher' => $user->teacher,
            'parent_profile' => $user->parentProfile,
        ];
    }
}
