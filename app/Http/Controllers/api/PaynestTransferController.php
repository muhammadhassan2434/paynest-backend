<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaynestTransferController extends Controller
{
    public function ValidatePaynestNumber(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reciever_number' => 'required|numeric',
        ], [
            'reciever_number' => 'Please enter the reciever number'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ]);
        }

        $verify = Account::where('phone', $request->reciever_number)->first();

        if (!$verify) {
            return response()->json([
                'status' => false,
                'message' => 'Account Not Found. Please enter the valid number'
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Account Found',
            'reciever_number' => $request->reciever_number
        ]);
    }

    public function enterAmount(Request $request){
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'reciever_number' => 'required'
        ], [
            'amount' => 'Please enter the amount'
            ]);
         
        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ]);    
        }

        $reciever = Account::with('user')->where('phone', $request->reciever_number)->first();

        return response()->json([
            'status' => true,
            'message' => 'Amount Found',
            'amount' => $request->amount,
            'reciever_paynestid' => $reciever->paynest_id,
            'reciever_name' => $reciever->user->first_name,
            'reciever_email' => $reciever->user->email,
            'reciever_number' => $request->reciever_number
        ]);

    }

    public function PaynestTransfer(Request $request)
{
    $validator = Validator::make($request->all(), [
        'amount' => 'required|numeric',
        'reciever_number' => 'required|numeric',
        'sender_id' => 'required',
    ], [
        'amount.required' => 'Please enter the amount',
        'reciever_number.required' => 'Please enter the receiver number',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => $validator->errors()
        ]);
    }

    $reference = 'paynest' . strtoupper(uniqid()) . rand(1000, 9999);

    try {
        DB::beginTransaction();

        // Find sender
        $sender = Account::where('user_id',$request->sender_id)->first();
        if (!$sender) {
            return response()->json(['status' => false, 'message' => 'Sender account not found']);
        }

        // Check balance
        if ($sender->balance < $request->amount) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient balance. Available: ' . $sender->balance
            ]);
        }

        // Find receiver
        $receiver = Account::where('phone', $request->reciever_number)->first();
        if (!$receiver) {
            return response()->json(['status' => false, 'message' => 'Receiver account not found']);
        }

        // Create transaction record
        $transaction = new Transaction();
        $transaction->sender_id = $request->sender_id;
        $transaction->reciever_number = $request->reciever_number;
        $transaction->amount = $request->amount;
        $transaction->transaction_type  = 'paynest';
        $transaction->status = 'completed';
        $transaction->reference = $reference;
        $transaction->save();

        // Update balances
        $sender->balance -= $request->amount;
        $sender->save();

        $receiver->balance += $request->amount;
        $receiver->save();

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'Transfer Successfully',
            'reference' => $reference
        ]);
    } catch (\Exception $e) {
        DB::rollBack();

        // Update transaction status to pending in case of any error
        Transaction::where('reference', $reference)->update(['status' => 'pending']);

        return response()->json([
            'status' => false,
            'message' => 'Transaction failed: ' . $e->getMessage(),
            'reference' => $reference
        ]);
    }
}

}
