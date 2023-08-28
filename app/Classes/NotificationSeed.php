<?php
namespace App\Classes;

use App\Models\User;
use App\Notifications\MainNotificaiton;

class NotificationSeed
{
    public static function seedDefaults(int $companyId): void
    {
        $admins = User::where('company_id', $companyId)
            ->whereHas('roles', fn($q) => $q->where('name', 'admin'))
            ->get();

        foreach ($admins as $admin) {
            $admin->notify(new MainNotificaiton(
                'Welcome to StockManager',
                'Your account is set up. Explore the dashboard to get started.',
                '/dashboard',
                'Go to Dashboard'
            ));
        }
    }

    public static function lowStockAlert(int $companyId, string $productName, float $qty): void
    {
        User::where('company_id', $companyId)->get()->each(
            fn($u) => $u->notify(new MainNotificaiton(
                'Low Stock Alert: ' . $productName,
                "Current stock is {$qty} units. Please reorder soon.",
                '/stock-report',
                'View Stock'
            ))
        );
    }
}
