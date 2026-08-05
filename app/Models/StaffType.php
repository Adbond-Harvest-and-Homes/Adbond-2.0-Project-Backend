<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use app\Enums\StaffTypes;

class StaffType extends Model
{
    use HasFactory;

    public static function PhysicalStaff()
    {
        return self::where('name', StaffTypes::PHYSICAL_STAFF->value)->first();
    }

    public static function HybridStaff()
    {
        return self::where('name', StaffTypes::HYBRID_STAFF->value)->first();
    }

    public static function VirtualStaff()
    {
        return self::where('name', StaffTypes::VIRTUAL_STAFF->value)->first();
    }
}
