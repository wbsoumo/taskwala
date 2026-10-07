<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index()
    {
        $user = auth('web')->user();
        $user->load('wallet');

        $transactions = WalletTransaction::with('conversion.campaign')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(20);

        return view('user.wallet.index', compact('user', 'transactions'));
    }
}
