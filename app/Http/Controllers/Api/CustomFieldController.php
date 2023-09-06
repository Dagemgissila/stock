<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\CustomField;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomFieldController extends ApiBaseController {
    public function index(): JsonResponse { return $this->sendResponse(CustomField::all()); }
    public function store(Request $request): JsonResponse {
        $data = $request->validate(['name'=>'required','type'=>'required|in:text,number,date,dropdown',
            'is_required'=>'boolean','values'=>'required_if:type,dropdown|array']);
        $data['company_id'] = auth('api')->user()->company_id;
        return $this->sendResponse(CustomField::create($data),'Custom field created',201);
    }
    public function update(Request $request, int $id): JsonResponse {
        CustomField::findOrFail($id)->update($request->only(['name','type','values','is_required','default_value']));
        return $this->sendResponse(CustomField::find($id),'Updated');
    }
    public function destroy(int $id): JsonResponse {
        CustomField::findOrFail($id)->delete(); return $this->sendResponse([],'Deleted');
    }
}
