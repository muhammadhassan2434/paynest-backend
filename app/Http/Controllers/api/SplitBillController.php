<?php

namespace App\Http\Controllers\api;

use App\Events\SplitBillCompleted;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\SplitBill;
use App\Models\SplitBillMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SplitBillController extends Controller
{
    public function fetchAllBills($userId)
    {
        try {

            $bills = SplitBill::with('members')
                ->where('created_by', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $bills
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch bills',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function fetchTransfaredBills($userId)
    {
        try {
            $bills = SplitBill::with('members')
                ->where('created_by', $userId)
                ->where('status', 'transferred')  // Filter by completed status
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $bills
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch bills',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function create(Request $request)
    {
        $Validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'receiver_account_no' => 'required',
            'receiver_bank' => 'required|string',
            'total_amount' => 'required|numeric|min:1',
            'title' => 'required|string',
            'note' => 'nullable',
            'members' => 'required|array|min:1',
            'members.*.phone' => 'required',
            'members.*.amount' => 'required|numeric|min:0.01'
        ]);

        if ($Validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => ' Validation Failed! Please fill all inputs',
                'errors' => $Validator->errors()
            ]);
        }

        DB::beginTransaction();

        try {
            $receiverveOriginalNumber = $request->receiver_account_no;
            $receiverSanitizedNumber = preg_replace('/^(\+92|0)/', '', $receiverveOriginalNumber);
            $receiverAccount = Account::where('phone', $receiverSanitizedNumber)->first();
            if (!$receiverAccount) {
                return response()->json([
                    'status' => false,
                    'message' => 'Receiver Account Not Found'
                ]);
            }
            if ($receiverAccount->user_id == $request->user_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You cannot split bill with yourself'
                ]);
            }
            $splitBill = SplitBill::create([
                'created_by' => $request->user_id,
                'receiver_account_no' => $receiverAccount->phone,
                'receiver_bank' => $request->receiver_bank,
                'total_amount' => $request->total_amount,
                'title' => $request->title,
                'note' => $request->note,
                'status' => 'pending',
            ]);

            foreach ($request->members as $member) {
                $originalNumber = $member['phone'];

                // Sanitize number: Remove +92 or leading 0
                $sanitizedNumber = preg_replace('/^(\+92|0)/', '', $originalNumber);
                $account = Account::where('phone', $sanitizedNumber)->first();
                if (!$account) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Account Not Found'
                    ]);
                }
                $user_id = $account->user_id;

                SplitBillMember::create([
                    'split_bill_id' => $splitBill->id,
                    'user_id' => $user_id,
                    'amount' => $member['amount'],
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Split bill created successfully', 'data' => $splitBill], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Failed to create split bill', 'error' => $e->getMessage()], 500);
        }
    }
    // SplitBillController.php

    public function getMySplitRequests($user_id)
    {
        $requests = SplitBillMember::with(['splitBill'])
            ->where('user_id', $user_id)
            ->get();

        return response()->json([
            'status' => true,
            'data' => $requests
        ]);
    }

    public function pay(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'split_bill_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

        DB::beginTransaction();

        try {
            $member = SplitBillMember::where('split_bill_id', $request->split_bill_id)
                ->where('user_id', $request->user_id)
                ->lockForUpdate()
                ->first();

            if (!$member) {
                return response()->json([
                    'status' => false,
                    'message' => 'Split bill not found for this user'
                ]);
            }

            if ($member->is_paid) {
                return response()->json([
                    'status' => false,
                    'message' => 'You have already paid this bill'
                ]);
            }
            $account = Account::where('user_id', $request->user_id)
                ->lockForUpdate()
                ->first();

            if (!$account) {
                return response()->json([
                    'status' => false,
                    'message' => 'Account not found for this user'
                ]);
            }

            if ($account->balance < $member->amount) {
                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient balance in your account'
                ]);
            }

            // Deduct amount from member's account balance
            $account->balance -= $member->amount;
            $account->save();

            //  Mark member as paid
            $member->is_paid = true;
            $member->paid_at = now();
            $member->save();

            //  Update collected amount
            $splitBill = SplitBill::lockForUpdate()->find($request->split_bill_id);
            $splitBill->collected_amount += $member->amount;

            //  Update status
            if ($splitBill->collected_amount >= $splitBill->total_amount) {
                $splitBill->status = 'completed';
                $splitBill->save();

                // 🔥 Fire event to auto-transfer
                event(new SplitBillCompleted($splitBill));
            } else {
                $splitBill->status = 'partial';
                $splitBill->save();
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Payment successful',
                'data' => [
                    'member' => $member,
                    'split_bill' => $splitBill
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Payment failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
