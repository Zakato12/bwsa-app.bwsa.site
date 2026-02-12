    <div id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-water"></i> <span>BWASA</span>
            </div>
            <button id="sidebarClose" class="btn btn-link text-white d-lg-none ms-auto">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <ul class="list-unstyled components">

            <!-- Dashboard -->
            <li class="{{ request()->is('dashboard') ? 'active' : '' }}">
                <a href="{{ url('/dashboard') }}">
                    <i class="fas fa-th-large"></i> <span>Dashboard</span>
                </a>
            </li>

            @if (session('usr_role') == 'admin')
                <!-- Users with sublist -->
                    <li class="has-sub {{ request()->is('users*') ? 'open active' : '' }}">
                        <a class="sublist-toggle">
                            <i class="fas fa-users"></i> <span>Users</span>
                        </a>

                        <ul class="sublist">
                            <li class="{{ request()->is('users/add') ? 'active' : '' }}">
                                <a href="{{ url('/users/add') }}">Add User</a>
                            </li>

                            <li class="{{ request()->is('users/list') ? 'active' : '' }}">
                                <a href="{{ url('/users/list') }}">User List</a>
                            </li>
                        </ul>
                    </li>

                    <!-- Barangays -->
                    <li class="{{ request()->is('barangays*') ? 'active' : '' }}">
                        <a href="{{ url('/barangays') }}">
                            <i class="fas fa-map-marker-alt"></i> <span>Barangays</span>
                        </a>
                    </li>
            @endif

            @if (session('usr_role') == 'official')
                <!-- Residents with sublist -->
                <li class="has-sub {{ request()->is('residents*') ? 'open active' : '' }}">
                    <a class="sublist-toggle">
                        <i class="fas fa-user-friends"></i> <span>Residents</span>
                    </a>

                    <ul class="sublist">
                        <li class="{{ request()->is('residents') ? 'active' : '' }}">
                            <a href="{{ url('/residents') }}">View Residents</a>
                        </li>

                        <li class="{{ request()->is('residents/create') ? 'active' : '' }}">
                            <a href="{{ url('/residents/create') }}">Add Resident</a>
                        </li>
                    </ul>
                </li>
            @endif

            <!-- Payments -->
            @if (in_array(session('usr_role'), ['admin', 'official', 'treasurer', 'resident']))
                <li class="{{ request()->is('payments*') ? 'active' : '' }}">
                    <a href="{{ url('/payments') }}">
                        <i class="fas fa-money-bill-wave"></i> <span>Payments</span>
                    </a>
                </li>
            @endif

            @if (session('usr_role') == 'treasurer')
                <li class="{{ request()->is('payments/bill/create') ? 'active' : '' }}">
                    <a href="{{ url('/payments/bill/create') }}">
                        <i class="fas fa-file-invoice"></i> <span>Generate Bill</span>
                    </a>
                </li>
                <li class="{{ request()->is('payments/walkin/create') ? 'active' : '' }}">
                    <a href="{{ url('/payments/walkin/create') }}">
                        <i class="fas fa-cash-register"></i> <span>Walk-In Payment</span>
                    </a>
                </li>
            @endif

            @if (session('usr_role') == 'resident')
                <li class="{{ request()->is('payments/create') ? 'active' : '' }}">
                    <a href="{{ url('/payments/create') }}">
                        <i class="fas fa-receipt"></i> <span>Submit Payment</span>
                    </a>
                </li>
            @endif

            @if (in_array(session('usr_role'), ['official', 'treasurer']))
                <li class="has-sub {{ request()->is('reports*') ? 'open active' : '' }}">
                    <a class="sublist-toggle">
                        <i class="fas fa-chart-line"></i> <span>Reports</span>
                    </a>

                    <ul class="sublist">
                        @if (session('usr_role') == 'official')
                            <li class="{{ request()->is('reports/residents') ? 'active' : '' }}">
                                <a href="{{ route('reports.residents') }}">Resident List Report</a>
                            </li>
                        @endif

                        @if (session('usr_role') == 'treasurer')
                            <li class="{{ request()->is('reports/payments') ? 'active' : '' }}">
                                <a href="{{ route('reports.payments') }}">Payment Report</a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif

            @if (session('usr_role') == 'admin')
            <li class="{{ request()->is('audit-logs') ? 'active' : '' }}">
                <a href="{{ url('/audit-logs') }}">
                    <i class="fas fa-clipboard-list"></i> <span>Audit Logs</span>
                </a>
            </li>
            @endif

            <li>
                <a href="">
                    <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="link-style-button">
                        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                    </button>
                    </form>
                </a>
            </li>
        </ul>
</div>
<script>
    document.querySelectorAll('.sublist-toggle').forEach(item => {
        item.addEventListener('click', function (e) {
            this.parentElement.classList.toggle('open');
    });
});
</script>
