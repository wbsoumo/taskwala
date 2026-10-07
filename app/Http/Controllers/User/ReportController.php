<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Click;
use App\Models\Conversion;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function clicks()
    {
        $user = auth('web')->user();

        $clicks = Click::with(['campaign', 'link'])
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->paginate(20);

        return view('user.reports.clicks', compact('clicks'));
    }

    public function conversions()
    {
        $user = auth('web')->user();

        $conversions = Conversion::with(['campaign', 'payoutSnapshot'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(20);

        return view('user.reports.conversions', compact('conversions'));
    }
}
