<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsCampaign extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $fillable = [
        'created_by',
        'sender_id',
        'message',
        'status',
        'total_recipients',
        'sent_count',
        'failed_count',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients()
    {
        return $this->hasMany(SmsCampaignRecipient::class);
    }
}
