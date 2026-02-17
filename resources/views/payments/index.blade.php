@extends('layouts.main')

@section('page-title', 'Payments')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>{{ in_array(session('usr_role'), ['admin', 'official', 'treasurer']) ? 'Payments Management' : 'My Bills & Payments' }}</h1>
        @if(session('usr_role') == 'treasurer')
            <a href="{{ route('payments.createBill') }}" class="btn btn-success">Generate Bill</a>
        @elseif(!in_array(session('usr_role'), ['admin', 'official', 'treasurer']))
            <a href="{{ route('payments.create') }}" class="btn btn-primary">Submit Payment</a>
        @endif
    </div>

    <form method="GET" action="{{ route('payments.index') }}" class="row g-2 mb-3">
        <div class="col-sm-8 col-md-5">
            <input type="text" name="q" class="form-control" placeholder="Search by ID, user, or amount" value="{{ $search ?? '' }}">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-primary">Search</button>
        </div>
        <div class="col-auto">
            <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    @if(in_array(session('usr_role'), ['admin', 'official', 'treasurer']))
        <div class="mb-4">
            <h3>Unpaid List</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th><a href="{{ request()->fullUrlWithQuery(['sort_by' => 'amount', 'sort_order' => $sortOrder == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">Amount {{ $sortBy == 'amount' ? ($sortOrder == 'asc' ? '^' : 'v') : '' }}</a></th>
                            <th>Method</th>
                            <th><a href="{{ request()->fullUrlWithQuery(['sort_by' => 'status', 'sort_order' => $sortOrder == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">Status {{ $sortBy == 'status' ? ($sortOrder == 'asc' ? '^' : 'v') : '' }}</a></th>
                            <th><a href="{{ request()->fullUrlWithQuery(['sort_by' => 'due_date', 'sort_order' => $sortOrder == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">Due Date {{ $sortBy == 'due_date' ? ($sortOrder == 'asc' ? '^' : 'v') : '' }}</a></th>
                            <th>Receipt</th>
                            <th><a href="{{ request()->fullUrlWithQuery(['sort_by' => 'created_at', 'sort_order' => $sortOrder == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">Date {{ $sortBy == 'created_at' ? ($sortOrder == 'asc' ? '^' : 'v') : '' }}</a></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($unpaidRecords as $p)
                            <tr>
                                <td>{{ $p->id }}</td>
                                <td>{{ $p->full_name ?? $p->user_name }}</td>
                                <td>{{ number_format($p->amount, 2) }}</td>
                                <td>{{ $p->payment_method == 0 ? 'Bill' : ($p->payment_method == 1 ? 'Cash' : 'GCash') }}</td>
                                <td>
                                    @if($p->status == -1)
                                        <span class="badge bg-danger">Overdue Bill</span>
                                    @elseif($p->status == 0)
                                        <span class="badge bg-secondary">Bill Generated</span>
                                    @elseif($p->status == 1)
                                        <span class="badge bg-warning">Pending</span>
                                    @elseif($p->status == 2)
                                        <span class="badge bg-info">Verified</span>
                                    @else
                                        <span class="badge bg-danger">Failed</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($p->due_date))
                                        {{ \Carbon\Carbon::parse($p->due_date)->format('M d, Y') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($p->receipt_image_path)
                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary btn-sm js-receipt-preview"
                                            data-bs-toggle="modal"
                                            data-bs-target="#receiptPreviewModal"
                                            data-receipt-url="{{ route('payments.receipt', $p->id) }}"
                                            data-download-url="{{ route('payments.receipt', $p->id) }}?download=1"
                                        >
                                            View
                                        </button>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($p->created_at)->format('M d, Y') }}</td>
                                <td>
                                    @if(session('usr_role') == 'treasurer')
                                        @if(($p->row_type ?? 'payment') === 'payment' && $p->status == 1 && $p->payment_method == 2)
                                            <form action="{{ route('payments.verify', $p->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm">Verify</button>
                                            </form>
                                        @endif
                                        @if(($p->row_type ?? 'payment') === 'payment' && $p->status == 2)
                                            <form action="{{ route('payments.approve', $p->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-primary btn-sm">Approve</button>
                                            </form>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">No unpaid records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-2">
                {{ $unpaidRecords->links() }}
            </div>
        </div>

        <div class="mb-4">
            <h3>Paid List</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th><a href="{{ request()->fullUrlWithQuery(['sort_by' => 'amount', 'sort_order' => $sortOrder == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">Amount {{ $sortBy == 'amount' ? ($sortOrder == 'asc' ? '^' : 'v') : '' }}</a></th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Receipt</th>
                            <th><a href="{{ request()->fullUrlWithQuery(['sort_by' => 'created_at', 'sort_order' => $sortOrder == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">Date {{ $sortBy == 'created_at' ? ($sortOrder == 'asc' ? '^' : 'v') : '' }}</a></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paidRecords as $p)
                            <tr>
                                <td>{{ $p->id }}</td>
                                <td>{{ $p->full_name ?? $p->user_name }}</td>
                                <td>{{ number_format($p->amount, 2) }}</td>
                                <td>{{ $p->payment_method == 1 ? 'Cash' : 'GCash' }}</td>
                                <td><span class="badge bg-success">Approved</span></td>
                                <td>
                                    @if($p->receipt_image_path)
                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary btn-sm js-receipt-preview"
                                            data-bs-toggle="modal"
                                            data-bs-target="#receiptPreviewModal"
                                            data-receipt-url="{{ route('payments.receipt', $p->id) }}"
                                            data-download-url="{{ route('payments.receipt', $p->id) }}?download=1"
                                        >
                                            View
                                        </button>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($p->created_at)->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No paid records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-2">
                {{ $paidRecords->links() }}
            </div>
        </div>
    @else
        <!-- Resident View: Bills and Payment History -->
        <div class="row">
            <div class="col-md-6">
                <h3>My Bills</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Bill</th>
                                <th>Base Amount</th>
                                <th>Amount Due</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Upcoming</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bills as $bill)
                            <tr>
                                <td>{{ $bill->id }}</td>
                                <td>{{ $bill->bill_name }}</td>
                                <td>{{ number_format($bill->amount, 2) }}</td>
                                <td>{{ number_format($bill->amount_due ?? $bill->amount, 2) }}</td>
                                <td>{{ \Carbon\Carbon::parse($bill->due_date)->format('M d, Y') }}</td>
                                <td>
                                    @if($bill->status === 'overdue')
                                        <span class="badge bg-danger">Overdue</span>
                                    @else
                                        <span class="badge bg-secondary">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    @if((int) ($bill->is_upcoming_due ?? 0) === 1)
                                        <span class="badge bg-warning text-dark">Due Soon</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('payments.create') }}?bill_id={{ $bill->id }}" class="btn btn-primary btn-sm">Pay Bill</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center">No bills found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-2">
                    {{ $bills->links() }}
                </div>
            </div>
            <div class="col-md-6">
                <h3>Payment History</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $p)
                            <tr>
                                <td>{{ $p->id }}</td>
                                <td>{{ number_format($p->amount, 2) }}</td>
                                <td>{{ $p->payment_method == 1 ? 'Cash' : 'GCash' }}</td>
                                <td>
                                    @if($p->status == 1)
                                        <span class="badge bg-warning">Pending</span>
                                    @elseif($p->status == 2)
                                        <span class="badge bg-info">Verified</span>
                                    @elseif($p->status == 3)
                                        <span class="badge bg-success">Approved</span>
                                    @else
                                        <span class="badge bg-danger">Failed</span>
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($p->created_at)->format('M d, Y') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">No payments found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-2">
                    {{ $payments->links() }}
                </div>
            </div>
        </div>
    @endif
</div>

@if(in_array(session('usr_role'), ['admin', 'official', 'treasurer']))
<div class="modal fade" id="receiptPreviewModal" tabindex="-1" aria-labelledby="receiptPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="receiptPreviewModalLabel">Receipt Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="receiptPreviewImage" src="" alt="Receipt preview" class="img-fluid rounded border" style="max-height: 70vh; object-fit: contain;">
            </div>
            <div class="modal-footer">
                <a id="receiptDownloadLink" href="#" class="btn btn-secondary">Download</a>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('receiptPreviewModal');
    const previewImage = document.getElementById('receiptPreviewImage');
    const downloadLink = document.getElementById('receiptDownloadLink');
    if (!modal || !previewImage || !downloadLink) {
        return;
    }

    modal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        if (!trigger) {
            return;
        }

        const receiptUrl = trigger.getAttribute('data-receipt-url');
        const receiptDownloadUrl = trigger.getAttribute('data-download-url');

        previewImage.src = receiptUrl || '';
        downloadLink.href = receiptDownloadUrl || '#';
    });

    modal.addEventListener('hidden.bs.modal', function () {
        previewImage.src = '';
    });
});
</script>
@endif
@endsection
