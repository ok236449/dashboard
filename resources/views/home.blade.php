@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Dashboard</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a class="text-muted" href="">Dashboard</a></li>
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
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-info elevation-1"><i class="fas fa-server"></i></span>

                        <div class="info-box-content">
                            <span class="info-box-text">Servers</span>
                            <span class="info-box-number">{{Auth::user()->servers()->count()}}</span>
                        </div>
                        <!-- /.info-box-content -->
                    </div>
                    <!-- /.info-box -->
                </div>
                <!-- /.col -->
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-secondary elevation-1"><i class="fas fa-coins"></i></span>

                        <div class="info-box-content">
                            <span class="info-box-text">{{CREDITS_DISPLAY_NAME}}</span>
                            <span class="info-box-number">{{Auth::user()->Credits()}}</span>
                        </div>
                        <!-- /.info-box-content -->
                    </div>
                    <!-- /.info-box -->
                </div>
                <!-- /.col -->

                <!-- fix for small devices only -->
                <div class="clearfix hidden-md-up"></div>

                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-chart-line"></i></span>

                        <div class="info-box-content">
                            <span class="info-box-text">{{CREDITS_DISPLAY_NAME}} usage</span>
                            <span class="info-box-number">{{number_format($useage, 2, '.', '')}} <sup>per month</sup></span>
                        </div>
                        <!-- /.info-box-content -->
                    </div>
                    <!-- /.info-box -->
                </div>

                <!-- /.col -->
                @if(Auth::user()->Credits() > 0.01 and $useage > 0)
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box mb-3">
                        @if(number_format((Auth::user()->Credits()*30)/$useage,0,'.','') >= 15)
                            <span class="info-box-icon bg-success elevation-1">
                        @elseif (number_format((Auth::user()->Credits()*30)/$useage,0,'.','') >= 8 && number_format((Auth::user()->Credits()*30)/$useage,0,'.','') <= 14)
                            <span class="info-box-icon bg-warning elevation-1">
                        @elseif (number_format((Auth::user()->Credits()*30)/$useage,0,'.','') <= 7)
                            <span class="info-box-icon bg-danger elevation-1">
                        @endif
                            <i class="fas fa-hourglass-half"></i></span>

                        <div class="info-box-content">
                            <span class="info-box-text">Out of {{CREDITS_DISPLAY_NAME}} in </span>
                            @if(number_format((Auth::user()->Credits()*30)/$useage,2,'.','') < "1")
                                @if(number_format(Auth::user()->Credits()/($useage/30/24),2,'.','') < "1")
                                    <span class="info-box-number">You ran out of Credits </span>
                                @else
                                    <span class="info-box-number">{{number_format(Auth::user()->Credits()/($useage/30/24),0,'.','')}} <sup> hours</sup></span>
                                @endif
                            @else
                               <span class="info-box-number">{{number_format((Auth::user()->Credits()*30)/$useage,0,'.','')}} <sup> days</sup></span>
                            @endif
                        </div>
                        <!-- /.info-box-content -->
                    </div>
                    <!-- /.info-box -->
                </div>
                <!-- /.col -->
            @endif
            </div>



            <div class="row">
                <div class="col-md-6">
                    <div class="card card-default">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-link mr-2"></i>
                                Useful Links
                            </h3>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            @foreach ($useful_links as $useful_link)
                                <div class="alert alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                    <h5>
                                        <a class="alert-link text-decoration-none" target="__blank" href="{{ $useful_link->link }}">
                                            <i class="{{ $useful_link->icon }} mr-2"></i>{{ $useful_link->title }}
                                        </a>
                                    </h5>
                                    {!! $useful_link->description !!}
                                </div>
                            @endforeach
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col -->

                <div class="col-md-6">
                    <div class="card card-default">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-history mr-2"></i>
                                Activity Log
                            </h3>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body py-0 pb-2">
                            <ul class="list-group list-group-flush">
                                @foreach(Auth::user()->actions()->take(8)->orderBy('created_at' , 'desc')->get() as $log)
                                    <li class="list-group-item d-flex justify-content-between text-muted">
                                        <span>
                                            @switch($log->description)
                                                @case('created')
                                                    <small><i class="fas text-success fa-plus mr-2"></i></small>
                                                @break
                                                @case('redeemed')
                                                <small><i class="fas text-success fa-money-check-alt mr-2"></i></small>
                                                @break
                                                @case('deleted')
                                                    <small><i class="fas text-danger fa-times mr-2"></i></small>
                                                @break
                                                @case('updated')
                                                    <small><i class="fas text-info fa-pen mr-2"></i></small>
                                                @break
                                            @endswitch
                                            {{ucfirst($log->description)}}
                                            {{ explode("\\" , $log->subject_type)[2]}}
                                        </span>
                                        <small>
                                            {{$log->created_at->diffForHumans()}}
                                        </small>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col -->

            </div>
            <!-- END CUSTOM CONTENT -->
        </div>
    </section>
    <!-- END CONTENT -->

@endsection
