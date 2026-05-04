<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientBondRequest extends Model
{
    use HasFactory;

    public function bond()
    {
        return $this->belongsTo(ClientBond::class);
    }
}
