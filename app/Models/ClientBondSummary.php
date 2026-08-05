<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientBondSummary extends Model
{
    protected $table = 'client_bond_summary';

    public $timestamps = false;

    protected $guarded = [];

    public $incrementing = false;
}
