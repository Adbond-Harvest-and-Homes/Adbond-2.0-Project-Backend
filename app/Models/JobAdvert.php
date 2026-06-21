<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobAdvert extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $fillable = [
        "title",
        "slug",
        "department_id",
        "employment_type_id",
        "location",
        "slots",
        "deadline",
        "description",
        "is_open",
        "opened_on"
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($jobAdvert) {
            $jobAdvert->opened_on = now();
        });
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function employmentType()
    {
        return $this->belongsTo(EmploymentType::class);
    }
}
