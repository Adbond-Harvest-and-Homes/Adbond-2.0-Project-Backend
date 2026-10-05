<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use app\Enums\PaymentGateway as Gateway;

class PaymentGateway extends Model
{
    use HasFactory;

    public static function paystack()
    {
        return self::where("name", Gateway::PAYSTACK->value)->first();
    }

    public static function opay()
    {
        return self::where("name", Gateway::OPAY->value)->first();
    }
}
