<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

use app\Models\PaymentStatus;

class Offer extends Model
{
    use HasFactory;

    public static $type = "app\Models\Offer";

    public function asset()
    {
        return $this->belongsTo(ClientPackage::class, 'client_package_id');
    }

    public function paymentStatus()
    {
        return $this->belongsTo(PaymentStatus::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resellOrder()
    {
        return $this->belongsTo(ResellOrder::class);
    }

    /**
     * Get all client packages for this offer
     */
    public function clientPackage()
    {
        // return $this->hasOne(ClientPackage::class, 'purchase_id');
        $this->morphOne(ClientPackage::class, 'purchase');
    }

    /**
     * Get all payments for this order
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'purchase');
    }

    public function bids()
    {
        return $this->hasMany(OfferBid::class);
    }

    public function acceptedBid()
    {
        // return $this->bids()->where('cancelled', 0)->where("accepted", 1)->first();
        return $this->belongsTo(OfferBid::class, "accepted_bid_id", "id");
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($offer) {
            if($offer->resell_order_id) {
                $offer->approved = 1;
            }
        });

        self::updating(function (Offer $offer) {
            if($offer->accepted_bid_id && !$offer->payment_status_id) {
                $offer->payment_status_id = PaymentStatus::awaiting_payment()->id;
            }
            $acceptedBid = $offer->acceptedBid;
            if ($acceptedBid) {
                $acceptedBid->payment_status_id = $offer->payment_status_id;
                $acceptedBid->save();
            }
            // $offer->acceptedBid->payment_status_id = $offer->payment_status_id;
            // $offer->acceptedBid->update();
        });
    }

}
