<?php

namespace app\Services;


use app\Models\Order;
use app\Models\OrderDiscount;
use app\Models\Promo;
use app\Models\PromoCode;
use app\Models\PromoProduct;

use app\Helpers;

class PromoService
{

    public function save($data)
    {
        $promo = new Promo;
        $promo->title = $data['title'];
        if(isset($data['discount'])) {
            $promo->discount = $data['discount'];
        }else{
            $promo->discount_amount = $data['discountAmount'];
        }
        $promo->user_id = $data['userId'];
        if(isset($data['start'])) $promo->start = $data['start'];
        if(isset($data['end'])) $promo->end = $data['end'];
        if(isset($data['description'])) $promo->description = $data['description'];
        if(isset($data['products'])) $promo->package_limited = 1;
        if(isset($data['promoCode'])) $promo->has_promo_code = 1;

        $promo->save();

        return $promo;
    }

    public function update($data, $promo)
    {
        if(isset($data['title'])) $promo->title = $data['title'];
        if(isset($data['discount'])) {
            $promo->discount = $data['discount'];
            $promo->discount_amount = null;
        }
        if(isset($data['discountAmount'])) {
            $promo->discount_amount = $data['discountAmount'];
            $promo->discount = null;
        }
        if(isset($data['start'])) $promo->start = $data['start'];
        if(isset($data['end'])) $promo->end = $data['end'];
        if(isset($data['description'])) $promo->description = $data['description'];
        if(isset($data['products'])) $promo->package_limited = 1;
        if(isset($data['promoCode'])) $promo->has_promo_code = 1;

        $promo->update();

        return $promo;
    }

    public function savePromoProduct($product)
    {
            $promoProduct = new PromoProduct;
            $promoProduct->product_type = $product['type'];
            $promoProduct->product_id = $product['id'];
            $promoProduct->promo_id = $product['promoId'];
            $promoProduct->save();

            return $promoProduct;
    }


    public function savePromoProducts($products)
    {
        foreach($products as $product) {
            $this->savePromoProduct($product);
        }
    }

    public function getPromoProductByDetail($promoId, $type, $id)
    {
        return PromoProduct::where("promo_id", $promoId)->where("product_type", $type)->where("product_id", $id)->first();
    }

    public function removePromoProduct($product)
    {
        $product->delete();
    }

    public function toggleActivate($promo)
    {
        $promo->active = 1 - $promo->active;
        $promo->save();

        return $promo;
    }

    public function getPromo($id, $with=[])
    {
        return Promo::with($with)->where("id", $id)->first();
    }

    public function promos($with)
    {
        return Promo::with($with)->orderBy("created_at", "DESC")->get();
    }

    public function getPromos($package, $user)
    {
        // instantiate applicable promos array; an array that will hold all the promos that are applicable to the package and user
        $applicablePromos = [];
        $packageLimitedPromos = [];

        // Get all active and not expired promos
        $promos = $this->running();

        // Filter out those that are package limited and those that are not and add those that are not into the applicable promos array
        if($promos->count() > 0) {
            foreach($promos as $promo) {
                if($promo->package_limited) {
                    $packageLimitedPromos[] = $promo;
                }else{
                    $applicablePromos[] = $promo;
                }
            }
        }

        //Go through the package Limited promos and add those that are applicable to this package to the applicable promos array
        if(count($packageLimitedPromos) > 0) {
            foreach($packageLimitedPromos as $limitedPromo) {
                if(in_array($package?->project?->id, $limitedPromo->projects()->pluck('id')->toArray())) $applicablePromos[] = $limitedPromo;
                if(in_array($package?->id, $limitedPromo->packages()->pluck('id')->toArray())) $applicablePromos[] = $limitedPromo;
            }
        }

        // return the applicable promos array
        return $applicablePromos;
    }

    public function running($promoCodesIncluded=false)
    {
        return ($promoCodesIncluded) ?
                     Promo::whereActive()->where("start", "<", now())->where("end", ">", now())->get()
                     :
                     Promo::whereActive(true)->where("start", "<", now())->where("end", ">", now())->where("has_promo_code", false)->get();
    }

    public function packageLimited()
    {
        return Promo::whereActive()->where("start", "<", now())->where("end", ">", now())->wherePackageLimited()->get();
    }

    public function delete($promo)
    {
        $promo->delete();
    }

}
