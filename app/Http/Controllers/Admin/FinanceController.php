<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerPayout;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function ledger(Request $request)
    {
        $transactions = WalletTransaction::with(['user', 'conversion.campaign'])
            ->latest()
            ->paginate(25);

        return view('admin.finance.ledger', compact('transactions'));
    }

    public function customerPayouts(Request $request)
    {
        $payouts = CustomerPayout::with(['conversion.campaign', 'conversion.user'])
            ->latest()
            ->paginate(25);

        return view('admin.finance.customer_payouts', compact('payouts'));
    }
}
