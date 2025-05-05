<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\PaymentSchedule;
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
            'account_id'         => 'required|exists:accounts,id',
            'amount'             => 'required|numeric|min:1',
            'scheduled_for'      => 'required|date|after_or_equal:today',
            'purpose'            => 'required|string|max:255',
            'type'               => 'required|in:bill,transfer',
            'category'           => 'nullable|string|max:255',
            'reference_no'       => 'nullable|string|max:255',
            'receiver_name'      => 'nullable|string|max:255',
            'receiver_account_no' => 'nullable|string|max:255',
            'receiver_bank'      => 'nullable|string|max:255',
            'note'               => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }

        $account = Account::findOrFail($request->account_id);

        if ($account->balance < $request->amount) {
            return response()->json(['status' => false, 'message' => 'Insufficient balance'], 400);
        }

        $account->balance -= $request->amount;
        $account->held_balance += $request->amount;
        $account->save();

        $schedule = PaymentSchedule::create([
            'account_id'           => $account->id,
            'amount'               => $request->amount,
            'scheduled_for'        => Carbon::parse($request->scheduled_for)->format('Y-m-d'),
            'purpose'              => $request->purpose,
            'type'                 => $request->type ?? 'bill',
            'category'             => $request->category,
            'reference_no'         => $request->reference_no,
            'receiver_name'        => $request->receiver_name,
            'receiver_account_no'  => $request->receiver_account_no,
            'receiver_bank'        => $request->receiver_bank,
            'note'                 => $request->note,
            'is_funded'            => true,
        ]);

        return response()->json(['status' => true, 'message' => 'Payment scheduled successfully', 'data' => $schedule], 201);
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
