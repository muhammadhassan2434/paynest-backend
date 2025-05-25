<?php

namespace App\Listeners;

use App\Events\SplitBillCompleted;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoTransferToReceiver
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SplitBillCompleted $event): void
{
    $bill = $event->splitBill;

    // Find the receiver's account by receiver_account_no (assuming it's stored as phone in accounts)
    $receiverAccount = Account::where('phone', $bill->receiver_account_no)->first();

    if (!$receiverAccount) {
        // You can log error or throw exception
        Log::error('Receiver account not found for account no: ' . $bill->receiver_account_no);
        return;
    }

    // Start transaction
    DB::beginTransaction();

    try {
        // Add collected amount to receiver's account balance
        $receiverAccount->balance += $bill->collected_amount;
        $receiverAccount->save();

        // Create a transaction record
        $transaction = new Transaction();
        $transaction->sender_id = $bill->created_by; // who created the split bill
        $transaction->reciever_number = $bill->receiver_account_no;
        $transaction->amount = $bill->collected_amount;
        $transaction->transaction_type = 'splitbill'; // your business logic type
        $transaction->status = 'completed';
        $transaction->reference = 'SPLITBILL-' . $bill->id . '-' . now()->format('YmdHis'); // example reference
        $transaction->save();

        // Update split bill status to transferred
        $bill->status = 'transferred';
        $bill->save();

        DB::commit();
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Transfer failed: ' . $e->getMessage());
        // optionally notify admin or handle failure
    }
}

}
