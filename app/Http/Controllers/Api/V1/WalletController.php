<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user()->load('wallet');
        $transactions = WalletTransaction::where('user_id', $user->id)->latest()->paginate(20);

        return response()->json([
            'wallet' => $user->wallet,
            'transactions' => $transactions,
        ]);
    }
}
