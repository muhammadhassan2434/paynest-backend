<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\PaymentSchedule;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            ->whereDate('scheduled_for', $today)
            ->get();

        foreach ($schedules as $schedule) {
            $account = $schedule->account;

            if (!$account) {
                Log::warning("Account not found for PaymentSchedule #{$schedule->id}");
            }

            // If not funded, notify and skip
            if (!$schedule->is_funded || !$account) {
                // Send notification to user (optional)
                Log::warning("Schedule #{$schedule->id} not funded. Notifying user.");
                continue;
            }

            DB::beginTransaction();
            try {
                // Reduce held balance and confirm schedule as executed
                $account->held_balance -= $schedule->amount;
                $account->save();

                // Mark the schedule as executed
                $schedule->status = 'executed';
                $schedule->save();

                // Determine the transaction type and handle accordingly
                if ($schedule->type === 'bill') {
                    // Handle bill payment - amount should be deducted from the sender's balance
                    // Bill payment goes to the bill, not to a user’s account
                    Log::info("Bill payment for schedule #{$schedule->id} completed.");

                    // Create transaction for bill payment
                    $transaction = Transaction::create([
                        'sender_id'        => $account->user_id,
                        'receiver_number'  => $schedule->receiver_contact,
                        'amount'           => $schedule->amount,
                        'transaction_type' => 'bill_payment',
                        'status'           => 'completed',
                        'reference'        => 'TXN-' . strtoupper(Str::random(10)),
                    ]);

                    // Log the bill payment (optional)
                    Log::info("Bill payment for schedule #{$schedule->id} successfully executed to receiver's bill.");
                } else {
                    // Handle transfer - amount should be added to the receiver's account balance
                    // Find receiver's account using the receiver_account_no
                    $receiverAccount = Account::where('phone', $schedule->receiver_account_no)->first();

                    if ($receiverAccount) {
                        // Transfer the amount to the receiver's account
                        $receiverAccount->balance += $schedule->amount;
                        $receiverAccount->save();

                        // Create transaction for transfer
                        $transaction = Transaction::create([
                            'sender_id'        => $account->user_id,
                            'receiver_number'  => $schedule->receiver_contact,
                            'amount'           => $schedule->amount,
                            'transaction_type' => 'transfer',
                            'status'           => 'completed',
                            'reference'        => 'TXN-' . strtoupper(Str::random(10)),
                        ]);

                        // Log the transfer (optional)
                        Log::info("Transfer for schedule #{$schedule->id} completed to receiver's account.");
                    } else {
                        // Handle case where the receiver account is not found
                        Log::error("Receiver account not found for schedule #{$schedule->id}. Transaction failed.");
                        continue;
                    }
                }

                // Attach transaction to schedule
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
