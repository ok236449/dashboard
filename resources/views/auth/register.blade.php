@extends('layouts.app')

@section('content')
    <body class="hold-transition dark-mode register-page">
    <div class="register-box">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <a href="{{route('welcome')}}" class="h1"><b class="mr-1">{{config('app.name', 'Laravel')}}</b></a>
            </div>
            <div class="card-body">
                <p class="login-box-msg">{{__('Register a new membership')}}</p>

                <form method="POST" action="{{ route('register') }}">

                    @error('ip')
                    <span class="text-danger" role="alert">
                                <small><strong>{{ $message }}</strong></small>
                            </span>
                    @enderror

                    @error('registered')
                    <span class="text-danger" role="alert">
                                <small><strong>{{ $message }}</strong></small>
                            </span>
                    @enderror
                    @if( $errors->has('ptero_registration_error') )
                        @foreach( $errors->get('ptero_registration_error') as $err )
                            <span class="text-danger" role="alert">
                                 <small><strong>{{ $err }}</strong></small>
                           </span>
                        @endforeach
                    @endif

                    @csrf
                    <div class="form-group">
                        <div class="input-group">
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}"
                                   placeholder="{{__('Username')}}" required autocomplete="name" autofocus>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-user"></span>
                                </div>
                            </div>
                        </div>
                        @error('name')
                        <span class="text-danger" role="alert">
                                <small><strong>{{ $message }}</strong></small>
                            </span>
                        @enderror
                    </div>


                    <div class="form-group">
                        <div class="input-group mb-3">
                            <input type="email" name="email" class="form-control  @error('email') is-invalid @enderror" placeholder="{{__('Email')}}"
                                   value="{{ old('email') }}" required autocomplete="email">
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-envelope"></span>
                                </div>
                            </div>
                        </div>
                        @error('email')
                        <span class="text-danger" role="alert">
                                <small><strong>{{ $message }}</strong></small>
                            </span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <div class="input-group mb-3">
                            <input type="password" class="form-control @error('password') is-invalid @enderror" placeholder="{{__('Password')}}" name="password" required autocomplete="new-password">
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-lock"></span>
                                </div>
                            </div>
                        </div>
                        @error('password')
                        <span class="text-danger" role="alert">
                                <small><strong>{{ $message }}</strong></small>
                            </span>
                        @enderror
                    </div>

                    <div class="input-group mb-3">
                        <input type="password" class="form-control" name="password_confirmation" placeholder="{{__('Retype password')}}" required autocomplete="new-password">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    @if(config('SETTINGS::REFERRAL::ENABLED') == "true")
                        <div class="input-group mb-3">
                            <input type="text" value="{{ \Request::get('ref') }}" class="form-control" name="referral_code" placeholder="{{__('Referral code')}} ({{__("optional")}})">
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-user-check"></span>
                                </div>
                            </div>
                        </div>
                    @endif
                    @if (config('SETTINGS::RECAPTCHA:ENABLED') == 'true')
                    <div class="input-group mb-3">
                        {!! htmlFormSnippet() !!}
                        @error('g-recaptcha-response')
                        <span class="text-danger" role="alert">
                                <small><strong>{{ $message }}</strong></small>
                            </span>
                        @enderror
                    </div>
                    @endif

                    <div class="row">
                        <div class="col-8">
                            <div class="icheck-primary">
                                <input type="checkbox" id="agreeTerms" required name="terms" value="agree">
                                <label for="agreeTerms">
                                    Souhlasím s <a href="https://home.vagonbrei.eu/VOP">VOP</a>
                                </label>
                            </div>
                        </div>
                        <!-- /.col -->
                        <div class="col-4">
                            <button type="submit" class="btn btn-primary">{{__('Register')}}</button>
                        </div>
                        <!-- /.col -->
                    </div>
                </form>

{{--                <div class="social-auth-links text-center">--}}
{{--                    <a href="#" class="btn btn-block btn-primary">--}}
{{--                        <i class="fab fa-facebook mr-2"></i>--}}
{{--                        Sign up using Facebook--}}
{{--                    </a>--}}
{{--                    <a href="#" class="btn btn-block btn-danger">--}}
{{--                        <i class="fab fa-google-plus mr-2"></i>--}}
{{--                        Sign up using Google+--}}
{{--                    </a>--}}
{{--                </div>--}}

                <a href="{{route('login')}}" class="text-center">{{__('I already have a membership')}}</a>
            </div>
            <!-- /.form-box -->
        </div><!-- /.card -->
    </div>
    <!-- /.register-box -->
    </body>
@endsection
