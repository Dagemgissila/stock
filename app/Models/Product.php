<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Class Product
 *
 * Represents a product in the inventory management system.
 * Products can have multiple variants (color, size), belong to
 * categories and brands, track stock across multiple warehouses,
 * and have custom fields for additional metadata.
 *
 * @property int         $id
 * @property int         $company_id
 * @property int|null    $category_id
 * @property int|null    $brand_id
 * @property int|null    $unit_id
 * @property int|null    $tax_id
 * @property string      $name
 * @property string      $slug
 * @property string|null $barcode
 * @property string|null $image
 * @property float       $purchase_price
 * @property float       $sales_price
 * @property float|null  $mrp
 * @property int         $stock_alert
 * @property bool        $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read Category|null     $category
 * @property-read Brand|null        $brand
 * @property-read Unit|null         $unit
 * @property-read Tax|null          $tax
 * @property-read float             $total_stock
 * @property-read string            $stock_status
 * @property-read string            $image_url
 * @property-read float             $profit_margin
 *
 * @package App\Models
 */
class Product extends BaseModel
{
    use SoftDeletes, HasFactory;

    /**
     * Stock status constants for type-safe comparisons.
     */
    const STATUS_IN_STOCK    = 'in_stock';
    const STATUS_LOW_STOCK   = 'low_stock';
    const STATUS_OUT_OF_STOCK = 'out_of_stock';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id',
        'category_id',
        'brand_id',
        'unit_id',
        'tax_id',
        'name',
        'slug',
        'barcode',
        'image',
        'purchase_price',
        'sales_price',
        'mrp',
        'stock_alert',
        'status',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'purchase_price' => 'float',
        'sales_price'    => 'float',
        'mrp'            => 'float',
        'stock_alert'    => 'integer',
        'status'         => 'boolean',
        'deleted_at'     => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<string>
     */
    protected $appends = [
        'total_stock',
        'stock_status',
        'image_url',
        'profit_margin',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * Get the category this product belongs to.
     *
     * @return BelongsTo<Category, Product>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the brand this product is associated with.
     *
     * @return BelongsTo<Brand, Product>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the measurement unit for this product.
     *
     * @return BelongsTo<Unit, Product>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the tax configuration applied to this product.
     *
     * @return BelongsTo<Tax, Product>
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * Get all warehouse stock records for this product.
     *
     * Returns stock quantities across all warehouses in the company.
     *
     * @return HasMany<WarehouseStock>
     */
    public function warehouseStocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    /**
     * Get all product variants (color, size, etc.)
     *
     * @return HasMany<ProductVariant>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get all custom field values for this product.
     *
     * @return HasMany<ProductCustomField>
     */
    public function customFields(): HasMany
    {
        return $this->hasMany(ProductCustomField::class);
    }

    /**
     * Get all order items that include this product.
     *
     * @return HasMany<OrderItem>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get all stock adjustment records for this product.
     *
     * @return HasMany<StockAdjustment>
     */
    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    /**
     * Get all stock history movements for this product.
     *
     * @return HasMany<StockHistory>
     */
    public function stockHistory(): HasMany
    {
        return $this->hasMany(StockHistory::class);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    /**
     * Get the total stock quantity across all warehouses.
     *
     * Sums the quantity from all WarehouseStock records associated
     * with this product. If warehouseStocks is already eager-loaded,
     * uses the collection to avoid an extra query.
     *
     * @return float  Total stock quantity
     */
    public function getTotalStockAttribute(): float
    {
        if ($this->relationLoaded('warehouseStocks')) {
            return (float) $this->warehouseStocks->sum('quantity');
        }

        return (float) $this->warehouseStocks()->sum('quantity');
    }

    /**
     * Get the current stock status label.
     *
     * Returns one of three status strings based on quantity vs alert threshold:
     *   - out_of_stock: quantity <= 0
     *   - low_stock:    quantity <= stock_alert threshold
     *   - in_stock:     quantity > stock_alert threshold
     *
     * @return string  One of the STATUS_* constants
     */
    public function getStockStatusAttribute(): string
    {
        $total = $this->total_stock;

        if ($total <= 0) {
            return self::STATUS_OUT_OF_STOCK;
        }

        if ($total <= $this->stock_alert) {
            return self::STATUS_LOW_STOCK;
        }

        return self::STATUS_IN_STOCK;
    }

    /**
     * Get the full URL to the product's image.
     *
     * Returns the URL if an image exists, otherwise returns
     * a default placeholder image URL.
     *
     * @return string  Absolute URL to product image
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            return asset('uploads/products/' . $this->image);
        }

        return asset('images/product.png');
    }

    /**
     * Get the profit margin percentage.
     *
     * Calculates: ((sales_price - purchase_price) / purchase_price) * 100
     * Returns 0 if purchase price is zero to avoid division by zero.
     *
     * @return float  Profit margin as a percentage (e.g. 25.5 for 25.5%)
     */
    public function getProfitMarginAttribute(): float
    {
        if ($this->purchase_price <= 0) {
            return 0.0;
        }

        return round(
            (($this->sales_price - $this->purchase_price) / $this->purchase_price) * 100,
            2
        );
    }

    /**
     * Get the formatted sales price with currency symbol.
     *
     * @return string  Formatted price string
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->sales_price, 2);
    }

    /**
     * Get the formatted purchase price with currency symbol.
     *
     * @return string  Formatted price string
     */
    public function getFormattedPurchasePriceAttribute(): string
    {
        return number_format($this->purchase_price, 2);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Scope a query to only include active products.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    /**
     * Scope a query to only include inactive products.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', false);
    }

    /**
     * Scope a query to products with low stock.
     *
     * Returns products where warehouse stock is at or below the
     * stock alert threshold for the given warehouse.
     *
     * @param  Builder   $query
     * @param  int|null  $warehouseId  Optional warehouse to check stock for
     * @return Builder
     */
    public function scopeLowStock(Builder $query, ?int $warehouseId = null): Builder
    {
        return $query->whereHas('warehouseStocks', function (Builder $q) use ($warehouseId) {
            $q->whereColumn('quantity', '<=', 'products.stock_alert')
              ->where('quantity', '>', 0);

            if ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }
        });
    }

    /**
     * Scope a query to products with zero stock (out of stock).
     *
     * @param  Builder   $query
     * @param  int|null  $warehouseId
     * @return Builder
     */
    public function scopeOutOfStock(Builder $query, ?int $warehouseId = null): Builder
    {
        return $query->whereHas('warehouseStocks', function (Builder $q) use ($warehouseId) {
            $q->where('quantity', '<=', 0);

            if ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }
        });
    }

    /**
     * Scope a query to search products by name, barcode, or slug.
     *
     * @param  Builder  $query
     * @param  string   $term  The search term
     * @return Builder
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('name',    'like', "%{$term}%")
              ->orWhere('barcode', $term)
              ->orWhere('slug',   'like', "%{$term}%");
        });
    }

    /**
     * Scope a query to products in a specific category (including subcategories).
     *
     * @param  Builder  $query
     * @param  int      $categoryId  The category ID to filter by
     * @return Builder
     */
    public function scopeInCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where(function (Builder $q) use ($categoryId) {
            $q->where('category_id', $categoryId)
              ->orWhereHas('category', function (Builder $cat) use ($categoryId) {
                  $cat->where('parent_id', $categoryId);
              });
        });
    }

    /**
     * Scope a query to products from a specific brand.
     *
     * @param  Builder  $query
     * @param  int      $brandId
     * @return Builder
     */
    public function scopeFromBrand(Builder $query, int $brandId): Builder
    {
        return $query->where('brand_id', $brandId);
    }

    /**
     * Scope to products within a price range.
     *
     * @param  Builder    $query
     * @param  float|null $min  Minimum sales price
     * @param  float|null $max  Maximum sales price
     * @return Builder
     */
    public function scopePriceRange(Builder $query, ?float $min, ?float $max): Builder
    {
        if ($min !== null) {
            $query->where('sales_price', '>=', $min);
        }
        if ($max !== null) {
            $query->where('sales_price', '<=', $max);
        }
        return $query;
    }

    // ─── Business Logic Methods ───────────────────────────────────────────────

    /**
     * Check if this product is currently in stock in a given warehouse.
     *
     * @param  int  $warehouseId  The warehouse to check stock for
     * @return bool  True if quantity > 0
     */
    public function isInStock(int $warehouseId): bool
    {
        $stock = $this->warehouseStocks()
            ->where('warehouse_id', $warehouseId)
            ->value('quantity');

        return ($stock ?? 0) > 0;
    }

    /**
     * Get the available quantity for this product in a specific warehouse.
     *
     * @param  int  $warehouseId  The warehouse to check
     * @return float  Available quantity (0 if not found)
     */
    public function getAvailableQuantity(int $warehouseId): float
    {
        return (float) $this->warehouseStocks()
            ->where('warehouse_id', $warehouseId)
            ->value('quantity') ?? 0.0;
    }

    /**
     * Calculate the selling price after applying tax.
     *
     * For exclusive tax: adds tax on top of sales price.
     * For inclusive tax: tax is already included in sales price.
     *
     * @return float  The final selling price including tax
     */
    public function getPriceWithTax(): float
    {
        if (!$this->tax) {
            return $this->sales_price;
        }

        if ($this->tax->tax_type === 'exclusive') {
            return $this->sales_price * (1 + ($this->tax->rate / 100));
        }

        // Inclusive: price already contains tax
        return $this->sales_price;
    }

    /**
     * Calculate the tax amount for a given quantity.
     *
     * @param  float  $quantity  Number of units
     * @param  float  $price     Unit price (defaults to sales_price)
     * @return float  Total tax amount
     */
    public function calculateTax(float $quantity = 1.0, float $price = 0.0): float
    {
        if (!$this->tax) {
            return 0.0;
        }

        $unitPrice = $price > 0 ? $price : $this->sales_price;
        $subtotal  = $unitPrice * $quantity;

        if ($this->tax->tax_type === 'exclusive') {
            return round($subtotal * ($this->tax->rate / 100), 2);
        }

        // Inclusive: extract tax from price
        return round($subtotal - ($subtotal / (1 + $this->tax->rate / 100)), 2);
    }

    /**
     * Generate a unique slug for this product.
     *
     * Appends a numeric suffix if the base slug already exists
     * for another product in the same company.
     *
     * @param  string  $name  The product name to slugify
     * @return string  The unique slug
     */
    public static function generateUniqueSlug(string $name): string
    {
        $base  = Str::slug($name);
        $slug  = $base;
        $count = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$count}";
            $count++;
        }

        return $slug;
    }

    /**
     * Get products that need restocking (stock at or below alert threshold).
     *
     * @param  int   $companyId    Company to check for
     * @param  int   $warehouseId  Optional specific warehouse
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getNeedingRestock(int $companyId, int $warehouseId = 0): \Illuminate\Database\Eloquent\Collection
    {
        $query = static::where('company_id', $companyId)
            ->where('status', true)
            ->with(['warehouseStocks', 'category', 'unit']);

        if ($warehouseId) {
            $query->whereHas('warehouseStocks', function (Builder $q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)
                  ->whereColumn('quantity', '<=', 'products.stock_alert');
            });
        } else {
            $query->where(function (Builder $q) {
                $q->whereHas('warehouseStocks', function (Builder $sq) {
                    $sq->whereColumn('quantity', '<=', 'products.stock_alert');
                });
            });
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get the most sold products for a company within a date range.
     *
     * Joins with order_items to sum up total quantity sold per product.
     *
     * @param  int         $companyId  Company to analyze
     * @param  string|null $fromDate   Start date filter
     * @param  string|null $toDate     End date filter
     * @param  int         $limit      Number of top products to return
     * @return \Illuminate\Support\Collection
     */
    public static function getTopSelling(
        int     $companyId,
        ?string $fromDate = null,
        ?string $toDate   = null,
        int     $limit    = 10
    ): \Illuminate\Support\Collection {
        $query = static::where('products.company_id', $companyId)
            ->join('order_items', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.order_type', 'sales')
            ->where('orders.order_status', 'completed')
            ->select(
                'products.id',
                'products.name',
                'products.image',
                'products.sales_price',
                \DB::raw('SUM(order_items.quantity) as total_sold'),
                \DB::raw('SUM(order_items.subtotal) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.image', 'products.sales_price')
            ->orderByDesc('total_sold')
            ->limit($limit);

        if ($fromDate) {
            $query->whereDate('orders.order_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('orders.order_date', '<=', $toDate);
        }

        return $query->get();
    }

    /**
     * Duplicate a product with a new name.
     *
     * Creates a copy of the product (without variants or custom fields)
     * with a modified name and fresh slug.
     *
     * @param  string  $newName  The name for the duplicated product
     * @return static  The newly created duplicate product
     */
    public function duplicate(string $newName): static
    {
        $clone = $this->replicate(['id', 'created_at', 'updated_at', 'deleted_at']);
        $clone->name = $newName;
        $clone->slug = static::generateUniqueSlug($newName);
        $clone->save();

        return $clone;
    }

    /**
     * Check whether this product has any active variants.
     *
     * @return bool
     */
    public function hasVariants(): bool
    {
        return $this->variants()->exists();
    }

    /**
     * Check whether this product has any custom fields defined.
     *
     * @return bool
     */
    public function hasCustomFields(): bool
    {
        return $this->customFields()->exists();
    }

    /**
     * Get the barcode image URL for this product.
     *
     * Returns a URL that can be used to render the barcode
     * using the warehouse's configured barcode type.
     *
     * @return string|null  URL to barcode image, or null if no barcode set
     */
    public function getBarcodeImageUrl(): ?string
    {
        if (!$this->barcode) {
            return null;
        }

        return route('barcode.generate', ['value' => $this->barcode]);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        // Remove sensitive internal fields from API responses
        unset($array['deleted_at']);

        return $array;
    }
}
