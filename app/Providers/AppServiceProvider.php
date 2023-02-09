<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Brand;       use App\Observers\BrandObserver;
use App\Models\Category;    use App\Observers\CategoryObserver;
use App\Models\Currency;    use App\Observers\CurrencyObserver;
use App\Models\Customer;    use App\Observers\CustomerObserver;
use App\Models\CustomField; use App\Observers\CustomFieldObserver;
use App\Models\Expense;     use App\Observers\ExpenseObserver;
use App\Models\ExpenseCategory; use App\Observers\ExpenseCategoryObserver;
use App\Models\Order;       use App\Observers\OrderObserver;
use App\Models\OrderPayment; use App\Observers\OrderPaymentObserver;
use App\Models\OrderShippingAddress; use App\Observers\OrderShippingAddressObserver;
use App\Models\Payment;     use App\Observers\PaymentObserver;
use App\Models\PaymentMode; use App\Observers\PaymentModeObserver;
use App\Models\Product;     use App\Observers\ProductObserver;
use App\Models\Role;        use App\Observers\RoleObserver;
use App\Models\Settings;    use App\Observers\SettingObserver;
use App\Models\StockAdjustment; use App\Observers\StockAdjustmentObserver;
use App\Models\StockHistory;    use App\Observers\StockHistoryObserver;
use App\Models\Supplier;    use App\Observers\SupplierObserver;
use App\Models\Tax;         use App\Observers\TaxObserver;
use App\Models\Unit;        use App\Observers\UnitObserver;
use App\Models\User;        use App\Observers\UserObserver;
use App\Models\UserAddress; use App\Observers\UserAddressObserver;
use App\Models\Variation;   use App\Observers\VariationObserver;
use App\Models\Warehouse;   use App\Observers\WarehouseObserver;
use App\Models\WarehouseHistory; use App\Observers\WarehouseHistoryObserver;
use App\Models\WarehouseStock;   use App\Observers\WarehouseStockObserver;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Brand::observe(BrandObserver::class);
        Category::observe(CategoryObserver::class);
        Currency::observe(CurrencyObserver::class);
        Customer::observe(CustomerObserver::class);
        CustomField::observe(CustomFieldObserver::class);
        Expense::observe(ExpenseObserver::class);
        ExpenseCategory::observe(ExpenseCategoryObserver::class);
        Order::observe(OrderObserver::class);
        OrderPayment::observe(OrderPaymentObserver::class);
        OrderShippingAddress::observe(OrderShippingAddressObserver::class);
        Payment::observe(PaymentObserver::class);
        PaymentMode::observe(PaymentModeObserver::class);
        Product::observe(ProductObserver::class);
        Role::observe(RoleObserver::class);
        Settings::observe(SettingObserver::class);
        StockAdjustment::observe(StockAdjustmentObserver::class);
        StockHistory::observe(StockHistoryObserver::class);
        Supplier::observe(SupplierObserver::class);
        Tax::observe(TaxObserver::class);
        Unit::observe(UnitObserver::class);
        User::observe(UserObserver::class);
        UserAddress::observe(UserAddressObserver::class);
        Variation::observe(VariationObserver::class);
        Warehouse::observe(WarehouseObserver::class);
        WarehouseHistory::observe(WarehouseHistoryObserver::class);
        WarehouseStock::observe(WarehouseStockObserver::class);
    }
}
