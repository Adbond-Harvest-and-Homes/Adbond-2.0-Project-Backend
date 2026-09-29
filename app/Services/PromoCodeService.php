<?php

namespace app\Services;


use app\Models\Order;
use app\Models\OrderDiscount;
use app\Models\Promo;
use app\Models\PromoCode;
use app\Models\PromoProduct;

use app\Helpers;

class PromoCodeService
{
    public function save($data)
    {
        $promoCode = new PromoCode;
        $promoCode->promo_id = $data['promoId'];
        $promoCode->code = $data['code'];
        if(isset($data['expiry'])) $promoCode->expiry = $data['expiry'];
        if(isset($data['maxUsage'])) $promoCode->max_usage = $data['maxUsage'];
        if(isset($data['packageLimited'])) $promoCode->package_limited = $data['packageLimited'];
        $promoCode->combine_with_full_payment_discount = $data['combineWithFullPaymentDiscount'] ?? true;

        $promoCode->save();

        return $promoCode;
    }

    public function update($data, $promoCode)
    {
        if(isset($data['code'])) $promoCode->code = $data['code'];
        if(isset($data['expiry'])) $promoCode->expiry = $data['expiry'];
        if(isset($data['maxUsage'])) $promoCode->max_usage = $data['maxUsage'];
        if(isset($data['packageLimited'])) $promoCode->package_limited = $data['packageLimited'];
        if(isset($data['combineWithFullPaymentDiscount'])) $promoCode->combine_with_full_payment_discount = $data['combineWithFullPaymentDiscount'];

        $promoCode->update();

        return $promoCode;
    }

    public function toggleActivate($promoCode)
    {
        $promoCode->active = 1 - $promoCode->active;
        $promoCode->save();

        return $promoCode;
    }

    public function validatePromoCode($code, $package)
    {
        $promoCode = PromoCode::whereCode($code)->first();
        if($promoCode->promo) {
            if(!$this->checkExpiry($promoCode)) return ["valid" => false, "message" => "Promo code has expired"];
            
            if(!$this->checkActive($promoCode)) return ["valid" => false, "message" => "The Promo is no longer active"];
            
            if(!$this->checkUsage($promoCode)) return ["valid" => false, "message" => "This Promo Code has reached its maximum usage"];
            
            if(!$this->checkPackage($promoCode, $package)) return ["valid" => false, "message" => "This package is not included in the promo"];
        }else{
            return ["valid" => false, "message" => "Cannot find this Promo"];
        }
        return [
            "valid" => true,
            "discount" => $promoCode->promo->discount,
            "discountAmount" => $promoCode->promo->discount_amount,
            "combineWithFullPaymentDiscount" => $promoCode->combine_with_full_payment_discount
        ];
    }

    public function checkExpiry($promoCode)
    {
        return (
                    (!$promoCode->expiry || ($promoCode->expiry > now()))
                    &&
                    (!$promoCode->promo->end || ($promoCode->promo->end > now()))
                );
    }

    public function checkActive($promoCode)
    {
        return ($promoCode->active && $promoCode->promo->active);
    }

    public function checkUsage($promoCode)
    {
        return (!$promoCode->max_usage || ($promoCode->usage_count < $promoCode->max_usage));
    }

    public function checkPackage($promoCode, $package)
    {
        if(!$promoCode->package_limited) return true;
        if(
            ($package->project && in_array($promoCode->promo->id, $package->project->promos()->pluck('promo_id')->toArray()))
            ||
            in_array($promoCode->promo->id, $package->promos()->pluck('promo_id')->toArray())
        ) return true;

        return false;
    }

    public function promoCodes($with=[])
    {
        return PromoCode::with($with)->orderBy("created_at", "DESC")->get();
    }

    public function promoCode($code)
    {
        return PromoCode::whereCode($code)->first();
    }

    public function promoCodeById($id)
    {
        return PromoCode::find($id);
    }

    public function delete($promoCode)
    {
        $promoCode->delete();
    }

    public function incrementUsage($promoCode)
    {
        $promoCode->increment('usage_count');

        return $promoCode;
    }

}
