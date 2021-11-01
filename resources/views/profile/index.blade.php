@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Profil</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('home')}}">Přehled</a></li>
                        <li class="breadcrumb-item"><a class="text-muted" href="{{route('profile.index')}}">Profil</a>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <!-- END CONTENT HEADER -->

    <!-- MAIN CONTENT -->
    <section class="content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-lg-12 px-0">
                    @if(!Auth::user()->hasVerifiedEmail() && strtolower($force_email_verification) == 'true')
                        <div class="alert alert-warning p-2 m-2">
                            <h5><i class="icon fas fa-exclamation-circle"></i>Je nutné ověření emailu!</h5>
                            Ještě nemáte ověřenou emailovou adresu. 
                            <a class="text-primary" href="{{route('verification.send')}}">Klikněte zde pro opětovné zaslání ověřovacího linku.</a> <br>
                            Prosím kontaktujte podporu v případě, že vám nebyl ověřovací link doručen.
                        </div>
                    @endif

                    @if(is_null(Auth::user()->discordUser) && strtolower($force_discord_verification) == 'true')
                        @if(!empty(env('DISCORD_CLIENT_ID')) && !empty(env('DISCORD_CLIENT_SECRET')))
                            <div class="alert alert-warning p-2 m-2">
                                <h5><i class="icon fas fa-exclamation-circle"></i>Je nutné ověření discordu!</h5>
                                Ještě nemáte ověřený discord. 
                                <a class="text-primary" href="{{route('auth.redirect')}}">Přihlásit se s discordem</a> <br>
                                Prosím kontaktujte podporu, pokud máte jakýkoliv problém s ověřením.
                            </div>
                        @else
                            <div class="alert alert-danger p-2 m-2">
                                <h5><i class="icon fas fa-exclamation-circle"></i>Je nutné ověření discordu!</h5>
                                Kvůli systémovému nastavení je třeba ověření discordu.<br>
                                Vypadá to, že vaše ověření neproběhlo správně. Prosím kontaktujte podporu.
                            </div>
                        @endif
                    @endif

                </div>
            </div>

            <form class="form" action="{{route('profile.update' , Auth::user()->id)}}" method="post">
                @csrf
                @method('PATCH')
                <div class="card">
                    <div class="card-body">
                        <div class="e-profile">
                            <div class="row">
                                <div class="col-12 col-sm-auto mb-4">
                                    <div class="slim rounded-circle  border-secondary border text-gray-dark"
                                         data-label="Upravit obrázek"
                                         data-max-file-size="3"
                                         data-save-initial-image="true"
                                         style="width: 140px;height:140px; cursor: pointer"
                                         data-size="140,140">
                                        <img src="{{$user->getAvatar()}}" alt="avatar">
                                    </div>
                                </div>
                                <div class="col d-flex flex-column flex-sm-row justify-content-between mb-3">
                                    <div class="text-center text-sm-left mb-2 mb-sm-0"><h4
                                            class="pt-sm-2 pb-1 mb-0 text-nowrap">{{$user->name}}</h4>
                                        <p class="mb-0">{{$user->email}}
                                            @if($user->hasVerifiedEmail())
                                                <i data-toggle="popover" data-trigger="hover" data-content="Verified" class="text-success fas fa-check-circle"></i>
                                            @else
                                                <i data-toggle="popover" data-trigger="hover" data-content="Not verified" class="text-danger fas fa-exclamation-circle"></i>
                                            @endif

                                        </p>
                                        <div class="mt-1">
                                            <span class="badge badge-primary"><i class="fa fa-coins mr-2"></i>{{$user->Credits()}}</span>
                                        </div>
                                    </div>

                                    <div class="text-center text-sm-right"><span
                                            class="badge badge-secondary">{{$user->role}}</span>
                                        <div class="text-muted"><small>{{$user->created_at->isoFormat('LL')}}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <ul class="nav nav-tabs">
                                <li class="nav-item"><a href="javasript:void(0)" class="active nav-link">Nastavení</a>
                                </li>
                            </ul>
                            <div class="tab-content pt-3">
                                <div class="tab-pane active">
                                    <div class="row">
                                        <div class="col">
                                            <div class="row">
                                                <div class="col">
                                                    <div class="form-group"><label>Uživatelské jméno</label> <input
                                                            class="form-control @error('name') is-invalid @enderror"
                                                            type="text" name="name"
                                                            placeholder="{{$user->name}}" value="{{$user->name}}">

                                                        @error('name')
                                                        <div class="invalid-feedback">
                                                            {{$message}}
                                                        </div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col">
                                                    <div class="form-group"><label>Email</label> <input
                                                            class="form-control @error('email') is-invalid @enderror"
                                                            type="text"
                                                            placeholder="{{$user->email}}" name="email"
                                                            value="{{$user->email}}">

                                                        @error('email')
                                                        <div class="invalid-feedback">
                                                            {{$message}}
                                                        </div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12 col-sm-6 mb-3">
                                            <div class="mb-3"><b>Změnit heslo</b></div>
                                            <div class="row">
                                                <div class="col">
                                                    <div class="form-group"><label>Aktuální heslo</label> <input
                                                            class="form-control @error('current_password') is-invalid @enderror"
                                                            name="current_password" type="password"
                                                            placeholder="••••••">

                                                        @error('current_password')
                                                        <div class="invalid-feedback">
                                                            {{$message}}
                                                        </div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col">
                                                    <div class="form-group"><label>Nové heslo</label> <input
                                                            class="form-control @error('new_password') is-invalid @enderror"
                                                            name="new_password" type="password" placeholder="••••••">

                                                        @error('new_password')
                                                        <div class="invalid-feedback">
                                                            {{$message}}
                                                        </div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col">
                                                    <div class="form-group"><label>Potvrdit nové heslo<span
                                                                class="d-none d-xl-inline"></span></label>
                                                        <input
                                                            class="form-control @error('new_password_confirmation') is-invalid @enderror"
                                                            name="new_password_confirmation" type="password"
                                                            placeholder="••••••">

                                                        @error('new_password_confirmation')
                                                        <div class="invalid-feedback">
                                                            {{$message}}
                                                        </div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @if(!empty(env('DISCORD_CLIENT_ID')) && !empty(env('DISCORD_CLIENT_SECRET')))
                                            <div class="col-12 col-sm-5 offset-sm-1 mb-3">
                                                <b>Ověřte si discord účet!</b>
                                                @if(is_null(Auth::user()->discordUser))
                                                    <div class="verify-discord">
                                                        <div class="mb-3">
                                                            @if($credits_reward_after_verify_discord)
                                                                <p>Ověřením discord účtu získáte
                                                                    <b><i
                                                                            class="fa fa-coins mx-1"></i>{{$credits_reward_after_verify_discord}}
                                                                    </b> {{CREDITS_DISPLAY_NAME}}.</br><small>K ověření prosím použijte účet, který používáte na našem discordu. Po ověření dostanete také roli "Ověřený" a přístup k dalším kanálům na serveru.</small>
                                                                </p>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <a class="btn btn-light" href="{{route('auth.redirect')}}">
                                                        <i class="fab fa-discord mr-2"></i>Přihlásit se s discordem
                                                    </a>
                                                @else
                                                    <div class="verified-discord">
                                                        <div class="my-3 callout callout-info">
                                                            <p>Jste ověřený!</p>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                            <div class="small-box bg-dark">
                                                                <div class="d-flex justify-content-between">
                                                                    <div class="p-3">
                                                                        <h3>{{$user->discordUser->username}} <sup>{{$user->discordUser->locale}}</sup> </h3>
                                                                        <p>{{$user->discordUser->id}}
                                                                        </p>
                                                                    </div>
                                                                    <div class="p-3"><img width="100px" height="100px" class="rounded-circle" src="{{$user->discordUser->getAvatar()}}" alt="avatar"></div>
                                                                </div>
								                                <div class="small-box-footer">
                                                                    <a href="{{route('auth.redirect')}}">
                                                                        <i class="fab fa-discord mr-1"></i>Znovu propojit discord
                                                                    </a>
                                                                </div>
                                                        </div>
                                                    </div>
                                                @endif

                                            </div>
                                        @endif
                                    </div>
                                    <div class="row">
                                        <div class="col d-flex justify-content-end">
                                            <button class="btn btn-primary" type="submit">Uložit změny</button>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>


        </div>
        <!-- END CUSTOM CONTENT -->

        </div>
    </section>
    <!-- END CONTENT -->

@endsection
