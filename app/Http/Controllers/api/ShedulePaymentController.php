<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\FakeBill;
use App\Models\PaymentSchedule;
use App\Models\ServiceProvider;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShedulePaymentController extends Controller
{

    public function index(Request $request)
    {
        $schedulePayment = PaymentSchedule::where('account_id', $request->account_id)->get();
        return response()->json(['status' => true, 'schedulePayment' => $schedulePayment], 200);
    }
    public function executed(Request $request)
    {
        $schedulePayment = PaymentSchedule::where('account_id', $request->account_id)->where('status', 'executed')->get();
        return response()->json(['status' => true, 'schedulePayment' => $schedulePayment], 200);
    }
    public function cancelled(Request $request)
    {
        $schedulePayment = PaymentSchedule::where('account_id', $request->account_id)->where('status', 'cancelled')->get();
        return response()->json(['status' => true, 'schedulePayment' => $schedulePayment], 200);
    }
    public function failed(Request $request)
    {
        $schedulePayment = PaymentSchedule::where('account_id', $request->account_id)->where('status', 'failed')->get();
        return response()->json(['status' => true, 'schedulePayment' => $schedulePayment], 200);
    }

    public function redunded(Request $request)
    {
        $schedulePayment = PaymentSchedule::where('account_id', $request->account_id)->where('status', 'scheduled')->where('is_funded', false)->get();
        return response()->json(['status' => true, 'schedulePayment' => $schedulePayment], 200);
    }



    public function store(Request $request)
{
   $validator = Validator::make($request->all(), [
    'account_id'           => 'required|exists:accounts,id',
    'scheduled_for'        => 'required|date|after_or_equal:today',
    'purpose'              => 'required|string|max:255',
    'type'                 => 'required|in:bill,transfer',
    'service_provider_id'  => 'nullable|string|max:255',
    'consumer_number'      => 'nullable|string|max:255',
    'receiver_name'        => 'nullable|string|max:255',
    'amount'               => 'required|numeric|min:1',
    'receiver_account_no'  => 'nullable|string|max:255',
    'receiver_bank'        => 'nullable|string|max:255',
    'note'                 => 'nullable|string',
]);


    if ($validator->fails()) {
        return response()->json(['status' => false, 'message' => 'Validation Failed! Please fill all inputs'], 422);
    }

    $originalNumber = $request->receiver_account_no;
    
        // Sanitize number: Remove +92 or leading 0
        $sanitizedRecieverNumber = preg_replace('/^(\+92|0)/', '', $originalNumber);

    // Always get sender's account here
    $senderAccount = Account::findOrFail($request->account_id);

    if ($request->type == 'transfer') {
        $receiverAccount = Account::where('phone', $sanitizedRecieverNumber)->first();
        if (!$receiverAccount) {
            return response()->json(['status' => false, 'message' => 'Receiver account not found'], 404);
        }

        $finalAmount = $request->amount;
    }

    if ($request->type == 'bill') {
        $bill = FakeBill::where('service_provider_id', $request->service_provider_id)
            ->where('consumer_number', $request->consumer_number)
            ->first();
        if (!$bill) {
            return response()->json(['status' => false, 'message' => 'Bill not found'], 404);
        }

        $finalAmount = $bill->amount;
    }

    // Check if sender has enough balance
    if ($senderAccount->balance < $finalAmount) {
        return response()->json(['status' => false, 'message' => 'Insufficient balance'], 400);
    }

    // Deduct and hold the amount
    $senderAccount->balance -= $finalAmount;
    $senderAccount->held_balance += $finalAmount;
    $senderAccount->save();

    // Store schedule
    $schedule = PaymentSchedule::create([
        'account_id'           => $senderAccount->id,
        'amount'               => $finalAmount,
        'scheduled_for'        => Carbon::parse($request->scheduled_for)->format('Y-m-d'),
        'purpose'              => $request->purpose,
        'type'                 => $request->type,
        'service_provider_id'  => $request->service_provider_id,
        'consumer_number'      => $request->consumer_number,
        'receiver_name'        => $request->receiver_name,
        'receiver_account_no'  => $sanitizedRecieverNumber,
        'receiver_bank'        => $request->receiver_bank,
        'note'                 => $request->note,
        'is_funded'            => true,
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Payment scheduled successfully',
        'data' => $schedule
    ], 201);
}

    public function refundOnly($id)
    {
        $schedule = PaymentSchedule::findOrFail($id);

        if (!$schedule->is_funded || $schedule->status !== 'scheduled') {
            return response()->json(['status' => false, 'message' => 'Payment not in refundable state']);
        }

        $account = $schedule->account;
        $account->balance += $schedule->amount;
        $account->held_balance -= $schedule->amount;
        $account->save();

        $schedule->is_funded = false;
        $schedule->save();

        return response()->json(['status' => true, 'message' => 'Refunded, schedule still active']);
    }

    // ✅ CANCEL
    public function cancel($id)
    {
        $schedule = PaymentSchedule::findOrFail($id);
        $account = $schedule->account;

        if ($schedule->is_funded) {
            $account->balance += $schedule->amount;
            $account->held_balance -= $schedule->amount;
            $account->save();
        }

        $schedule->update(['status' => 'cancelled', 'is_funded' => false]);

        return response()->json(['status' => true, 'message' => 'Payment cancelled and funds returned']);
    }

    // ♻️ RE-FUND (Manual)
    public function refundBack($id)
    {
        $schedule = PaymentSchedule::findOrFail($id);
        $account = $schedule->account;

        if ($schedule->is_funded || $schedule->status !== 'scheduled') {
            return response()->json(['status' => false, 'message' => 'Already funded or not valid']);
        }

        if ($account->balance < $schedule->amount) {
            return response()->json(['status' => false, 'message' => 'Insufficient balance']);
        }

        $account->balance -= $schedule->amount;
        $account->held_balance += $schedule->amount;
        $account->save();

        $schedule->is_funded = true;
        $schedule->save();

        return response()->json(['status' => true, 'message' => 'Funds added back to schedule']);
    }
}
