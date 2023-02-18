<?php
namespace App\Classes;

use Illuminate\Support\Str;

class Common
{
    public static function generateUniqueSlug(string $name, string $model): string
    {
        $slug  = Str::slug($name);
        $count = $model::where('slug', 'like', $slug . '%')->count();
        return $count ? $slug . '-' . ($count + 1) : $slug;
    }

    public static function uploadFile($request, string $field, string $folder): ?string
    {
        if (!$request->hasFile($field)) return null;
        $file     = $request->file($field);
        $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('uploads/' . $folder), $filename);
        return $filename;
    }

    public static function deleteFile(?string $filename, string $folder): void
    {
        if ($filename) {
            $path = public_path('uploads/' . $folder . '/' . $filename);
            if (file_exists($path)) unlink($path);
        }
    }

    public static function formatCurrency(float $amount, string $symbol = '$', string $position = 'prefix'): string
    {
        $formatted = number_format($amount, 2);
        return $position === 'prefix' ? $symbol . $formatted : $formatted . ' ' . $symbol;
    }
}
