<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class UploadPolicy
{
    /** Validates the file limits required by BR-09 and returns its category. */
    public function validate(UploadedFile $file, string $field = 'file'): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $groups = [
            'image' => [['jpg', 'jpeg', 'png', 'gif', 'webp'], 5 * 1024],
            'pdf' => [['pdf'], 10 * 1024],
            'audio' => [['mp3', 'wav', 'm4a', 'aac', 'ogg'], 15 * 1024],
            'video' => [['mp4', 'mov', 'avi', 'webm', 'mkv'], 50 * 1024],
        ];

        foreach ($groups as $type => [$extensions, $maxKilobytes]) {
            if (in_array($extension, $extensions, true)) {
                if ($file->getSize() > $maxKilobytes * 1024) {
                    throw ValidationException::withMessages([$field => ["Ukuran file {$type} melebihi batas yang diizinkan."]]);
                }
                return $type;
            }
        }

        throw ValidationException::withMessages([$field => ['Format file tidak diizinkan.']]);
    }
}
