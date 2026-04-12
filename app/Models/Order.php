<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Event;

use app\Domain\Orders\Events\OrderCompleted;

use app\Models\PaymentPeriodStatus;
use app\Models\PaymentStatus;

class Order extends Model
{
    use HasFactory;

    public static $type = "app\Models\Order";

    public $completedEvent = false;

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function paymentStatus()
    {
        return $this->belongsTo(PaymentStatus::class);
    }

    /**
     * Get all payments for this order
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'purchase');
    }

    public function discounts()
    {
        return $this->hasMany(OrderDiscount::class);
    }

    /**
     * Get all client packages for this order
     */
    public function clientPackages(): MorphMany
    {
        return $this->morphMany(ClientPackage::class, 'purchase');
    }

    public function clientPackage()
    {
        // return $this->hasOne(ClientPackage::class, 'purchase_id', "id");
        return $this->morphOne(ClientPackage::class, 'purchase');
    }

    public function clientInvestment()
    {
        return $this->hasOne(ClientInvestment::class);
    }

    public function upgrade()
    {
        return $this->belongsTo(AssetUpgrade::class, "upgrade_id", "id");
    }

    public function downgrade()
    {
        return $this->belongsTo(AssetDowngrade::class, "downgrade_id", "id");
    }

    public function totalPaymentAmount()
    {
        return $this->payments()->where("confirmed", 1)->sum('amount');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            $order->payment_period_status_id = PaymentPeriodStatus::normal()->id;
            $order->balance = $order->amount_payable - $order->amount_payed;
            if($order->payment_status_id == PaymentStatus::complete()->id) {
                $order->balance = 0;
                $order->completed = 1;
            } 
            // if(!$order->balance) $order->bala
            if($order->balance < 0) $order->balance = 0;
        });

        self::created(function (Order $order) {
            if(!$this->completedEvent && ($this->completed == 1 || $this->totalPaymentAmount() >= $this->amount_payable)) {
                Event::dispatch(new OrderCompleted($order));
            }
        });

        self::updating(function (Order $order) {
            $order->balance = $order->amount_payable - $order->amount_payed;
            if($order->balance < 0) $order->balance = 0;
            $order->payment_status_id = ($order->balance <= 0) ? PaymentStatus::complete()->id : PaymentStatus::deposit()->id;
        });

        self::updated(function (Order $order) {
            if(!$this->completedEvent && ($this->completed == 1 || $this->totalPaymentAmount() >= $this->amount_payable)) {
                Event::dispatch(new OrderCompleted($order));
            }
        });
    }
}
