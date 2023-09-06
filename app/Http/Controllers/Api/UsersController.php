<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UsersController extends ApiBaseController {
    public function index(Request $request): JsonResponse {
        $query = User::with('roles');
        if ($request->search)       $query->where('name','like','%'.$request->search.'%');
        if ($request->warehouse_id) $query->whereHas('warehouses',fn($q)=>$q->where('warehouse_id',$request->warehouse_id));
        return $this->sendResponse($this->paginate($this->applySorting($query,'name','asc')));
    }
    public function store(Request $request): JsonResponse {
        $data = $request->validate(['name'=>'required','email'=>'required|email|unique:users',
            'password'=>'required|min:8','role_id'=>'nullable|exists:roles,id']);
        $data['company_id'] = auth('api')->user()->company_id;
        $data['password']   = Hash::make($data['password']);
        $user = User::create($data);
        if ($request->role_id) $user->roles()->sync([$request->role_id]);
        if ($request->warehouse_ids) $user->warehouses()->sync($request->warehouse_ids);
        return $this->sendResponse($user->load('roles'),'User created',201);
    }
    public function show(int $id): JsonResponse {
        return $this->sendResponse(User::with(['roles','warehouses'])->findOrFail($id));
    }
    public function update(Request $request, int $id): JsonResponse {
        $user = User::findOrFail($id);
        $user->update($request->only(['name','phone','status']));
        if ($request->role_id)      $user->roles()->sync([$request->role_id]);
        if ($request->warehouse_ids)$user->warehouses()->sync($request->warehouse_ids);
        return $this->sendResponse($user->fresh()->load(['roles','warehouses']),'User updated');
    }
    public function changePassword(Request $request, int $id): JsonResponse {
        $request->validate(['password'=>'required|min:8|confirmed']);
        User::findOrFail($id)->update(['password'=>Hash::make($request->password)]);
        return $this->sendResponse([],'Password updated');
    }
    public function destroy(int $id): JsonResponse {
        User::findOrFail($id)->delete(); return $this->sendResponse([],'User deleted');
    }
    public function warehouses(int $id): JsonResponse {
        return $this->sendResponse(User::findOrFail($id)->warehouses);
    }
}
