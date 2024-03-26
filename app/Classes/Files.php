<?php
namespace App\Classes;
use Illuminate\Support\Str;

class Files
{
    private static array $allowed = ['jpg','jpeg','png','gif','webp','pdf','xlsx','csv'];

    public static function upload($file, string $folder, int $maxKb = 2048): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, self::$allowed))
            throw new \InvalidArgumentException('File type .'.$ext.' is not allowed.');
        if ($file->getSize() > $maxKb * 1024)
            throw new \InvalidArgumentException('File exceeds '.$maxKb.'KB limit.');
        $filename = time().'_'.Str::random(10).'.'.$ext;
        $file->move(public_path('uploads/'.$folder), $filename);
        return $filename;
    }

    public static function delete(?string $filename, string $folder): void
    {
        if (!$filename) return;
        $path = public_path('uploads/'.$folder.'/'.$filename);
        if (file_exists($path)) unlink($path);
    }

    /**
     * Strip UTF-8 BOM added by Excel to CSV exports.
     * Without this, the first heading key becomes unrecognisable.
     */
    public static function stripBom(string $content): string
    {
        return str_starts_with($content, "\xEF\xBB\xBF") ? substr($content, 3) : $content;
    }

    /**
     * Detect and convert Windows-1252 encoded content to UTF-8.
     */
    public static function ensureUtf8(string $content): string
    {
        if (!mb_detect_encoding($content, 'UTF-8', true)) {
            return mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }
        return $content;
    }
}
