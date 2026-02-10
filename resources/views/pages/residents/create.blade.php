@extends('layouts.main')

@section('title', 'Add Resident')

@section('content')
    <div>
        <h1>Add Resident</h1>

        <form action="{{ route('residents.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" id="full_name" name="full_name" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="address">Address (within barangay):</label>
                <input type="text" id="address" name="address" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="barangay_display">Barangay</label>
                <input type="text" id="barangay_display" class="form-control" value="{{ $barangays->first()->name ?? 'Assigned Barangay' }}" readonly>
            </div>
            <input type="hidden" id="barangay_id" name="barangay_id" value="{{ $barangays->first()->id ?? '' }}">

            <button type="submit" class="btn btn-primary">Add Resident</button>
            <a href="{{ route('residents.index') }}" class="btn btn-secondary">Back</a>
        </form>
    </div>
@endsection
