<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon"
          href="{{\Illuminate\Support\Facades\Storage::disk('public')->exists('favicon.ico') ? asset('storage/favicon.ico') : asset('favicon.ico')}}"
          type="image/x-icon">

    {{--    <link rel="stylesheet" href="{{asset('css/adminlte.min.css')}}">--}}
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.24/datatables.min.css"/>

    {{--  summernote --}}
    <link rel="stylesheet" href="{{asset('plugins/summernote/summernote-bs4.min.css')}}">

    {{--  datetimepicker --}}
    <link rel="stylesheet" href="{{asset('plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css')}}">

    {{-- select2 --}}
    <link rel="stylesheet" href="{{asset('plugins/select2/css/select2.min.css')}}">

    <link rel="stylesheet" href="{{asset('css/app.css')}}">
    <link rel="preload" href="{{asset('plugins/fontawesome-free/css/all.min.css')}}" as="style"
          onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="{{asset('plugins/fontawesome-free/css/all.min.css')}}">
    </noscript>
</head>
<body class="sidebar-mini layout-fixed dark-mode" style="height: auto;">
<div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header sticky-top navbar navbar-expand navbar-dark navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="http://home.vagonbrei.eu" class="nav-link">Home</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="https://panel.vagonbrei.eu" class="nav-link">Panel</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="https://phpmyadmin.vagonbrei.eu" class="nav-link">PhpMyAdmin</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{env('DISCORD_INVITE_URL')}}" class="nav-link" target="__blank">Discord</a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <!-- Notifications Dropdown Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-bell"></i>
                    @if(Auth::user()->unreadNotifications->count() != 0)
                        <span
                            class="badge badge-warning navbar-badge">{{Auth::user()->unreadNotifications->count()}}</span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-item dropdown-header">{{Auth::user()->unreadNotifications->count()}} Notifikací</span>
                    <div class="dropdown-divider"></div>

                    @foreach(Auth::user()->unreadNotifications->sortBy('created_at')->take(5) as $notification)
                        <a href="{{route('notifications.show' , $notification->id)}}" class="dropdown-item">
                            <span class="d-inline-block text-truncate" style="max-width: 150px;"><i
                                    class="fas fa-envelope mr-2"></i>{{$notification->data['title']}}</span>
                            <span
                                class="float-right text-muted text-sm">{{$notification->created_at->longAbsoluteDiffForHumans()}} zpět</span>
                        </a>
                    @endforeach

                    <div class="dropdown-divider"></div>
                    <a href="{{route('notifications.index')}}" class="dropdown-item dropdown-footer">Zobrazit všechny notifikace</a>
                </div>
            </li>

            <li class="nav-item dropdown no-arrow">
                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown"
                   aria-haspopup="true"
                   aria-expanded="false">
                    <span class="mr-1 d-none d-lg-inline text-gray-600 small">
                        {{Auth::user()->name}}
                        <img width="28px" height="28px" class="rounded-circle ml-1" src="{{Auth::user()->getAvatar()}}">
                    </span>
                </a>
                <!-- Dropdown - User Information -->
                <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                    <a class="dropdown-item" href="{{route('profile.index')}}">
                        <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                        Profil
                    </a>
                    {{--                    <a class="dropdown-item" href="#">--}}
                    {{--                        <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>--}}
                    {{--                        Activity Log--}}
                    {{--                    </a>--}}
                    @if(session()->get('previousUser'))
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{route('users.logbackin')}}">
                            <i class="fas fa-sign-in-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                            Přihlásit se zpět za sebe
                        </a>
                    @endif
                    <a class="dropdown-item" data-toggle="modal" data-target="#redeemVoucherModal"
                       href="javascript:void(0)">
                        <i class="fas fa-money-check-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                        Využít voucher
                    </a>
                    <div class="dropdown-divider"></div>
                    <form method="post" action="{{route('logout')}}">
                        @csrf
                        <button class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                            <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                            Odhlásit se
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->
    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-open sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="{{route('home')}}" class="brand-link">
            <img width="64" height="64"
                 src="{{\Illuminate\Support\Facades\Storage::disk('public')->exists('icon.png') ? asset('storage/icon.png') : asset('images/bitsec.png')}}"
                 alt="{{ config('app.name', 'Laravel') }} Logo" class="brand-image img-circle" style="opacity: .8">
            <span class="brand-text font-weight-light">{{ config('app.name', 'Laravel') }}</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar" style="overflow-y: auto">

            <!-- Sidebar Menu -->
            <nav class="my-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                    data-accordion="false">
                    <!-- Add icons to the links using the .nav-icon class
                         with font-awesome or any other icon font library -->
                    <li class="nav-item">
                        <a href="{{route('home')}}" class="nav-link @if(Request::routeIs('home')) active @endif">
                            <i class="nav-icon fa fa-home"></i>
                            <p>Přehled</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{route('servers.index')}}"
                           class="nav-link @if(Request::routeIs('servers.*')) active @endif">
                            <i class="nav-icon fa fa-server"></i>
                            <p>Servery
                                <span
                                    class="badge badge-info right">{{Auth::user()->servers()->count()}} / {{Auth::user()->server_limit}}</span>
                            </p>
                        </a>
                    </li>

                    @if(env('PAYPAL_SECRET') && env('PAYPAL_CLIENT_ID') || env('APP_ENV', 'local') == 'local')
                        <li class="nav-item">
                            <a href="{{route('store.index')}}"
                               class="nav-link @if(Request::routeIs('store.*') || Request::routeIs('checkout')) active @endif">
                                <i class="nav-icon fa fa-coins"></i>
                                <p>Dokoupit kredity</p>
                            </a>
                        </li>
                    @endif

                    @if(Auth::user()->role == 'admin')
                        <li class="nav-header">Admin</li>

                        <li class="nav-item">
                            <a href="{{route('admin.users.index')}}"
                               class="nav-link @if(Request::routeIs('admin.users.*')) active @endif">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Uživatelé</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{route('admin.servers.index')}}"
                               class="nav-link @if(Request::routeIs('admin.servers.*')) active @endif">
                                <i class="nav-icon fas fa-server"></i>
                                <p>Všechny servery</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{route('admin.products.index')}}"
                               class="nav-link @if(Request::routeIs('admin.products.*')) active @endif">
                                <i class="nav-icon fas fa-sliders-h"></i>
                                <p>Balíčky</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{route('admin.store.index')}}"
                               class="nav-link @if(Request::routeIs('admin.store.*')) active @endif">
                                <i class="nav-icon fas fa-shopping-basket"></i>
                                <p>Nastavení obchodu</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{route('admin.vouchers.index')}}"
                               class="nav-link @if(Request::routeIs('admin.vouchers.*')) active @endif">
                                <i class="nav-icon fas fa-money-check-alt"></i>
                                <p>Vouchery</p>
                            </a>
                        </li>

                        <li class="nav-header">Pterodactyl</li>

                        <li class="nav-item">
                            <a href="{{route('admin.nodes.index')}}"
                               class="nav-link @if(Request::routeIs('admin.nodes.*')) active @endif">
                                <i class="nav-icon fas fa-sitemap"></i>
                                <p>Nodes</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{route('admin.nests.index')}}"
                               class="nav-link @if(Request::routeIs('admin.nests.*')) active @endif">
                                <i class="nav-icon fas fa-th-large"></i>
                                <p>Nests</p>
                            </a>
                        </li>

                        <li class="nav-header">Historie</li>

                        <li class="nav-item">
                            <a href="{{route('admin.payments.index')}}"
                               class="nav-link @if(Request::routeIs('admin.payments.*')) active @endif">
                                <i class="nav-icon fas fa-money-bill-wave"></i>
                                <p>Platby
                                    <span class="badge badge-success right">{{\App\Models\Payment::count()}}</span>
                                </p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{route('admin.activitylogs.index')}}"
                               class="nav-link @if(Request::routeIs('admin.activitylogs.*')) active @endif">
                                <i class="nav-icon fas fa-clipboard-list"></i>
                                <p>Historie aktivity</p>
                            </a>
                        </li>


                        <li class="nav-header">Dashboard</li>

                        <li class="nav-item">
                            <a href="{{route('admin.api.index')}}"
                               class="nav-link @if(Request::routeIs('admin.api.*')) active @endif">
                                <i class="nav-icon fa fa-gamepad"></i>
                                <p>Application API</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{route('admin.usefullinks.index')}}"
                               class="nav-link @if(Request::routeIs('admin.usefullinks.*')) active @endif">
                                <i class="nav-icon fas fa-link"></i>
                                <p>Užitečné linky</p>
                            </a>
                        </li>

                        <li class="nav-header">Nastavení</li>

                        <li class="nav-item">
                            <a href="{{route('admin.configurations.index')}}"
                               class="nav-link @if(Request::routeIs('admin.configurations.*')) active @endif">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>Konfigurace</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{route('admin.settings.index')}}"
                               class="nav-link @if(Request::routeIs('admin.settings.*')) active @endif">
                                <i class="nav-icon fas fa-tools"></i>
                                <p>Nastavení ikony</p>
                            </a>
                        </li>

                    @endif

                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->

    <div class="content-wrapper">
        @if(strtolower($force_email_verification) == 'true')
            @if(!Auth::user()->hasVerifiedEmail())
                @if(Auth::user()->created_at->diffInHours(now(), false) > 1)
                    <div class="alert alert-warning p-2 m-2">
                        <h5><i class="fas fa-envelope"></i> Varování!</h5>
                        Ještě nemáte ověřenou emailovou adresu. <a class="text-primary"
                                                                        href="{{route('verification.send')}}">Klikněte zde pro opětovné zaslání ověřovacího linku.</a> <br>
                       Prosím kontaktujte podporu v případě, že vám nebyl ověřovací link doručen.
                    </div>
                @else
                    <div class="alert alert-warning p-2 m-2">
                        <h5><i class="fas fa-envelope"></i> Varování!</h5>
                        Na email vám byl zaslán link pro ověření emailové adresy.<br>
                        Prosím kontaktujte podporu v případě, že vám nebyl ověřovací link doručen.
                    </div>
                @endif
            
            @endif
        @endif
        @if((is_null(Auth::user()->discordUser))&&(strtolower($force_discord_verification) == 'true'))
            <div class="alert alert-warning p-2 m-2">
                <h5><i class="fab fa-discord mr-2"></i>Varování!</h5>
                Ještě nemáte ověřený discord. <a class="text-primary"
                href="{{route('auth.redirect')}}">Klikněte zde pro přihlášení pomocí discordu.</a> <br>
                Prosím kontaktujte podporu v případě, že se vám nedaří discord ověřit.
            </div>
        @endif

        @yield('content')

        @include('models.redeem_voucher_modal')
    </div>
    <!-- /.content-wrapper -->
    <footer class="main-footer">
        Copyright &copy; 2021-{{date('Y')}} <strong><a href="{{url('/')}}">{{env('APP_NAME' , 'Laravel')}}</a>.</strong>
        Všechna práva vyhrazena. Používáme <a href="https://controlpanel.gg">ControlPanel</a>. Verze <b>{{config('app')['version']}}</b>
    </footer>

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
        <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- Scripts -->
{{--<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>--}}
{{--<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>--}}
{{--<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>--}}
{{--<script src="{{ asset('js/adminlte.min.js') }}"></script>--}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.14.1/dist/sweetalert2.all.min.js"></script>
<script src="{{asset('js/app.js')}}"></script>
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.24/datatables.min.js"></script>
<!-- Summernote -->
<script src="{{asset('plugins/summernote/summernote-bs4.min.js')}}"></script>

<!-- Moment.js -->
<script src="{{asset('plugins/moment/moment.min.js')}}"></script>

<!-- Datetimepicker -->
<script src="{{asset('plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js')}}"></script>

<!-- Select2 -->
<script src={{asset('plugins/select2/js/select2.min.js')}}>

<script>
    $(document).ready(function () {
        $('[data-toggle="popover"]').popover();

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    });
</script>
<script>
    @if(Session::has('error'))
    Swal.fire({
        icon: 'error',
        title: 'Jejda...',
        html: '{{Session::get('error')}}',
    })
    @endif

    @if(Session::has('success'))
    Swal.fire({
        icon: 'success',
        title: '{{Session::get('Úspěch!')}}',
        position: 'top-end',
        showConfirmButton: false,
        background: '#343a40',
        toast: true,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    })
    @endif
</script>
</body>
</html>
