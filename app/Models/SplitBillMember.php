<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SplitBillMember extends Model
{
    protected $fillable = ['split_bill_id', 'user_id', 'amount', 'is_paid', 'paid_at'];

    public function splitBill()
    {
        return $this->belongsTo(SplitBill::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
}
