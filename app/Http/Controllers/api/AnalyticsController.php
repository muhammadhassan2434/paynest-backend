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

        $account = Account::where('user_id', $id)->first();
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
    public function yearToYear($id)
    {
        $now = Carbon::now();
        $currentYearStart = $now->copy()->startOfYear();
        $currentYearEnd = $now->copy()->endOfYear();

        $previousYearStart = $now->copy()->subYear()->startOfYear();
        $previousYearEnd = $now->copy()->subYear()->endOfYear();

        $account = Account::where('user_id', $id)->firstOrFail();
        $authId = $account->user_id;
        $authAccountNumber = $account->phone;

        // Current year total income & expense
        $current = DB::table('transactions')
            ->whereBetween('created_at', [$currentYearStart, $currentYearEnd])
            ->selectRaw("
            SUM(CASE WHEN sender_id = ? THEN amount ELSE 0 END) as expense,
            SUM(CASE WHEN reciever_number = ? THEN amount ELSE 0 END) as income
        ", [$authId, $authAccountNumber])
            ->first();

        // Previous year total income & expense
        $previous = DB::table('transactions')
            ->whereBetween('created_at', [$previousYearStart, $previousYearEnd])
            ->selectRaw("
            SUM(CASE WHEN sender_id = ? THEN amount ELSE 0 END) as expense,
            SUM(CASE WHEN reciever_number = ? THEN amount ELSE 0 END) as income
        ", [$authId, $authAccountNumber])
            ->first();

        // YoY Percentage Change Calculation
        $incomeChange = ($previous->income != 0)
            ? round((($current->income - $previous->income) / $previous->income) * 100, 2)
            : null;

        $expenseChange = ($previous->expense != 0)
            ? round((($current->expense - $previous->expense) / $previous->expense) * 100, 2)
            : null;

        return response()->json([
            'current_year' => [
                'income' => round($current->income, 2),
                'expense' => round($current->expense, 2),
            ],
            'previous_year' => [
                'income' => round($previous->income, 2),
                'expense' => round($previous->expense, 2),
            ],
            'yoy_change' => [
                'income_percent_change' => $incomeChange,
                'expense_percent_change' => $expenseChange,
            ],
        ]);
    }
}
