@extends('layouts.main')

@section('title', 'Audit Logs')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Audit Logs</h1>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted">Latest 200 entries</span>
        </div>
    </div>

    <div class="mb-3">
        <span class="badge {{ $dbLoggingOk ? 'bg-success' : 'bg-danger' }}">DB Log: {{ $dbLoggingOk ? 'OK' : 'FAIL' }}</span>
        <span class="badge {{ $fileLoggingOk ? 'bg-success' : 'bg-danger' }} ms-2">File Log: {{ $fileLoggingOk ? 'OK' : 'FAIL' }}</span>
    </div>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Event</th>
                    <th>Actor</th>
                    <th>Role</th>
                    <th>Context</th>
                    <th>IP</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $eventLabels = [
                        'users.created' => 'User Created',
                        'users.updated' => 'User Updated',
                        'users.deleted' => 'User Deleted',
                        'payments.submitted' => 'Payment Submitted',
                        'payments.verified' => 'Payment Verified',
                        'payments.approved' => 'Payment Approved',
                        'payments.rejected' => 'Payment Rejected',
                        'payments.walkin_created' => 'Walk-in Payment Recorded',
                        'payments.bill_batch_created' => 'Bills Generated',
                        'audit.test' => 'Audit Test Entry',
                    ];
                    $formatEvent = function ($event) use ($eventLabels) {
                        return $eventLabels[$event] ?? ucwords(str_replace(['.', '_'], ' ', (string) $event));
                    };
                    $formatMessage = function ($event, array $data) {
                        $paymentId = $data['payment_id'] ?? null;
                        $userId = $data['user_id'] ?? null;
                        $method = $data['method'] ?? null;
                        $reason = $data['reason'] ?? $data['rejection_reason'] ?? null;
                        $generated = $data['generated_count'] ?? null;
                        $skipped = $data['skipped_count'] ?? null;

                        $methodLabel = $method === 2 ? 'GCash' : ($method === 1 ? 'Cash' : null);
                        switch ($event) {
                            case 'payments.submitted':
                                return trim('Payment' . ($paymentId ? " #{$paymentId}" : '') . ' submitted' . ($methodLabel ? " via {$methodLabel}" : ''));
                            case 'payments.verified':
                                return trim('Payment' . ($paymentId ? " #{$paymentId}" : '') . ' verified');
                            case 'payments.approved':
                                return trim('Payment' . ($paymentId ? " #{$paymentId}" : '') . ' approved');
                            case 'payments.rejected':
                                return trim('Payment' . ($paymentId ? " #{$paymentId}" : '') . ' rejected' . ($reason ? " ({$reason})" : ''));
                            case 'payments.walkin_created':
                                return trim('Walk-in payment' . ($paymentId ? " #{$paymentId}" : '') . ($userId ? " for user #{$userId}" : '') . ' recorded');
                            case 'payments.bill_batch_created':
                                if ($generated !== null || $skipped !== null) {
                                    return 'Bills generated: ' . (int) ($generated ?? 0) . ', skipped: ' . (int) ($skipped ?? 0);
                                }
                                return 'Bills generated';
                            case 'users.created':
                            case 'users.updated':
                            case 'users.deleted':
                                return $userId ? ('User #' . $userId) : 'User record changed';
                            case 'audit.test':
                                return $data['note'] ?? 'Audit test entry';
                            default:
                                if (!empty($data)) {
                                    return collect($data)->map(function ($v, $k) {
                                        if (is_array($v)) {
                                            $v = json_encode($v, JSON_UNESCAPED_SLASHES);
                                        }
                                        return "{$k}: {$v}";
                                    })->implode(', ');
                                }
                                return '-';
                        }
                    };
                @endphp
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->id }}</td>
                        <td>{{ $formatEvent($log->event) }}</td>
                        <td>{{ $log->actor_id ?? '-' }}</td>
                        <td>{{ $log->role ?? '-' }}</td>
                        <td>
                            @php
                                $ctx = $log->context ?? null;
                                $decoded = null;
                                if (is_string($ctx)) {
                                    $decoded = json_decode($ctx, true);
                                }
                            @endphp
                            @if(is_array($decoded))
                                @php
                                    $data = $decoded['data'] ?? $decoded;
                                @endphp
                                <span>{{ $formatMessage($log->event, is_array($data) ? $data : []) }}</span>
                            @elseif($ctx)
                                <span>{{ $ctx }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $log->ip_address ?? '-' }}</td>
                        <td>{{ \Carbon\Carbon::parse($log->created_at)->format('M d, Y h:i A') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No audit logs found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
