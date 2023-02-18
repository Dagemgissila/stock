<?php
namespace App\Classes;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Files
{
    public static function upload($file, string $folder, int $maxSizeKb = 2048): string
    {
        $allowedTypes = ['jpg','jpeg','png','gif','webp','pdf','xlsx','csv'];
        $ext = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, $allowedTypes)) {
            throw new \InvalidArgumentException('File type .' . $ext . ' not allowed.');
        }
        if ($file->getSize() > $maxSizeKb * 1024) {
            throw new \InvalidArgumentException('File exceeds maximum size of ' . $maxSizeKb . 'KB.');
        }

        $filename = time() . '_' . Str::random(10) . '.' . $ext;
        $file->move(public_path('uploads/' . $folder), $filename);
        return $filename;
    }

    public static function delete(?string $filename, string $folder): void
    {
        if (!$filename) return;
        $path = public_path('uploads/' . $folder . '/' . $filename);
        if (file_exists($path)) unlink($path);
    }

    public static function generateThumbnail(string $filename, string $folder): void
    {
        // Thumbnail generation placeholder - uses GD or Intervention Image in production
        \Log::info('Thumbnail requested for: ' . $folder . '/' . $filename);
    }
}
