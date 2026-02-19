@extends('layouts.main')

@section('page-title', 'Submit Payment')

@section('content')
<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>{{ $bill ? 'Pay Bill' : 'Submit Payment' }}</h5>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            Please fix the highlighted field errors before submitting.
                        </div>
                    @endif

                    <form id="gcashPaymentForm" action="{{ route('payments.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="submission_token" value="{{ $submissionToken ?? '' }}">
                        @if($bill)
                            <input type="hidden" name="bill_id" value="{{ $bill->id }}">
                            <div class="mb-3">
                                <label class="form-label">Bill Name</label>
                                <input type="text" class="form-control" value="{{ $bill->bill_name }}" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Due Date</label>
                                <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($bill->due_date)->format('M d, Y') }}" readonly>
                            </div>
                        @endif
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount</label>
                            <input
                                type="number"
                                step="0.01"
                                class="form-control"
                                name="amount"
                                value="{{ $bill ? ($bill->amount_due ?? $bill->amount) : '' }}"
                                min="{{ $bill ? ($bill->amount_due ?? $bill->amount) : '0.01' }}"
                                required
                            >
                            @if($bill && ($bill->amount_due ?? $bill->amount) > $bill->amount)
                                <small class="text-danger">Overdue penalty applied (2x of base amount).</small>
                            @endif
                            @if($bill)
                                <small class="text-muted d-block mt-1">
                                    You may enter a higher amount to advance future monthly bills.
                                </small>
                            @endif
                        </div>
                        <input type="hidden" name="payment_method" value="2">
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <input type="text" class="form-control" value="GCash" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="receipt" class="form-label">Receipt</label>
                            <input
                                type="file"
                                class="form-control @error('receipt') is-invalid @enderror"
                                name="receipt"
                                accept="image/jpeg,image/png"
                                required
                            >
                            <small class="text-muted d-block mt-1">Accepted files: JPG, JPEG, PNG (max 2MB).</small>
                            @error('receipt')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary" id="gcashSubmitBtn">{{ $bill ? 'Pay Bill' : 'Submit Payment' }}</button>
                        <a href="{{ route('payments.index') }}" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('gcashPaymentForm');
    const submitBtn = document.getElementById('gcashSubmitBtn');
    if (!form || !submitBtn) {
        return;
    }

    form.addEventListener('submit', function () {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';
    });
});
</script>
@endsection
