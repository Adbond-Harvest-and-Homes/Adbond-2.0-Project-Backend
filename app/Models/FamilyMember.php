<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyMember extends Model
{
    use HasFactory;

    public static $type = "app\Models\FamilyMember";

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function orders()
    {
        return $this->morphMany(Order::class, 'owner');
    }

    public function clientPackages()
    {
        return $this->morphMany(ClientPackage::class, 'owner');
    }

    public function photo()
    {
        return $this->belongsTo(File::class, "photo_id", "id");
    }

    public function getFullNameAttribute()
    {
        $fullname = '';
        if ($this->title) $fullname .= $this->title . ' ';
        $fullname .= $this->firstname . ' ' . $this->lastname;
        return $fullname;
    }
}
