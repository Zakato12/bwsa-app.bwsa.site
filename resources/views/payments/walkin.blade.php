@extends('layouts.main')

@section('page-title', 'Walk-In Payment')

@section('content')
<div class="container-fluid mt-4">
    <h1 class="mb-4">Record Walk-In Payment</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('payments.walkin.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="user_id" class="form-label">Resident</label>
                    <select id="user_id" name="user_id" class="form-control" required>
                        <option value="">Select Resident</option>
                        @foreach($residents as $resident)
                            <option
                                value="{{ $resident->id }}"
                                data-amount="{{ number_format((float) $resident->walkin_amount, 2, '.', '') }}"
                                data-due-date="{{ $resident->unpaid_bill_due_date ?? '' }}"
                            >
                                {{ $resident->full_name }} ({{ $resident->username }})
                            </option>
                        @endforeach
                    </select>
                    <small id="walkinDueNote" class="text-muted d-block mt-1">Select a resident to auto-fill amount.</small>
                </div>

                <div class="mb-3">
                    <label for="amount" class="form-label d-flex align-items-center gap-2">
                        <span>Amount</span>
                        <span class="badge bg-info text-dark">Auto-computed</span>
                    </label>
                    <input type="number" step="0.01" id="amount" name="amount" class="form-control" required readonly>
                </div>

                <button type="submit" class="btn btn-primary">Record Payment</button>
                <a href="{{ route('payments.index') }}" class="btn btn-secondary">Back</a>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const residentSelect = document.getElementById('user_id');
    const amountInput = document.getElementById('amount');
    const dueNote = document.getElementById('walkinDueNote');
    if (!residentSelect || !amountInput || !dueNote) {
        return;
    }

    const setAmountFromSelection = () => {
        const selectedOption = residentSelect.options[residentSelect.selectedIndex];
        const amount = selectedOption ? selectedOption.getAttribute('data-amount') : '';
        const dueDate = selectedOption ? selectedOption.getAttribute('data-due-date') : '';

        amountInput.value = amount || '';

        if (dueDate) {
            dueNote.textContent = 'Unpaid bill due date: ' + dueDate;
        } else if (residentSelect.value) {
            dueNote.textContent = 'No unpaid bill. Using default barangay amount.';
        } else {
            dueNote.textContent = 'Select a resident to auto-fill amount.';
        }
    };

    residentSelect.addEventListener('change', setAmountFromSelection);
    setAmountFromSelection();
});
</script>
@endsection
