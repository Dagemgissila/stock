<?php
namespace App\Http\Controllers\Api\Common;
use App\Http\Controllers\ApiBaseController;
use App\Models\Lang;
use App\Models\Translation;
use App\Classes\LangTrans;
use App\Imports\TranslationImport;
use App\Exports\LangExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LangController extends ApiBaseController {
    public function index(): JsonResponse {
        return $this->sendResponse(Lang::where('status',1)->get());
    }
    public function translations(string $key): JsonResponse {
        return $this->sendResponse(LangTrans::getTranslations($key));
    }
    public function importTranslations(Request $request, int $langId): JsonResponse {
        $request->validate(['file'=>'required|file|mimes:csv,xlsx']);
        Excel::import(new TranslationImport($langId), $request->file('file'));
        LangTrans::clearCache(Lang::findOrFail($langId)->key);
        return $this->sendResponse([],'Translations imported and cache cleared');
    }
    public function exportTranslations(int $langId): mixed {
        $lang = Lang::findOrFail($langId);
        return Excel::download(new LangExport($langId), $lang->key.'_translations.xlsx');
    }
}
