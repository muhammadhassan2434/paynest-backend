<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\BillReminder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class BillReminderController extends Controller
{

    public function index($id)
    {
        $user = User::find($id);
        $user_id = $user->id;
        $billReminders = Cache::remember("bill_reminders:index", now()->addMinutes(10), function () use ($user_id) {
            return BillReminder::where('user_id', $user_id)
                ->latest('due_date')
                ->get();
        });
        return response()->json($billReminders);
    }

    public function pendingbillreminders($id)
    {
        $user = User::find($id);
        $user_id = $user->id;
        $billReminders = BillReminder::where('user_id', $user_id)
            ->where('is_notified', 0)
            ->latest('due_date')
            ->get();

        return response()->json($billReminders);
    }

    public function successbillreminders($id)
    {
        $user = User::find($id);
        $user_id = $user->id;
        $billReminders = BillReminder::where('user_id', $user_id)
            ->where('is_notified', 1)
            ->latest('due_date')
            ->get();

        return response()->json($billReminders);
    }



    public function store(Request $request)
    {

        $data = $request->only(['user_id', 'bill_type', 'amount', 'due_date', 'frequency']);

        $validator = Validator::make($data, [
            'bill_type' => 'required|string|max:255',
            'amount'    => 'required|numeric|min:0',
            'due_date'  => 'required|date|after_or_equal:today',
            // 'frequency' => 'required|in:once,monthly,yearly',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => "validation error fill all inputs"
            ], 422);
        }

        // Convert due_date if format is d-m-Y
        if (Carbon::hasFormat($request->due_date, 'd-m-Y')) {
            $data['due_date'] = Carbon::createFromFormat('d-m-Y', $request->due_date)->format('Y-m-d');
        }

        BillReminder::create($data);
        Cache::forget('bill_reminders:index');

        return response()->json([
            'status'  => true,
            'message' => 'Bill Reminder added successfully',
        ], 201);
    }


    public function edit($id)
    {
        $bill_reminder = BillReminder::select('bill_type', 'amount', 'due_date', 'frequency')->find($id);
        return response()->json($bill_reminder);
    }

    public function update(Request $request, $id)
    {

        $data = $request->only(['user_id', 'bill_type', 'amount', 'due_date', 'frequency']);

        $validator = Validator::make($data, [
            'bill_type' => 'required|string|max:255',
            'amount'    => 'required|numeric|min:0',
            'due_date'  => 'required|date|after_or_equal:today',
            // 'frequency' => 'required|in:once,monthly,yearly',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        // Convert due_date if format is d-m-Y
        if (Carbon::hasFormat($request->due_date, 'd-m-Y')) {
            $data['due_date'] = Carbon::createFromFormat('d-m-Y', $request->due_date)->format('Y-m-d');
        }

        // Find and update the record
        $billReminder = BillReminder::find($id);
        if (!$billReminder) {
            return response()->json([
                'status' => false,
                'message' => 'Bill Reminder not found.'
            ], 404);
        }

        $billReminder->update($data);

        Cache::forget("bill_reminders:index");

        return response()->json([
            'status'  => true,
            'message' => 'Bill Reminder updated successfully',
        ], 200);
    }

    public function delete($id)
    {
        $bill_reminder = BillReminder::find($id);
        if (!$bill_reminder) {
            return response()->json([
                'status' => false,
                'message' => 'Bill Reminder not found.'
            ], 404);
        }
        $bill_reminder->delete();
        Cache::forget('bill_reminders:index');

        return response()->json([
            'status' => true,
            'message' => 'Bill Reminder deleted successfully'
        ]);
    }
}
