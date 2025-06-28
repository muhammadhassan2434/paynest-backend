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

        $account = Account::where('user_id', $id)->firstOrFail();

        // Daily aggregation
        $transactions = DB::table('transactions')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->selectRaw("
            DATE(created_at) as day,
            SUM(CASE WHEN sender_id = ? THEN amount ELSE 0 END) as expense,
            SUM(CASE WHEN reciever_number = ? THEN amount ELSE 0 END) as income
        ", [$account->user_id, $account->phone])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('day')
            ->get();

        // Weekly aggregation with fallback
        $weeklyStats = DB::table('transactions')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where('reciever_number', $account->phone)
            ->selectRaw("
            WEEK(created_at, 1) as week,
            SUM(amount) as income
        ")
            ->groupBy(DB::raw('WEEK(created_at, 1)'))
            ->get();

        // Handle empty weekly data
        $bestWeek = $weeklyStats->sortByDesc('income')->first();
        $worstWeek = $weeklyStats->sortBy('income')->first();

        return response()->json([
            'daily' => $transactions,
            'summary' => [
                'best_week' => $bestWeek ?? (object)['week' => null, 'income' => '0'],
                'worst_week' => $worstWeek ?? (object)['week' => null, 'income' => '0'],
                'average_value' => $transactions->avg('income') ?? 0,
                'transactions' => $transactions->count(),
            ]
        ]);
    }
//      public function monthly($id)
// {
//     $startOfMonth = Carbon::now()->startOfMonth();
//     $endOfMonth = Carbon::now()->endOfMonth();

//     $account = Account::where('user_id', $id)->first();
//     $authId = $account->user_id;
//     $authAccountNumber = $account->phone;

//     // Daily aggregation
//     $transactions = DB::table('transactions')
//         ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
//         ->selectRaw("
//             DATE(created_at) as day,
//             SUM(CASE WHEN sender_id = ? THEN amount ELSE 0 END) as expense,
//             SUM(CASE WHEN reciever_number = ? THEN amount ELSE 0 END) as income
//         ", [$authId, $authAccountNumber])
//         ->groupBy(DB::raw('DATE(created_at)'))
//         ->orderBy('day')
//         ->get();

//     // Weekly aggregation
//     $weeklyStats = DB::table('transactions')
//         ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
//         ->selectRaw("
//             WEEK(created_at) as week,
//             SUM(CASE WHEN reciever_number = ? THEN amount ELSE 0 END) as income
//         ", [$authAccountNumber])
//         ->groupBy(DB::raw('WEEK(created_at)'))
//         ->orderBy('income', 'desc')
//         ->get();

//     // Fallback values if no weekly data
//     $bestWeek = $weeklyStats->first();
//     $worstWeek = $weeklyStats->count() > 1 ? $weeklyStats->last() : $bestWeek;

//     if (!$bestWeek) {
//         $bestWeek = (object)[
//             'week' => null,
//             'income' => 0
//         ];
//         $worstWeek = $bestWeek;
//     }

//     $averageValue = DB::table('transactions')
//         ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
//         ->where(function ($query) use ($authId, $authAccountNumber) {
//             $query->where('sender_id', $authId)
//                 ->orWhere('reciever_number', $authAccountNumber);
//         })
//         ->avg('amount') ?? 0;

//     $transactionCount = DB::table('transactions')
//         ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
//         ->where(function ($query) use ($authId, $authAccountNumber) {
//             $query->where('sender_id', $authId)
//                 ->orWhere('reciever_number', $authAccountNumber);
//         })
//         ->count();

//     return response()->json([
//         'daily' => $transactions,
//         'summary' => [
//             'best_week' => $bestWeek,
//             'worst_week' => $worstWeek,
//             'average_value' => round($averageValue, 2),
//             'transactions' => $transactionCount,
//         ]
//     ]);
// }

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

        $bestWeek = $weeklyStats->first() ?? (object)[
            'week' => null,
            'income' => 0
        ];

        $worstWeek = $weeklyStats->count() > 1
            ? $weeklyStats->last()
            : $bestWeek;


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
    public function yearly($id)
    {
        // Define start and end of the year
        $startOfYear = Carbon::now()->startOfYear();
        $endOfYear = Carbon::now()->endOfYear();

        $account = Account::where('user_id', $id)->firstOrFail();
        $authId = $account->user_id;
        $authAccountNumber = $account->phone;

        // Daily aggregation
        $transactions = DB::table('transactions')
            ->whereBetween('created_at', [$startOfYear, $endOfYear])
            ->selectRaw("
            DATE(created_at) as day,
            SUM(CASE WHEN sender_id = ? THEN amount ELSE 0 END) as expense,
            SUM(CASE WHEN reciever_number = ? THEN amount ELSE 0 END) as income
        ", [$authId, $authAccountNumber])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('day')
            ->get();

        // Monthly aggregation for year
        $monthlyStats = DB::table('transactions')
            ->whereBetween('created_at', [$startOfYear, $endOfYear])
            ->selectRaw("
            MONTH(created_at) as month,
            SUM(CASE WHEN reciever_number = ? THEN amount ELSE 0 END) as income
        ", [$authAccountNumber])
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy('income', 'desc')
            ->get();

        $bestMonth = $monthlyStats->first();
        $worstMonth = $monthlyStats->count() > 1 ? $monthlyStats->last() : $bestMonth;

        // Average of relevant transactions (sent or received)
        $averageValue = DB::table('transactions')
            ->whereBetween('created_at', [$startOfYear, $endOfYear])
            ->where(function ($query) use ($authId, $authAccountNumber) {
                $query->where('sender_id', $authId)
                    ->orWhere('reciever_number', $authAccountNumber);
            })
            ->avg('amount');

        // Count of transactions relevant to user
        $transactionCount = DB::table('transactions')
            ->whereBetween('created_at', [$startOfYear, $endOfYear])
            ->where(function ($query) use ($authId, $authAccountNumber) {
                $query->where('sender_id', $authId)
                    ->orWhere('reciever_number', $authAccountNumber);
            })
            ->count();

        return response()->json([
            'daily' => $transactions,
            'summary' => [
                'best_month' => $bestMonth,
                'worst_month' => $worstMonth,
                'average_value' => round($averageValue, 2),
                'transactions' => $transactionCount,
            ]
        ]);
    }
}
