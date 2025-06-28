<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Jobs\TransactionSuccessJob;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaynestTransferController extends Controller
{
    public function ValidatePaynestNumber(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reciever_number' => 'required|numeric',
        ], [
            'reciever_number.required' => 'Please enter the receiver number',
            'reciever_number.numeric' => 'The receiver number must be numeric'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ]);
        }
    
        $originalNumber = $request->reciever_number;
    
        // Sanitize number: Remove +92 or leading 0
        $sanitizedNumber = preg_replace('/^(\+92|0)/', '', $originalNumber);
    
        $user = Auth::user();
        $user_id = $user->id;
        $account = Account::where('user_id', $user_id)->first();
    
        if ($sanitizedNumber == $account->phone) {
            return response()->json([
                'status' => false,
                'message' => 'You cannot transfer money to your own number. Please enter a different number.'
            ]);
        }
    
        $verify = Account::where('phone', $sanitizedNumber)->first();
    
        if (!$verify) {
            return response()->json([
                'status' => false,
                'message' => 'Account Not Found. Please enter a valid number'
            ]);
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Account Found',
            'reciever_number' => $sanitizedNumber
        ]);
    }
    

    public function enterAmount(Request $request){
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'reciever_number' => 'required',
            'account_id' => 'required'
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
        if (!$reciever) {
            return response()->json([
                'status' => false,
                'message' => 'Receiver not found!'
            ]);
        }
        $sender = Account::where('id', $request->account_id)->first();
    
        if ($sender->balance < $request->amount) {
            return response()->json([
                'status' => false,
                'message' => 'Low balance! Enter correct amount',
            ]);
        }

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
        'amount' => 'required|numeric|min:1',  // Ensure the amount is greater than 0
        'reciever_number' => 'required',
        // 'account_id' => 'required'
    ], [
        'amount.required' => 'Please enter the amount',
        'amount.numeric' => 'The amount must be a valid number',
        'amount.min' => 'Please enter a valid amount greater than zero',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => $validator->errors()
        ]);
    }
    $originalNumber = $request->reciever_number;
    
        // Sanitize number: Remove +92 or leading 0
        $sanitizedRecieverNumber = preg_replace('/^(\+92|0)/', '', $originalNumber);

    $reference = 'paynest' . strtoupper(uniqid()) . rand(1000, 9999);

    try {
        DB::beginTransaction();

        // Find sender
        $sender = Auth::user();
        $sender_id = $sender->id;
        $sender = Account::where('user_id',$sender_id)->first();
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
        $receiver = Account::where('phone', $sanitizedRecieverNumber)->first();
        if (!$receiver) {
            return response()->json(['status' => false, 'message' => 'Receiver account not found']);
        }

        // Create transaction record
        $transaction = new Transaction();
        $transaction->sender_id = $request->sender_id;
        $transaction->reciever_number = $sanitizedRecieverNumber;
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

        dispatch(new TransactionSuccessJob($sender->user->first_name,$sender->user->email, $sender->phone, $receiver->user->first_name, $transaction->amount));

        return response()->json([
            'status' => true,
            'message' => 'Transfer Successfully',
            'reference' => $reference,
            'amount' => $request->amount,
            'reciver_name' => $receiver->first_name,
            'reciver_lastname' => $receiver->last_name,
            'receiver_number' => $sanitizedRecieverNumber,
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
