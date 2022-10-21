<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\ApiBaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UsersController extends ApiBaseController {
    public function index(Request $request): JsonResponse {
        $query = User::with('role');
        if ($request->search) $query->where('name','like','%'.$request->search.'%');
        return $this->sendResponse($this->paginate($query));
    }
    public function store(Request $request): JsonResponse {
        $data = $request->validate(['name'=>'required','email'=>'required|email|unique:users',
            'password'=>'required|min:8','role_id'=>'nullable|exists:roles,id']);
        $data['company_id'] = auth('api')->user()->company_id;
        $data['password']   = Hash::make($data['password']);
        return $this->sendResponse(User::create($data)->load('role'),'User created',201);
    }
    public function changePassword(Request $request, int $id): JsonResponse {
        $request->validate(['password'=>'required|min:8|confirmed']);
        User::findOrFail($id)->update(['password'=>Hash::make($request->password)]);
        return $this->sendResponse([],'Password updated');
    }
    public function destroy(int $id): JsonResponse {
        User::findOrFail($id)->delete(); return $this->sendResponse([],'Deleted');
    }
}
