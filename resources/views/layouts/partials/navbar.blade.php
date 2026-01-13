@include('layouts.partials.head')
<nav class="navbar navbar-custom navbar-expand-lg">
    <div class="container-fluid">
        <!-- Toggle button -->
        <button class="btn btn-dark shadow-none text-white me-3" id="sidebarToggle">
            ☰
        </button>

        <!-- Dynamic Page Title -->
        <span class="navbar-brand mb-0 h1 text-dark fw-semibold d-none d-md-inline">
            @yield('page-title', 'Dashboard')
        </span>

        <!-- User dropdown -->
        <div class="ms-auto d-flex align-items-center">
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none text-dark dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa fa-user" style="font-size: 1.6rem;"></i>
                        <span class="d-none d-sm-inline mx-2 fw-medium">{{ session('usr_name') ?? 'User' }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="dropdownUser">
                   <li>
                        <button type="button" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="fa fa-pencil me-2 text-muted"></i> 
                                <span>Change Password</span>
                        </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger d-flex align-items-center" href="{{ url('/logout') }}">
                            <i class="fa fa-sign-out me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>