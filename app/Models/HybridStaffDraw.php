<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HybridStaffDraw extends Model
{
    use HasFactory;

    protected $table = 'hybrid_staff_draws';

    protected $fillable = [
        'total',
        'selected',
        'completed',
    ];

    protected $casts = [
        'completed' => 'boolean',
    ];
}
