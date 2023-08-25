<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Company;
use App\Models\Settings;
use App\Classes\Files;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CompanyController extends ApiBaseController
{
    public function show(): JsonResponse
    {
        $company = Company::with(['currencies','warehouses'])->findOrFail(auth('api')->user()->company_id);
        return $this->sendResponse($company);
    }

    public function update(Request $request): JsonResponse
    {
        $company = Company::findOrFail(auth('api')->user()->company_id);
        $data    = $request->only(['name','email','phone','country','currency_code','is_rtl']);

        if ($request->hasFile('logo')) {
            Files::delete($company->logo, 'companies');
            $data['logo'] = Files::upload($request->file('logo'), 'companies');
        }
        if ($request->hasFile('login_image')) {
            Files::delete($company->login_image, 'companies');
            $data['login_image'] = Files::upload($request->file('login_image'), 'companies');
        }

        $company->update($data);
        return $this->sendResponse($company->fresh(), 'Company updated');
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $companyId = auth('api')->user()->company_id;
        foreach ($request->settings as $name => $value) {
            Settings::updateSetting($name, $value, $companyId);
        }
        return $this->sendResponse([], 'Settings updated');
    }
}
