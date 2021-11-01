@extends('layouts.main')
<?php use App\Models\PaypalProduct; ?>

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Dokoupit kredity</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a class="" href="{{route('home')}}">Přehled</a></li>
                        <li class="breadcrumb-item"><a class="text-muted" href="{{route('store.index')}}">Dokoupit kredity</a></li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <!-- END CONTENT HEADER -->

    <!-- MAIN CONTENT -->
    <section class="content">
        <div class="container-fluid">

            <div class="text-right mb-3">
                <button type="button" data-toggle="modal" data-target="#redeemVoucherModal" class="btn btn-primary">
                    <i class="fas fa-money-check-alt mr-2"></i>Využít voucher
                </button>
            </div>

            @if($isPaypalSetup && $products->count() > 0)

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fa fa-coins mr-2"></i>{{CREDITS_DISPLAY_NAME}}</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped table-responsive-sm">
                            <thead>
                            <tr>
                                <th>Cena</th>
                                <th>Typ</th>
                                <th>Množství kreditů</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php /** @var $product PaypalProduct */?>
                            @foreach($products as $product)
                                <tr>
                                    <td>{{$product->formatCurrency()}}</td>
                                    <td>{{strtolower($product->type) == 'credits' ? CREDITS_DISPLAY_NAME : $product->type}}</td>
                                    <td><i class="fa fa-coins mr-2"></i>Nákup {{$product->display}}</td>
                                    <td><a href="{{route('checkout' , $product->id)}}" class="btn btn-info">Zakoupit</a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        </br><i class="fas fa-info-circle"></i> Pokud Vám nevyhovuje platba přes PayPal, nebo se chcete vyhnout poplatku, kontaktujte majitele na <a href="https://discord.gg/kF5F8ss4wU">discordu</a>.</br>Paypal si účtuje poplatek 10Kč/0,35€ + 3,4% z platby. Z tohoto důvodu je to u menších balíčků bráno v potaz.
                    </div>
                </div>

            @else
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h4><i class="icon fa fa-ban"></i> @if($products->count() == 0) Nejsou tu žádné balíčky pro dokoupení kreditů! @else Tato stránka není správně nastavena! Kontaktujte prosím podporu. @endif
                    </h4>
                </div>

            @endif


        </div>
    </section>
    <!-- END CONTENT -->

@endsection
