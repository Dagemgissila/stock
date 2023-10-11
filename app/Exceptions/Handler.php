<?php
namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = ['current_password','password','password_confirmation'];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {});
    }

    public function render($request, Throwable $e): mixed
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $e);
        }
        return parent::render($request, $e);
    }

    private function handleApiException($request, Throwable $e): mixed
    {
        if ($e instanceof ModelNotFoundException) {
            return response()->json(['success'=>false,
                'message'=>class_basename($e->getModel()).' not found.'], 404);
        }
        if ($e instanceof AuthenticationException) {
            return response()->json(['success'=>false,'message'=>'Unauthenticated.'], 401);
        }
        if ($e instanceof ValidationException) {
            return response()->json(['success'=>false,'message'=>'Validation failed.',
                'errors'=>$e->errors()], 422);
        }
        if ($e instanceof QueryException) {
            return response()->json(['success'=>false,
                'message'=>'A database error occurred. Please try again.'], 500);
        }
        return response()->json(['success'=>false,
            'message'=>config('app.debug') ? $e->getMessage() : 'Server error.'], 500);
    }
}
