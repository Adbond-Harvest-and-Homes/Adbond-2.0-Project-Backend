<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstallmentDiscount extends Model
{
    use HasFactory;

    protected $fillable = ["duration", "discount"];
}
