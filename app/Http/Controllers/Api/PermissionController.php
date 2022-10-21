<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;

class PermissionController extends ApiBaseController {
    public function index(): JsonResponse {
        $permissions = Permission::all()->groupBy('module');
        return $this->sendResponse($permissions);
    }
}
