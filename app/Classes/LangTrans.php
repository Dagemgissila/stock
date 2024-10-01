<?php
namespace App\Classes;
use App\Models\Lang;
use Illuminate\Support\Facades\Cache;

class LangTrans
{
    public static function getTranslations(string $langKey): array
    {
        return Cache::remember('translations_'.$langKey, 3600, function() use ($langKey){
            $lang = Lang::where('key',$langKey)->with('translations')->first();
            if (!$lang) return [];
            return $lang->translations->pluck('value','key')->toArray();
        });
    }

    public static function trans(string $key, string $langKey='en'): string
    {
        $translations = static::getTranslations($langKey);
        return $translations[$key] ?? $key;
    }

    public static function clearCache(string $langKey): void
    {
        Cache::forget('translations_'.$langKey);
    }
}
