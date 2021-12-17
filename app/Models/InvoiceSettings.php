<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceSettings extends Model
{
    use HasFactory;

    protected $table = 'invoice_settings';

    protected $fillable = [
        'company_name',
        'company_adress',
        'company_phone',
        'company_mail',
        'company_vat',
        'company_web',
        'invoice_prefix'
    ];
}
