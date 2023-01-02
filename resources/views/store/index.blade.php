@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Store') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a class=""
                                                       href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a class="text-muted"
                                                       href="{{ route('store.index') }}">{{ __('Store') }}</a></li>
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
                    <i class="fas fa-money-check-alt mr-2"></i>{{ __('Redeem code') }}
                </button>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><i class="fa fa-coins mr-2"></i>{{ __('Credit store') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('payment.Pay') }}">
                        <div class="row">
                            <div class="col-xl-2 col-12" style="padding: 0px">
                                <div class="inward-border-div quick-select-div" id="quick-select-div">
                                    <label for="quick-select-div" style="margin:8px;">{{__('Quick select')}}: </label>
                                    <div class="row">
                                        @foreach($quick_select_values as $value)
                                            <div class="col-xl-12 col-lg-2 col-sm-4 col-6 quick-select-amount">
                                                <button type="button" class="btn btn-info" onclick="changeValue({{$value}});" id="quick_select_button{{$value}}">{{$value}} {{ CREDITS_DISPLAY_NAME }}</button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                
                            </div>
                            <div class="col-xl-10 col-12 inward-border-div">
                                <div class="slider-2">
                                    <label for="slider" class="mb-0" style="white-space: nowrap;">{{__('Amount of credits')}}: </label>
                                    <input id="slider" class="m-2" type="range" min="{{$min_amount}}" max="{{$max_amount/$min_amount/4*$min_amount}}" oninput="changeValue(this.value);" ondblclick="changeValue(100);">
                                    <input type="number" class="form-control" name="credit_amount" id="credit_amount" min="{{$min_amount}}" max="{{$max_amount}}" required style="width: 80px; height: 24px; padding-right: 0px" oninput="changeValue(this.value);">
                                    
                                </div>  
                                
                                <div class="row" style="padding: 8px">
                                    <div class="col-lg-8 col-sm-7 col-12" style="padding: 0px;">
                                        <div class="row" style="padding: 0px; margin: 0px">
                                            <div class="col-xl-7 col-12" style="padding: 6px; padding-top: 0px;">
                                                <label for="payment_methods_tax" class="mb-1" style="display: block;">{{__('Payment method fees')}}:</label>
                                                <div class="border-div" id="payment_methods_tax" style="padding-top: 4px">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-12 taxtable">
                                                            <p><b>PayPal:</b></p>
                                                        </div>
                                                        <div class="col-lg-5 col-7 taxtable">
                                                            <p>{{__('PayPal account')}}:</p>
                                                        </div>
                                                        <div class="col-lg-4 col-5 taxtable">
                                                            <p id="paypal_tax"></p>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-3 col-12 taxtable">
                                                            <p><b>Stripe:</b></p>
                                                        </div>
                                                        <div class="col-lg-5 col-7 taxtable">
                                                            <p>{{__('Credit card')}}:</p>
                                                        </div>
                                                        <div class="col-lg-4 col-5 taxtable">
                                                            <p id="stripe_tax"></p>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-3 col-12 taxtable">
                                                            <p><b>GoPay:</b></p>
                                                        </div>
                                                        <div class="col-lg-5 col-7 taxtable">
                                                            <p>
                                                                {{__('Bank transfer')}}:<br>
                                                                {{__('M-payment (SMS)')}}:<br>
                                                                {{__('Paysafecard')}}:<br>
                                                                {{__('Bitcoin')}}:
                                                            </p>
                                                        </div>
                                                        <div class="col-lg-4 col-5 taxtable">
                                                            <p id="gopay_tax" style="white-space: pre;"></p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <label class="mb-1 mt-1" for="payment_methods" style="display: block;">{{__('Currency')}}: </label>
                                                <div class="border-div" id="payment_methods">
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="mb-3 mt-2" style="position: relative; justify-content:center">
                                                                <label for="czk">
                                                                    <input style="position: absolute; top: 50%; transform: translateY(-50%);" type="radio" value="czk" name="currency" id="czk" oninput="changeCurrency();" required>
                                                                    <img height="40px" style="position: absolute; top: 50%; transform: translateY(-50%); margin-left: 20px" src="{{ asset('images/czech-flag.png') }}"/>
                                                                    <label for="czk" style="position: absolute; top: 50%; transform: translateY(-50%); margin-left: 64px; font-size: 34px">CZK</label>
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="tool-tip mb-2" style="position: relative; justify-content:center">
                                                                <label for="eur">
                                                                    <span class="tool-tip-text" style="left: 28px; width: 90px">1€ = {{config("SETTINGS::PAYMENTS:EUR_RATIO")}}Kč</span>
                                                                    <input style="position: absolute; top: 50%; transform: translateY(-50%);" type="radio" value="eur" name="currency" id="eur" oninput="changeCurrency();" required>
                                                                    <img height="40px" style="position: absolute; top: 50%; transform: translateY(-50%); margin-left: 20px" src="{{ asset('images/slovak-flag.png') }}"/>
                                                                    <label for="eur" style="position: absolute; top: 50%; transform: translateY(-50%); margin-left: 64px; font-size: 34px">EUR</label>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xl-5 col-12" style="padding: 6px; padding-top: 0px;">
                                                <label class="mb-1" for="payment_methods" style="display: block;">{{__('Payment methods')}}: </label>
                                                <div class="border-div" id="payment_methods">
                                                    <div class="row">
                                                        @if (config('SETTINGS::PAYMENTS:PAYPAL:SECRET') || config('SETTINGS::PAYMENTS:PAYPAL:SANDBOX_SECRET'))
                                                            <div class="col-12">
                                                                <div class="tool-tip mb-4 mt-3" style="position: relative; justify-content:center">
                                                                    <label for="paypal">
                                                                        <span class="tool-tip-text" style="left: 12px">{{__('PayPal account')}},<br>{{__('Credit card')}}</span>
                                                                        <input style="position: absolute; top: 50%; transform: translateY(-50%);" type="radio" value="paypal" name="payment_method" id="paypal" oninput="changePaymentMethod();" required>
                                                                        <img height="40px" style="position: absolute; top: 50%; transform: translateY(-50%); margin-left: 20px" src="{{ asset('images/paypal_logo.png') }}"/>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        @endif
                                                        @if (config('SETTINGS::PAYMENTS:STRIPE:TEST_SECRET') || config('SETTINGS::PAYMENTS:STRIPE:SECRET'))
                                                            <div class="col-12">
                                                                <div class="tool-tip mb-4" style="position: relative; justify-content:center">
                                                                    <label for="stripe">
                                                                        <span class="tool-tip-text" style="left: -40px">{{__('Credit card')}},<br>{{__('Google Pay')}}, {{__('Apple Pay')}}</span>
                                                                        <input style="position: absolute; top: 50%; transform: translateY(-50%);" type="radio" value="stripe" name="payment_method" id="stripe" oninput="changePaymentMethod();" required>
                                                                        <img height="40px" style="position: absolute; top: 50%; transform: translateY(-50%); margin-left: 20px" src="{{ asset('images/stripe_logo.png') }}"/>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        @endif
                                                        @if (config('SETTINGS::PAYMENTS:GOPAY:TEST_CLIENT_SECRET') || config('SETTINGS::PAYMENTS:GOPAY:CLIENT_SECRET'))
                                                            <div class="col-12">
                                                                <div class="tool-tip mb-3" style="position: relative; justify-content:center">
                                                                    <label for="gopay">
                                                                        <span class="tool-tip-text">{{__('M-payment (SMS)')}}, {{__('Bank transfer')}}, {{__('Paysafecard')}}, {{__('Bitcoin')}}</span>
                                                                        <input   style="position: absolute; top: 50%; transform: translateY(-50%);" type="radio" value="gopay" name="payment_method" id="gopay" oninput="changePaymentMethod();" required>
                                                                        <img height="40px" style="position: absolute; top: 50%; transform: translateY(-50%); margin-left: 20px" src="{{ asset('images/gopay_logo.png') }}"/>
                                                                    </label>
                                                                    
                                                                </div>
                                                                <div class="ml-4 mt-0" style="display: none; margin-top: -8px" id="gopay_payment_method">
                                                                    <div>
                                                                        <input style="display: inline;" type="radio" value="bank_bitcoin" name="gopay_payment_method" id="gopay_payment_method_bank_bitcoin" oninput="changePaymentMethod();">
                                                                        <label for="gopay_payment_method_bank_bitcoin" style="display: inline">{{__('Bank transfer or Bitcoin')}}</label>
                                                                    </div>
                                                                    <div>
                                                                        <input style="display: inline;" type="radio" value="paysafecard" name="gopay_payment_method" id="gopay_payment_method_paysafecard" oninput="changePaymentMethod();">
                                                                        <label for="gopay_payment_method_paysafecard" style="display: inline">{{__('PaySafeCard')}}</label>
                                                                    </div>
                                                                    <div class="mb-1">
                                                                        <input style="display: inline" type="radio" value="sms" name="gopay_payment_method" id="gopay_payment_method_sms" oninput="changePaymentMethod();">
                                                                        <label for="gopay_payment_method_sms" style="display: inline">{{__('M-payment (SMS)')}}</label>
                                                                    </div>
                                                                </div>
                                                                
                                                                
                                                                
                                                            </div>
                                                        @endif
                                                        
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-5 col-12" style="padding: 8px; padding-top: 0px">
                                        <label class="mb-1" for="overview" style="display: block;">{{__('Payment overview')}}: </label>
                                        <div class="row border-div" style="margin: 0px" id="overview">
                                            <div class="table-responsive">
                                                <table class="table" style="margin-top: -8px; margin-bottom: 0px">
                                                    <tr>
                                                        <th>{{ __('Amount of credits') }}:</th>
                                                        <td id="amount"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>{{ __('Quantity discount') }}:</th>
                                                        <td id="quantity_discount"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>{{ __('Subtotal') }}:</th>
                                                        <td id="subtotal"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>{{ __('Your Discount') }}:</th>
                                                        <td id="your_discount"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>{{ __('Tax') }} <span id="tax_span"></span></th>
                                                        <td id="tax"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>{{ __('Total') }}:</th>
                                                        <td id="total" style="font-weight: bold"></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12 mt-2" style="padding: 8px; padding-bottom: 0px">
                                                <a type="button" id="submit_button" onclick="this.classList.add('disabled'); this.closest('form').submit();"
                                                    class="btn btn-success disabled" style="width: 100%"><i
                                                        class="far fa-credit-card mr-2"></i>
                                                    {{ __('Submit Payment') }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </section>
    <!-- END CONTENT -->

    <script>
        //var taxes = ["czk": ["paypal": ["fixed": 10, "percent": 3.4]]];
        const taxes = {
            czk: {
                symbol: "Kč",
                paypal: {fixed: 10, percent: 3.4},
                stripe: {fixed: 6.5, percent: 1.4},
                gopay: {
                    bank_bitcoin: {fixed: 1.5, percent: 1.2},
                    sms: {fixed: 0, percent: 12.1},
                    paysafecard: {fixed: 0, percent: 13}}},
            eur: {
                symbol: "€",
                paypal: {fixed: 0.35, percent: 3.4},
                stripe: {fixed: 0.25, percent: 1.4},
                gopay: {
                    bank_bitcoin: {fixed: 0.06, percent: 1.2},
                    //sms: {fixed: 0, percent: 45.2},
                    paysafecard: {fixed: 0, percent: 13}}
            }};

        const getUrlParameter = (param) => {
            const queryString = window.location.search;
            const urlParams = new URLSearchParams(queryString);
            return urlParams.get(param);
        }
        const voucherCode = getUrlParameter('voucher');
        //if voucherCode not empty, open the modal and fill the input
        if (voucherCode) {
            $(function() {
                $('#redeemVoucherModal').modal('show');
                $('#redeemVoucherCode').val(voucherCode);
            });
        }
        function changeValue(value){
            document.getElementById('slider').value = value;
            document.getElementById('credit_amount').value = value;
            overviewCalc();
        }
        function changeCurrency(){
            if(document.querySelector('input[name="currency"]:checked').value=="eur") document.getElementById('gopay_payment_method_sms').checked = false;

            var taxArray = taxes[document.querySelector('input[name="currency"]:checked').value]["paypal"];
            document.getElementById('paypal_tax').textContent = ((taxArray.fixed!=0)?(taxArray.fixed + taxes[document.querySelector('input[name="currency"]:checked').value].symbol + ((taxArray.percent!=0)?(" + "):"")):"") + ((taxArray.percent!=0)?(taxArray.percent + "%"):"");
            taxArray = taxes[document.querySelector('input[name="currency"]:checked').value]["stripe"];
            document.getElementById('stripe_tax').textContent = ((taxArray.fixed!=0)?(taxArray.fixed + taxes[document.querySelector('input[name="currency"]:checked').value].symbol + ((taxArray.percent!=0)?(" + "):"")):"") + ((taxArray.percent!=0)?(taxArray.percent + "%"):"");
            taxArray = taxes[document.querySelector('input[name="currency"]:checked').value]["gopay"]["bank_bitcoin"];
            var bank_bitcoin = ((taxArray.fixed!=0)?(taxArray.fixed + taxes[document.querySelector('input[name="currency"]:checked').value].symbol + ((taxArray.percent!=0)?(" + "):"")):"") + ((taxArray.percent!=0)?(taxArray.percent + "%"):"");
            taxArray = taxes[document.querySelector('input[name="currency"]:checked').value]["gopay"]["sms"];
            var sms = document.querySelector('input[name="currency"]:checked').value=="eur"?"---":((taxArray.fixed!=0)?(taxArray.fixed + taxes[document.querySelector('input[name="currency"]:checked').value].symbol + ((taxArray.percent!=0)?(" + "):"")):"") + ((taxArray.percent!=0)?(taxArray.percent + "%"):"");
            taxArray = taxes[document.querySelector('input[name="currency"]:checked').value]["gopay"]["paysafecard"];
            var paysafecard = ((taxArray.fixed!=0)?(taxArray.fixed + taxes[document.querySelector('input[name="currency"]:checked').value].symbol + ((taxArray.percent!=0)?(" + "):"")):"") + ((taxArray.percent!=0)?(taxArray.percent + "%"):"");
            document.getElementById('gopay_tax').textContent = bank_bitcoin + "\r\n" + sms + "\r\n" + paysafecard + "\r\n" + bank_bitcoin;
            changePaymentMethod();
        }
        function changePaymentMethod(){
            if(document.querySelector('input[name="payment_method"]:checked').value == "gopay"){
                document.getElementById('gopay_payment_method_sms').setAttribute("required", "required");
                document.getElementById('gopay_payment_method_paysafecard').setAttribute("required", "required");
                document.getElementById('gopay_payment_method_bank_bitcoin').setAttribute("required", "required");
                document.getElementById('gopay_payment_method').style.display = "block";
            }
            else{
                document.getElementById('gopay_payment_method_sms').removeAttribute("required");
                document.getElementById('gopay_payment_method_paysafecard').removeAttribute("required");
                document.getElementById('gopay_payment_method_bank_bitcoin').removeAttribute("required");
                document.getElementById('gopay_payment_method').style.display = "none";
            }

            if(document.querySelector('input[name="currency"]:checked').value=="eur") document.getElementById('gopay_payment_method_sms').setAttribute("disabled", "disabled");
            else document.getElementById('gopay_payment_method_sms').removeAttribute("disabled");
            overviewCalc();
        }
        function overviewCalc(){
            var credits = parseInt(document.getElementById('credit_amount').value);
            var currency = document.querySelector('input[name="currency"]:checked').value;
            if((document.querySelector('input[name="payment_method"]:checked').value!="gopay"||document.querySelector('input[name="gopay_payment_method"]:checked')!=null)&&document.querySelector('input[name="payment_method"]:checked').value!=null){
                document.getElementById('amount').textContent = credits;
                document.getElementById('quantity_discount').textContent = 100-getDiscountByAmount(credits) + "%";

                var subtotal = getDiscountByAmount(credits)*credits/100;
                if(currency=="eur") subtotal = subtotal/{{config("SETTINGS::PAYMENTS:EUR_RATIO")}};
                document.getElementById('subtotal').textContent = subtotal.toFixed(2) + taxes[currency].symbol;

                var taxArray = document.querySelector('input[name="payment_method"]:checked').value!="gopay"?taxes[currency][document.querySelector('input[name="payment_method"]:checked').value]:taxes[currency][document.querySelector('input[name="payment_method"]:checked').value][document.querySelector('input[name="gopay_payment_method"]:checked').value];
                if(taxArray.fixed+taxArray.percent>0) document.getElementById('tax_span').textContent = "(" + ((taxArray.fixed!=0)?(taxArray.fixed + taxes[currency].symbol + ((taxArray.percent!=0)?(" + "):"")):"") + ((taxArray.percent!=0)?(taxArray.percent + "%"):"") + ")";
                else document.getElementById('tax_span').textContent = "";

                var tax = taxArray.fixed + taxArray.percent*subtotal/100;
                document.getElementById('tax').textContent = tax.toFixed(2) + taxes[currency].symbol;
                document.getElementById('total').textContent = (parseFloat(subtotal.toFixed(2)) + parseFloat(tax.toFixed(2))).toFixed(2) + taxes[currency].symbol;
                document.getElementById('submit_button').classList.remove("disabled");
            }
            else{
                document.getElementById('amount').textContent = "";
                document.getElementById('quantity_discount').textContent = "";
                document.getElementById('subtotal').textContent = "";
                document.getElementById('tax').textContent = "";
                document.getElementById('your_discount').textContent = "";
                document.getElementById('total').textContent = "";
                document.getElementById('submit_button').classList.add("disabled");
            }
        }
        function getDiscountByAmount(amount){
            if(amount<50) return 100;
            else if(amount<100) return (100-(amount-50)*3/50);
            else if(amount<200) return (100-(amount-100)*3/100)-3;
            else if(amount<300) return (100-(amount-200)*2/100)-6;
            else if(amount<500) return (100-(amount-300)*2/300)-8;
            else if(amount<1000) return (100-(amount-500)*4/500)-10;
            else return (100-(amount-1000)*6/1000)-14;
        }
        
        //changePaymentMethod();
        document.getElementById("czk").click();
        changeValue(100);
        changePaymentMethod();
    </script>
    <!--<script type="text/javascript">
        var jArray= <?php echo json_encode($quick_select_values); ?>;
        var i = 0;
        jArray.forEach(element => {
            i++;
            if(i>4) document.getElementById('quick_select_button' + element).style.display = "none";
        });
     </script>-->
     <script>
        document.getElementById('credit_amount').addEventListener('change', () => {
            if(document.getElementById('credit_amount').value > {{$max_amount}}){document.getElementById('credit_amount').value = {{$max_amount}};}
            else if(document.getElementById('credit_amount').value < {{$min_amount}}){document.getElementById('credit_amount').value = {{$min_amount}};}
        })
     </script>


@endsection
