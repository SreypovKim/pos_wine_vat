<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleServiceDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'price', 'sale_id', 'service_id','description'
    ];

      public function service()
    {
        return $this->belongsTo('App\Models\service');
    }
}
