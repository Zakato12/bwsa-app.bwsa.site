@extends('layouts.main')
@section('title', 'Admin Dashboard')
@section('content')
<div class="dashboard-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="page-title">Admin Command Center</h2>
            <span class="text-muted">Welcome back, {{ session('usr_name') }}</span>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card card-blue">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-data">
                        <h3>{{ number_format($totalResidents) }}</h3>
                        <p>Total Residents</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card-green">
                    <div class="stat-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="stat-data">
                        <h3>{{ number_format($totalBarangays) }}</h3>
                        <p>Total Barangays</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card-orange">
                    <div class="stat-icon"><i class="fas fa-receipt"></i></div>
                    <div class="stat-data">
                        <h3>{{ number_format($totalPayments) }}</h3>
                        <p>Total Payments</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card-red">
                    <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                    <div class="stat-data">
                        <h3>{{ number_format($pendingPayments) }}</h3>
                        <p>Pending Payments</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-8">
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>System Overview</h5>
                        <a href="{{ url('/barangays') }}" class="btn btn-sm btn-outline-primary">View Barangays</a>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-2 text-muted small">Payments Summary</div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-secondary">Today: {{ number_format($paymentsToday) }}</span>
                                <span class="badge bg-warning text-dark">Pending: {{ number_format($pendingPayments) }}</span>
                                <span class="badge bg-info text-dark">Verified: {{ number_format($verifiedPayments) }}</span>
                                <span class="badge bg-success">Approved: {{ number_format($approvedPayments) }}</span>
                                <span class="badge bg-danger">Rejected: {{ number_format($rejectedPayments) }}</span>
                            </div>
                            <div class="mt-3 text-muted small">Payment Methods</div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-primary">GCash: {{ number_format($gcashPayments) }}</span>
                                <span class="badge bg-dark">Cash: {{ number_format($cashPayments) }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2 text-muted small">Top Barangays (Residents)</div>
                            <ul class="list-unstyled mb-0">
                                @forelse($topBarangays as $barangay)
                                    <li class="d-flex justify-content-between border-bottom py-1">
                                        <span>{{ $barangay->name }}</span>
                                        <span class="text-muted">{{ number_format($barangay->resident_count) }}</span>
                                    </li>
                                @empty
                                    <li class="text-muted">No barangay data available.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="content-card">
                    <h5>Recent Activity Log</h5>
                    <ul class="activity-list">
                        @forelse($recentLogs as $line)
                            @php
                                $log = json_decode($line, true) ?? [];
                                $event = $log['event'] ?? 'event';
                                $data = is_array($log['context'] ?? null) ? $log['context'] : ($log['context'] ?? []);
                                if (!is_array($data)) {
                                    $data = [];
                                }

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
                                $eventLabel = $eventLabels[$event] ?? ucwords(str_replace(['.', '_'], ' ', (string) $event));

                                $paymentId = $data['payment_id'] ?? null;
                                $userId = $data['user_id'] ?? null;
                                $method = $data['method'] ?? null;
                                $reason = $data['reason'] ?? $data['rejection_reason'] ?? null;
                                $generated = $data['generated_count'] ?? null;
                                $skipped = $data['skipped_count'] ?? null;
                                $methodLabel = $method === 2 ? 'GCash' : ($method === 1 ? 'Cash' : null);

                                $message = match ($event) {
                                    'payments.submitted' => trim('Payment' . ($paymentId ? " #{$paymentId}" : '') . ' submitted' . ($methodLabel ? " via {$methodLabel}" : '')),
                                    'payments.verified' => trim('Payment' . ($paymentId ? " #{$paymentId}" : '') . ' verified'),
                                    'payments.approved' => trim('Payment' . ($paymentId ? " #{$paymentId}" : '') . ' approved'),
                                    'payments.rejected' => trim('Payment' . ($paymentId ? " #{$paymentId}" : '') . ' rejected' . ($reason ? " ({$reason})" : '')),
                                    'payments.walkin_created' => trim('Walk-in payment' . ($paymentId ? " #{$paymentId}" : '') . ($userId ? " for user #{$userId}" : '') . ' recorded'),
                                    'payments.bill_batch_created' => ($generated !== null || $skipped !== null)
                                        ? ('Bills generated: ' . (int) ($generated ?? 0) . ', skipped: ' . (int) ($skipped ?? 0))
                                        : 'Bills generated',
                                    'users.created', 'users.updated', 'users.deleted' => $userId ? ('User #' . $userId) : 'User record changed',
                                    'audit.test' => $data['note'] ?? 'Audit test entry',
                                    default => '',
                                };
                            @endphp
                            <li>
                                <div><strong>{{ $eventLabel }}</strong></div>
                                @if($message !== '')
                                    <div>{{ $message }}</div>
                                @endif
                                <div class="text-muted" style="font-size: 0.8rem;">
                                    {{ $log['ts'] ?? '' }} • role: {{ $log['role'] ?? 'n/a' }} • actor: {{ $log['actor_id'] ?? 'n/a' }}
                                </div>
                            </li>
                        @empty
                            <li class="text-muted">No recent activity.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .stat-card {
        padding: 20px;
        border-radius: 12px;
        color: white;
        display: flex;
        align-items: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .card-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .card-green { background: linear-gradient(135deg, #10b981, #059669); }
    .card-orange { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .card-red { background: linear-gradient(135deg, #ef4444, #dc2626); }

    .stat-icon {
        font-size: 2.5rem;
        opacity: 0.3;
        margin-right: 15px;
    }

    .stat-data h3 {
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0;
    }

    .stat-data p {
        margin: 0;
        font-size: 0.9rem;
        opacity: 0.9;
    }

    .content-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        height: 100%;
    }

    .activity-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .activity-list li {
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
        font-size: 0.85rem;
    }
</style>
@endsection
