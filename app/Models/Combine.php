<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Combine extends Model
{
    use HasFactory;

    protected $table = 'combines';

    protected $fillable = [
        'product_id','qty'
    ];

    protected $casts = [
        'product_id' => 'integer',
        'product_combine_id'=>'integer',
        'qty' => 'double',
    ];
}
