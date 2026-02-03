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