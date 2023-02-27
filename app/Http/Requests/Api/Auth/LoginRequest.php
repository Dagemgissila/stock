<?php
namespace App\Http\Requests\Api\Auth;
use App\Http\Requests\Api\BaseRequest;

class LoginRequest extends BaseRequest {
    public function rules(): array {
        return [
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ];
    }
}
