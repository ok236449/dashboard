<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualPrivateServer extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'user_id',
        'uuid',
        'price',
        'last_payment',
    ];
}
