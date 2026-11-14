<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    // Only admins may read the log
    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $logs = AuditLog::with('user:id,name,email')->latest('id')->paginate(50);

        return view('admin.activity.index', compact('logs'));
    }
}