<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualStaffCategory extends Model
{
    use HasFactory;

    protected $fillable = ["name"];
}
