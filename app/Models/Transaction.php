<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'sender_id',
        'reciever_number',
        'amount',
        'transaction_type',
        'status',
        'reference',
    ];
}
