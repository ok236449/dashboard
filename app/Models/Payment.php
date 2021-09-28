<?php

namespace App\Models;

use Hidehalo\Nanoid\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use NumberFormatter;
use Spatie\Activitylog\Traits\LogsActivity;

class Payment extends Model
{
    use HasFactory;
    use LogsActivity;

    public $incrementing = false;

    /**
     * @var string[]
     */
    protected $fillable = [
        'id',
        'user_id',
        'payment_id',
        'payer_id',
        'payer',
        'status',
        'type',
        'amount',
        'price',
        'currency_code',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function (Payment $payment) {
            $client = new Client();

            $payment->{$payment->getKeyName()} = $client->generateId($size = 8);
        });
    }

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function formatCurrency($locale = 'en_US')
    {
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($this->price, $this->currency_code);
    }
    public function CalculateTax($product)
    {
        return 1.5*($product->price);
    }
}
