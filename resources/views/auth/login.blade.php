<x-layouts.app title="Login">
    <div class="grid min-h-screen place-items-center p-4">
        <form method="post" action="{{ route('login.perform') }}" class="panel w-full max-w-md">
            @csrf
            <h1 class="mb-5 text-2xl font-bold">Masuk LMS</h1>
            <label class="label mb-3">Username <input class="input" name="username" value="{{ old('username') }}" required></label>
            <label class="label mb-5">Password <input class="input" type="password" name="password" required></label>
            @error('username') <p class="mb-3 text-sm text-red-600">{{ $message }}</p> @enderror
            <button class="btn w-full">Masuk</button>
        </form>
    </div>
</x-layouts.app>
