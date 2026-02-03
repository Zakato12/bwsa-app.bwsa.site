@extends('layouts.main')

@section('title', 'Add User')

@section('content')
    <div>
        <h1>Add User</h1>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('users.add') }}" method="POST" id="addUserForm">
            @csrf

            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" id="full_name" name="full_name" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="role_id">Role:</label>
                <select id="role_id" name="role_id" class="form-control" required>
                    @if(session('usr_role') == 'admin')
                        <option value="1">Admin</option>
                        <option value="2">Official</option>
                        <option value="3">Treasurer</option>
                    @elseif(session('usr_role') == 'official')
                        <option value="4">Resident</option>
                    @endif
                </select>
            </div>

            <div class="form-group" id="barangayGroup" style="display: none;">
                <label for="barangay_id">Barangay:</label>
                <select id="barangay_id" name="barangay_id" class="form-control">
                    @foreach(\DB::table('barangays')->where('status', 1)->get() as $barangay)
                        <option value="{{ $barangay->id }}">{{ $barangay->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="status">Status:</label>
                <select id="status" name="status" class="form-control" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Add User</button>
        </form>
    </div>

    <script>
        document.getElementById('role_id').addEventListener('change', function() {
            var barangayGroup = document.getElementById('barangayGroup');
            var barangaySelect = document.getElementById('barangay_id');
            if (this.value == '2' || this.value == '3' || this.value == '4') {
                barangayGroup.style.display = 'block';
                barangaySelect.required = true;
            } else {
                barangayGroup.style.display = 'none';
                barangaySelect.required = false;
            }
        });
    </script>
@endsection