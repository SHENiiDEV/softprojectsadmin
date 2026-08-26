<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('telegram-messages', function (object $job) {
            return Limit::perSecond(20);
        });

        // Dynamically load settings if table exists
        if (Schema::hasTable('settings')) {
            $appName = Setting::get('app_name');
            if ($appName) {
                config(['app.name' => $appName]);
            }

            // SMTP settings
            $smtpHost = Setting::get('mail_host');
            if ($smtpHost) {
                config([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp.host' => $smtpHost,
                    'mail.mailers.smtp.port' => Setting::get('mail_port', config('mail.mailers.smtp.port')),
                    'mail.mailers.smtp.username' => Setting::get('mail_username', config('mail.mailers.smtp.username')),
                    'mail.mailers.smtp.password' => Setting::get('mail_password', config('mail.mailers.smtp.password')),
                    'mail.mailers.smtp.encryption' => Setting::get('mail_encryption', config('mail.mailers.smtp.encryption')),
                    'mail.from.address' => Setting::get('mail_from_address', config('mail.from.address')),
                    'mail.from.name' => Setting::get('mail_from_name', config('mail.from.name')),
                ]);
            }

            // Telegram settings
            $tgToken = Setting::get('telegram_bot_token');
            if ($tgToken) {
                config(['services.telegram.bot_token' => $tgToken]);
            }
        }
    }
}
