<?php

namespace App\Http\Controllers\Admin;

use App\Events\UserUpdateCreditsEvent;
use App\Http\Controllers\Controller;
use App\Models\InvoiceSettings;
use App\Models\PartnerDiscount;
use App\Models\Payment;
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
use GoPay\Definition\Payment\PaymentItemType;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

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

    public function Pay(Request $request)
    {
        if(!$request->credit_amount||!$request->currency||!$request->payment_method||
            $request->credit_amount<25||$request->credit_amount>2000||
            !($request->currency=="czk"||$request->currency=="eur")||
            !($request->payment_method=="paypal"||$request->payment_method=="stripe"||$request->payment_method=="gopay")||
            ($request->payment_method=="gopay"&&!($request->gopay_payment_method=="bank_bitcoin"||$request->gopay_payment_method=="sms"||$request->gopay_payment_method=="paysafecard"))||
            $this->AmountTooSmall($request->credit_amount, $request->currency, $request->payment_method, $request->gopay_payment_method)
        ) return redirect()->route('home')->with('error', __('There was a problem with your input. Please try again. If the issue persists, please contact support.'));

        if($request->payment_method=="paypal") return $this->PaypalPay($request->credit_amount, $request->currency);
        elseif($request->payment_method=="stripe") return $this->StripePay($request->credit_amount, $request->currency);
        elseif($request->payment_method=="gopay") return $this->GopayPay($request->credit_amount, $request->currency, $request->gopay_payment_method);
    }

    public function getDiscountByAmount($amount)
    {
        /*if($amount<100) return 100;
        else if($amount<200) return 95;
        else if($amount<500) return 90;
        else if($amount<1000) return 85;
        else return 80;*/

        /*if($amount<50) return 100;
        else if($amount<100) return (100-($amount-50)*3/50);
        else if($amount<200) return (100-($amount-100)*3/100)-3;
        else if($amount<300) return (100-($amount-200)*2/100)-6;
        else if($amount<500) return (100-($amount-300)*2/200)-8;
        else if($amount<1000) return (100-($amount-500)*4/500)-10;
        else return (100-($amount-1000)*6/1000)-14;*/

        if($amount<100) return 100;
        else if($amount<200) return (100-($amount-100)*3/100);
        else if($amount<300) return (100-($amount-200)*1.5/100)-3;
        else if($amount<500) return (100-($amount-300)*1.5/200)-4.5;
        else if($amount<1000) return (100-($amount-500)*2/500)-6;
        else return (100-($amount-1000)*2/1000)-8;
    }

    public function getTaxesArray(){
        return [
            "czk" => [
                "paypal" => ["fixed" => 10, "percent" => 3.4, "minimum" => 0],
                "stripe" => ["fixed" => 6.5, "percent" => 1.4, "minimum" => 15],
                "gopay" => [
                    "bank_bitcoin" => ["fixed" => 1.5, "percent" => 1.2, "minimum" => 0],
                    "sms" => ["fixed" => 0, "percent" => 12.1, "minimum" => 0],
                    "paysafecard" => ["fixed" => 0, "percent" => 13, "minimum" => 0]]],
            "eur" => [
                "paypal" => ["fixed" => 0.35, "percent" => 3.4, "minimum" => 0],
                "stripe" => ["fixed" => 0.25, "percent" => 1.4, "minimum" => 0.5],
                "gopay" => [
                    "bank_bitcoin" => ["fixed" => 0.06, "percent" => 1.2, "minimum" => 0],
                    "sms" => ["fixed" => 0, "percent" => 45.2, "minimum" => 0],
                    "paysafecard" => ["fixed" => 0, "percent" => 13, "minimum" => 0]]]
        ];
    }

    public function getTaxes($amount, $currency, $payment_method, $gopay_payment_method=null)
    {
        $taxes = $this->getTaxesArray();
        $taxArray = $payment_method!="gopay"?$taxes[$currency][$payment_method]:$taxes[$currency][$payment_method][$gopay_payment_method];
        return ($taxArray["fixed"] + $amount) * 100 / (100 - $taxArray["percent"]) - $amount;
    }

    public function AmountTooSmall($amount, $currency, $payment_method, $gopay_payment_method=null)
    {
        $taxes = $this->getTaxesArray();
        $taxArray = $payment_method!="gopay"?$taxes[$currency][$payment_method]:$taxes[$currency][$payment_method][$gopay_payment_method];
        $price = ($this->getDiscountByAmount($amount)*$amount/100);
        if($currency=="eur") $price = $price/config('SETTINGS::PAYMENTS:EUR_RATIO');
        $subtotal = ($price)*(100-PartnerDiscount::getDiscount())/100;
        return $subtotal + $this->getTaxes($subtotal, $currency, $payment_method, $gopay_payment_method) <= $taxArray['minimum'];
    }

    public function PaypalPay($amount, $currency)
    {
        $subtotal = ($this->getDiscountByAmount($amount)*$amount/100)*(100-PartnerDiscount::getDiscount())/100;
        if($currency=="eur") $subtotal = $subtotal/config("SETTINGS::PAYMENTS:EUR_RATIO");
        $tax = $this->getTaxes($subtotal, $currency, "paypal");
        $total = Round(Round($subtotal, 2) + Round($tax, 2), 2);


        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        $request->body = [
            "intent" => "CAPTURE",
            "purchase_units" => [
                [
                    "reference_id" => uniqid(),
                    "description" => $amount . " " . CREDITS_DISPLAY_NAME,
                    "amount"       => [
                        "value"         => $total,
                        'currency_code' => strtoupper($currency),
                        'breakdown' => [
                            'item_total' =>
                            [
                                'currency_code' => strtoupper($currency),
                                'value' => round($subtotal, 2),
                            ],
                            'tax_total' =>
                            [
                                'currency_code' => strtoupper($currency),
                                'value' => round($tax, 2),
                            ]
                        ]
                    ]
                ]
            ],
            "application_context" => [
                "cancel_url" => route('payment.Cancel'),
                "return_url" => route('payment.PaypalSuccess', ['user_id' => Auth::user()->id]),
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
        $user = User::where('id', $laravelRequest->user_id)->first();

        $request = new OrdersCaptureRequest($laravelRequest->input('token'));
        $request->prefer('return=representation');
        try {
            // Call API with your client and get a response for your call
            $response = $this->getPayPalClient()->execute($request);
            $amount = str_replace(" " . CREDITS_DISPLAY_NAME, "", $response->result->purchase_units[0]->description);
            $price = strtolower($response->result->purchase_units[0]->amount->currency_code)=='eur'?$this->getDiscountByAmount($amount)*$amount/100/config("SETTINGS::PAYMENTS:EUR_RATIO"):$this->getDiscountByAmount($amount)*$amount/100;
            $subtotal = ($price)*(100-PartnerDiscount::getDiscount($laravelRequest->user_id))/100;
            $tax = $this->getTaxes($subtotal, strtolower($response->result->purchase_units[0]->amount->currency_code), "paypal");

            if (($response->statusCode == 201 || $response->statusCode == 200)) {

                //update server limit
                if (config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE') !== 0) {
                    if ($user->server_limit < config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE')) {
                        $user->update(['server_limit' => config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE')]);
                    }
                }

                //update User with bought item
                $user->increment('credits', $amount);

                //give referral commission always
                if((config("SETTINGS::REFERRAL:MODE") == "commission" || config("SETTINGS::REFERRAL:MODE") == "both") && config("SETTINGS::REFERRAL::ALWAYS_GIVE_COMMISSION") == "true"){
                    if($ref_user = DB::table("user_referrals")->where('registered_user_id', '=', $user->id)->first()){
                        $ref_user = User::findOrFail($ref_user->referral_id);
                        $increment = number_format($amount/100*(PartnerDiscount::getCommission($ref_user->id))/100,0,"","");
                        $ref_user->increment('credits', $increment);

                        //LOGS REFERRALS IN THE ACTIVITY LOG
                        activity()
                            ->performedOn($user)
                            ->causedBy($ref_user)
                            ->log('gained '. $increment.' '.config("SETTINGS::SYSTEM:CREDITS_DISPLAY_NAME").' for commission-referral of '.$user->name.' (ID:'.$user->id.')');
                    }

                }

                //update role give Referral-reward
                if ($user->role == 'member') {
                    $user->update(['role' => 'client']);

                    //give referral commission only on first purchase
                    if((config("SETTINGS::REFERRAL:MODE") == "commission" || config("SETTINGS::REFERRAL:MODE") == "both") && config("SETTINGS::REFERRAL::ALWAYS_GIVE_COMMISSION") == "false"){
                        if($ref_user = DB::table("user_referrals")->where('registered_user_id', '=', $user->id)->first()){
                            $ref_user = User::findOrFail($ref_user->referral_id);
                            $increment = number_format($amount/100*(PartnerDiscount::getCommission($ref_user->id))/100,0,"","");
                            $ref_user->increment('credits', $increment);

                            //LOGS REFERRALS IN THE ACTIVITY LOG
                            activity()
                                ->performedOn($user)
                                ->causedBy($ref_user)
                                ->log('gained '. $increment.' '.config("SETTINGS::SYSTEM:CREDITS_DISPLAY_NAME").' for commission-referral of '.$user->name.' (ID:'.$user->id.')');
                        }

                    }

                    //give client discord role
                    $discord = Socialite::driver('discord')->user();
                    $botToken = config('SETTINGS::DISCORD:BOT_TOKEN');
                    $guildId = config('SETTINGS::DISCORD:GUILD_ID');
                    if (! empty($clientRoleId)&&$discord) {
                        $response = Http::withHeaders(
                            [
                                'Authorization' => 'Bot '.$botToken,
                                'Content-Type' => 'application/json',
                            ]
                        )->put(
                            "https://discord.com/api/guilds/{$guildId}/members/{$discord->id}/roles/{$clientRoleId}",
                            ['access_token' => $discord->token]
                        );
                    }

                }

                //store payment
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'payment_id' => $response->result->id,
                    'payment_method' => 'paypal',
                    'type' => 'Credits',
                    'status' => 'paid',
                    'amount' => $amount,
                    'price' => $price,
                    'tax_value' => $tax,
                    'total_price' => $subtotal + $tax,
                    'tax_percent' => config("SETTINGS::PAYMENTS:SALES_TAX")<0?0:config("SETTINGS::PAYMENTS:SALES_TAX"),
                    'currency_code' => $response->result->purchase_units[0]->amount->currency_code,
                    'shop_item_product_id' => 'none',
                ]);

                event(new UserUpdateCreditsEvent($user));

                //only create invoice if SETTINGS::INVOICE:ENABLED is true
                if (config('SETTINGS::INVOICE:ENABLED') == 'true') {
                    $this->createInvoice($user, $payment, 'paid', $response->result->purchase_units[0]->amount->currency_code);
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

    public function StripePay($amount, $currency)
    {
        $subtotal = ($this->getDiscountByAmount($amount)*$amount/100)*(100-PartnerDiscount::getDiscount())/100;
        if($currency=="eur") $subtotal = $subtotal/config("SETTINGS::PAYMENTS:EUR_RATIO");
        $tax = $this->getTaxes($subtotal, $currency, "stripe");

        $stripeClient = $this->getStripeClient();

        $request = $stripeClient->checkout->sessions->create([
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => strtoupper($currency),
                        'product_data' => [
                            'name' => $amount . " " . CREDITS_DISPLAY_NAME,
                            'description' => $amount . " " . CREDITS_DISPLAY_NAME,
                        ],
                        'unit_amount_decimal' => round($subtotal * 100),
                    ],
                    'quantity' => 1,
                ],
                [
                    'price_data' => [
                        'currency' => strtoupper($currency),
                        'product_data' => [
                            'name' => __('Tax'),
                            'description' => __('Tax'),
                        ],
                        'unit_amount_decimal' => round($tax * 100),
                    ],
                    'quantity' => 1,
                ]
            ],

            'mode' => 'payment',
            "payment_method_types" => str_getcsv(config("SETTINGS::PAYMENTS:STRIPE:METHODS")),
            'success_url' => route('payment.StripeSuccess',  ['amount' => $amount, 'user_id' => Auth::user()->id]) . '&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('payment.Cancel'),
        ]);



        return redirect($request->url, 303);
    }

    /**
     * @param Request $request
     */
    public function StripeSuccess(Request $request)
    {
        $subtotal = 0;
        $tax = 0;
        $user = User::where('id', $request->user_id)->first();

        $stripeClient = $this->getStripeClient();
        try {
            //get stripe data
            $paymentSession = $stripeClient->checkout->sessions->retrieve($request->input('session_id'));
            $paymentIntent = $stripeClient->paymentIntents->retrieve($paymentSession->payment_intent);
            $price = $paymentSession->currency=='eur'?$this->getDiscountByAmount($request->amount)*$request->amount/100/config("SETTINGS::PAYMENTS:EUR_RATIO"):$this->getDiscountByAmount($request->amount)*$request->amount/100;
            $subtotal = ($price)*(100-PartnerDiscount::getDiscount($request->user_id))/100;
            $tax = $this->getTaxes($subtotal, $paymentSession->currency, "stripe");

            //dd($paymentSession->amount_total);
            if(abs($subtotal+$tax-$paymentSession->amount_total/100)>=0.05) return redirect()->route('home')->with('error', __('There was a problem verifying your payment. Please contact support.'));
            else $amount = $request->amount;

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
                $user->increment('credits', $amount);

                //update role give Referral-reward
                if ($user->role == 'member') {
                    $user->update(['role' => 'client']);

                    if((config("SETTINGS::REFERRAL:MODE") == "commission"  || config("SETTINGS::REFERRAL:MODE") == "both")){
                        if($ref_user = DB::table("user_referrals")->where('registered_user_id', '=', $user->id)->first()){
                            $ref_user = User::findOrFail($ref_user->referral_id);
                            $increment = number_format($amount/100*config("SETTINGS::REFERRAL:PERCENTAGE"),0,"","");
                            $ref_user->increment('credits', $increment);

                            //LOGS REFERRALS IN THE ACTIVITY LOG
                            activity()
                                ->performedOn($user)
                                ->causedBy($ref_user)
                                ->log('gained '. $increment.' '.config("SETTINGS::SYSTEM:CREDITS_DISPLAY_NAME").' for commission-referral of '.$user->name.' (ID:'.$user->id.')');
                        }

                    }

                    //give client discord role
                    $discord = Socialite::driver('discord')->user();
                    $botToken = config('SETTINGS::DISCORD:BOT_TOKEN');
                    $guildId = config('SETTINGS::DISCORD:GUILD_ID');
                    if (! empty($clientRoleId)&&$discord) {
                        $response = Http::withHeaders(
                            [
                                'Authorization' => 'Bot '.$botToken,
                                'Content-Type' => 'application/json',
                            ]
                        )->put(
                            "https://discord.com/api/guilds/{$guildId}/members/{$discord->id}/roles/{$clientRoleId}",
                            ['access_token' => $discord->token]
                        );
                    }

                }

                //store paid payment
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'payment_id' => $paymentSession->payment_intent,
                    'payment_method' => 'stripe',
                    'type' => 'Credits',
                    'status' => 'paid',
                    'amount' => $amount,
                    'price' => $price,
                    'tax_value' => $tax,
                    'total_price' => $subtotal + $tax,
                    'tax_percent' => config("SETTINGS::PAYMENTS:SALES_TAX")<0?0:config("SETTINGS::PAYMENTS:SALES_TAX"),
                    'currency_code' => strtoupper($paymentSession->currency),
                    'shop_item_product_id' => 'none',
                ]);

                //payment notification
                $user->notify(new ConfirmPaymentNotification($payment));

                event(new UserUpdateCreditsEvent($user));

                //only create invoice if SETTINGS::INVOICE:ENABLED is true
                if (config('SETTINGS::INVOICE:ENABLED') == 'true') {
                    $this->createInvoice($user, $payment, 'paid', strtoupper($paymentSession->currency));
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
                        'type' => 'Credits',
                        'status' => 'processing',
                        'amount' => $amount,
                        'price' => $this->getDiscountByAmount($request->amount)*$request->amount/100,
                        'tax_value' => $tax,
                        'total_price' => $subtotal + $tax,
                        'tax_percent' => config("SETTINGS::PAYMENTS:SALES_TAX")<0?0:config("SETTINGS::PAYMENTS:SALES_TAX"),
                        'currency_code' => strtoupper($paymentSession->currency),
                        'shop_item_product_id' => 'none',
                    ]);

                    //only create invoice if SETTINGS::INVOICE:ENABLED is true
                    if (config('SETTINGS::INVOICE:ENABLED') == 'true') {
                        $this->createInvoice($user, $payment, 'paid', strtoupper($paymentSession->currency));
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

            $price = strtolower($payment->currency_code)=='eur'?$this->getDiscountByAmount($payment->amount)*$payment->amount/100/config("SETTINGS::PAYMENTS:EUR_RATIO"):$this->getDiscountByAmount($payment->amount)*$payment->amount/100;
            $subtotal = ($price)*(100-PartnerDiscount::getDiscount($payment->user_id))/100;
            $tax = $this->getTaxes($subtotal, strtolower($payment->currency_code), "stripe");

            if ($paymentIntent->status == 'succeeded' && $payment->status == 'processing') {

                //update server limit
                if (config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE') !== 0) {
                    if ($user->server_limit < config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE')) {
                        $user->update(['server_limit' => config('SETTINGS::USER:SERVER_LIMIT_AFTER_IRL_PURCHASE')]);
                    }
                }
                //update User with bought item
                $user->increment('credits', $payment->amount);

                //update role give Referral-reward
                if ($user->role == 'member') {
                    $user->update(['role' => 'client']);

                    if((config("SETTINGS::REFERRAL:MODE") == "commission"  || config("SETTINGS::REFERRAL:MODE") == "both")){
                        if($ref_user = DB::table("user_referrals")->where('registered_user_id', '=', $user->id)->first()){
                            $ref_user = User::findOrFail($ref_user->referral_id);
                            $increment = number_format($payment->amount/100*config("SETTINGS::REFERRAL:PERCENTAGE"),0,"","");
                            $ref_user->increment('credits', $increment);

                            //LOGS REFERRALS IN THE ACTIVITY LOG
                            activity()
                                ->performedOn($user)
                                ->causedBy($ref_user)
                                ->log('gained '. $increment.' '.config("SETTINGS::SYSTEM:CREDITS_DISPLAY_NAME").' for commission-referral of '.$user->name.' (ID:'.$user->id.')');
                        }

                    }

                    //give client discord role
                    $discord = Socialite::driver('discord')->user();
                    $botToken = config('SETTINGS::DISCORD:BOT_TOKEN');
                    $guildId = config('SETTINGS::DISCORD:GUILD_ID');
                    if (! empty($clientRoleId)&&$discord) {
                        $response = Http::withHeaders(
                            [
                                'Authorization' => 'Bot '.$botToken,
                                'Content-Type' => 'application/json',
                            ]
                        )->put(
                            "https://discord.com/api/guilds/{$guildId}/members/{$discord->id}/roles/{$clientRoleId}",
                            ['access_token' => $discord->token]
                        );
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

    public static function gopayEurPremiumSMSamounts()
    {
        $amounts = array();
        for($i = 10; $i<=2000; $i+=5) if($i<=105||($i%10==0&&$i<=1100)||$i%100==0||($i%100==5&&$i<1100))array_push($amounts, $i/100);
        return $amounts;
    }

    public function GopayPay($amount, $currency, $method)
    {
        $subtotal = ($this->getDiscountByAmount($amount)*$amount/100)*(100-PartnerDiscount::getDiscount())/100;
        if($currency=="eur") $subtotal = $subtotal/config("SETTINGS::PAYMENTS:EUR_RATIO");
        $tax = $this->getTaxes($subtotal, $currency, "gopay", $method);
        $total = Round(Round($subtotal, 2) + Round($tax, 2), 2);

        $allowedEurAmounts = $this->gopayEurPremiumSMSamounts();
        if($currency=="eur"&&$method=="sms"&&!in_array($total, $allowedEurAmounts)){
            for($i = 0; $i<count($allowedEurAmounts); $i++){
                if($allowedEurAmounts[$i]>=$total){
                    $total = $allowedEurAmounts[$i];
                    $tax = $total - $subtotal;
                    break;
                }
                if($i==count($allowedEurAmounts)-1) return redirect()->route('store.index')->with('error', __('The maximum possible payment with the chosen payment method is') . " " . $allowedEurAmounts[count($allowedEurAmounts)-1]);
            }
        }

        $gopay =  $this->gopay();
        $gopay_methods = array();
        if($method=="bank_bitcoin") array_push($gopay_methods, PaymentInstrument::BANK_ACCOUNT, PaymentInstrument::BITCOIN);
        elseif($method=="sms") array_push($gopay_methods, $currency=='czk'?PaymentInstrument::MPAYMENT:PaymentInstrument::PREMIUM_SMS);
        elseif($method=="paysafecard") array_push($gopay_methods, PaymentInstrument::PAYSAFECARD);

        $response = $gopay->createPayment([
            'payer' => [
                    //'default_payment_instrument' => PaymentInstrument::MPAYMENT,
                    'allowed_payment_instruments' => $gopay_methods,
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
            'amount' => Round($subtotal*100) + Round($tax*100),
            'currency' => strtoupper($currency),
            //'order_number' => '001',
            'order_description' => $amount . " " . CREDITS_DISPLAY_NAME,
            'items' => [
                [
                    'type' => PaymentItemType::ITEM,
                    'name' => $amount . " " . CREDITS_DISPLAY_NAME,
                    'amount' => Round($subtotal*100),
                    'count' => 1,
                    'vat_rate' => 0
                ],
                [
                    'type' => PaymentItemType::ITEM,
                    'name' => __('Tax'),
                    'amount' => Round($tax*100),
                    'count' => 1,
                    'vat_rate' => 0
                ]],
            'callback' => [
                    'return_url' => route('payment.GopayReturn', ['amount' => $amount, 'user_id' => Auth::user()->id]),
                    'notification_url' => route('payment.GopayReturn', ['amount' => $amount, 'user_id' => Auth::user()->id, 'bot' => true])
            ],
            'lang' => strtoupper(session()->get('language'))
        ]);
        if($response->hasSucceed()){
            return redirect()->away($response->json['gw_url']);
        }
        else dd($response);
    }
    public function getGopayMethod($instruments){
        foreach($instruments as $instrument){
            switch ($instrument){
                case 'MPAYMENT':
                    return "sms";
                    break;
                case 'PRSMS':
                    return "sms";
                    break;
                case 'PAYSAFECARD':
                    return "paysafecard";
                    break;
                default:
                    return 'bank_bitcoin';
                    break;
            }
        }
    }

    public function GopayReturn(Request $request)
    {
        $gopay = $this->gopay();
        $user = User::where('id', $request->user_id)->first();

        try {
            // Call API with your client and get a response for your call
            $paymentStatus = $gopay->getStatus($request->id);
            
            $currency = strtolower($paymentStatus->json['currency']);
            $method = $this->getGopayMethod($paymentStatus->json['payer']['allowed_payment_instruments']);
            $price = $currency=='eur'?$this->getDiscountByAmount($request->amount)*$request->amount/100/config("SETTINGS::PAYMENTS:EUR_RATIO"):$this->getDiscountByAmount($request->amount)*$request->amount/100;
            $subtotal = ($price)*(100-PartnerDiscount::getDiscount($request->user_id))/100;
            $tax = $this->getTaxes($subtotal, $currency, "gopay", $method);

            $total = Round(Round($subtotal, 2) + Round($tax, 2), 2);

            $allowedEurAmounts = $this->gopayEurPremiumSMSamounts();
            if($currency=="eur"&&$method=="sms"&&!in_array($total, $allowedEurAmounts)){
                for($i = 0; $i<count($allowedEurAmounts); $i++){
                    if($allowedEurAmounts[$i]>=$total){
                        $total = $allowedEurAmounts[$i];
                        $tax = $total - $subtotal;
                        break;
                    }
                    if($i==count($allowedEurAmounts)-1){
                        if($request->bot==true) abort(400);
                        else return redirect()->route('store.index')->with('error', __('The maximum possible payment with the chosen payment method is') . " " . $allowedEurAmounts[count($allowedEurAmounts)-1]);
                    }
                }
            }

            if(abs($total-$paymentStatus->json['amount']/100)>=0.05){
                if($request->bot==true) abort(409);
                else return redirect()->route('home')->with('error', __('There was a problem verifying your payment. If you have already paid, please contact support.'));
            } 
            else $amount = $request->amount;

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
                $user->increment('credits', $amount);

                //update role give Referral-reward
                if ($user->role == 'member') {
                    $user->update(['role' => 'client']);

                    if((config("SETTINGS::REFERRAL:MODE") == "commission" || config("SETTINGS::REFERRAL:MODE") == "both")){
                        if($ref_user = DB::table("user_referrals")->where('registered_user_id', '=', $user->id)->first()){
                            $ref_user = User::findOrFail($ref_user->referral_id);
                            $increment = number_format($amount/100*config("SETTINGS::REFERRAL:PERCENTAGE"),0,"","");
                            $ref_user->increment('credits', $increment);

                            //LOGS REFERRALS IN THE ACTIVITY LOG
                            activity()
                                ->performedOn($user)
                                ->causedBy($ref_user)
                                ->log('gained '. $increment.' '.config("SETTINGS::SYSTEM:CREDITS_DISPLAY_NAME").' for commission-referral of '.$user->name.' (ID:'.$user->id.')');
                        }
                    }

                    //give client discord role
                    $discord = Socialite::driver('discord')->user();
                    $botToken = config('SETTINGS::DISCORD:BOT_TOKEN');
                    $guildId = config('SETTINGS::DISCORD:GUILD_ID');
                    if (! empty($clientRoleId)&&$discord) {
                        $response = Http::withHeaders(
                            [
                                'Authorization' => 'Bot '.$botToken,
                                'Content-Type' => 'application/json',
                            ]
                        )->put(
                            "https://discord.com/api/guilds/{$guildId}/members/{$discord->id}/roles/{$clientRoleId}",
                            ['access_token' => $discord->token]
                        );
                    }

                }

                //store payment
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'payment_id' => $request->id,
                    'payment_method' => 'gopay(' . $this->getGopayMethod($paymentStatus->json['payer']['allowed_payment_instruments']) . ')',
                    'type' => 'Credits',
                    'status' => 'paid',
                    'amount' => $amount,
                    'price' => $price,
                    'tax_value' => $tax,
                    'tax_percent' => config("SETTINGS::PAYMENTS:SALES_TAX")<0?0:config("SETTINGS::PAYMENTS:SALES_TAX"),
                    'total_price' => $subtotal + $tax,
                    'currency_code' => $paymentStatus->json['currency'],
                    'shop_item_product_id' => 'none',
                ]);

                event(new UserUpdateCreditsEvent($user));

                //only create invoice if SETTINGS::INVOICE:ENABLED is true
                if (config('SETTINGS::INVOICE:ENABLED') == 'true') {
                    $this->createInvoice($user, $payment, 'paid', $paymentStatus->json['currency']);
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

    protected function createInvoice($user, $payment, $paymentStatus, $currencyCode)
    {
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
            ->title($payment->amount . " " . CREDITS_DISPLAY_NAME)
            ->pricePerUnit($payment->price/$payment->amount)
            ->quantity($payment->amount);

        $notes = [
            "",
            __("Payment method") . ": " . $payment->payment_method,
            __('The seller is registered in the trade register') . "."
        ];
        $notes = implode("<br>", $notes);

        $invoice = Invoice::make()
            ->template('controlpanel')
            ->name(__("Invoice"))
            ->buyer($customer)
            ->seller($seller)
            ->discountByPercent(PartnerDiscount::getDiscount($payment->user_id))
            ->totalTaxes($payment->tax_value)
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
                return 
                ($payment->user)?'<a href="'.route('admin.users.show', $payment->user->id).'">'.$payment->user->name.'</a>':__('Unknown user');
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
                return [
                    'display' => $payment->created_at ? $payment->created_at->diffForHumans() : '',
                    'raw' => $payment->created_at ? strtotime($payment->created_at) : ''
                ];
            })
            ->addColumn('actions', function (Payment $payment) {
                return '<a data-content="' . __("Download") . '" data-toggle="popover" data-trigger="hover" data-placement="top"  href="' . route('admin.invoices.downloadSingleInvoice', "id=" . $payment->payment_id) . '" class="btn btn-sm text-white btn-info mr-1"><i class="fas fa-file-download"></i></a>';
            })
            ->rawColumns(['actions', 'user'])
            ->make(true);
    }
}