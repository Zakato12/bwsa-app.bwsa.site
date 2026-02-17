@extends('layouts.main')

@section('title', 'Official Dashboard')

@section('content')
<div class="dashboard-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title mb-1">Official Dashboard</h2>
                @if(session('usr_barangay'))
                    <div class="fw-semibold text-primary">Barangay: {{ session('usr_barangay') }}</div>
                @endif
            </div>
            <span class="text-muted">Welcome back, {{ session('usr_name') }}</span>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stat-card card-blue">
                    <div class="stat-icon"><i class="fas fa-user-friends"></i></div>
                    <div class="stat-data">
                        <h3>{{ number_format($residentCount) }}</h3>
                        <p>Residents in Barangay</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card card-green">
                    <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="stat-data">
                        <h3>{{ number_format($paymentCount) }}</h3>
                        <p>Payments in Barangay</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Residents (CRUD)</h5>
                        <div class="d-flex gap-2">
                            <a href="{{ url('/residents') }}" class="btn btn-sm btn-outline-primary">View All</a>
                            <a href="{{ url('/residents/create') }}" class="btn btn-sm btn-primary">Add Resident</a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentResidents as $resident)
                                    <tr>
                                        <td>{{ $resident->full_name }}</td>
                                        <td>{{ $resident->username }}</td>
                                        <td>{{ $resident->created_at }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No residents found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Payments (List Only)</h5>
                        <a href="{{ url('/payments') }}" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Amount</th>
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
                                            @if($payment->status == 0)
                                                Bill
                                            @elseif($payment->status == 1)
                                                Pending
                                            @elseif($payment->status == 2)
                                                Verified
                                            @elseif($payment->status == 3)
                                                Approved
                                            @else
                                                Failed
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
