@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Store</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a class="" href="{{route('home')}}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a class="text-muted" href="{{route('store.index')}}">Store</a></li>
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
                                    <small class="float-right">Date: {{Carbon\Carbon::now()->isoFormat('LL')}}</small>
                                </h4>
                            </div>
                            <!-- /.col -->
                        </div>
                        <!-- info row -->
                        <div class="row invoice-info">
                            <div class="col-sm-4 invoice-col">
                                From
                                <address>
                                    <strong>{{config('app.name' , 'Laravel')}}</strong><br>
                                    Email: {{env('PAYPAL_EMAIL' , env('MAIL_FROM_NAME'))}}
                                </address>
                            </div>
                            <!-- /.col -->
                            <div class="col-sm-4 invoice-col">
                                To
                                <address>
                                    <strong>{{Auth::user()->name}}</strong><br>
                                    Email: {{Auth::user()->email}}
                                </address>
                            </div>
                            <!-- /.col -->
                            <div class="col-sm-4 invoice-col">
                                <b>Status</b><br>
                                <span class="badge badge-warning">Pending</span><br>
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
                                        <th>Quantity</th>
                                        <th>Product</th>
                                        <th>Description</th>
                                        <th>Subtotal</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td>1</td>
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
                                <p class="lead">Payment Methods:</p>

                                <img src="https://www.paypalobjects.com/digitalassets/c/website/logo/full-text/pp_fc_hl.svg" alt="Paypal">

                                <p class="text-muted well well-sm shadow-none" style="margin-top: 10px;">
                                    By purchasing this product you agree and accept our terms of service</a>
                                </p>
                            </div>
                            <!-- /.col -->
                            <div class="col-6">
                                <p class="lead">Amount Due {{Carbon\Carbon::now()->isoFormat('LL')}}</p>

                                <div class="table-responsive">
                                    <table class="table">
                                        <tr>
                                            <th style="width:50%">Subtotal:</th>
                                            <td>{{$product->formatCurrency()}}</td>
                                        </tr>
                                        <tr>
                                            <th>Tax (0%)</th>
                                            <td>{{(env('TAX_PERCENTAGE'))*($product)+(env('TAX_FIXED'))}}</td>
                                        </tr>
                                        <tr>
                                            <th>Quantity:</th>
                                            <td>1</td>
                                        </tr>
                                        <tr>
                                            <th>Total:</th>
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
                                <a href="{{route('payment.pay' , $product->id)}}" type="button" class="btn btn-success float-right"><i class="far fa-credit-card mr-2"></i> Submit
                                    Payment
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
