<?php
namespace App\Classes;

use Illuminate\Support\Str;

class Files
{
    private static array $allowedExtensions = ['jpg','jpeg','png','gif','webp','pdf','xlsx','csv'];

    public static function upload($file, string $folder, int $maxSizeKb = 2048): string
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, self::$allowedExtensions)) {
            throw new \InvalidArgumentException('File type .' . $ext . ' is not allowed.');
        }
        if ($file->getSize() > $maxSizeKb * 1024) {
            throw new \InvalidArgumentException('File size exceeds ' . $maxSizeKb . 'KB limit.');
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

    /**
     * Strip UTF-8 BOM from CSV string (added by Excel on export).
     * BOM causes first column key to be unrecognisable in heading-row imports.
     */
    public static function stripBom(string $content): string
    {
        if (str_starts_with($content, "ï»¿")) {
            return substr($content, 3);
        }
        return $content;
    }

    /**
     * Detect and convert Windows-1252 encoded file content to UTF-8.
     */
    public static function ensureUtf8(string $content): string
    {
        if (!mb_detect_encoding($content, 'UTF-8', true)) {
            return mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }
        return $content;
    }
}
