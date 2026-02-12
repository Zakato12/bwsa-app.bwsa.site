@extends('layouts.main')

@section('title', 'Payment Report')
@section('page-title', 'Payment Report')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-1">Payment Report</h1>
            <p class="text-muted mb-0">Barangay: {{ $barangay->name ?? 'N/A' }}</p>
        </div>
        <button class="btn btn-outline-primary" onclick="window.print()">Print</button>
    </div>

    <div class="row mb-3">
        <div class="col-md-3 mb-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <small class="text-muted">Transactions</small>
                    <h4 class="mb-0">{{ $summary['total_transactions'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <small class="text-muted">Total Amount</small>
                    <h4 class="mb-0">{{ number_format($summary['total_amount'], 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <small class="text-muted">Approved</small>
                    <h4 class="mb-0">{{ $summary['approved_count'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <small class="text-muted">Approved Amount</small>
                    <h4 class="mb-0">{{ number_format($summary['approved_amount'], 2) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Resident</th>
                            <th>Username</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr>
                                <td>#{{ $payment->id }}</td>
                                <td>{{ $payment->full_name }}</td>
                                <td>{{ $payment->username }}</td>
                                <td>{{ number_format($payment->amount, 2) }}</td>
                                <td>
                                    @if ($payment->payment_method == 0)
                                        Bill
                                    @elseif ($payment->payment_method == 1)
                                        Cash
                                    @else
                                        GCash
                                    @endif
                                </td>
                                <td>
                                    @if ($payment->status == 0)
                                        Bill
                                    @elseif ($payment->status == 1)
                                        Pending
                                    @elseif ($payment->status == 2)
                                        Verified
                                    @elseif ($payment->status == 3)
                                        Approved
                                    @else
                                        Unknown
                                    @endif
                                </td>
                                <td>{{ $payment->created_at }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No payments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
