<?php

namespace App\Classes\Settings;

use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class Payments
{
    public function __construct()
    {

    }

    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'paypal-client_id' => 'nullable|string',
            'paypal-client-secret' => 'nullable|string',
            'paypal-sandbox-secret' => 'nullable|string',
            'stripe-secret-key' => 'nullable|string',
            'stripe-endpoint-secret' => 'nullable|string',
            'stripe-test-secret-key' => 'nullable|string',
            'stripe-test-endpoint-secret' => 'nullable|string',
            'stripe-methods' => 'nullable|string',
            'eur_ratio' => 'nullable|integer',
            "gopay-goid" => "nullable|string",
            "gopay-client-id" => "nullable|string",
            "gopay-client-secret" => "nullable|string",
            "gopay-test-client-id" => "nullable|string",
            "gopay-test-client-secret" => "nullable|string",
            'sales-tax' => 'nullable|numeric',
        ]);
        if ($validator->fails()) {
            return redirect(route('admin.settings.index').'#payment')->with('error', __('Payment settings have not been updated!'))->withErrors($validator)
                ->withInput();
        }

        $values = [
            //SETTINGS::VALUE => REQUEST-VALUE (coming from the html-form)
            'SETTINGS::PAYMENTS:PAYPAL:SECRET' => 'paypal-client-secret',
            'SETTINGS::PAYMENTS:PAYPAL:CLIENT_ID' => 'paypal-client-id',
            'SETTINGS::PAYMENTS:PAYPAL:SANDBOX_SECRET' => 'paypal-sandbox-secret',
            'SETTINGS::PAYMENTS:PAYPAL:SANDBOX_CLIENT_ID' => 'paypal-sandbox-id',
            'SETTINGS::PAYMENTS:STRIPE:SECRET' => 'stripe-secret',
            'SETTINGS::PAYMENTS:STRIPE:ENDPOINT_SECRET' => 'stripe-endpoint-secret',
            'SETTINGS::PAYMENTS:STRIPE:TEST_SECRET' => 'stripe-test-secret',
            'SETTINGS::PAYMENTS:STRIPE:ENDPOINT_TEST_SECRET' => 'stripe-endpoint-test-secret',
            'SETTINGS::PAYMENTS:STRIPE:METHODS' => 'stripe-methods',
            "SETTINGS::PAYMENTS:EUR_RATIO" => "eur_ratio",
            "SETTINGS::PAYMENTS:GOPAY:GOID" => "gopay-goid",
            "SETTINGS::PAYMENTS:GOPAY:CLIENT_ID" => "gopay-client-id",
            "SETTINGS::PAYMENTS:GOPAY:CLIENT_SECRET" => "gopay-client-secret",
            "SETTINGS::PAYMENTS:GOPAY:TEST_CLIENT_ID" => "gopay-test-client-id",
            "SETTINGS::PAYMENTS:GOPAY:TEST_CLIENT_SECRET" => "gopay-test-client-secret",
            'SETTINGS::PAYMENTS:SALES_TAX' => 'sales-tax',
        ];

        foreach ($values as $key => $value) {
            $param = $request->get($value);

            Settings::where('key', $key)->updateOrCreate(['key' => $key], ['value' => $param]);
            Cache::forget('setting'.':'.$key);
        }

        return redirect(route('admin.settings.index').'#payment')->with('success', __('Payment settings updated!'));
    }
}
