<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientBondPayoutSummary extends Model
{
    protected $table = 'client_bond_payout_summary';

    public $timestamps = false;

    protected $guarded = [];

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'next_payout_date' => 'date',
        ];
    }
}
