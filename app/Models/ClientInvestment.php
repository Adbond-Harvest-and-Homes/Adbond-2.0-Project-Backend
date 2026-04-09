<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\MorphMany;

class ClientInvestment extends Model
{
    use HasFactory;

    public static $type = "app\Models\ClientInvestment";

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function redemptionPackage()
    {
        return $this->belongsTo(Package::class, "redemption_package_id", "id");
    }

    public function clientPackage()
    {
        return $this->hasOne(ClientPackage::class, "purchase_id");
    }

    /**
     * Get all files associated with the ClientPackage.
     */
    public function files()
    {
        return $this->morphMany(File::class, 'belongs');
    }

    public function interests(): MorphMany
    {
        return $this->morphMany(WalletTransaction::class, 'source');
    }

    public function markDocUploaded()
    {
        $this->docs_uploaded = 1;
        $this->save();

        return $this;
    }

    public function markMouSent()
    {
        $this->mou_sent = 1;
        $this->save();

        return $this;
    }
}
