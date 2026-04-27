<?php

namespace app\Services;

use app\Models\Discount;
use app\Models\InstallmentDiscount;

use app\Enums\DiscountType;

use app\Helpers;

class DiscountService
{
    public function getFullPayment()
    {
        return Discount::fullPayment();
    }

    public function getInstallmentDurations()
    {
        return InstallmentDiscount::orderBy("duration", "ASC")->get();
    }

    public function getInstallmentDuration($duration)
    {
        return InstallmentDiscount::where("duration", $duration)->first();
    }

    public function updateDiscount($type, $data)
    {
        $discount = Discount::where("type", $type)->first();
        $discount->discount = $data['discount'];
        if(isset($data['measurement'])) $discount->discount_measurement = $data['measurement'];
        $discount->update();

        return $discount;
    }

    public function updateInstallmentDiscounts($installments)
    {
        if(count($installments) > 0) {
            foreach($installments as $installmentArr) {
                $installment = $installmentArr[0];
                $installmentDiscount = InstallmentDiscount::where("duration", $installment['duration'])->first();
                if(!$installmentDiscount) {
                    $installmentDiscount = new InstallmentDiscount;
                    $installmentDiscount->duration = $installment['duration'];
                }
                $installmentDiscount->discount = $installment['discount'];
                $installmentDiscount->save();
            }
        }
    }

    public function addInstallmentDiscounts($installments)
    {
        if(count($installments) > 0) {
            foreach($installments as $installmentArr) {
                $installment = $installmentArr[0];
                InstallmentDiscount::firstOrCreate([
                    "duration" => $installment['duration'],
                    "discount" => $installment['discount']
                ]);
            }
        }
    }

    public function delete($installment)
    {
        $installment->delete();
    }

}
