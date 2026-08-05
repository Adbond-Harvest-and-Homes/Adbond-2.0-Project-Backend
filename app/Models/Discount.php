<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use app\Enums\DiscountType;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = ["type", "discount", "discount_measurement"];

    public static function fullPayment()
    {
        return self::whereType(DiscountType::FULL_PAYMENT->value)->latest("id")->first();
    }

    public static function bond()
    {
        return self::whereType(DiscountType::BOND->value)->latest("id")->first();
    }

    public static function bondInstallment()
    {
        return self::whereType(DiscountType::BOND_INSTALLMENT->value)->latest("id")->first();
    }

    public static function loyalty()
    {
        return self::whereType(DiscountType::LOYALTY->value)->latest("id")->first();
    }
}
