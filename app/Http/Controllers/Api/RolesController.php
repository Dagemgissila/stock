<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RolesController extends ApiBaseController {
    public function index(): JsonResponse { return $this->sendResponse(Role::with('permissions')->get()); }
    public function store(Request $request): JsonResponse {
        $data = $request->validate(['name'=>'required|max:100','display_name'=>'nullable']);
        $data['company_id'] = auth('api')->user()->company_id;
        $role = Role::create($data);
        if ($request->permissions) $role->permissions()->sync($request->permissions);
        return $this->sendResponse($role->load('permissions'),'Role created',201);
    }
    public function syncPermissions(Request $request, int $id): JsonResponse {
        $role = Role::findOrFail($id);
        $role->permissions()->sync($request->input('permission_ids',[]));
        return $this->sendResponse($role->load('permissions'),'Permissions updated');
    }
    public function destroy(int $id): JsonResponse {
        Role::findOrFail($id)->delete(); return $this->sendResponse([],'Deleted');
    }
}
