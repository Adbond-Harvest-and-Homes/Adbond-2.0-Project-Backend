<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffTypeUserSummary extends Model
{
    use HasFactory;

    protected $table = "staff_type_user_summary";

    public $timestamps = false;

    protected $guarded = [];

    public $incrementing = false;
}
