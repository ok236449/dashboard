@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Dokoupení kreditů</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a class="" href="{{route('home')}}">Přehled</a></li>
                        <li class="breadcrumb-item"><a class="text-muted" href="{{route('store.index')}}">Dokoupení kreditů</a></li>
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
                <div class="col-12">


                    <!-- Main content -->
                    <div class="invoice p-3 mb-3">
                        <!-- title row -->
                        <div class="row">
                            <div class="col-12">
                                <h4>
                                    <i class="fas fa-globe"></i> {{ config('app.name', 'Laravel') }}
                                    <small class="float-right">Datum: {{Carbon\Carbon::now()->isoFormat('LL')}}</small>
                                </h4>
                            </div>
                            <!-- /.col -->
                        </div>
                        <!-- info row -->
                        <div class="row invoice-info">
                            <div class="col-sm-4 invoice-col">
                                To
                                <address>
                                    <strong>{{config('app.name' , 'Laravel')}}</strong><br>
                                    Email: {{env('PAYPAL_EMAIL' , env('MAIL_FROM_NAME'))}}
                                </address>
                            </div>
                            <!-- /.col -->
                            <div class="col-sm-4 invoice-col">
                                From
                                <address>
                                    <strong>{{Auth::user()->name}}</strong><br>
                                    Email: {{Auth::user()->email}}
                                </address>
                            </div>
                            <!-- /.col -->
                            <div class="col-sm-4 invoice-col">
                                <b>Stav</b><br>
                                <span class="badge badge-warning">Čekání</span><br>
{{--                                <b>Order ID:</b> 4F3S8J<br>--}}
                            </div>
                            <!-- /.col -->
                        </div>
                        <!-- /.row -->

                        <!-- Table row -->
                        <div class="row">
                            <div class="col-12 table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                    <tr>
                                        <th>Množství kreditů</th>
                                        <th>Popis</th>
                                        <th>Mezisoučet</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td><i class="fa fa-coins mr-2"></i>{{$product->quantity}} {{strtolower($product->type) == 'credits' ? CREDITS_DISPLAY_NAME : $product->type}}</td>
                                        <td>{{$product->description}}</td>
                                        <td>{{$product->formatCurrency()}}</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!-- /.col -->
                        </div>
                        <!-- /.row -->

                        <div class="row">
                            <!-- accepted payments column -->
                            <div class="col-6">
                                <p class="lead">Platební metody:</p>

                                <img src="https://www.paypalobjects.com/digitalassets/c/website/logo/full-text/pp_fc_hl.svg" alt="Paypal">

                                <p class="text-muted well well-sm shadow-none" style="margin-top: 10px;">
                                    Zakoupením souhlasíte s <a href="http://home.vagonbrei.eu/podminky-uziti">podmínkami užití</a></a>
                                </p>
                            </div>
                            <!-- /.col -->
                            <div class="col-6">
                                <p class="lead">Platba ke dni {{Carbon\Carbon::now()->isoFormat('LL')}}</p>

                                <div class="table-responsive">
                                    <table class="table">
                                        /*<tr>
                                            <th style="width:50%">Subtotal:</th>
                                            <td>{{$product->formatCurrency()}}</td>
                                        </tr>
                                        <tr>
                                            <th>Tax (0%)</th>
                                            <td>0.00</td>
                                        </tr>
                                        <tr>
                                            <th>Quantity:</th>
                                            <td>1</td>
                                        </tr>*/
                                        <tr>
                                            <th>Celkem:</th>
                                            <td>{{$product->formatCurrency()}}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <!-- /.col -->
                        </div>
                        <!-- /.row -->

                        <!-- this row will not appear when printing -->
                        <div class="row no-print">
                            <div class="col-12">
                                <a href="{{route('payment.pay' , $product->id)}}" type="button" class="btn btn-success float-right"><i class="far fa-credit-card mr-2"></i> Potvrdit platbu
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- /.invoice -->
                </div><!-- /.col -->
            </div><!-- /.row -->


        </div>
    </section>
    <!-- END CONTENT -->

@endsection
