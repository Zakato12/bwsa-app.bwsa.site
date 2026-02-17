@extends('layouts.main')

@section('page-title', 'Generate Bill')

@section('content')
<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Generate Barangay-Wide Bill</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('payments.storeBill') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Barangay</label>
                            <input type="text" class="form-control" value="{{ $barangay->name ?? 'Assigned Barangay' }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Residents to Bill</label>
                            <input type="text" class="form-control" value="{{ $residentCount }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="bill_name" class="form-label">Bill Name</label>
                            <input
                                type="text"
                                name="bill_name"
                                id="bill_name"
                                class="form-control"
                                maxlength="150"
                                value="{{ old('bill_name') }}"
                                placeholder="e.g. Monthly Water Bill"
                                required
                            >
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount</label>
                            <input
                                type="number"
                                name="amount"
                                id="amount"
                                class="form-control"
                                step="0.01"
                                min="0.01"
                                value="{{ old('amount', $barangay->payment_amount_per_bill ?? '') }}"
                                required
                            >
                        </div>
                        <div class="mb-3">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input
                                type="date"
                                name="due_date"
                                id="due_date"
                                class="form-control"
                                value="{{ old('due_date') }}"
                                required
                            >
                        </div>
                        <button type="submit" class="btn btn-primary">Generate Bills for All Residents</button>
                        <a href="{{ route('payments.index') }}" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
