<?php
namespace App\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class ApiBaseController extends Controller
{
    public function sendResponse($data, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json(['success'=>true,'message'=>$message,'data'=>$data], $code);
    }

    public function sendError(string $message, array $errors = [], int $code = 422): JsonResponse
    {
        return response()->json(['success'=>false,'message'=>$message,'errors'=>$errors], $code);
    }

    protected function paginate($query, int $default = 15): array
    {
        $perPage   = min((int)request()->input('per_page', $default), 100);
        $paginator = $query->paginate($perPage);
        return [
            'data' => $paginator->items(),
            'meta' => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
        ];
    }

    protected function applySorting(Builder $query, string $sortCol = 'id', string $defaultDir = 'desc'): Builder
    {
        $col = request()->input('sort', $sortCol);
        $dir = request()->input('order', $defaultDir);
        if (!in_array($dir, ['asc','desc'])) $dir = $defaultDir;
        return $query->orderBy($col, $dir);
    }
}
