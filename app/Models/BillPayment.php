<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillPayment extends Model
{
    protected $fillable = [
        'user_id',
        'service_id',
        'service_provider_id',
        'consumer_number',
        'customer_name',
        'amount',
        'due_date',
        'payment_date',
        'status',
        'transaction_id',
    ];
    
}
