<?php

namespace App\Console\Commands;

use App\Mail\UpcomingPaymentReminder;
use App\Models\Account;
use App\Models\PaymentSchedule;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ExecuteScheduledPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:execute-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute all scheduled payments and create transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today()->toDateString();

        $schedules = PaymentSchedule::with('account')
            ->where('status', 'scheduled')
            ->whereDate('scheduled_for', '<=', $today)
            ->get();

        foreach ($schedules as $schedule) {
            $account = $schedule->account_id ? Account::find($schedule->account_id) : null;


            if (!$account) {
                Log::warning("Account not found for PaymentSchedule #{$schedule->id}");

                // Mark status as failed when account not found
                $schedule->status = 'failed';
                $schedule->save();
                continue;
            }

            // ✅ If the scheduled date +1 day has passed and not executed, mark as failed
            $scheduledDate = Carbon::parse($schedule->scheduled_for)->toDateString();

            if (Carbon::parse($today)->greaterThan(Carbon::parse($scheduledDate)) && $schedule->status === 'scheduled') {
                $schedule->status = 'failed';
                $schedule->save();
                Log::warning("Schedule #{$schedule->id} missed execution date ($scheduledDate). Marked as failed.");
                continue;
            }

            // If not funded, notify and skip
            if (!$schedule->is_funded) {
                Log::warning("Schedule #{$schedule->id} not funded. Notifying user.");

                // Send email to user
                if ($schedule->account && $schedule->account->user && $schedule->account->user->email) {
                    try {
                        Mail::to($schedule->account->user->email)->send(new UpcomingPaymentReminder($schedule));
                    } catch (\Exception $e) {
                        Log::error("Failed to send email for schedule #{$schedule->id}: " . $e->getMessage());
                    }
                }

                continue;
            }


            DB::beginTransaction();
            try {
                $account->held_balance -= $schedule->amount;
                $account->save();

                $schedule->status = 'executed';
                $schedule->save();

                if ($schedule->type === 'bill') {
                    Log::info("Bill payment for schedule #{$schedule->id} completed.");

                    $transaction = Transaction::create([
                        'sender_id'        => $account->user_id,
                        'reciever_number'  => null,
                        'amount'           => $schedule->amount,
                        'transaction_type' => 'bill_payment',
                        'status'           => 'completed',
                        'reference'        => 'TXN-' . strtoupper(Str::random(10)),
                    ]);

                    Log::info("Bill payment for schedule #{$schedule->id} successfully executed to receiver's bill.");
                } else {
                    $receiverAccount = Account::where('phone', $schedule->receiver_account_no)->first();

                    if ($receiverAccount) {
                        $receiverAccount->balance += $schedule->amount;
                        $receiverAccount->save();

                        $transaction = Transaction::create([
                            'sender_id'        => $account->user_id,
                            'reciever_number'  => $receiverAccount->phone,
                            'amount'           => $schedule->amount,
                            'transaction_type' => 'transfer',
                            'status'           => 'completed',
                            'reference'        => 'TXN-' . strtoupper(Str::random(10)),
                        ]);

                        Log::info("Transfer for schedule #{$schedule->id} completed to receiver's account.");
                    } else {
                        $schedule->status = 'failed';
                        $schedule->save();
                        // Refund the held amount if funded
                        if ($schedule->is_funded) {
                            $account->held_balance += $schedule->amount;
                            $account->save();
                            Log::info("Refunded amount back to account #{$account->id} for failed schedule #{$schedule->id}.");
                        }

                        Log::error("Receiver account not found for schedule #{$schedule->id}. Transaction failed.");
                        DB::commit(); // commit to save status change
                        continue;
                    }
                }

                $schedule->transaction_id = $transaction->id;
                $schedule->save();

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Schedule #{$schedule->id} failed: " . $e->getMessage());
            }
        }

        $this->info('Scheduled payments have been processed successfully.');
    }
}
