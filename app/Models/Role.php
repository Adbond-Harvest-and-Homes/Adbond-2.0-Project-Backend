<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use app\Enums\Roles;

class Role extends Model
{
    use HasFactory;

    public static function SuperAdmin()
    {
        return self::where('name', Roles::SUPER_ADMIN->value)->first();
    }

    public static function Admin()
    {
        return self::where('name', Roles::ADMIN->value)->first();
    }

    public static function HR()
    {
        return self::where('name', Roles::HUMAN_RESOURCE->value)->first();
    }

    public static function logistics()
    {
        return self::where('name', Roles::LOGISTICS->value)->first();
    }

    public static function CustomerRelation()
    {
        return self::where('name', Roles::CUSTOMER_RELATION->value)->first();
    }

    public static function OperationAndAccount()
    {
        return self::where('name', Roles::OPERATION_ACCOUNTING->value)->first();
    }

    public static function ContentManagement()
    {
        return self::where('name', Roles::CONTENT_MANAGEMENT->value)->first();
    }
}
