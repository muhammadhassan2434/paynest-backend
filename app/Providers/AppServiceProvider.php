<?php

namespace App\Providers;

use App\Console\Commands\SendBillReminderNotifications;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->commands([
            SendBillReminderNotifications::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
{
    $this->app->booted(function () {
        $schedule = app(Schedule::class);
        $schedule->command('send:bill-reminders')->dailyAt('09:00');
    });
}
}
