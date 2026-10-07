<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LoginLog;
use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Http\Request;

class SecurityController extends Controller
{
    public function auditLogs(Request $request)
    {
        $logs = AuditLog::latest('created_at')->paginate(25);

        return view('admin.security.audit_logs', compact('logs'));
    }

    public function loginLogs(Request $request)
    {
        $logs = LoginLog::latest('created_at')->paginate(25);

        return view('admin.security.login_logs', compact('logs'));
    }
}
