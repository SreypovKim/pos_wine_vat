<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorSchedule extends Model
{
    use HasFactory;

    protected $casts = [
        'start_time' => 'datetime: H:i',
        'end_time' => 'datetime: H:i',
    ]; 

    protected $fillable = [
        'user_id', 'sun', 'mon','tue','wed','thu','fri','sat','sun_start_time','sun_end_time','mon_start_time','mon_end_time'
        ,'tue_start_time','tue_end_time','wed_start_time','wed_end_time','thu_start_time','thu_end_time','fri_start_time','fri_end_time'
        ,'sat_start_time','sat_end_time'
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}
