@extends('layouts.main')

@section('title', 'Edit Resident')

@section('content')
    <div>
        <h1>Edit Resident</h1>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

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
            </div>

            <div class="form-group">
                <label for="barangay_id">Barangay:</label>
                <select id="barangay_id" name="barangay_id" class="form-control" required>
                    <option value="">Select Barangay</option>
                    @foreach($barangays as $barangay)
                        <option value="{{ $barangay->id }}" {{ $barangay->id == $resident->barangay_id ? 'selected' : '' }}>{{ $barangay->name }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Update Resident</button>
            <a href="{{ route('residents.index') }}" class="btn btn-secondary">Back</a>
        </form>
    </div>
@endsection