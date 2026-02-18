@extends('layouts.main')

@section('title', 'Billing History')
@section('page-title', 'Billing History')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-1">Billing History / Log</h1>
            <p class="text-muted mb-0">Barangay: {{ $barangay->name ?? 'N/A' }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('reports.billing_history') }}" class="row g-2 mb-3">
        <div class="col-md-5">
            <input type="text" name="q" class="form-control" value="{{ $search ?? '' }}" placeholder="Search by resident, username, bill name, or ID">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-control">
                <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>All Statuses</option>
                <option value="pending" {{ ($status ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="overdue" {{ ($status ?? '') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                <option value="paid" {{ ($status ?? '') === 'paid' ? 'selected' : '' }}>Paid</option>
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary">Filter</button>
            <a href="{{ route('reports.billing_history') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="row mb-3">
        <div class="col-md-3 mb-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <small class="text-muted">Total Bills</small>
                    <h4 class="mb-0">{{ $summary['total_bills'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <small class="text-muted">Pending</small>
                    <h4 class="mb-0">{{ $summary['pending_bills'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <small class="text-muted">Overdue</small>
                    <h4 class="mb-0">{{ $summary['overdue_bills'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <small class="text-muted">Paid</small>
                    <h4 class="mb-0">{{ $summary['paid_bills'] }}</h4>
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
                            <th>Bill ID</th>
                            <th>Resident</th>
                            <th>Username</th>
                            <th>Bill Name</th>
                            <th>Base Amount</th>
                            <th>Amount Due</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Generated</th>
                            <th>Paid At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($billingLogs as $log)
                            <tr>
                                <td>#{{ $log->id }}</td>
                                <td>{{ $log->full_name }}</td>
                                <td>{{ $log->username }}</td>
                                <td>{{ $log->bill_name }}</td>
                                <td>{{ number_format($log->amount, 2) }}</td>
                                <td>{{ number_format($log->amount_due_now, 2) }}</td>
                                <td>{{ \Carbon\Carbon::parse($log->due_date)->format('M d, Y') }}</td>
                                <td>
                                    @if($log->status === 'paid')
                                        <span class="badge bg-success">Paid</span>
                                    @elseif($log->status === 'overdue')
                                        <span class="badge bg-danger">Overdue</span>
                                    @else
                                        <span class="badge bg-secondary">Pending</span>
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($log->created_at)->format('M d, Y h:i A') }}</td>
                                <td>{{ $log->paid_at ? \Carbon\Carbon::parse($log->paid_at)->format('M d, Y h:i A') : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">No billing logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $billingLogs->links() }}
    </div>
@endsection
