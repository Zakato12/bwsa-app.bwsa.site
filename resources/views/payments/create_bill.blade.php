@extends('layouts.main')

@section('page-title', 'Generate Bill')

@section('content')
<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Generate New Bill</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('payments.storeBill') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Resident</label>
                            <select name="user_id" id="user_id" class="form-select" required>
                                <option value="">Select Resident</option>
                                @foreach($residents as $resident)
                                    <option value="{{ $resident->id }}">{{ $resident->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount</label>
                            <input type="number" name="amount" id="amount" class="form-control" step="0.01" min="0.01" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Generate Bill</button>
                        <a href="{{ route('payments.index') }}" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection