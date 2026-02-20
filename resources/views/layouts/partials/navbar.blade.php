@php
    $displayBarangay = session('usr_barangay');

    if (!$displayBarangay && session('usr_id')) {
        $displayBarangay = \Illuminate\Support\Facades\DB::table('users')
            ->leftJoin('barangays', 'users.barangay_id', '=', 'barangays.id')
            ->where('users.id', session('usr_id'))
            ->value('barangays.name');

        if (!$displayBarangay) {
            $displayBarangay = \Illuminate\Support\Facades\DB::table('residents')
                ->join('barangays', 'residents.barangay_id', '=', 'barangays.id')
                ->where('residents.user_id', session('usr_id'))
                ->value('barangays.name');
        }
    }
@endphp
<nav class="navbar navbar-custom navbar-expand-lg">
    <div class="container-fluid">
        <!-- Toggle button -->
        <button class="btn btn-dark shadow-none text-white me-3 d-lg-none" id="sidebarToggle">
            &#9776;
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
                        @if($displayBarangay)
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-none d-md-inline">{{ $displayBarangay }}</span>
                        @endif
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="dropdownUser">
                   @if($displayBarangay)
                    <li>
                        <span class="dropdown-item-text text-muted small">Barangay: {{ $displayBarangay }}</span>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                   @endif
                   <li>
                        <button type="button" class="dropdown-item d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="fa fa-pencil me-2 text-muted"></i> 
                                <span>Change Password</span>
                        </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item d-flex align-items-center text-danger w-100">
                            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                        </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

