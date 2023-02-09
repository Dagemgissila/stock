<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use App\Models\Settings;

class SmtpSettingsProvider extends ServiceProvider
{
    public function boot(): void
    {
        try {
            $smtp = Settings::where('setting_type','email')->get()->keyBy('name');
            if ($smtp->isNotEmpty()) {
                config(['mail.mailers.smtp.host'     => $smtp->get('mail_host')?->type]);
                config(['mail.mailers.smtp.port'     => $smtp->get('mail_port')?->type]);
                config(['mail.mailers.smtp.username' => $smtp->get('mail_username')?->type]);
                config(['mail.mailers.smtp.password' => $smtp->get('mail_password')?->type]);
            }
        } catch (\Exception $e) { /* DB not ready during migrations */ }
    }
}
