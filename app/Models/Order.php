<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class Order
 *
 * Central model representing all transactional documents in the system.
 * Supports multiple order types through a single polymorphic structure:
 *   - purchase:        Supplier purchase orders (incoming stock)
 *   - sales:           Customer sales orders (outgoing stock)
 *   - quotation:       Customer quotations (no stock movement)
 *   - sales_return:    Customer returns (stock reversal)
 *   - purchase_return: Supplier returns (stock reversal)
 *   - stock_transfer:  Inter-warehouse stock movements
 *
 * @property int         $id
 * @property int         $company_id
 * @property int|null    $warehouse_id
 * @property int|null    $from_warehouse_id
 * @property string      $order_type
 * @property string      $invoice_number
 * @property string      $order_status
 * @property int|null    $party_id
 * @property string|null $party_type
 * @property float       $subtotal
 * @property float       $discount
 * @property float       $tax_amount
 * @property float       $shipping
 * @property float       $grand_total
 * @property float       $due_amount
 * @property \Carbon\Carbon $order_date
 * @property string|null $notes
 * @property int|null    $staff_user_id
 *
 * @package App\Models
 */
class Order extends BaseModel
{
    use SoftDeletes, HasFactory, \App\Traits\OrderTraits;

    // ─── Order Type Constants ─────────────────────────────────────────────────

    /** @var string Purchase order from supplier */
    const TYPE_PURCHASE        = 'purchase';

    /** @var string Sales order to customer */
    const TYPE_SALES           = 'sales';

    /** @var string Quotation (not yet confirmed) */
    const TYPE_QUOTATION       = 'quotation';

    /** @var string Sales return / credit note */
    const TYPE_SALES_RETURN    = 'sales_return';

    /** @var string Purchase return / debit note */
    const TYPE_PURCHASE_RETURN = 'purchase_return';

    /** @var string Stock transfer between warehouses */
    const TYPE_STOCK_TRANSFER  = 'stock_transfer';

    // ─── Order Status Constants ───────────────────────────────────────────────

    /** @var string Order created but not yet processed */
    const STATUS_PENDING    = 'pending';

    /** @var string Order has been confirmed/accepted */
    const STATUS_CONFIRMED  = 'confirmed';

    /** @var string Order has been ordered from supplier */
    const STATUS_ORDERED    = 'ordered';

    /** @var string Items have been received (purchase) */
    const STATUS_RECEIVED   = 'received';

    /** @var string Order has been shipped (sales) */
    const STATUS_SHIPPED    = 'shipped';

    /** @var string Order has been fully completed */
    const STATUS_COMPLETED  = 'completed';

    /** @var string Order has been cancelled */
    const STATUS_CANCELLED  = 'cancelled';

    /** @var string Quotation has been sent to customer */
    const STATUS_SENT       = 'sent';

    /** @var string Quotation accepted by customer */
    const STATUS_ACCEPTED   = 'accepted';

    /** @var string Quotation rejected by customer */
    const STATUS_REJECTED   = 'rejected';

    /** @var string Quotation converted to sale */
    const STATUS_CONVERTED  = 'converted';

    /** @var string Quotation has expired */
    const STATUS_EXPIRED    = 'expired';

    /** @var string Order is a draft and not yet finalized */
    const STATUS_DRAFT      = 'draft';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id',
        'warehouse_id',
        'from_warehouse_id',
        'order_type',
        'invoice_number',
        'order_status',
        'party_id',
        'party_type',
        'subtotal',
        'discount',
        'tax_amount',
        'shipping',
        'grand_total',
        'due_amount',
        'order_date',
        'notes',
        'staff_user_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'subtotal'     => 'float',
        'discount'     => 'float',
        'tax_amount'   => 'float',
        'shipping'     => 'float',
        'grand_total'  => 'float',
        'due_amount'   => 'float',
        'order_date'   => 'date',
        'deleted_at'   => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * Get all line items for this order.
     *
     * @return HasMany<OrderItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get all payment records linked to this order.
     *
     * Note: uses OrderPayment (junction) not Payment directly,
     * to stay scoped to this specific order's payments.
     *
     * @return HasMany<OrderPayment>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    /**
     * Get the warehouse this order is assigned to.
     *
     * @return BelongsTo<Warehouse, Order>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the source warehouse for stock transfer orders.
     *
     * Only relevant when order_type = stock_transfer.
     *
     * @return BelongsTo<Warehouse, Order>
     */
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    /**
     * Get the polymorphic party (Customer or Supplier).
     *
     * Sales orders link to Customer, purchase orders link to Supplier.
     *
     * @return MorphTo
     */
    public function party(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get custom field values attached to this order.
     *
     * @return HasMany<OrderCustomField>
     */
    public function customFields(): HasMany
    {
        return $this->hasMany(OrderCustomField::class);
    }

    /**
     * Get the shipping address for this order.
     *
     * @return HasOne<OrderShippingAddress>
     */
    public function shippingAddress(): HasOne
    {
        return $this->hasOne(OrderShippingAddress::class);
    }

    /**
     * Get the staff member who processed this order.
     *
     * @return BelongsTo<User, Order>
     */
    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Scope to orders of a specific type.
     *
     * @param  Builder  $query
     * @param  string   $type  One of the TYPE_* constants
     * @return Builder
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('order_type', $type);
    }

    /**
     * Scope to orders within a date range.
     *
     * @param  Builder     $query
     * @param  string|null $from  Start date (Y-m-d)
     * @param  string|null $to    End date (Y-m-d)
     * @return Builder
     */
    public function scopeWithDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate('order_date', '>=', $from);
        }

        if ($to) {
            $query->whereDate('order_date', '<=', $to);
        }

        return $query;
    }

    /**
     * Scope to orders with a specific status.
     *
     * @param  Builder  $query
     * @param  string   $status  One of the STATUS_* constants
     * @return Builder
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('order_status', $status);
    }

    /**
     * Scope to completed orders only.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('order_status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to pending orders only.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('order_status', self::STATUS_PENDING);
    }

    /**
     * Scope to orders with outstanding balance.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeWithDueBalance(Builder $query): Builder
    {
        return $query->where('due_amount', '>', 0);
    }

    /**
     * Scope to orders for a specific party (customer/supplier).
     *
     * @param  Builder  $query
     * @param  int      $partyId
     * @return Builder
     */
    public function scopeForParty(Builder $query, int $partyId): Builder
    {
        return $query->where('party_id', $partyId);
    }

    /**
     * Scope to orders for a specific warehouse.
     *
     * @param  Builder  $query
     * @param  int      $warehouseId
     * @return Builder
     */
    public function scopeForWarehouse(Builder $query, int $warehouseId): Builder
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    /**
     * Get the total amount paid for this order.
     *
     * Sums all OrderPayment records linked to this order.
     *
     * @return float  Total paid amount
     */
    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    /**
     * Get a formatted invoice number with type prefix.
     *
     * @return string
     */
    public function getFormattedInvoiceAttribute(): string
    {
        return $this->invoice_number;
    }

    /**
     * Check if this order is fully paid.
     *
     * @return bool  True if due_amount is zero or negative
     */
    public function getIsFullyPaidAttribute(): bool
    {
        return $this->due_amount <= 0;
    }

    /**
     * Check if this order is partially paid.
     *
     * @return bool  True if some but not all amount is paid
     */
    public function getIsPartiallyPaidAttribute(): bool
    {
        return $this->due_amount > 0 && $this->due_amount < $this->grand_total;
    }

    /**
     * Get the payment progress as a percentage.
     *
     * @return float  0.0 to 100.0
     */
    public function getPaymentProgressAttribute(): float
    {
        if ($this->grand_total <= 0) {
            return 100.0;
        }

        $paid = $this->grand_total - $this->due_amount;
        return round(($paid / $this->grand_total) * 100, 1);
    }

    // ─── Business Logic ───────────────────────────────────────────────────────

    /**
     * Determine if this order can be edited.
     *
     * Only draft and pending orders can be edited.
     * Completed, shipped, and received orders are locked.
     *
     * @return bool
     */
    public function isEditable(): bool
    {
        return in_array($this->order_status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING,
            self::STATUS_ORDERED,
        ]);
    }

    /**
     * Determine if this order can be cancelled.
     *
     * @return bool
     */
    public function isCancellable(): bool
    {
        return !in_array($this->order_status, [
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_CONVERTED,
        ]);
    }

    /**
     * Determine if a payment can be added to this order.
     *
     * @return bool
     */
    public function acceptsPayment(): bool
    {
        return in_array($this->order_status, [
            self::STATUS_COMPLETED,
            self::STATUS_RECEIVED,
            self::STATUS_SHIPPED,
        ]) && $this->due_amount > 0;
    }

    /**
     * Determine if this is a purchase order type.
     *
     * @return bool
     */
    public function isPurchase(): bool
    {
        return $this->order_type === self::TYPE_PURCHASE;
    }

    /**
     * Determine if this is a sales order type.
     *
     * @return bool
     */
    public function isSales(): bool
    {
        return $this->order_type === self::TYPE_SALES;
    }

    /**
     * Determine if this is a quotation.
     *
     * @return bool
     */
    public function isQuotation(): bool
    {
        return $this->order_type === self::TYPE_QUOTATION;
    }

    /**
     * Determine if this is a return order.
     *
     * @return bool
     */
    public function isReturn(): bool
    {
        return in_array($this->order_type, [
            self::TYPE_SALES_RETURN,
            self::TYPE_PURCHASE_RETURN,
        ]);
    }

    /**
     * Determine if this is a stock transfer.
     *
     * @return bool
     */
    public function isStockTransfer(): bool
    {
        return $this->order_type === self::TYPE_STOCK_TRANSFER;
    }

    /**
     * Get a human-readable label for the order type.
     *
     * @return string
     */
    public function getOrderTypeLabel(): string
    {
        return match($this->order_type) {
            self::TYPE_PURCHASE        => 'Purchase Order',
            self::TYPE_SALES           => 'Sales Order',
            self::TYPE_QUOTATION       => 'Quotation',
            self::TYPE_SALES_RETURN    => 'Sales Return',
            self::TYPE_PURCHASE_RETURN => 'Purchase Return',
            self::TYPE_STOCK_TRANSFER  => 'Stock Transfer',
            default                    => ucfirst(str_replace('_', ' ', $this->order_type)),
        };
    }

    /**
     * Get a human-readable label for the order status.
     *
     * @return string
     */
    public function getStatusLabel(): string
    {
        return ucfirst(str_replace('_', ' ', $this->order_status));
    }

    /**
     * Recalculate and persist the grand total from line items.
     *
     * Fetches fresh subtotal, discount, tax_amount from items and
     * recalculates grand_total using calculateGrandTotal().
     * Saves the order quietly to avoid triggering observers.
     *
     * @return void
     */
    public function recalculateTotals(): void
    {
        $this->subtotal   = $this->items()->sum('subtotal');
        $this->tax_amount = $this->items()->sum('tax_amount');
        $this->grand_total = $this->calculateGrandTotal();
        $this->saveQuietly();
    }

    /**
     * Get the count of items in this order.
     *
     * @return int
     */
    public function getItemCount(): int
    {
        return $this->items()->count();
    }

    /**
     * Get the total quantity of all items.
     *
     * @return float
     */
    public function getTotalQuantity(): float
    {
        return (float) $this->items()->sum('quantity');
    }
}
