@extends('layouts.main')

@section('title', 'Treasurer Dashboard')

@section('content')
<div class="dashboard-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="page-title">Treasurer Dashboard</h2>
            <span class="text-muted">Welcome back, {{ session('usr_name') }}</span>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stat-card card-orange">
                    <div class="stat-icon"><i class="fas fa-search-dollar"></i></div>
                    <div class="stat-data">
                        <h3>{{ number_format($pendingVerifications) }}</h3>
                        <p>GCash Pending Verification</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card card-blue">
                    <div class="stat-icon"><i class="fas fa-file-invoice"></i></div>
                    <div class="stat-data">
                        <h3>{{ number_format($pendingBills) }}</h3>
                        <p>Unpaid Bills</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="content-card">
                    <h5>Generate Bills</h5>
                    <p class="text-muted">Create bills for residents in your barangay.</p>
                    <a href="{{ url('/payments/bill/create') }}" class="btn btn-primary">Generate Bill</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="content-card">
                    <h5>Walk‑In Payments</h5>
                    <p class="text-muted">Record cash payments and update billing status.</p>
                    <a href="{{ route('payments.walkin.create') }}" class="btn btn-outline-primary">Record Walk‑In</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="content-card">
                    <h5>GCash Verifier</h5>
                    <p class="text-muted">Review GCash proofs and verify submissions.</p>
                    <a href="{{ url('/payments') }}" class="btn btn-outline-primary">Review GCash</a>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Recent Payments</h5>
                <a href="{{ url('/payments') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPayments as $payment)
                            <tr>
                                <td>#{{ $payment->id }}</td>
                                <td>{{ $payment->username }}</td>
                                <td>{{ number_format($payment->amount, 2) }}</td>
                                <td>
                                    @if($payment->payment_method == 0) Bill
                                    @elseif($payment->payment_method == 1) Cash
                                    @else GCash
                                    @endif
                                </td>
                                <td>
                                    @if($payment->status == 0) Bill
                                    @elseif($payment->status == 1) Pending
                                    @elseif($payment->status == 2) Verified
                                    @elseif($payment->status == 3) Approved
                                    @else Failed
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No payments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
    .card-orange { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .card-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .stat-icon { font-size: 2.2rem; opacity: 0.3; margin-right: 15px; }
    .stat-data h3 { font-size: 1.6rem; font-weight: 700; margin: 0; }
    .stat-data p { margin: 0; font-size: 0.9rem; opacity: 0.9; }
    .content-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        height: 100%;
    }
</style>
@endsection
