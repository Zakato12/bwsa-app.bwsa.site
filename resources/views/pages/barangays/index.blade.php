@extends('layouts.main')

@section('title', 'Barangays')

@section('content')
    <div>
        <h1>Barangays</h1>

        <a href="{{ route('barangays.create') }}" class="btn btn-primary mb-3">Add Barangay</a>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Address</th>
                    <th>Residents</th>
                    <th>Status</th>
                    <th>Payment Amount per Bill</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($barangays as $barangay)
                <tr>
                    <td>{{ $barangay->id }}</td>
                    <td>{{ $barangay->name }}</td>
                    <td>{{ $barangay->brgy_code ?? 'N/A' }}</td>
                    <td>{{ $barangay->address }}</td>
                    <td>{{ $barangay->resident_count }}</td>
                    <td>{{ $barangay->status == 1 ? 'Active' : 'Inactive' }}</td>
                    <td>{{ $barangay->payment_amount_per_bill ?? 'N/A' }}</td>
                    <td>{{ $barangay->created_at }}</td>
                    <td>{{ $barangay->updated_at }}</td>
                    <td>
                        <a href="{{ route('barangays.edit', $barangay->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('barangays.destroy', $barangay->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
