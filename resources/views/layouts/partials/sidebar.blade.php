    <nav id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-water"></i> <span>BWASA</span>
            </div>
        </div>

        <ul class="list-unstyled components">
            <li class="{{ request()->is('dashboard') ? 'active' : '' }}">
                <a href="{{ url('/dashboard') }}">
                    <i class="fas fa-th-large"></i> <span>Dashboard</span>
                </a>
            </li>

            <li class="{{ request()->is('users*') ? 'active' : '' }}">
                <a href="{{ url('/users') }}">
                    <i class="fas fa-users"></i> <span>Users</span>
                </a>
            </li>

            <li class="{{ request()->is('reports*') ? 'active' : '' }}">
                <a href="{{ url('/reports') }}">
                    <i class="fas fa-chart-line"></i> <span>Reports</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <a href="{{ url('/logout') }}" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        </div>
    </nav>