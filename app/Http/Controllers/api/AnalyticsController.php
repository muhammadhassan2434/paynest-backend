<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{

    public function monthly($id)
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $account = Account::where('user_id',$id)->first();
        $authId = $account->user_id;
        $authAccountNumber = $account->phone;

        // Daily aggregation
        $transactions = DB::table('transactions')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->selectRaw("
            DATE(created_at) as day,
            SUM(CASE WHEN sender_id = ? THEN amount ELSE 0 END) as expense,
            SUM(CASE WHEN reciever_number = ? THEN amount ELSE 0 END) as income
        ", [$authId, $authAccountNumber])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('day')
            ->get();

        $billPaymentCount = DB::table('bill_payments')
        ->where('user_id', $authId)
        ->where('status', 'paid')
        ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
        ->count();
        $schedulePaymentCount = DB::table('payment_schedules')
    ->where('account_id', $account->id)
    ->where('status', 'executed') 
    ->whereBetween('scheduled_for', [$startOfMonth, $endOfMonth])
    ->count();


        $averageValue = DB::table('transactions')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where(function ($query) use ($authId, $authAccountNumber) {
                $query->where('sender_id', $authId)
                    ->orWhere('reciever_number', $authAccountNumber);
            })
            ->avg('amount');

        // Count of transactions relevant to user
        $transactionCount = DB::table('transactions')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where(function ($query) use ($authId, $authAccountNumber) {
                $query->where('sender_id', $authId)
                    ->orWhere('reciever_number', $authAccountNumber);
            })
            ->count();

        return response()->json([
            'daily' => $transactions,
            'summary' => [
                'best_week' => $billPaymentCount,
                'worst_week' => $schedulePaymentCount,
                'average_value' => round($averageValue, 2),
                'transactions' => $transactionCount,
            ]
        ]);
    }
}
