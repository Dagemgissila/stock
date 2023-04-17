<?php
namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class ApiBaseController extends Controller
{
    public function sendResponse($data, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $code);
    }

    public function sendError(string $message, array $errors = [], int $code = 422): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message, 'errors' => $errors], $code);
    }

    protected function paginate($query, int $defaultPerPage = 15): array
    {
        $perPage   = (int) request()->input('per_page', $defaultPerPage);
        $perPage   = min($perPage, 100); // hard cap at 100 per page
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

    protected function applySorting(Builder $query, string $defaultSort = 'id', string $defaultDir = 'desc'): Builder
    {
        $sort = request()->input('sort',  $defaultSort);
        $dir  = request()->input('order', $defaultDir);
        return $query->orderBy($sort, in_array($dir, ['asc','desc']) ? $dir : 'desc');
    }
}
