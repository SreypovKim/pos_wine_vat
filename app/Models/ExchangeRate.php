<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'exchange_date', 'exchange_rate',
    ];

}
