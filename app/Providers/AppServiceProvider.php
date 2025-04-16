<?php

namespace App\Providers;

use App\Console\Commands\ExecuteScheduledPayments;
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
            ExecuteScheduledPayments::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
{
    $this->app->booted(function () {
        $schedule = app(Schedule::class);

            // Schedule the 'send:bill-reminders' command to run daily at 09:00
            $schedule->command('send:bill-reminders')->dailyAt('09:00');

            // Schedule the 'payments:execute-scheduled' command to run daily at 10:00 AM
            $schedule->command('payments:execute-scheduled')->dailyAt('10:00');
    });
}
}
