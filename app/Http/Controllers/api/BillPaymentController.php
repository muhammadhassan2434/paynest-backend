<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BillPayment;
use App\Models\FakeBill;
use App\Models\Service;
use App\Models\ServiceProvider;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BillPaymentController extends Controller
{
    public function index(Request $request)
    {
        $user_id = $request->user_id;
        $bills = BillPayment::where('user_id', $user_id)->get();
        return response()->json(['status' => true, 'bills' => $bills]);
    }
    public function failed(Request $request)
    {
        $user_id = $request->user_id;
        $bills = BillPayment::where('user_id', $user_id)->where('status', '!=', 'paid')->get();
        return response()->json(['status' => true, 'bills' => $bills]);
    }

    public function allServiceProvider()
    {
        $AllServices = Service::where('status', 'active')
            ->whereIn('name', ['Electricity bill', 'Gas bill'])
            ->get();

        if ($AllServices->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Services not found'], 404);
        }

        $serviceIds = $AllServices->pluck('id')->toArray();

        $serviceProviders = ServiceProvider::select(['id', 'service_id', 'name', 'logo'])
            ->whereIn('service_id', $serviceIds)
            ->where('status', 'active')
            ->get();

        return response()->json([
            'status' => true,
            'serviceProviders' => $serviceProviders
        ], 200);
    }


    public function serviceProviderElectricityBill()
    {
        $electricity = Service::where('status', 'active')->where('name', 'Electricity bill')->first();

        if (!$electricity) {
            return response()->json(['status' => false, 'message' => 'Electricity service not found'], 404);
        }

        $serviceProviders = ServiceProvider::select(['id', 'service_id', 'name', 'logo'])
            ->where('service_id', $electricity->id)
            ->where('status', 'active')
            ->get();

        return response()->json([
            'status' => true,
            'serviceProviders' => $serviceProviders
        ], 200);
    }

    public function serviceProviderGasBill()
    {
        $gasbill = Service::where('status', 'active')->where('name', 'Gas bill')->first();

        if (!$gasbill) {
            return response()->json(['status' => false, 'message' => 'Gas service not found'], 404);
        }

        $serviceProviders = ServiceProvider::select(['id', 'service_id', 'name', 'logo'])->where('service_id', $gasbill->id)
            ->where('status', 'active')
            ->get();

        return response()->json([
            'status' => true,
            'serviceProviders' => $serviceProviders
        ], 200);
    }

    public function validateConsumernumber(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'consumer_number' => 'required',
            'service_provider_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Please enter the consumer number', 'errors' => $validator->errors()], 422);
        }

        $bill = FakeBill::where('service_provider_id', $request->service_provider_id)
            ->where('consumer_number', $request->consumer_number)
            ->first();

        $serivceprovider = ServiceProvider::where('id', $request->service_provider_id)->first();
        $billprovider = $serivceprovider->name;

        if ($bill) {
            return response()->json([
                'status' => true,
                'bill_provider' => $billprovider,
                'consumer_number' => $bill->consumer_number,
                'customer_name' => $bill->customer_name,
                'amount' => $bill->amount,
                'due_date' => $bill->due_date,
            ], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'Bill not found'], 404);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'consumer_number' => 'required',
            'service_provider_id' => 'required',
            'amount' => 'required|min:1',
            'due_date' => 'required',
            'customer_name' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }
        $reference = 'paynest' . strtoupper(uniqid()) . rand(1000, 9999);

        try {
            DB::beginTransaction();
            $userAccount = Account::where('user_id', $request->user_id)->first();

            if (!$userAccount) {
                return response()->json([
                    'status' => false,
                    'message' => 'User account not found.',
                ], 404);
            }

            //  Check if balance is sufficient
            if ($userAccount->balance < $request->amount) {
                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient balance.',
                ], 400);
            }

            //  Deduct amount
            $userAccount->balance -= $request->amount;
            $userAccount->save();


            // Create the transaction first
            $transaction = Transaction::create([
                'sender_id' => $request->user_id,
                'reciever_number' => '',
                'amount' => $request->amount,
                'transaction_type' => 'bill_payment',
                'status' => 'completed',
                'reference' => $reference,
            ]);

            // Create the bill and use the transaction's ID as the transaction_id
            $bill = BillPayment::create([
                'user_id' => $request->user_id,
                'service_provider_id' => $request->service_provider_id,
                'consumer_number' => $request->consumer_number,
                'customer_name' => $request->customer_name,
                'amount' => $request->amount,
                'due_date' => $request->due_date,
                'payment_date' => now(),
                'status' => 'paid',
                'transaction_id' => $transaction->id,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Bill paid and transaction created successfully.',
                'bill_payment' => $bill,
                'transaction' => $transaction,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to process bill payment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
