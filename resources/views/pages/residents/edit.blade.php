@extends('layouts.main')

@section('title', 'Edit Resident')

@section('content')
    <div>
        <h1>Edit Resident</h1>

        <form action="{{ route('residents.update', $resident->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" class="form-control" value="{{ $resident->username }}" required>
            </div>

            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" id="full_name" name="full_name" class="form-control" value="{{ $resident->full_name }}" required>
                @error('full_name')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group">
                <label for="address">Purok:</label>
                <select id="address" name="address" class="form-control" required>
                    <option value="">Select Purok</option>
                    @for($i = 1; $i <= 10; $i++)
                        <option value="Purok {{ $i }}" {{ old('address', $resident->address ?? '') === "Purok {$i}" ? 'selected' : '' }}>
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
                <input type="text" id="contact_number" name="contact_number" class="form-control" value="{{ old('contact_number', $resident->contact_number ?? '') }}" placeholder="e.g. 09XXXXXXXXX">
                @error('contact_number')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group">
                <label for="barangay_display">Barangay</label>
                <input type="text" id="barangay_display" class="form-control" value="{{ $barangays->first()->name ?? 'Assigned Barangay' }}" readonly>
            </div>
            <input type="hidden" id="barangay_id" name="barangay_id" value="{{ $barangays->first()->id ?? $resident->barangay_id }}">

            <button type="submit" class="btn btn-primary">Update Resident</button>
            <a href="{{ route('residents.index') }}" class="btn btn-secondary">Back</a>
        </form>
    </div>
@endsection
