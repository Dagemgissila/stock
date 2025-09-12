<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Class ApiBaseController
 *
 * Base controller for all API endpoints. Provides standardized response
 * formatting, pagination, sorting, filtering, and error handling helpers.
 * All API controllers should extend this class to ensure consistency
 * in response structure across the entire application.
 *
 * @package App\Http\Controllers
 * @author  StockManager Development Team
 * @version 2.1.0
 */
class ApiBaseController extends Controller
{
    /**
     * Default number of items per page for paginated responses.
     *
     * @var int
     */
    protected int $defaultPerPage = 15;

    /**
     * Maximum allowed items per page to prevent memory exhaustion.
     *
     * @var int
     */
    protected int $maxPerPage = 100;

    /**
     * Default sort column when no sort parameter is provided.
     *
     * @var string
     */
    protected string $defaultSortColumn = 'id';

    /**
     * Default sort direction when no order parameter is provided.
     *
     * @var string
     */
    protected string $defaultSortDirection = 'desc';

    /**
     * Send a successful JSON response.
     *
     * Wraps the given data in a standardized success response envelope
     * with success flag, message, and data payload. Used by all API
     * endpoints to ensure consistent response structure.
     *
     * @param  mixed   $data    The response payload (array, object, collection)
     * @param  string  $message Human-readable success message
     * @param  int     $code    HTTP status code (default: 200)
     * @return JsonResponse
     *
     * @example
     * return $this->sendResponse($product, 'Product created successfully', 201);
     */
    public function sendResponse(mixed $data, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    /**
     * Send an error JSON response.
     *
     * Returns a standardized error response with success=false, an error
     * message, and optionally a structured errors array containing field-level
     * validation errors or other error details.
     *
     * @param  string  $message Human-readable error description
     * @param  array   $errors  Field-level errors (e.g. from validation failures)
     * @param  int     $code    HTTP status code (default: 422 Unprocessable Entity)
     * @return JsonResponse
     *
     * @example
     * return $this->sendError('Validation failed.', $validator->errors()->toArray(), 422);
     */
    public function sendError(string $message, array $errors = [], int $code = 422): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Send a 404 Not Found response.
     *
     * Convenience method for resource-not-found situations,
     * producing a consistent 404 error payload.
     *
     * @param  string  $resource  Human-readable name of the missing resource
     * @return JsonResponse
     */
    public function sendNotFound(string $resource = 'Resource'): JsonResponse
    {
        return $this->sendError("{$resource} not found.", [], 404);
    }

    /**
     * Send a 403 Forbidden response.
     *
     * Used when an authenticated user does not have sufficient
     * permissions to perform the requested operation.
     *
     * @param  string  $message  Custom forbidden message
     * @return JsonResponse
     */
    public function sendForbidden(string $message = 'Permission denied.'): JsonResponse
    {
        return $this->sendError($message, [], 403);
    }

    /**
     * Send a 401 Unauthorized response.
     *
     * Used when the request is not authenticated or the token
     * has expired or been revoked.
     *
     * @param  string  $message  Custom unauthorized message
     * @return JsonResponse
     */
    public function sendUnauthorized(string $message = 'Unauthenticated.'): JsonResponse
    {
        return $this->sendError($message, [], 401);
    }

    /**
     * Paginate an Eloquent query and return standardized pagination array.
     *
     * Reads per_page from the current request (default: $defaultPerPage),
     * hard-caps it at $maxPerPage to prevent memory exhaustion, runs the
     * query through Laravel's paginator, and returns both the data items
     * and pagination metadata in a consistent structure.
     *
     * @param  Builder|mixed  $query       Eloquent query builder instance
     * @param  int            $defaultPerPage  Items per page if not in request
     * @return array{data: array, meta: array}
     *
     * @example
     * return $this->sendResponse($this->paginate(Product::query()));
     */
    protected function paginate(mixed $query, int $defaultPerPage = 0): array
    {
        $perPage = (int) request()->input('per_page', $defaultPerPage ?: $this->defaultPerPage);
        $perPage = max(1, min($perPage, $this->maxPerPage));

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
                'has_more'     => $paginator->hasMorePages(),
                'links'        => [
                    'next'     => $paginator->nextPageUrl(),
                    'prev'     => $paginator->previousPageUrl(),
                    'first'    => $paginator->url(1),
                    'last'     => $paginator->url($paginator->lastPage()),
                ],
            ],
        ];
    }

    /**
     * Apply sorting to an Eloquent query from request parameters.
     *
     * Reads sort column and order direction from the current HTTP request.
     * Validates the direction to prevent SQL injection. Falls back to
     * defaults if parameters are missing or invalid.
     *
     * Request parameters:
     *   - sort:  Column name to sort by (default: $defaultSortColumn)
     *   - order: Direction 'asc' or 'desc' (default: $defaultSortDirection)
     *
     * @param  Builder  $query       The Eloquent query builder to apply sorting to
     * @param  string   $sortCol     Default sort column
     * @param  string   $defaultDir  Default sort direction ('asc' or 'desc')
     * @return Builder               The query with ORDER BY clause applied
     *
     * @example
     * return $this->paginate($this->applySorting(Product::query(), 'name', 'asc'));
     */
    protected function applySorting(Builder $query, string $sortCol = '', string $defaultDir = ''): Builder
    {
        $col = request()->input('sort', $sortCol ?: $this->defaultSortColumn);
        $dir = request()->input('order', $defaultDir ?: $this->defaultSortDirection);

        // Validate direction to prevent injection
        if (!in_array(strtolower($dir), ['asc', 'desc'])) {
            $dir = $defaultDir ?: $this->defaultSortDirection;
        }

        return $query->orderBy($col, $dir);
    }

    /**
     * Apply common search filter to a query.
     *
     * Reads the search term from request input 'search' or 'q' parameter
     * and applies a LIKE filter across specified columns using OR conditions.
     *
     * @param  Builder  $query    The query to filter
     * @param  array    $columns  Columns to search across
     * @return Builder
     *
     * @example
     * $this->applySearch($query, ['name', 'email', 'phone']);
     */
    protected function applySearch(Builder $query, array $columns): Builder
    {
        $term = request()->input('search') ?? request()->input('q');

        if ($term && !empty($columns)) {
            $query->where(function (Builder $q) use ($term, $columns) {
                foreach ($columns as $i => $column) {
                    if ($i === 0) {
                        $q->where($column, 'like', "%{$term}%");
                    } else {
                        $q->orWhere($column, 'like', "%{$term}%");
                    }
                }
            });
        }

        return $query;
    }

    /**
     * Apply date range filter to a query.
     *
     * Filters a query by a date range using from_date and to_date request
     * parameters. Uses whereDate() for timezone-safe comparisons on datetime
     * columns.
     *
     * @param  Builder  $query       The query to apply date filter to
     * @param  string   $column      The date/datetime column to filter on
     * @param  string   $fromParam   Request parameter name for start date
     * @param  string   $toParam     Request parameter name for end date
     * @return Builder
     */
    protected function applyDateRange(
        Builder $query,
        string  $column    = 'created_at',
        string  $fromParam = 'from_date',
        string  $toParam   = 'to_date'
    ): Builder {
        $from = request()->input($fromParam);
        $to   = request()->input($toParam);

        if ($from) {
            $query->whereDate($column, '>=', $from);
        }

        if ($to) {
            $query->whereDate($column, '<=', $to);
        }

        return $query;
    }

    /**
     * Apply status filter to a query.
     *
     * Reads status from the request and applies it as a where condition.
     * Accepts boolean-like values: 1, 0, true, false, 'active', 'inactive'.
     *
     * @param  Builder  $query   The query to filter
     * @param  string   $column  The status column name (default: 'status')
     * @return Builder
     */
    protected function applyStatusFilter(Builder $query, string $column = 'status'): Builder
    {
        $status = request()->input('status');

        if ($status !== null && $status !== '') {
            $query->where($column, (bool) $status);
        }

        return $query;
    }

    /**
     * Get the current authenticated user's company ID.
     *
     * Returns the company_id from the authenticated API user. Throws
     * an exception if no authenticated user is found (should not happen
     * in routes protected by auth.api middleware).
     *
     * @return int  The company ID of the authenticated user
     * @throws \RuntimeException  If not authenticated
     */
    protected function getCompanyId(): int
    {
        $user = auth('api')->user();

        if (!$user) {
            throw new \RuntimeException('No authenticated user found. Ensure auth.api middleware is applied.');
        }

        return (int) $user->company_id;
    }

    /**
     * Get the current authenticated user's ID.
     *
     * @return int  The ID of the authenticated user
     */
    protected function getAuthUserId(): int
    {
        return (int) auth('api')->id();
    }

    /**
     * Merge company ID into request data array.
     *
     * Convenience method to add company_id to a data array before
     * passing to Eloquent create/update methods.
     *
     * @param  array  $data  The data array to merge company_id into
     * @return array  The data array with company_id added
     */
    protected function withCompany(array $data): array
    {
        $data['company_id'] = $this->getCompanyId();
        return $data;
    }

    /**
     * Merge company ID and created_by into request data array.
     *
     * @param  array  $data  The data array to merge into
     * @return array
     */
    protected function withCompanyAndUser(array $data): array
    {
        $data['company_id'] = $this->getCompanyId();
        $data['created_by'] = $this->getAuthUserId();
        return $data;
    }

    /**
     * Log an API action for audit purposes.
     *
     * Records the action, user, and context to the application log
     * at info level for audit trail purposes.
     *
     * @param  string  $action   Description of the action (e.g. 'product.created')
     * @param  array   $context  Additional context data to log
     * @return void
     */
    protected function logAction(string $action, array $context = []): void
    {
        Log::info("API Action: {$action}", array_merge([
            'user_id'    => $this->getAuthUserId(),
            'company_id' => $this->getCompanyId(),
            'ip'         => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $context));
    }

    /**
     * Build a cache key scoped to the current company.
     *
     * Generates a consistent, company-scoped cache key to prevent
     * data leakage between tenants.
     *
     * @param  string  $key  The base cache key
     * @return string  The company-scoped cache key
     */
    protected function cacheKey(string $key): string
    {
        return "company_{$this->getCompanyId()}_{$key}";
    }

    /**
     * Clear a company-scoped cache key.
     *
     * @param  string  $key  The base cache key to forget
     * @return void
     */
    protected function clearCache(string $key): void
    {
        Cache::forget($this->cacheKey($key));
    }

    /**
     * Get the request's per_page value within bounds.
     *
     * @return int  The validated per_page value
     */
    protected function getPerPage(): int
    {
        $perPage = (int) request()->input('per_page', $this->defaultPerPage);
        return max(1, min($perPage, $this->maxPerPage));
    }

    /**
     * Check if the request wants all records (no pagination).
     *
     * Some list endpoints support ?all=1 to return all records
     * without pagination. This should only be allowed for small
     * datasets (e.g. lookup lists, dropdowns).
     *
     * @param  int  $limit  Maximum records to allow with all=1 (safety cap)
     * @return bool
     */
    protected function wantsAll(int $limit = 500): bool
    {
        return (bool) request()->input('all', false);
    }

    /**
     * Return all records for a query (no pagination).
     *
     * Used for dropdown/select lists where pagination is not needed.
     * Applies a safety cap to prevent returning too many records.
     *
     * @param  Builder  $query  The query to fetch all records from
     * @param  int      $limit  Maximum number of records (safety cap)
     * @return Collection
     */
    protected function getAll(Builder $query, int $limit = 500): Collection
    {
        return $query->limit($limit)->get();
    }

    /**
     * Parse boolean from request input.
     *
     * Handles various truthy/falsy string representations from HTTP
     * form data and query strings.
     *
     * @param  string  $key      Request parameter key
     * @param  bool    $default  Default value if parameter absent
     * @return bool
     */
    protected function parseBoolean(string $key, bool $default = false): bool
    {
        $value = request()->input($key);

        if ($value === null) {
            return $default;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on']);
    }

    /**
     * Validate that a given ID belongs to the current company.
     *
     * Prevents cross-company access by verifying that a record's
     * company_id matches the authenticated user's company_id.
     *
     * @param  mixed   $model     The Eloquent model instance to check
     * @param  string  $resource  Name of the resource for error messages
     * @return bool    True if valid, false if company mismatch
     */
    protected function belongsToCompany(mixed $model, string $resource = 'Resource'): bool
    {
        return (int) $model->company_id === $this->getCompanyId();
    }

    /**
     * Handle bulk operation with transaction and error collection.
     *
     * Wraps a bulk operation in a database transaction, collecting
     * per-item errors while processing as many items as possible.
     *
     * @param  array     $ids       Array of record IDs to process
     * @param  callable  $callback  Function to call for each ID
     * @return array     Summary: ['success' => int, 'failed' => int, 'errors' => array]
     */
    protected function processBulk(array $ids, callable $callback): array
    {
        $success = 0;
        $failed  = 0;
        $errors  = [];

        foreach ($ids as $id) {
            try {
                $callback($id);
                $success++;
            } catch (\Exception $e) {
                $failed++;
                $errors[$id] = $e->getMessage();
            }
        }

        return compact('success', 'failed', 'errors');
    }
}
