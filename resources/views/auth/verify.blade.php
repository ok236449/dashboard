@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Ověření emailové adresy') }}</div>

                <div class="card-body">
                    @if (session('resent'))
                        <div class="alert alert-success" role="alert">
                            {{ __('Na email vám byl zaslán nový ověřovací link.') }}
                        </div>
                    @endif

                    {{ __('Před pokračováním si prosím zkontrolujte emailovou schránku.') }}
                    {{ __('Pokud nebyl odkaz doručen') }},
                    <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                        @csrf
                        <button type="submit" class="btn btn-link p-0 m-0 align-baseline">{{ __('klikněte zde pro zaslání nového') }}</button>.
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
