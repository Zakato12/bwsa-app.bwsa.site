@extends('layouts.main')

@section('title', 'Residents')

@section('content')
    <div>
        <h1>Residents</h1>

        @if(in_array(session('usr_role'), ['admin', 'official']))
            <a href="{{ route('residents.create') }}" class="btn btn-primary mb-3">Add Resident</a>
        @endif

        <form method="GET" action="{{ route('residents.index') }}" class="row g-2 mb-3">
            <div class="col-sm-8 col-md-5">
                <input type="text" name="q" class="form-control" placeholder="Search residents" value="{{ $search ?? '' }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">Search</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('residents.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Address</th>
                    <th>Created At</th>
                    @if(in_array(session('usr_role'), ['admin', 'official']))
                        <th>Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($residents as $resident)
                <tr>
                    <td>{{ $resident->id }}</td>
                    <td>{{ $resident->full_name }}</td>
                    <td>{{ $resident->username }}</td>
                    <td>{{ $resident->address }}</td>
                    <td>{{ $resident->created_at }}</td>
                    @if(in_array(session('usr_role'), ['admin', 'official']))
                        <td>
                            <a href="{{ route('residents.edit', $resident->id) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('residents.destroy', $resident->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-2">
            {{ $residents->links() }}
        </div>
    </div>
@endsection
