@extends('layouts.main') @section('title', 'Admin Dashboard')
@section('content')
<div class="dashboard-wrapper">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="page-title">Admin Command Center</h2>
            <span class="text-muted">Welcome back, {{ session('usr_name') }}</span>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card card-blue">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-data">
                        <h3>1,240</h3>
                        <p>Total Residents</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card-green">
                    <div class="stat-icon"><i class="fas fa-hand-holding-usd"></i></div>
                    <div class="stat-data">
                        <h3>₱45,200</h3>
                        <p>Monthly Collection</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card-orange">
                    <div class="stat-icon"><i class="fas fa-tools"></i></div>
                    <div class="stat-data">
                        <h3>12</h3>
                        <p>Pending Repairs</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card-red">
                    <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-data">
                        <h3>8</h3>
                        <p>Overdue Accounts</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-8">
                <div class="content-card">
                    <h5>Revenue vs Consumption (6 Months)</h5>
                    <div class="chart-placeholder">
                        <canvas id="revenueChart" style="height: 300px;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="content-card">
                    <h5>Recent System Logs</h5>
                    <ul class="activity-list">
                        <li><span class="text-primary">[Official]</span> Approved new connection</li>
                        <li><span class="text-success">[Treasurer]</span> Recorded payment #882</li>
                        <li><span class="text-danger">[Admin]</span> Changed Water Rate to ₱12.00</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Recent Resident Activity</h5>
                <a href="{{ url('/users/list') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table custom-table">
                    <thead>
                        <tr>
                            <th>Account ID</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Last Bill</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#BW-1092</td>
                            <td>John Doe</td>
                            <td><span class="badge badge-success">Active</span></td>
                            <td>₱350.00</td>
                            <td><button class="btn btn-sm btn-light"><i class="fas fa-edit"></i></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<style>
    /* Custom Stat Cards */
.stat-card {
    padding: 20px;
    border-radius: 12px;
    color: white;
    display: flex;
    align-items: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.card-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
.card-green { background: linear-gradient(135deg, #10b981, #059669); }
.card-orange { background: linear-gradient(135deg, #f59e0b, #d97706); }
.card-red { background: linear-gradient(135deg, #ef4444, #dc2626); }

.stat-icon {
    font-size: 2.5rem;
    opacity: 0.3;
    margin-right: 15px;
}

.stat-data h3 {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0;
}

.stat-data p {
    margin: 0;
    font-size: 0.9rem;
    opacity: 0.9;
}

/* Content Area Cards */
.content-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    height: 100%;
}

.activity-list {
    list-style: none;
    padding: 0;
}

.activity-list li {
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
    font-size: 0.85rem;
}

/* Table Styling */
.custom-table thead {
    background-color: #f8f9fa;
}

.badge-success { background-color: #d1fae5; color: #065f46; padding: 5px 10px; border-radius: 20px; font-size: 12px; }
</style>
@endsection