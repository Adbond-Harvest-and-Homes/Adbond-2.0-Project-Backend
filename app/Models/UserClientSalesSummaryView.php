<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserClientSalesSummaryView extends Model
{
    protected $table = "user_client_sales_summary";
    
    public $timestamps = false;

    protected $guarded = [];

    public $incrementing = false;
}
