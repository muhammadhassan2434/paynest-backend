<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillReminder extends Model
{
    protected $fillable = [
        'user_id',
        'bill_type',
        'amount',
        'due_date',
        'frequency',
    ];

    public function user()
    {
      return  $this->belongsTo(User::class);
    }
   
}
