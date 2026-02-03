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
                    <form action="{{ route('payments.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @if($bill)
                            <input type="hidden" name="bill_id" value="{{ $bill->id }}">
                        @endif
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount</label>
                            <input type="number" step="0.01" class="form-control" name="amount" value="{{ $bill ? $bill->amount : '' }}" required {{ $bill ? 'readonly' : '' }}>
                        </div>
                        <div class="mb-3">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select class="form-select" name="payment_method" required>
                                <option value="1">Cash</option>
                                <option value="2">GCash</option>
                            </select>
                        </div>
                        <div class="mb-3" id="receipt" style="display:none;">
                            <label for="receipt" class="form-label">Receipt</label>
                            <input type="file" class="form-control" name="receipt" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary">{{ $bill ? 'Pay Bill' : 'Submit Payment' }}</button>
                        <a href="{{ route('payments.index') }}" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelector('[name=payment_method]').addEventListener('change', function() {
    document.getElementById('receipt').style.display = this.value == '2' ? 'block' : 'none';
});
</script>
@endsection