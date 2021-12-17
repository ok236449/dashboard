@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{__('Servery')}}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('home')}}">{{__('Přehled')}}</a></li>
                        <li class="breadcrumb-item"><a class="text-muted" href="{{route('servers.index')}}">{{__('Servery')}}</a>
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

            <!-- CUSTOM CONTENT -->
            <div class="d-flex justify-content-between mb-3">
                <a @if(Auth::user()->Servers->count() >= Auth::user()->server_limit) disabled="disabled" title="{{__('Bylo dosaženo limitu počtu vašich serverů!')}}" @endif href="{{route('servers.create')}}" class="btn @if(Auth::user()->Servers->count() >= Auth::user()->server_limit) disabled @endif btn-primary"><i class="fa fa-plus mr-2"></i>{{__('Vytvořit server')}}</a>
            </div>

            <div class="row">
                @foreach($servers as $server)
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header ">
                                <div class="d-flex justify-content-between">
                                    <h5 class="card-title"><i class="fas {{$server->isSuspended() ? 'text-danger' : 'text-success'}} fa-circle mr-2"></i>{{$server->name}}</h5>
                                    <div class="card-tools">
                                        <div class="dropdown no-arrow">
                                            <a  href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fas fa-ellipsis-v fa-sm fa-fw text-white-50"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                                                <a href="{{env('PTERODACTYL_URL' , 'http://localhost')}}/server/{{$server->identifier}}"  target="__blank" class="dropdown-item text-info"><i title="manage" class="fas fa-tasks mr-2"></i><span>{{__('Spravovat')}}</span></a>
                                                @if(!empty(env('PHPMYADMIN_URL')))
                                                    <a href="{{env('PHPMYADMIN_URL' , 'http://localhost')}}" class="dropdown-item text-info"  target="__blank"><i title="manage" class="fas fa-database mr-2"></i><span>{{__('PhpMyAdmin')}}</span></a>
                                                @endif
                                                <form method="post" onsubmit="return submitResult();" action="{{route('servers.destroy' , $server->id)}}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="dropdown-item text-danger"><i title="delete" class="fas fa-trash mr-2"></i><span>{{__('Odstranit server')}}</span></button>
                                                </form>
                                                <div class="dropdown-divider"></div>
                                                <span class="dropdown-item"><i title="Created at" class="fas fa-sync-alt mr-2"></i><span>{{$server->created_at->isoFormat('LL')}}</span></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <span class="text-muted">{{__('Detaily serveru')}}</span>
                                <table class="table">
                                    <tr>
                                        <td>{{__('Cpu')}}</td>
                                        <td>{{$server->product->cpu}} %</td>
                                    </tr>
                                    <tr>
                                        <td>{{__('Memory')}}</td>
                                        <td>{{$server->product->memory}} MB</td>
                                    </tr>
                                    <tr>
                                        <td>{{__('Úložiště')}}</td>
                                        <td>{{$server->product->disk}} MB</td>
                                    </tr>
                                    <tr>
                                        <td>{{__('Databáze')}}</td>
                                        <td>{{$server->product->databases}} MySQL</td>
                                    </tr>
                                    <tr>
                                        <td>{{__('Zálohy')}}</td>
                                        <td>{{$server->product->backups}}</td>
                                    </tr>
                                    <tr>
                                        <td>{{__('Cena za hodinu')}}</td>
                                        <td>{{number_format($server->product->getHourlyPrice(),2,".", "")}} {{CREDITS_DISPLAY_NAME}}</td>
                                    </tr>
                                    <tr>
                                        <td>{{__('Cena za měsíc')}}</td>
                                        <td>{{$server->product->getHourlyPrice()*24*30}} {{CREDITS_DISPLAY_NAME}}</td>
                                    </tr>
                                </table>
                            </div>


                            <div class="card-footer d-flex justify-content-between">
                                <a href="{{env('PTERODACTYL_URL' , 'http://localhost')}}/server/{{$server->identifier}}"  target="__blank" class="btn btn-info mx-3 w-100"><i class="fas fa-tasks mr-2"></i>{{__('Spravovat')}}</a>
                                @if(!empty(env('PHPMYADMIN_URL')))
                                    <a href="{{env('PHPMYADMIN_URL' , 'http://localhost')}}" target="__blank" class="btn btn-info mx-3 w-100" ><i class="fas fa-database mr-2"></i>{{__('Databáze')}}</a>
                                @endif
                            </div>

                        </div>
                    </div>
                @endforeach
            </div>
            <!-- END CUSTOM CONTENT -->


        </div>
    </section>
    <!-- END CONTENT -->

    <script>
        function submitResult() {
            return confirm("{{__('Opravdu si přejete server odstranit? Tato akce je nevratná.')}}") !== false;
        }
    </script>
@endsection
