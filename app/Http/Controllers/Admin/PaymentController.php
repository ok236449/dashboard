<?php

namespace App\Http\Controllers\Admin;

use App\Events\UserUpdateCreditsEvent;
use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Models\InvoiceSettings;
use App\Models\Payment;
use App\Models\PaypalProduct;
use App\Models\User;
use App\Notifications\InvoiceNotification;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
     * @param PaypalProduct $paypalProduct
     * @return Application|Factory|View
     */
    public function checkOut(Request $request, PaypalProduct $paypalProduct)
    {
        return view('store.checkout')->with([
            'product' => $paypalProduct,
            'taxvalue' => $paypalProduct->getTaxValue(),
            'taxpercent' => $paypalProduct->getTaxPercent(),
            'total' => $paypalProduct->getTotalPrice()
        ]);
    }

    /**
     * @param Request $request
     * @param PaypalProduct $paypalProduct
     * @return RedirectResponse
     */
    public function pay(Request $request, PaypalProduct $paypalProduct)
    {
        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        $request->body = [
            "intent" => "CAPTURE",
            "purchase_units" => [
                [
                    "reference_id" => uniqid(),
                    "description" => $paypalProduct->description,
                    "amount" => [
                        "value" => $paypalProduct->getTotalPrice(),
                        'currency_code' => strtoupper($paypalProduct->currency_code),
                        'breakdown' => [
                            'item_total' =>
                                [
                                    'currency_code' => strtoupper($paypalProduct->currency_code),
                                    'value' => $paypalProduct->price,
                                ],
                            'tax_total' =>
                                [
                                    'currency_code' => strtoupper($paypalProduct->currency_code),
                                    'value' => $paypalProduct->getTaxValue(),
                                ]
                        ]
                    ]
                ]
            ],
            "application_context" => [
                "cancel_url" => route('payment.cancel'),
                "return_url" => route('payment.success', ['product' => $paypalProduct->id]),
                'brand_name' => config('app.name', 'Laravel'),
                'shipping_preference' => 'NO_SHIPPING'
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
            ? new SandboxEnvironment($this->getClientId(), $this->getClientSecret())
            : new ProductionEnvironment($this->getClientId(), $this->getClientSecret());

        return new PayPalHttpClient($environment);
    }

    /**
     * @return string
     */
    protected function getClientId()
    {
        return env('APP_ENV') == 'local' ? env('PAYPAL_SANDBOX_CLIENT_ID') : env('PAYPAL_CLIENT_ID');
    }

    /**
     * @return string
     */
    protected function getClientSecret()
    {
        return env('APP_ENV') == 'local' ? env('PAYPAL_SANDBOX_SECRET') : env('PAYPAL_SECRET');
    }

    /**
     * @param Request $laravelRequest
     */
    public function success(Request $laravelRequest)
    {
        /** @var PaypalProduct $paypalProduct */
        $paypalProduct = PaypalProduct::findOrFail($laravelRequest->input('product'));
        /** @var User $user */
        $user = Auth::user();

        $request = new OrdersCaptureRequest($laravelRequest->input('token'));
        $request->prefer('return=representation');
        try {
            // Call API with your client and get a response for your call
            $response = $this->getPayPalClient()->execute($request);
            if ($response->statusCode == 201 || $response->statusCode == 200) {

                //update credits
                $user->increment('credits', $paypalProduct->quantity);

                //update server limit
                if (Configuration::getValueByKey('SERVER_LIMIT_AFTER_IRL_PURCHASE') !== 0) {
                    if ($user->server_limit < Configuration::getValueByKey('SERVER_LIMIT_AFTER_IRL_PURCHASE')) {
                        $user->update(['server_limit' => Configuration::getValueByKey('SERVER_LIMIT_AFTER_IRL_PURCHASE')]);
                    }
                }

                //update role
                if ($user->role == 'member') {
                    $user->update(['role' => 'client']);
                }

                //store payment
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'payment_id' => $response->result->id,
                    'payer_id' => $laravelRequest->input('PayerID'),
                    'type' => 'Credits',
                    'status' => $response->result->status,
                    'amount' => $paypalProduct->quantity,
                    'price' => $paypalProduct->price,
                    'tax_value' => $paypalProduct->getTaxValue(),
                    'tax_percent' => $paypalProduct->getTaxPercent(),
                    'total_price' => $paypalProduct->getTotalPrice(),
                    'currency_code' => $paypalProduct->currency_code,
                    'payer' => json_encode($response->result->payer),
                ]);


                event(new UserUpdateCreditsEvent($user));

                //create invoice
                $lastInvoiceID = \App\Models\Invoice::where("invoice_name", "like", "%" . now()->format('mY') . "%")->count("id");
                $newInvoiceID = $lastInvoiceID + 1;
                $InvoiceSettings = InvoiceSettings::query()->first();
                $logoPath = storage_path('app/public/logo.png');

                $seller = new Party([
                    'name' => $InvoiceSettings->company_name,
                    'phone' => $InvoiceSettings->company_phone,
                    'address' => $InvoiceSettings->company_adress,
                    'vat' => $InvoiceSettings->company_vat,
                    'custom_fields' => [
                        'E-Mail' => $InvoiceSettings->company_mail,
                        "Web" => $InvoiceSettings->company_web
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
                    ->title($paypalProduct->description)
                    ->pricePerUnit($paypalProduct->price);

                $invoice = Invoice::make()
                    ->template('controlpanel')
                    ->name(__("Invoice"))
                    ->buyer($customer)
                    ->seller($seller)
                    ->discountByPercent(0)
                    ->taxRate(floatval($paypalProduct->getTaxPercent()))
                    ->shipping(0)
                    ->addItem($item)
                    ->status(__('Paid'))
                    ->series(now()->format('mY'))
                    ->delimiter("-")
                    ->sequence($newInvoiceID)
                    ->serialNumberFormat($InvoiceSettings->invoice_prefix . '{DELIMITER}{SERIES}{SEQUENCE}');

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
    public function cancel(Request $request)
    {
        return redirect()->route('store.index')->with('success', __('Payment was Canceled'));
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
            ->editColumn('total_price', function (Payment $payment) {
                return $payment->formatToCurrency($payment->total_price);
            })
            ->editColumn('created_at', function (Payment $payment) {
                return $payment->created_at ? $payment->created_at->diffForHumans() : '';
            })
            ->make();
    }
}
