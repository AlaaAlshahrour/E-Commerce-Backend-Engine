<?php

namespace App\Models;

use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    protected $guarded = [];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}
