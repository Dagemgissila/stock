<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\FrontWebsiteSettings;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FrontWebsiteSettingsController extends ApiBaseController {
    public function show(): JsonResponse {
        $settings = FrontWebsiteSettings::where('company_id', auth('api')->user()->company_id)->first();
        return $this->sendResponse($settings);
    }
    public function update(Request $request): JsonResponse {
        $settings = FrontWebsiteSettings::updateOrCreate(
            ['company_id' => auth('api')->user()->company_id],
            $request->only(['store_name','store_description','primary_color','meta_title','meta_description','is_active'])
        );
        return $this->sendResponse($settings,'Settings updated');
    }
}
