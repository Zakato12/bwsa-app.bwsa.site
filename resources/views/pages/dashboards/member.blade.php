@extends('layouts.main')

@section('title', 'Resident Dashboard')

@section('content')
<div class="dashboard-wrapper">
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="page-title">Resident Dashboard</h2>
            <span class="text-muted">Welcome back, {{ session('usr_name') }}</span>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Edit Profile</h5>
                    </div>
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if(session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <form action="{{ route('change-password') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="currpassword">Current Password:</label>
                                <input type="password" id="currpassword" name="currpassword" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="newpassword">New Password:</label>
                                <input type="password" id="newpassword" name="newpassword" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="confirmpassword">Confirm New Password:</label>
                                <input type="password" id="confirmpassword" name="confirmpassword" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
