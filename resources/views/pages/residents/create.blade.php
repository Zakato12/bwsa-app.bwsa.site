@extends('layouts.main')

@section('title', 'Add Resident')

@section('content')
    <div>
        <h1>Add Resident</h1>

        <form action="{{ route('residents.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" id="full_name" name="full_name" class="form-control" value="{{ old('full_name') }}" required>
                @error('full_name')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group">
                <label for="address">Purok:</label>
                <select id="address" name="address" class="form-control" required>
                    <option value="">Select Purok</option>
                    @for($i = 1; $i <= 10; $i++)
                        <option value="Purok {{ $i }}" {{ old('address') === "Purok {$i}" ? 'selected' : '' }}>
                            Purok {{ $i }}
                        </option>
                    @endfor
                </select>
                @error('address')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group">
                <label for="contact_number">Contact Number:</label>
                <input type="text" id="contact_number" name="contact_number" class="form-control" value="{{ old('contact_number') }}" placeholder="e.g. 09XXXXXXXXX">
                @error('contact_number')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
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
