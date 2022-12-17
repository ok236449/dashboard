<?php

namespace App\Http\Controllers\Admin;

use App\Events\UserUpdateCreditsEvent;
use App\Http\Controllers\Controller;
use App\Models\InvoiceSettings;
use App\Models\Payment;
use App\Models\ShopProduct;
use App\Models\Settings;
use App\Models\User;
use App\Notifications\InvoiceNotification;
use App\Notifications\ConfirmPaymentNotification;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use LaravelDaily\Invoices\Classes\Buyer;
use LaravelDaily\Invoices\Classes\InvoiceItem;
use LaravelDaily\Invoices\Classes\Party;
use LaravelDaily\Invoices\Invoice;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalHttp\HttpException;
use Stripe\Stripe;
use Symfony\Component\Intl\Currencies;
use GoPay\Definition\Payment\PaymentInstrument;
use GoPay\Definition\Payment\BankSwiftCode;
use GoPay\Definition\Payment\VatRate;
use GoPay\Definition\Payment\PaymentItemType;

require_once base_path() . '/vendor/autoload.php';

class PaymentController extends Controller
{

    /**
     * @return Application|Factory|View
     */
    public function index()
    {
        return view('admin.payments.index')->with([
            'payments' => Payment::paginate(15)
        ]);
    }

    /**
     * @param Request $request
     * @param ShopProduct $shopProduct
     * @return Application|Factory|View
     */
    public function checkOut(Request $request, ShopProduct $shopProduct)
    {
        return view('store.checkout')->with([
            'product'      => $shopProduct,
            'taxvalue'     => $shopProduct->getTaxValue(),
            'taxpercent'   => $shopProduct->getTaxPercent(),
            'total'        => $shopProduct->getTotalPrice()
        ]);
    }

    /**
     * @param Request $request
     * @param ShopProduct $shopProduct
     * @return RedirectResponse
     */
    public function PaypalPay(Request $request, ShopProduct $shopProduct)
    {
        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        $request->body = [
            "intent" => "CAPTURE",
            "purchase_units" => [
                [
                    "reference_id" => uniqid(),
                    "description" => $shopProduct->description,
                    "amount"       => [
                        "value"         => $shopProduct->getTotalPrice(),
                        'currency_code' => strtoupper($shopProduct->currency_code),
                        'breakdown' => [
                            'item_total' =>
                            [
                                'currency_code' => strtoupper($shopProduct->currency_code),
                                'value' => $shopProduct->price,
                            ],
                            'tax_total' =>
                            [
                                'currency_code' => strtoupper($shopProduct->currency_code),
                                'value' => $shopProduct->getTaxValue(),
                            ]
                        ]
                    ]
                ]
            ],
            "application_context" => [
                "cancel_url" => route('payment.Cancel'),
                "return_url" => route('payment.PaypalSuccess', ['product' => $shopProduct->id]),
                'brand_name' =>  config('app.name', 'Laravel'),
                'shipping_preference'  => 'NO_SHIPPING'
            ]


        ];


        try {
            // Call API with your client and get a response for your call
            $response = $this->getPayPalClient()->execute($request);
            return redirect()->away($response->result->links[1]->href);

            // If call returns body in response, you can get the deserialized version from the result attribute of the response
        } catch (HttpException $ex) {
            echo $ex->statusCode;
            dd(json_decode($ex->getMessage()));
        }
    }

    /**
     * @return PayPalHttpClient
     */
    protected function getPayPalClient()
    {
        $environment = env('APP_ENV') == 'local'
            ? new SandboxEnvironment($this->getPaypalClientId(), $this->getPaypalClientSecret())
            : new ProductionEnvironment($this->getPaypalClientId(), $this->getPaypalClientSecret());

        return new PayPalHttpClient($environment);
    }

    /**
     * @return string
     */
    protected function getPaypalClientId()
    {
        return env('APP_ENV') == 'local' ?  config("SETTINGS::PAYMENTS:PAYPAL:SANDBOX_CLIENT_ID") : config("SETTINGS::PAYMENTS:PAYPAL:CLIENT_ID");
    }

    /**
     * @return string
     */
    protected function getPaypalClientSecret()
    {
        return env('APP_ENV') == 'local' ? config("SETTINGS::PAYMENTS:PAYPAL:SANDBOX_SECRET") : config("SETTINGS::PAYMENTS:PAYPAL:SECRET");
    }

    /**
     * @param Request $laravelRequest
     */
    public function PaypalSuccess(Request $laravelRequest)
    {
        /** @var ShopProduct $shopProduct */
        $shopProduct = ShopProduct::findOrFail($laravelRequest->input('product'));

        /** @var User $user */
        $user = Auth::user();

        $request = new OrdersCaptureRequest($laravelRequest->input('token'));
        $request->prefer('return=representation');
        try {
            // Call API with your client and get a response for your call
            $response = $this->getPayPalClient()->execute($request);
            if ($response->statusCode == 201 || $response->statusCode == 200) {

                //update server limit
                if (config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE') !== 0) {
                    if ($user->server_limit < config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE')) {
                        $user->update(['server_limit' => config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE')]);
                    }
                }

                //update User with bought item
                if ($shopProduct->type=="Credits") {
                    $user->increment('credits', $shopProduct->quantity);
                }elseif ($shopProduct->type=="Server slots"){
                    $user->increment('server_limit', $shopProduct->quantity);
                }


                //update role give Referral-reward
                if ($user->role == 'member') {
                    $user->update(['role' => 'client']);

                    if((config("SETTINGS::REFERRAL:MODE") == "commission" || config("SETTINGS::REFERRAL:MODE") == "both") && $shopProduct->type=="Credits"){
                        if($ref_user = DB::table("user_referrals")->where('registered_user_id', '=', $user->id)->first()){
                            $ref_user = User::findOrFail($ref_user->referral_id);
                            $increment = number_format($shopProduct->quantity/100*config("SETTINGS::REFERRAL:PERCENTAGE"),0,"","");
                            $ref_user->increment('credits', $increment);

                            //LOGS REFERRALS IN THE ACTIVITY LOG
                            activity()
                                ->performedOn($user)
                                ->causedBy($ref_user)
                                ->log('gained '. $increment.' '.config("SETTINGS::SYSTEM:CREDITS_DISPLAY_NAME").' for commission-referral of '.$user->name.' (ID:'.$user->id.')');
                        }

                    }

                }

                //store payment
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'payment_id' => $response->result->id,
                    'payment_method' => 'paypal',
                    'type' => $shopProduct->type,
                    'status' => 'paid',
                    'amount' => $shopProduct->quantity,
                    'price' => $shopProduct->price,
                    'tax_value' => $shopProduct->getTaxValue(),
                    'tax_percent' => $shopProduct->getTaxPercent(),
                    'total_price' => $shopProduct->getTotalPrice(),
                    'currency_code' => $shopProduct->currency_code,
                    'shop_item_product_id' => $shopProduct->id,
                ]);


                event(new UserUpdateCreditsEvent($user));

                //only create invoice if SETTINGS::INVOICE:ENABLED is true
                if (config('SETTINGS::INVOICE:ENABLED') == 'true') {
                    $this->createInvoice($user, $payment, 'paid', $shopProduct->currency_code);
                }


                //redirect back to home
                return redirect()->route('home')->with('success', __('Your credit balance has been increased!'));
            }


            // If call returns body in response, you can get the deserialized version from the result attribute of the response
            if (env('APP_ENV') == 'local') {
                dd($response);
            } else {
                abort(500);
            }
        } catch (HttpException $ex) {
            if (env('APP_ENV') == 'local') {
                echo $ex->statusCode;
                dd($ex->getMessage());
            } else {
                abort(422);
            }
        }
    }


    /**
     * @param Request $request
     */
    public function Cancel(Request $request)
    {
        return redirect()->route('store.index')->with('success', 'Payment was Canceled');
    }

    /**
     * @param Request $request
     * @param ShopProduct $shopProduct
     * @return RedirectResponse
     */
    public function StripePay(Request $request, ShopProduct $shopProduct)
    {
        $stripeClient = $this->getStripeClient();


        $request = $stripeClient->checkout->sessions->create([
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => $shopProduct->currency_code,
                        'product_data' => [
                            'name' => $shopProduct->display,
                            'description' => $shopProduct->description,
                        ],
                        'unit_amount_decimal' => round($shopProduct->price * 100, 2),
                    ],
                    'quantity' => 1,
                ],
                [
                    'price_data' => [
                        'currency' => $shopProduct->currency_code,
                        'product_data' => [
                            'name' => __('Product Tax'),
                            'description' => $shopProduct->getTaxPercent() . "%",
                        ],
                        'unit_amount_decimal' => round($shopProduct->getTaxValue(), 2) * 100,
                    ],
                    'quantity' => 1,
                ]
            ],

            'mode' => 'payment',
            "payment_method_types" => str_getcsv(config("SETTINGS::PAYMENTS:STRIPE:METHODS")),
            'success_url' => route('payment.StripeSuccess',  ['product' => $shopProduct->id]) . '&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('payment.Cancel'),
        ]);



        return redirect($request->url, 303);
    }

    /**
     * @param Request $request
     */
    public function StripeSuccess(Request $request)
    {
        /** @var ShopProduct $shopProduct */
        $shopProduct = ShopProduct::findOrFail($request->input('product'));

        /** @var User $user */
        $user = Auth::user();

        $stripeClient = $this->getStripeClient();

        try {
            //get stripe data
            $paymentSession = $stripeClient->checkout->sessions->retrieve($request->input('session_id'));
            $paymentIntent = $stripeClient->paymentIntents->retrieve($paymentSession->payment_intent);

            //get DB entry of this payment ID if existing
            $paymentDbEntry = Payment::where('payment_id', $paymentSession->payment_intent)->count();

            // check if payment is 100% completed and payment does not exist in db already
            if ($paymentSession->status == "complete" && $paymentIntent->status == "succeeded" && $paymentDbEntry == 0) {



                //update server limit
                if (config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE') !== 0) {
                    if ($user->server_limit < config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE')) {
                        $user->update(['server_limit' => config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE')]);
                    }
                }

                //update User with bought item
                if ($shopProduct->type=="Credits") {
                    $user->increment('credits', $shopProduct->quantity);
                }elseif ($shopProduct->type=="Server slots"){
                    $user->increment('server_limit', $shopProduct->quantity);
                }

                //update role give Referral-reward
                if ($user->role == 'member') {
                    $user->update(['role' => 'client']);

                    if((config("SETTINGS::REFERRAL:MODE") == "commission"  || config("SETTINGS::REFERRAL:MODE") == "both") && $shopProduct->type=="Credits"){
                        if($ref_user = DB::table("user_referrals")->where('registered_user_id', '=', $user->id)->first()){
                            $ref_user = User::findOrFail($ref_user->referral_id);
                            $increment = number_format($shopProduct->quantity/100*config("SETTINGS::REFERRAL:PERCENTAGE"),0,"","");
                            $ref_user->increment('credits', $increment);

                            //LOGS REFERRALS IN THE ACTIVITY LOG
                            activity()
                                ->performedOn($user)
                                ->causedBy($ref_user)
                                ->log('gained '. $increment.' '.config("SETTINGS::SYSTEM:CREDITS_DISPLAY_NAME").' for commission-referral of '.$user->name.' (ID:'.$user->id.')');
                        }

                    }

                }

                //store paid payment
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'payment_id' => $paymentSession->payment_intent,
                    'payment_method' => 'stripe',
                    'type' => $shopProduct->type,
                    'status' => 'paid',
                    'amount' => $shopProduct->quantity,
                    'price' => $shopProduct->price,
                    'tax_value' => $shopProduct->getTaxValue(),
                    'total_price' => $shopProduct->getTotalPrice(),
                    'tax_percent' => $shopProduct->getTaxPercent(),
                    'currency_code' => $shopProduct->currency_code,
                    'shop_item_product_id' => $shopProduct->id,
                ]);

                //payment notification
                $user->notify(new ConfirmPaymentNotification($payment));

                event(new UserUpdateCreditsEvent($user));

                //only create invoice if SETTINGS::INVOICE:ENABLED is true
                if (config('SETTINGS::INVOICE:ENABLED') == 'true') {
                    $this->createInvoice($user, $payment, 'paid', $shopProduct->currency_code);
                }

                //redirect back to home
                return redirect()->route('home')->with('success', __('Your credit balance has been increased!'));
            } else {
                if ($paymentIntent->status == "processing") {

                    //store processing payment
                    $payment = Payment::create([
                        'user_id' => $user->id,
                        'payment_id' => $paymentSession->payment_intent,
                        'payment_method' => 'stripe',
                        'type' => $shopProduct->type,
                        'status' => 'processing',
                        'amount' => $shopProduct->quantity,
                        'price' => $shopProduct->price,
                        'tax_value' => $shopProduct->getTaxValue(),
                        'total_price' => $shopProduct->getTotalPrice(),
                        'tax_percent' => $shopProduct->getTaxPercent(),
                        'currency_code' => $shopProduct->currency_code,
                        'shop_item_product_id' => $shopProduct->id,
                    ]);

                    //only create invoice if SETTINGS::INVOICE:ENABLED is true
                    if (config('SETTINGS::INVOICE:ENABLED') == 'true') {
                        $this->createInvoice($user, $payment, 'paid', $shopProduct->currency_code);
                    }

                    //redirect back to home
                    return redirect()->route('home')->with('success', __('Your payment is being processed!'));
                }
                if ($paymentDbEntry == 0 && $paymentIntent->status != "processing") {
                    $stripeClient->paymentIntents->cancel($paymentIntent->id);

                    //redirect back to home
                    return redirect()->route('home')->with('success', __('Your payment has been canceled!'));
                } else {
                    abort(402);
                }
            }
        } catch (HttpException $ex) {
            if (env('APP_ENV') == 'local') {
                echo $ex->statusCode;
                dd($ex->getMessage());
            } else {
                abort(422);
            }
        }
    }

    /**
     * @param Request $request
     */
    protected function handleStripePaymentSuccessHook($paymentIntent)
    {
        try {
            // Get payment db entry
            $payment = Payment::where('payment_id', $paymentIntent->id)->first();
            $user = User::where('id', $payment->user_id)->first();

            if ($paymentIntent->status == 'succeeded' && $payment->status == 'processing') {


                //update server limit
                if (config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE') !== 0) {
                    if ($user->server_limit < config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE')) {
                        $user->update(['server_limit' => config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE')]);
                    }
                }
                //update User with bought item
                if ($shopProduct->type=="Credits") {
                    $user->increment('credits', $shopProduct->quantity);
                }elseif ($shopProduct->type=="Server slots"){
                    $user->increment('server_limit', $shopProduct->quantity);
                }

                //update role give Referral-reward
                if ($user->role == 'member') {
                    $user->update(['role' => 'client']);

                    if((config("SETTINGS::REFERRAL:MODE") == "commission"  || config("SETTINGS::REFERRAL:MODE") == "both")&& $shopProduct->type=="Credits"){
                        if($ref_user = DB::table("user_referrals")->where('registered_user_id', '=', $user->id)->first()){
                            $ref_user = User::findOrFail($ref_user->referral_id);
                            $increment = number_format($shopProduct->quantity/100*config("SETTINGS::REFERRAL:PERCENTAGE"),0,"","");
                            $ref_user->increment('credits', $increment);

                            //LOGS REFERRALS IN THE ACTIVITY LOG
                            activity()
                                ->performedOn($user)
                                ->causedBy($ref_user)
                                ->log('gained '. $increment.' '.config("SETTINGS::SYSTEM:CREDITS_DISPLAY_NAME").' for commission-referral of '.$user->name.' (ID:'.$user->id.')');
                        }

                    }

                }

                //update payment db entry status
                $payment->update(['status' => 'paid']);

                //payment notification
                $user->notify(new ConfirmPaymentNotification($payment));
                event(new UserUpdateCreditsEvent($user));

                //only create invoice if SETTINGS::INVOICE:ENABLED is true
                if (config('SETTINGS::INVOICE:ENABLED') == 'true') {
                    $this->createInvoice($user, $payment, 'paid', strtoupper($paymentIntent->currency));
                }
            }
        } catch (HttpException $ex) {
            abort(422);
        }
    }

    /**
     * @param Request $request
     */
    public function StripeWebhooks(Request $request)
    {
        \Stripe\Stripe::setApiKey($this->getStripeSecret());

        try {
            $payload = @file_get_contents('php://input');
            $sig_header = $request->header('Stripe-Signature');
            $event = null;
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $this->getStripeEndpointSecret()
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload

            abort(400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature

            abort(400);
        }

        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object; // contains a \Stripe\PaymentIntent
                $this->handleStripePaymentSuccessHook($paymentIntent);
                break;
            default:
                echo 'Received unknown event type ' . $event->type;
        }
    }

    /**
     * @return \Stripe\StripeClient
     */
    protected function getStripeClient()
    {
        return new \Stripe\StripeClient($this->getStripeSecret());
    }

    /**
     * @return string
     */
    protected function getStripeSecret()
    {
        return env('APP_ENV') == 'local'
            ?  config("SETTINGS::PAYMENTS:STRIPE:TEST_SECRET")
            :  config("SETTINGS::PAYMENTS:STRIPE:SECRET");
    }

    /**
     * @return string
     */
    protected function getStripeEndpointSecret()
    {
        return env('APP_ENV') == 'local'
            ?  config("SETTINGS::PAYMENTS:STRIPE:ENDPOINT_TEST_SECRET")
            :  config("SETTINGS::PAYMENTS:STRIPE:ENDPOINT_SECRET");
    }


    /*public function GopayPay(Request $request, ShopProduct $shopProduct)
    {
        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        $request->body = [
            "intent" => "CAPTURE",
            "purchase_units" => [
                [
                    "reference_id" => uniqid(),
                    "description" => $shopProduct->description,
                    "amount"       => [
                        "value"         => $shopProduct->getTotalPrice(),
                        'currency_code' => strtoupper($shopProduct->currency_code),
                        'breakdown' => [
                            'item_total' =>
                            [
                                'currency_code' => strtoupper($shopProduct->currency_code),
                                'value' => $shopProduct->price,
                            ],
                            'tax_total' =>
                            [
                                'currency_code' => strtoupper($shopProduct->currency_code),
                                'value' => $shopProduct->getTaxValue(),
                            ]
                        ]
                    ]
                ]
            ],
            "application_context" => [
                "cancel_url" => route('payment.Cancel'),
                "return_url" => route('payment.PaypalSuccess', ['product' => $shopProduct->id]),
                'brand_name' =>  config('app.name', 'Laravel'),
                'shipping_preference'  => 'NO_SHIPPING'
            ]


        ];


        try {
            // Call API with your client and get a response for your call
            $response = $this->getPayPalClient()->execute($request);
            return redirect()->away($response->result->links[1]->href);

            // If call returns body in response, you can get the deserialized version from the result attribute of the response
        } catch (HttpException $ex) {
            echo $ex->statusCode;
            dd(json_decode($ex->getMessage()));
        }
    }*/

    public function gopay()
    {
        return $gopay = \GoPay\payments([
            'goid' => config('SETTINGS::PAYMENTS:GOPAY:GOID'),
            'clientId' => env('APP_ENV')=='local'?config('SETTINGS::PAYMENTS:GOPAY:TEST_CLIENT_ID'):config('SETTINGS::PAYMENTS:GOPAY:CLIENT_ID'),
            'clientSecret' => env('APP_ENV')=='local'?config('SETTINGS::PAYMENTS:GOPAY:TEST_CLIENT_SECRET'):config('SETTINGS::PAYMENTS:GOPAY:CLIENT_SECRET'),
            'gatewayUrl' => env('APP_ENV')=='local'?"https://gw.sandbox.gopay.com/":"https://gate.gopay.cz/",
            'scope' => \GoPay\Definition\TokenScope::ALL,
            'language' => strtoupper(session()->get('language')),
            'timeout' => 30
        ]);
    }
    public function GopayPay(Request $request, ShopProduct $shopProduct)
    {
        $gopay =  $this->gopay();
        $response = $gopay->createPayment([
            'payer' => [
                    //'default_payment_instrument' => PaymentInstrument::MPAYMENT,
                    'allowed_payment_instruments' => [PaymentInstrument::BANK_ACCOUNT, PaymentInstrument::MPAYMENT, PaymentInstrument::BITCOIN, PaymentInstrument::PAYSAFECARD],
                    //'default_swift' => BankSwiftCode::FIO_BANKA,
                    //'allowed_swifts' => [BankSwiftCode::FIO_BANKA, BankSwiftCode::MBANK, BankSwiftCode::CSOB, BankSwiftCode::CESKA_SPORITELNA, BankSwiftCode::KOMERCNI_BANKA, BankSwiftCode::RAIFFEISENBANK, BankSwiftCode::UNICREDIT_BANK_CZ, BankSwiftCode::POSTOVA_BANKA],
                    /*'contact' => ['first_name' => 'Zbynek',
                            'last_name' => 'Zak',
                            'email' => 'test@test.cz',
                            'phone_number' => '+420777456123',
                            'city' => 'C.Budejovice',
                            'street' => 'Plana 67',
                            'postal_code' => '373 01',
                            'country_code' => 'CZE'
                    ]*/
            ],
            'amount' => $shopProduct->getTotalPrice()*100,
            'currency' => strtoupper($shopProduct->currency_code),
            //'order_number' => '001',
            'order_description' => $shopProduct->description,
            'items' => [
                [
                    'type' => PaymentItemType::ITEM,
                    'name' => $shopProduct->display,
                    'amount' => $shopProduct->price*100,
                    'count' => 1,
                    'vat_rate' => 0
                ],
                [
                    'type' => PaymentItemType::ITEM,
                    'name' => $shopProduct->getTaxPercent() . "%",
                    'amount' => $shopProduct->getTaxValue()*100,
                    'count' => 1,
                    'vat_rate' => 0
                ]],
            'callback' => [
                    //"cancel_url" => route('payment.Cancel'),
                    'return_url' => route('payment.GopayReturn', ['product' => $shopProduct->id]),
                    'notification_url' => route('payment.GopayReturn', ['product' => $shopProduct->id])
            ],
            'lang' => strtoupper(session()->get('language'))
        ]);
        if($response->hasSucceed()){
            //dd($response);
            /*$realPayment->payment_token = $response->json['id'];
            $realPayment->payment_url = $response->json['gw_url'];
            $realPayment->state = $response->json['state'];
            $realPayment->save();*/
            return redirect()->away($response->json['gw_url']);
        }
        else dd($response);
        /*else{
            $realPayment->state = "Creation failed";
            $realPayment->save();
            $fail_messages = [
                __('We are sorry to inform you your purchase wasn´t successful.'),
                __('Our system was unable to create a valid GoPay session.'),
                __('Please try again later. If this happens again, please contact support immediately.'),
                __('Thanks for understanding.')
            ];
            session()->put('fail_messages', $fail_messages);
            return redirect('failed');
        }*/
    }

    public function GopayReturn(Request $request)
    {
        /** @var ShopProduct $shopProduct */
        $gopay = $this->gopay();
        $shopProduct = ShopProduct::findOrFail($request->input('product'));
        /** @var User $user */
        $user = Auth::user();

        try {
            // Call API with your client and get a response for your call

            $paymentStatus = $gopay->getStatus($request->id);

            //get DB entry of this payment ID if existing
            $paymentDbEntry = Payment::where('payment_id', $request->id)->count();

            // check if payment is 100% completed and payment does not exist in db already
            if ($paymentStatus->json['state'] == "PAID" && $paymentDbEntry == 0) {

                //update server limit
                if (config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE') !== 0) {
                    if ($user->server_limit < config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE')) {
                        $user->update(['server_limit' => config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE')]);
                    }
                }

                //update User with bought item
                if ($shopProduct->type=="Credits") {
                    $user->increment('credits', $shopProduct->quantity);
                }elseif ($shopProduct->type=="Server slots"){
                    $user->increment('server_limit', $shopProduct->quantity);
                }


                //update role give Referral-reward
                if ($user->role == 'member') {
                    $user->update(['role' => 'client']);

                    if((config("SETTINGS::REFERRAL:MODE") == "commission" || config("SETTINGS::REFERRAL:MODE") == "both") && $shopProduct->type=="Credits"){
                        if($ref_user = DB::table("user_referrals")->where('registered_user_id', '=', $user->id)->first()){
                            $ref_user = User::findOrFail($ref_user->referral_id);
                            $increment = number_format($shopProduct->quantity/100*config("SETTINGS::REFERRAL:PERCENTAGE"),0,"","");
                            $ref_user->increment('credits', $increment);

                            //LOGS REFERRALS IN THE ACTIVITY LOG
                            activity()
                                ->performedOn($user)
                                ->causedBy($ref_user)
                                ->log('gained '. $increment.' '.config("SETTINGS::SYSTEM:CREDITS_DISPLAY_NAME").' for commission-referral of '.$user->name.' (ID:'.$user->id.')');
                        }

                    }

                }

                //store payment
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'payment_id' => $request->id,
                    'payment_method' => 'gopay',
                    'type' => $shopProduct->type,
                    'status' => 'paid',
                    'amount' => $shopProduct->quantity,
                    'price' => $shopProduct->price,
                    'tax_value' => $shopProduct->getTaxValue(),
                    'tax_percent' => $shopProduct->getTaxPercent(),
                    'total_price' => $shopProduct->getTotalPrice(),
                    'currency_code' => $shopProduct->currency_code,
                    'shop_item_product_id' => $shopProduct->id,
                ]);


                event(new UserUpdateCreditsEvent($user));

                //only create invoice if SETTINGS::INVOICE:ENABLED is true
                if (config('SETTINGS::INVOICE:ENABLED') == 'true') {
                    $this->createInvoice($user, $payment, 'paid', $shopProduct->currency_code);
                }


                //redirect back to home
                return redirect()->route('home')->with('success', __('Your credit balance has been increased!'));
            }
            else {
                //redirect back to home
                return redirect()->route('home')->with('success', __('Your payment has been canceled!'));
            }


        } catch (HttpException $ex) {
            if (env('APP_ENV') == 'local') {
                echo $ex->statusCode;
                dd($ex->getMessage());
            } else {
                abort(422);
            }
        }
    }

    /**
     * @return PayPalHttpClient
     */
    /*protected function getPayPalClient()
    {
        $environment = env('APP_ENV') == 'local'
            ? new SandboxEnvironment($this->getPaypalClientId(), $this->getPaypalClientSecret())
            : new ProductionEnvironment($this->getPaypalClientId(), $this->getPaypalClientSecret());

        return new PayPalHttpClient($environment);
    }*/

    /**
     * @return string
     */
    /*protected function getPaypalClientId()
    {
        return env('APP_ENV') == 'local' ?  config("SETTINGS::PAYMENTS:PAYPAL:SANDBOX_CLIENT_ID") : config("SETTINGS::PAYMENTS:PAYPAL:CLIENT_ID");
    }*/

    /**
     * @return string
     */
    /*protected function getPaypalClientSecret()
    {
        return env('APP_ENV') == 'local' ? config("SETTINGS::PAYMENTS:PAYPAL:SANDBOX_SECRET") : config("SETTINGS::PAYMENTS:PAYPAL:SECRET");
    }*/

    /**
     * @param Request $laravelRequest
     */
    /*public function PaypalSuccess(Request $laravelRequest)
    {*/
        /** @var ShopProduct $shopProduct */
        /*$shopProduct = ShopProduct::findOrFail($laravelRequest->input('product'));
*/
        /** @var User $user */
        /*$user = Auth::user();

        $request = new OrdersCaptureRequest($laravelRequest->input('token'));
        $request->prefer('return=representation');
        try {
            // Call API with your client and get a response for your call
            $response = $this->getPayPalClient()->execute($request);
            if ($response->statusCode == 201 || $response->statusCode == 200) {

                //update server limit
                if (config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE') !== 0) {
                    if ($user->server_limit < config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE')) {
                        $user->update(['server_limit' => config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE')]);
                    }
                }

                //update User with bought item
                if ($shopProduct->type=="Credits") {
                    $user->increment('credits', $shopProduct->quantity);
                }elseif ($shopProduct->type=="Server slots"){
                    $user->increment('server_limit', $shopProduct->quantity);
                }


                //update role give Referral-reward
                if ($user->role == 'member') {
                    $user->update(['role' => 'client']);

                    if((config("SETTINGS::REFERRAL:MODE") == "commission" || config("SETTINGS::REFERRAL:MODE") == "both") && $shopProduct->type=="Credits"){
                        if($ref_user = DB::table("user_referrals")->where('registered_user_id', '=', $user->id)->first()){
                            $ref_user = User::findOrFail($ref_user->referral_id);
                            $increment = number_format($shopProduct->quantity/100*config("SETTINGS::REFERRAL:PERCENTAGE"),0,"","");
                            $ref_user->increment('credits', $increment);

                            //LOGS REFERRALS IN THE ACTIVITY LOG
                            activity()
                                ->performedOn($user)
                                ->causedBy($ref_user)
                                ->log('gained '. $increment.' '.config("SETTINGS::SYSTEM:CREDITS_DISPLAY_NAME").' for commission-referral of '.$user->name.' (ID:'.$user->id.')');
                        }

                    }

                }

                //store payment
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'payment_id' => $response->result->id,
                    'payment_method' => 'paypal',
                    'type' => $shopProduct->type,
                    'status' => 'paid',
                    'amount' => $shopProduct->quantity,
                    'price' => $shopProduct->price,
                    'tax_value' => $shopProduct->getTaxValue(),
                    'tax_percent' => $shopProduct->getTaxPercent(),
                    'total_price' => $shopProduct->getTotalPrice(),
                    'currency_code' => $shopProduct->currency_code,
                    'shop_item_product_id' => $shopProduct->id,
                ]);


                event(new UserUpdateCreditsEvent($user));

                //only create invoice if SETTINGS::INVOICE:ENABLED is true
                if (config('SETTINGS::INVOICE:ENABLED') == 'true') {
                    $this->createInvoice($user, $payment, 'paid', $shopProduct->currency_code);
                }


                //redirect back to home
                return redirect()->route('home')->with('success', __('Your credit balance has been increased!'));
            }


            // If call returns body in response, you can get the deserialized version from the result attribute of the response
            if (env('APP_ENV') == 'local') {
                dd($response);
            } else {
                abort(500);
            }
        } catch (HttpException $ex) {
            if (env('APP_ENV') == 'local') {
                echo $ex->statusCode;
                dd($ex->getMessage());
            } else {
                abort(422);
            }
        }
    }*/


    protected function createInvoice($user, $payment, $paymentStatus, $currencyCode)
    {
        $shopProduct = ShopProduct::where('id', $payment->shop_item_product_id)->first();
        //create invoice
        $lastInvoiceID = \App\Models\Invoice::where("invoice_name", "like", "%" . now()->format('mY') . "%")->count("id");
        $newInvoiceID = $lastInvoiceID + 1;
        $logoPath = storage_path('app/public/logo.png');

        $seller = new Party([
            'name' => config("SETTINGS::INVOICE:COMPANY_NAME"),
            'phone' => config("SETTINGS::INVOICE:COMPANY_PHONE"),
            'address' => config("SETTINGS::INVOICE:COMPANY_ADDRESS"),
            'vat' => config("SETTINGS::INVOICE:COMPANY_VAT"),
            'custom_fields' => [
                'E-Mail' => config("SETTINGS::INVOICE:COMPANY_MAIL"),
                "Web" => config("SETTINGS::INVOICE:COMPANY_WEBSITE")
            ],
        ]);


        $customer = new Buyer([
            'name' => $user->name,
            'custom_fields' => [
                'E-Mail' => $user->email,
                'Client ID' => $user->id,
            ],
        ]);
        $item = (new InvoiceItem())
            ->title($shopProduct->description)
            ->pricePerUnit($shopProduct->price);

        $notes = [
            __("Payment method") . ": " . $payment->payment_method,
        ];
        $notes = implode("<br>", $notes);


        $invoice = Invoice::make()
            ->template('controlpanel')
            ->name(__("Invoice"))
            ->buyer($customer)
            ->seller($seller)
            ->discountByPercent(0)
            ->taxRate(floatval($shopProduct->getTaxPercent()))
            ->shipping(0)
            ->addItem($item)
            ->status(__($paymentStatus))
            ->series(now()->format('mY'))
            ->delimiter("-")
            ->sequence($newInvoiceID)
            ->serialNumberFormat(config("SETTINGS::INVOICE:PREFIX") . '{DELIMITER}{SERIES}{SEQUENCE}')
            ->currencyCode($currencyCode)
            ->currencySymbol(Currencies::getSymbol($currencyCode))
            ->notes($notes);

        if (file_exists($logoPath)) {
            $invoice->logo($logoPath);
        }

        //Save the invoice in "storage\app\invoice\USER_ID\YEAR"
        $invoice->filename = $invoice->getSerialNumber() . '.pdf';
        $invoice->render();
        Storage::disk("local")->put("invoice/" . $user->id . "/" . now()->format('Y') . "/" . $invoice->filename, $invoice->output);


        \App\Models\Invoice::create([
            'invoice_user' => $user->id,
            'invoice_name' => $invoice->getSerialNumber(),
            'payment_id' => $payment->payment_id,
        ]);

        //Send Invoice per Mail
        $user->notify(new InvoiceNotification($invoice, $user, $payment));
    }

    /**
     * @return JsonResponse|mixed
     * @throws Exception
     */
    public function dataTable()
    {
        $query = Payment::with('user');

        return datatables($query)
            ->editColumn('user', function (Payment $payment) {
                return $payment->user->name;
            })
            ->editColumn('price', function (Payment $payment) {
                return $payment->formatToCurrency($payment->price);
            })
            ->editColumn('tax_value', function (Payment $payment) {
                return $payment->formatToCurrency($payment->tax_value);
            })
            ->editColumn('tax_percent', function (Payment $payment) {
                return $payment->tax_percent . ' %';
            })
            ->editColumn('total_price', function (Payment $payment) {
                return $payment->formatToCurrency($payment->total_price);
            })

            ->editColumn('created_at', function (Payment $payment) {
                return $payment->created_at ? $payment->created_at->diffForHumans() : '';
            })
            ->addColumn('actions', function (Payment $payment) {
                return '<a data-content="' . __("Download") . '" data-toggle="popover" data-trigger="hover" data-placement="top"  href="' . route('admin.invoices.downloadSingleInvoice', "id=" . $payment->payment_id) . '" class="btn btn-sm text-white btn-info mr-1"><i class="fas fa-file-download"></i></a>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }
}
