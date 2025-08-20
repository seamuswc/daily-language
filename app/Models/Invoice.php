<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'email', 'plan', 'chain', 'token', 'reference', 'recipient',
        'amount_usd', 'amount_token', 'status', 'tx_id',
    ];
}


