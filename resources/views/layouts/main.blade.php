<!DOCTYPE html>
<html lang="en">
    <head>
        @include('layouts.partials.head')
        <title>@yield('title','BWASA System')</title>
    </head>
    <body>
        <div id="wrapper">
            <!-- Modal -->
             @include('layouts.partials.modals')

            {{-- Sidebar --}}
            @include('layouts.partials.sidebar')

            {{-- Sidebar Overlay --}}
            <div id="sidebar-overlay"></div>

            {{-- Page Content --}}
            <div id="content">

                {{-- Navbar --}}
                @include('layouts.partials.navbar')

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

                @if($displayBarangay)
                    <div class="container-fluid mt-3">
                        <div class="alert alert-primary d-flex align-items-center py-2 px-3 mb-0" role="alert">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <strong class="me-2">Barangay:</strong>
                            <span>{{ $displayBarangay }}</span>
                        </div>
                    </div>
                @endif

                {{-- Main Content --}}
                <main class="container-fluid mt-4">
                    @yield('content')
                </main>

                {{-- Footer --}}
                @include('layouts.partials.footer')

            </div>
        </div>

    </body>
    @include('layouts.partials.scripts')
</html>
