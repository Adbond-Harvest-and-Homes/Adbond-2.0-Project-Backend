<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientBond extends Model
{
    use HasFactory;

    public function getTotalAttribute()
    {
        return $this->current_capital + $this->payouts->sum('payout_amount');
    }

    public static $type = "app\Models\ClientBond";

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

    public function parentBond()
    {
        return $this->belongsTo(ClientBond::class);
    }

    public function clientPackage()
    {
        return $this->hasOne(ClientPackage::class, "purchase_id");
    }

    public function payouts()
    {
        return $this->hasMany(ClientBondPayout::class);
    }

    /**
     * Get all files associated with the ClientPackage.
     */
    public function files()
    {
        return $this->morphMany(File::class, 'belongs');
    }

    public function mou()
    {
        return $this->belongsTo(File::class, "mou_file_id", "id");
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
