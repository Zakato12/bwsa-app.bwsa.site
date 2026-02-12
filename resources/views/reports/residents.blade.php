@extends('layouts.main')

@section('title', 'Resident List Report')
@section('page-title', 'Resident List Report')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-1">Resident List Report</h1>
            <p class="text-muted mb-0">Barangay: {{ $barangay->name ?? 'N/A' }}</p>
        </div>
        <button class="btn btn-outline-primary" onclick="window.print()">Print</button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Address</th>
                            <th>Registered At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($residents as $resident)
                            <tr>
                                <td>{{ $resident->id }}</td>
                                <td>{{ $resident->full_name }}</td>
                                <td>{{ $resident->username }}</td>
                                <td>{{ $resident->address }}</td>
                                <td>{{ $resident->created_at }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No residents found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
