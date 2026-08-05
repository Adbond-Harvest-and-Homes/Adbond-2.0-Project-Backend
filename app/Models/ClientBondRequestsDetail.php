<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientBondRequestsDetail extends Model
{
    protected $table = 'client_bond_requests_detail';

    public $timestamps = false;

    protected $guarded = [];

    public $incrementing = false;
}
