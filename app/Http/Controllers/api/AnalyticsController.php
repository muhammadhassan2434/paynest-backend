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

        // Weekly aggregation
        $weeklyStats = DB::table('transactions')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->selectRaw("
            WEEK(created_at) as week,
            SUM(CASE WHEN reciever_number = ? THEN amount ELSE 0 END) as income
        ", [$authAccountNumber])
            ->groupBy(DB::raw('WEEK(created_at)'))
            ->orderBy('income', 'desc')
            ->get();

        $bestWeek = $weeklyStats->first();
        $worstWeek = $weeklyStats->count() > 1 ? $weeklyStats->last() : $bestWeek;

        // Average of relevant transactions (sent or received)
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
                'best_week' => $bestWeek,
                'worst_week' => $worstWeek,
                'average_value' => round($averageValue, 2),
                'transactions' => $transactionCount,
            ]
        ]);
    }
    public function quarterly($id)
{
    // Get current date and calculate quarter range
    $now = Carbon::now();
    $currentQuarter = ceil($now->month / 3);
    $startOfQuarter = Carbon::create($now->year, ($currentQuarter - 1) * 3 + 1, 1)->startOfMonth();
    $endOfQuarter = (clone $startOfQuarter)->addMonths(2)->endOfMonth();

    $account = Account::where('user_id', $id)->firstOrFail();
    $authId = $account->user_id;
    $authAccountNumber = $account->phone;

    // Daily data
    $transactions = DB::table('transactions')
        ->whereBetween('created_at', [$startOfQuarter, $endOfQuarter])
        ->selectRaw("
            DATE(created_at) as day,
            SUM(CASE WHEN sender_id = ? THEN amount ELSE 0 END) as expense,
            SUM(CASE WHEN reciever_number = ? THEN amount ELSE 0 END) as income
        ", [$authId, $authAccountNumber])
        ->groupBy(DB::raw('DATE(created_at)'))
        ->orderBy('day')
        ->get();

    // Weekly aggregation for quarter
    $weeklyStats = DB::table('transactions')
        ->whereBetween('created_at', [$startOfQuarter, $endOfQuarter])
        ->selectRaw("
            WEEK(created_at) as week,
            SUM(CASE WHEN reciever_number = ? THEN amount ELSE 0 END) as income
        ", [$authAccountNumber])
        ->groupBy(DB::raw('WEEK(created_at)'))
        ->orderBy('income', 'desc')
        ->get();

    $bestWeek = $weeklyStats->first();
    $worstWeek = $weeklyStats->count() > 1 ? $weeklyStats->last() : $bestWeek;

    // Average of transactions (both sent & received)
    $averageValue = DB::table('transactions')
        ->whereBetween('created_at', [$startOfQuarter, $endOfQuarter])
        ->where(function ($query) use ($authId, $authAccountNumber) {
            $query->where('sender_id', $authId)
                  ->orWhere('reciever_number', $authAccountNumber);
        })
        ->avg('amount');

    // Count of transactions
    $transactionCount = DB::table('transactions')
        ->whereBetween('created_at', [$startOfQuarter, $endOfQuarter])
        ->where(function ($query) use ($authId, $authAccountNumber) {
            $query->where('sender_id', $authId)
                  ->orWhere('reciever_number', $authAccountNumber);
        })
        ->count();

    return response()->json([
        'daily' => $transactions,
        'summary' => [
            'best_week' => $bestWeek,
            'worst_week' => $worstWeek,
            'average_value' => round($averageValue, 2),
            'transactions' => $transactionCount,
        ]
    ]);
}

}
