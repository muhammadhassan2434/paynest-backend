<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\BillReminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class BillReminderController extends Controller
{

    public function index(){
        $billReminders = Cache::remember('bill_reminders:index', now()->addMinutes(10), function () {
            return BillReminder::select('bill_type', 'amount', 'due_date')
                ->latest('due_date')
                ->get();
        });
        return response()->json($billReminders);
    }

    public function store(Request $request){

        $data = $request->only(['user_id', 'bill_type', 'amount', 'due_date', 'frequency']);

        $validator = Validator::make($request->all(),[
            'user_id' => 'required',
            'bill_type' => 'required|string|max:255',
            'amount'    => 'required|numeric|min:0',
            'due_date'  => 'required|date|after_or_equal:today',
            // 'frequency' => 'required|in:once,monthly,yearly',
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ],422);
        }

        $data['due_date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $request->due_date)->format('Y-m-d');


        BillReminder::create($data);
        Cache::forget('bill_reminders:index');

        

        return response()->json([
            'status'  => true,
            'message' => 'Bill Reminder added successfully',
        ], 201);
    }
}
