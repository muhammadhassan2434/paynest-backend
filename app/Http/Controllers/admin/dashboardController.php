<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class dashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $pendingUsers = User::where('status', 'pending')->count();
        $activeUsers = User::where('status', 'active')->count();
        $blockedUsers = User::where('status', 'blocked')->count();
        $totalTransactions = Transaction::count();
        $pendingTransactions = Transaction::where('status', 'pending')->count();
        $completedTransactions = Transaction::where('status', 'completed')->count();
        $failedTransactions = Transaction::where('status', 'failed')->count();
        $latestUsers = User::where('role', 'user')
            ->with('account')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard.index', compact(
            'totalUsers',
            'pendingUsers',
            'activeUsers',
            'blockedUsers',
            'totalTransactions',
            'pendingTransactions',
            'completedTransactions',
            'failedTransactions',
            'latestUsers'
        ));
    }
}
