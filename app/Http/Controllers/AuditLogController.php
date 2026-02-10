<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    public function index()
    {
        if ($redirect = $this->requireRole(['admin'])) {
            return $redirect;
        }

        $dbLoggingOk = true;
        try {
            DB::table('audit_logs')->limit(1)->get();
        } catch (\Throwable $e) {
            $dbLoggingOk = false;
        }

        $fileLoggingOk = is_file(storage_path('logs/audit_ledger.log'));

        $logs = DB::table('audit_logs')
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get();

        return view('pages.audit_logs.index', compact('logs', 'dbLoggingOk', 'fileLoggingOk'));
    }

    public function test()
    {
        if ($redirect = $this->requireRole(['admin'])) {
            return $redirect;
        }

        $this->appendAuditLedger('audit.test', [
            'note' => 'Manual test entry from admin UI',
        ]);

        return redirect()->route('audit.logs')->with('success', 'Test audit log entry created.');
    }
}
