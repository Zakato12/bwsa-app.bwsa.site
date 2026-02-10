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
                    <p class="text-muted mb-0">
                        Monitor resident totals per barangay from the barangay list. Admin does not access the residents list directly.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="content-card">
                    <h5>Recent Activity Log</h5>
                    <ul class="activity-list">
                        @forelse($recentLogs as $line)
                            @php $log = json_decode($line, true); @endphp
                            <li>
                                <div><strong>{{ $log['event'] ?? 'event' }}</strong></div>
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
