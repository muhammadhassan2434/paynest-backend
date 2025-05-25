<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SplitBill extends Model
{
    protected $fillable = [
        'created_by', 'receiver_account_no', 'receiver_bank',
        'total_amount', 'collected_amount', 'title', 'note', 'status'
    ];

    public function members()
    {
        return $this->hasMany(SplitBillMember::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
