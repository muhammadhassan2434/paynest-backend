<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{

    protected $fillable = ['user_id', 'gender', 'address'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
