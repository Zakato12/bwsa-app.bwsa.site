@extends('layouts.main')

@section('title', 'Resident Dashboard')

@section('content')
<div class="dashboard-wrapper">
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="page-title">Resident Dashboard</h2>
            <span class="text-muted">Welcome back, {{ session('usr_name') }}</span>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stat-card card-blue">
                    <div class="stat-icon"><i class="fas fa-file-invoice"></i></div>
                    <div class="stat-data">
                        <h3>{{ number_format($unpaidBills) }}</h3>
                        <p>Total Unpaid Bills</p>
                        <p class="mt-1 mb-0">
                            Upcoming (7 days): {{ number_format($upcomingDueBills ?? 0) }}
                            @if(!empty($nextDueBillDate))
                                | Next due: {{ \Carbon\Carbon::parse($nextDueBillDate)->format('M d, Y') }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="content-card">
                    <h5>GCash Payment</h5>
                    <p class="text-muted">Submit a payment and upload proof.</p>
                    <a href="{{ url('/payments/create') }}" class="btn btn-primary">Create Payment</a>
                </div>
            </div>
        </div>

        <div class="content-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Recent Payments</h5>
                <a href="{{ url('/payments') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPayments as $payment)
                            <tr>
                                <td>#{{ $payment->id }}</td>
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
                                <td colspan="4" class="text-center text-muted">No payments found.</td>
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
