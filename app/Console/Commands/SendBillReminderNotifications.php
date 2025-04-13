<?php

namespace App\Console\Commands;

use App\Models\BillReminder;
use App\Models\User;
use App\Notifications\BillReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendBillReminderNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:bill-reminders {--test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
{
    if ($this->option('test')) {
        $reminder = BillReminder::with('user')->latest()->first();

        if ($reminder && $reminder->user) {
            $reminder->user->notify(new BillReminderNotification($reminder));
            $this->info("✅ Test notification sent to {$reminder->user->email}");
        } else {
            $this->error('❌ No reminder or user found for testing.');
        }

        return;
    }

    // for live enviornment
    $today = Carbon::today()->format('Y-m-d');
    $tomorrow = Carbon::tomorrow()->format('Y-m-d');

    $reminders = BillReminder::whereIn('due_date', [$today, $tomorrow])->get();

    $count = 0;

    foreach ($reminders as $reminder) {
        $user = $reminder->user ?? User::find($reminder->user_id);

        if ($user) {
            $user->notify(new BillReminderNotification($reminder));
            $count++;
        }
    }

    $this->info("✅ Notifications sent: $count");
}

}
