<?php
namespace App\Observers;

use App\Models\Settings;

class SettingObserver
{
    public function saved(Settings $setting): void
    {
        \Cache::forget('settings_'  . $setting->company_id);
        \Cache::forget('dashboard_' . $setting->company_id);
    }
}
