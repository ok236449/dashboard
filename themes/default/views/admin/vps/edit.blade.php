@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{__('VPS')}}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('home')}}">{{__('Dashboard')}}</a></li>
                        <li class="breadcrumb-item"><a href="{{route('admin.vps.index')}}">{{__('VPS')}}</a>
                        </li>
                        <li class="breadcrumb-item"><a class="text-muted"
                                                       href="{{route('admin.vps.edit', $vps->id)}}">{{__('Edit')}}</a>
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
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-handshake mr-2"></i>{{__('VPS details')}}
                            </h5>
                        </div>
                        <div class="card-body">
                            <form action="{{route('admin.vps.update', $vps->id)}}" method="PATCH">
                                @csrf
                                @method('PATCH')

                                <div class="form-group">

                                    <div class="custom-control mb-3 p-0">
                                        <label for="user_id">{{ __('User') }}:
                                        </label>
                                        <select id="user_id" style="width:100%" class="custom-select" name="user_id" required
                                                autocomplete="off" @error('user_id') is-invalid @enderror>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="description">{{__('Description')}}
                                        <i data-toggle="popover" data-trigger="hover"
                                        data-content="{{__('Description for the convoy VPS.')}}"
                                        class="fas fa-info-circle"></i>
                                    </label>
                                    <input value="{{$vps->description}}" placeholder="{{__('description')}}" id="description" name="description"
                                           type="text"
                                           class="form-control @error('description') is-invalid @enderror">
                                    @error('description')
                                    <div class="text-danger">
                                        {{$message}}
                                    </div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="price">{{__('Price')}}
                                        <i data-toggle="popover" data-trigger="hover"
                                        data-content="{{__('Monthly price for the VPS.')}}"
                                        class="fas fa-info-circle"></i>
                                    </label>
                                    <input value="{{$vps->price/100}}" placeholder="{{__('Price monthly')}}" id="price" name="price"
                                           type="number" step="0.01" min="0" max="10000"
                                           class="form-control @error('price') is-invalid @enderror">
                                    @error('price')
                                    <div class="text-danger">
                                        {{$message}}
                                    </div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="uuid">{{__('UUID')}}
                                        <i data-toggle="popover" data-trigger="hover"
                                        data-content="{{__('UUID of the convoy VPS.')}}"
                                        class="fas fa-info-circle"></i>
                                    </label>
                                    <input value="{{$vps->uuid}}" placeholder="{{__('uuid')}}" id="uuid" name="uuid"
                                           type="text"
                                           class="form-control @error('uuid') is-invalid @enderror">
                                    @error('uuid')
                                    <div class="text-danger">
                                        {{$message}}
                                    </div>
                                    @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label for="last_payment">{{__('Last payment')}} <i data-toggle="popover"
                                                                                    data-trigger="hover"
                                                                                    data-content="Timezone: {{ Config::get('app.timezone') }}"
                                                                                    class="fas fa-info-circle"></i></label>
                                    <div class="input-group date" id="last_payment" data-target-input="nearest">
                                        <input value="{{$vps->last_payment}}" name="last_payment"
                                               placeholder="dd-mm-yyyy hh:mm:ss" type="text"
                                               class="form-control @error('last_payment') is-invalid @enderror datetimepicker-input"
                                               data-target="#last_payment"/>
                                        <div class="input-group-append" data-target="#last_payment"
                                             data-toggle="datetimepicker">
                                            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                        </div>
                                    </div>
                                    @error('last_payment')
                                    <div class="text-danger">
                                        {{$message}}
                                    </div>
                                    @enderror
                                </div>

                                <div class="form-group text-right">
                                    <button type="submit" class="btn btn-primary">
                                        {{__('Submit')}}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <i class="fas"></i>

        </div>
    </section>
    <!-- END CONTENT -->


    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            $('#last_payment').datetimepicker({
                format: 'DD-MM-yyyy HH:mm:ss',
                icons: {
                    time: 'far fa-clock',
                    date: 'far fa-calendar',
                    up: 'fas fa-arrow-up',
                    down: 'fas fa-arrow-down',
                    previous: 'fas fa-chevron-left',
                    next: 'fas fa-chevron-right',
                    today: 'fas fa-calendar-check',
                    clear: 'far fa-trash-alt',
                    close: 'far fa-times-circle'
                }
            });
        })
    </script>

    <script type="application/javascript">
        function initUserIdSelect(data) {
            function escapeHtml(str) {
                var div = document.createElement('div');
                div.appendChild(document.createTextNode(str));
                return div.innerHTML;
            }

            $('#user_id').select2({
                ajax: {
                    url: '/admin/users.json',
                    dataType: 'json',
                    delay: 250,

                    data: function (params) {
                        return {
                            filter: { name: params.term },
                            page: params.page,
                        };
                    },

                    processResults: function (data, params) {
                        return { results: data };
                    },

                    cache: true,
                },

                data: data,
                escapeMarkup: function (markup) { return markup; },
                minimumInputLength: 2,
                templateResult: function (data) {
                    if (data.loading) return escapeHtml(data.text);

                    return '<div class="user-block"> \
                        <img class="img-circle img-bordered-xs" src="' + escapeHtml(data.avatarUrl) + '?s=120" alt="User Image"> \
                        <span class="username"> \
                            <a href="#">' + escapeHtml(data.name) +'</a> \
                        </span> \
                        <span class="description"><strong>' + escapeHtml(data.email) + '</strong>' + '</span> \
                    </div>';
                },
                templateSelection: function (data) {
                    return '<div> \
                        <span> \
                            <img class="img-rounded img-bordered-xs" src="' + escapeHtml(data.avatarUrl) + '?s=120" style="height:28px;margin-top:-4px;" alt="User Image"> \
                        </span> \
                        <span style="padding-left:5px;"> \
                            ' + escapeHtml(data.name) + ' (<strong>' + escapeHtml(data.email) + '</strong>) \
                        </span> \
                    </div>';
                }

            });
        }

        $(document).ready(function() {
            @if ($vps->user_id)
            $.ajax({
                url: '/admin/users.json?user_id={{$vps->user_id}}',
                dataType: 'json',
            }).then(function (data) {
                initUserIdSelect([ data ]);
            });
            @else
            initUserIdSelect();
            @endif
        });
    </script>


@endsection
