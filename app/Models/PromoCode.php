<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    use HasFactory;

    protected $casts = [
        'combine_with_full_payment_discount' => 'boolean',
    ];

    public function promo()
    {
        return $this->belongsTo(Promo::class);
    }
}
