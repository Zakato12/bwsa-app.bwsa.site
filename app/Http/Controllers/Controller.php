<?php

namespace App\Http\Controllers;

use App\Support\SecurityMonitor;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\RedirectResponse;
use App\Support\Access;
use Illuminate\Support\Facades\DB;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function requireLogin(): ?RedirectResponse
    {
        if (!Access::isAuthenticated()) {
            SecurityMonitor::event('auth.require_login_denied');
            return redirect()->route('login')->with('error', 'Please login first.');
        }

        return null;
    }

    protected function requireRole(array $roles): ?RedirectResponse
    {
        if (!Access::isAuthenticated()) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }

        if (!Access::hasRole($roles)) {
            SecurityMonitor::event('auth.require_role_denied', [
                'required_roles' => $roles,
                'current_role' => Access::currentRole(),
            ]);
            return redirect()->route('dashboard')->with('error', 'Unauthorized');
        }

        return null;
    }

    protected function currentUserBarangayId(): ?int
    {
        return Access::currentUserBarangayId();
    }

    protected function requireRoleInBarangay(
        array $roles,
        ?int $targetUserId = null,
        ?int $targetBarangayId = null
    ): ?RedirectResponse {
        if ($redirect = $this->requireRole($roles)) {
            return $redirect;
        }

        if (Access::currentRole() === 'admin') {
            return null;
        }

        $actorBarangayId = Access::currentUserBarangayId();
        if (!$actorBarangayId) {
            SecurityMonitor::event('auth.barangay_missing', [
                'required_roles' => $roles,
            ]);
            return redirect()->route('dashboard')->with('error', 'Barangay assignment required.');
        }

        if ($targetBarangayId !== null && (int) $targetBarangayId !== (int) $actorBarangayId) {
            SecurityMonitor::event('auth.barangay_scope_denied', [
                'target_barangay_id' => $targetBarangayId,
                'actor_barangay_id' => $actorBarangayId,
                'required_roles' => $roles,
            ]);
            return redirect()->route('dashboard')->with('error', 'Unauthorized');
        }

        if ($targetUserId !== null && !Access::userInSameBarangay($targetUserId)) {
                SecurityMonitor::event('auth.user_scope_denied', [
                    'target_user_id' => $targetUserId,
                    'actor_barangay_id' => $actorBarangayId,
                    'required_roles' => $roles,
                ]);
                return redirect()->route('dashboard')->with('error', 'Unauthorized');
        }

        return null;
    }

    protected function appendAuditLedger(string $event, array $context = []): void
    {
        $prevHash = $this->lastAuditHash();
        $record = [
            'ts' => now()->toDateTimeString(),
            'event' => $event,
            'actor_id' => session('usr_id'),
            'role' => session('usr_role'),
            'context' => $context,
            'prev_hash' => $prevHash,
        ];

        $line = json_encode($record, JSON_UNESCAPED_SLASHES);
        if ($line === false) {
            return;
        }

        $currHash = hash('sha256', $line);
        $record['hash'] = $currHash;
        $line = json_encode($record, JSON_UNESCAPED_SLASHES);
        if ($line === false) {
            return;
        }

        $path = storage_path('logs/audit_ledger.log');
        file_put_contents($path, $line . PHP_EOL, FILE_APPEND | LOCK_EX);

        try {
            \Illuminate\Support\Facades\DB::table('audit_logs')->insert([
                'event' => $event,
                'actor_id' => session('usr_id'),
                'role' => session('usr_role'),
                'context' => json_encode([
                    'data' => $context,
                    'prev_hash' => $prevHash,
                    'hash' => $currHash,
                ], JSON_UNESCAPED_SLASHES),
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 255),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Fail closed to file log only; DB log is best-effort.
        }
    }

    private function lastAuditHash(): ?string
    {
        $path = storage_path('logs/audit_ledger.log');
        if (!is_file($path)) {
            return null;
        }

        $fp = fopen($path, 'rb');
        if ($fp === false) {
            return null;
        }

        $pos = -1;
        $line = '';
        $char = '';
        fseek($fp, 0, SEEK_END);
        $len = ftell($fp);
        if ($len === 0) {
            fclose($fp);
            return null;
        }

        while ($len + $pos >= 0) {
            fseek($fp, $pos, SEEK_END);
            $char = fgetc($fp);
            if ($char === "\n" && $line !== '') {
                break;
            }
            $line = $char . $line;
            $pos--;
        }

        fclose($fp);
        $line = trim($line);
        if ($line === '') {
            return null;
        }

        $decoded = json_decode($line, true);
        if (!is_array($decoded) || empty($decoded['hash'])) {
            return null;
        }

        return (string) $decoded['hash'];
    }

    protected function generateCredentials(int $roleId, int $barangayId = null): array
    {
        $roleMap = [
            1 => 'ADM',
            2 => 'OFF',
            3 => 'TRE',
            4 => 'RES',
        ];

        $roleCode = $roleMap[$roleId] ?? 'USR';
        $brgyCode = 'BRGY';

        if ($barangayId) {
            $brgy = DB::table('barangays')->where('id', $barangayId)->value('brgy_code');
            if ($brgy) {
                $brgyCode = strtoupper($brgy);
            }
        }

        $prefix = $brgyCode . '-' . $roleCode;
        $seq = DB::table('users')->where('username', 'like', $prefix . '-%')->count() + 1;

        do {
            $username = $prefix . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            $exists = DB::table('users')->where('username', $username)->exists();
            $seq++;
        } while ($exists);

        return [$username, $username]; // password = username
    }
}
