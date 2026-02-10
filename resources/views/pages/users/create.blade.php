@extends('layouts.main')

@section('title', 'Add User')

@section('content')
    <div>
        <h1>Add User</h1>

        <form action="{{ route('users.add') }}" method="POST" id="addUserForm">
            @csrf

            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" class="form-control">
                <small class="text-muted">Auto-generated for officials, treasurers, and residents.</small>
            </div>

            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" id="full_name" name="full_name" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" class="form-control">
                <small class="text-muted">Auto-generated for officials, treasurers, and residents.</small>
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
        function toggleAutoCredentials() {
            var barangayGroup = document.getElementById('barangayGroup');
            var barangaySelect = document.getElementById('barangay_id');
            var username = document.getElementById('username');
            var password = document.getElementById('password');

            if (role_id.value == '2' || role_id.value == '3' || role_id.value == '4') {
                barangayGroup.style.display = 'block';
                barangaySelect.required = true;
                username.value = '';
                password.value = '';
                username.disabled = true;
                password.disabled = true;
            } else {
                barangayGroup.style.display = 'none';
                barangaySelect.required = false;
                username.disabled = false;
                password.disabled = false;
            }
        }

        var role_id = document.getElementById('role_id');
        role_id.addEventListener('change', toggleAutoCredentials);
        toggleAutoCredentials();
    </script>
@endsection
