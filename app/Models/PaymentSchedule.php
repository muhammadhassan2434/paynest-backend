<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentSchedule extends Model
{
    protected $fillable = [
        'account_id',
        'amount',
        'scheduled_for',
        'purpose',
        'type',
        'category',
        'reference_no',
        'receiver_name',
        'receiver_account_no',
        'receiver_bank',
        'note',
        'is_funded',
        'status',
        'transaction_id'
    ];


    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
    }
