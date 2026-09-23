<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageRequest extends Model
{
    use HasFactory;

    protected $table = "package_requests";

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
