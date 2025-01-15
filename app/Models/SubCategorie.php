<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategorie extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name','category_id'
    ];

    public function category()
    {
        return $this->belongsTo('App\Models\Category');
    }
    
}
