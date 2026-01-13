@extends('layouts.main')

@section('title', 'Dashboard')

@section('content')
    <h2>Dashboard</h2>
    <p>Welcome to the system.</p>

    <main>
        <div class="container-fluid mb-4">
            <div class="row">
                <div class="col-6">
                    <div class="form-group d-flex align-items-center">
                        <label for="barangay" class="fw-semibold text-nowrap me-2">BARANGAY:</label>
                        <select name="barangay" id="barangay" class="form-select">
                            <option value="AMAS">AMAS</option>
                        </select>
                    </div> 
                </div>
            </div>  
        </div>

        <div class="container mx-auto">
            <div class="row mb-4">
                <div class="col mb-2">
                    <div class="card border-success">
                        <a href="" class="btn card-body">
                            <div class="d-flex justify-content-center align-items-center">
                                <div>
                                    <h6 class=" card-title">Active Residents</h6>
                                    <span class="badge bg-success">
                                        <h6>45</h6>
                                    </span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="col mb-2">
                    <div class="card border-success">
                        <a href="" class="btn card-body">
                            <div class="d-flex justify-content-center align-items-center">
                                <div>
                                    <h6 class=" card-title">Total Collected Payments</h6>
                                    <span class="badge bg-success">
                                        <h6>PHP 12,000</h6>
                                    </span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="col mb-2">
                    <div class="card border-warning">
                        <a href="" class="btn card-body">
                            <div class="d-flex justify-content-center align-items-center">
                                <div>
                                    <h6 class=" card-title">Total Unpaid Accounts</h6>
                                    <span class="badge bg-warning">
                                        <h6>45</h6>
                                    </span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
    </main>
@endsection
