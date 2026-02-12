@extends('layouts.main')

@section('title', 'Edit Barangay')

@section('content')
    <div>
        <h1>Edit Barangay</h1>

        <form action="{{ route('barangays.update', $barangay->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">Barangay Name:</label>
                <input type="text" id="name" name="name" class="form-control" value="{{ $barangay->name }}" required>
            </div>

            <div class="form-group">
                <label for="brgy_code">Barangay Code:</label>
                <input type="text" id="brgy_code" class="form-control" value="{{ $barangay->brgy_code ?? 'N/A' }}" readonly>
                <small class="text-muted">Auto-generated code. Existing code stays unchanged.</small>
            </div>

            <div class="form-group">
                <label for="address">Address:</label>
                <input type="text" id="address" name="address" class="form-control" value="{{ $barangay->address }}" required>
            </div>

            <div class="form-group">
                <label for="status">Status:</label>
                <select id="status" name="status" class="form-control" required>
                    <option value="1" {{ $barangay->status == 1 ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ $barangay->status == 0 ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="form-group">
                <label for="payment_amount_per_bill">Payment Amount per Bill:</label>
                <input type="number" step="0.01" id="payment_amount_per_bill" name="payment_amount_per_bill" class="form-control" value="{{ $barangay->payment_amount_per_bill ?? 50.00 }}" required>
            </div>

            <button type="submit" class="btn btn-primary">Update Barangay</button>
            <a href="{{ route('barangays.index') }}" class="btn btn-secondary">Back</a>
        </form>
    </div>
@endsection
