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
                            <option value="{{ $resident->id }}">{{ $resident->full_name }} ({{ $resident->username }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="amount" class="form-label">Amount</label>
                    <input type="number" step="0.01" id="amount" name="amount" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary">Record Payment</button>
                <a href="{{ route('payments.index') }}" class="btn btn-secondary">Back</a>
            </form>
        </div>
    </div>
</div>
@endsection
