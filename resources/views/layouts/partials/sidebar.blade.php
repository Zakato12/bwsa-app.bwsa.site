    <div id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-water"></i> <span>BWASA</span>
            </div>
        </div>
        <ul class="list-unstyled components">

            <!-- Dashboard -->
            <li class="{{ request()->is('dashboard') ? 'active' : '' }}">
                <a href="{{ url('/dashboard') }}">
                    <i class="fas fa-th-large"></i> <span>Dashboard</span>
                </a>
            </li>

            @if (session('usr_id') === 1)
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
            @endif

            <!-- Reports -->
            <li class="{{ request()->is('reports*') ? 'active' : '' }}">
                <a href="{{ url('/reports') }}">
                    <i class="fas fa-chart-line"></i> <span>Reports</span>
                </a>
            </li>

            <li><a href="">
                <i class="fas fa-file-alt"></i> <span>Documents</span>
            </a></li>
            <li><a href="">
                <i class="fas fa-question-circle"></i> <span>Help</span>
            </a></li>
            <li><a href="">
                <i class="fas fa-envelope"></i> <span>Contact</span>
            </a></li>
            <li><a href="">
                <i class="fas fa-info-circle"></i> <span>About</span>
            </a></li>
            <li><a href="">
                <i class="fas fa-cog"></i> <span>Settings</span>
            </a></li>
        </ul>

        <div class="sidebar-footer">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="link-style-button">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </button>
            </form>
        </div>
</div>
<script>
    document.querySelectorAll('.sublist-toggle').forEach(item => {
        item.addEventListener('click', function (e) {
            this.parentElement.classList.toggle('open');
    });
});
</script>
