@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Activity Logs</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('home')}}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a class="text-muted" href="{{route('admin.activitylogs.index')}}">Activity Logs</a>
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
                <div class="col-lg-4">
                    @if($cronlogs)
                        <div class="callout callout-success">
                            <h4>{{$cronlogs}}</h4>
                        </div>
                    @else
                        <div class="callout callout-danger">
                            <h4>No recent activity from cronjobs</h4>
                            <p>Are cronjobs running? <a class="text-primary" target="_blank" href="https://github.com/ControlPanel-gg/dashboard/wiki/Installation#crontab-configuration">Check the docs for it here</a></p>
                        </div>
                    @endif

                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><i class="fas fa-history mr-2"></i>Activity Logs</h5>
                </div>
                <div class="card-body table-responsive">

                    <div class="row">
                        <div class="col-lg-3 offset-lg-9 col-xl-2 offset-xl-10 col-md-6 offset-md-6">
                            <form method="get" action="{{route('admin.activitylogs.index')}}">
                                @csrf
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control form-control-sm" value="" name="search" placeholder="Search">
                                    <div class="input-group-append">
                                        <button class="btn btn-light btn-sm" type="submit"><i class="fa fa-search"></i></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <table class="table table-sm table-striped">
                        <thead>
                        <tr>
                            <th>Causer</th>
                            <th>Description</th>
                            <th>Created At</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($logs as $log)
                            <tr>
        <td> @if($log->causer) <a href='/admin/users/{{$log->causer_id}}'> {{json_decode($log->causer)->name}} 
		@else 
			System
		@endif</td>
                                <td>
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
				            @php $first=true @endphp
					    @foreach(json_decode($log->properties, true) as $properties)
						@if($first)
						    @if(isset($properties['name']))
					    	       " {{$properties['name']}} "
						    @endif
						    @if(isset($properties['email']))
						       < {{$properties['email']}} >
						    @endif
						    @php $first=false @endphp
						@endif
					    @endforeach
                                        </span>
                                </td>

                                <td>{{$log->created_at->diffForHumans()}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    <div class="float-right">
                        {!!  $logs->links() !!}
                    </div>

                </div>
            </div>


        </div>
        <!-- END CUSTOM CONTENT -->
        </div>
    </section>
    <!-- END CONTENT -->

@endsection
