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

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function employmentType()
    {
        return $this->belongsTo(EmploymentType::class);
    }

    public function benefits()
    {
        return $this->belongsToMany(JobBenefit::class, "job_advert_benefits", "advert_id", "benefit_id");
    }

    public function responsibilities()
    {
        return $this->belongsToMany(JobResponsibility::class, "job_advert_responsibilities", "advert_id", "responsibility_id");
    }

    public function requirements()
    {
        return $this->belongsToMany(JobRequirement::class, "job_advert_requirements", "advert_id", "requirement_id");
    }



    public function jobBenefits()
    {
        return $this->hasMany(JobAdvertBenefit::class, "advert_id", "id");
    }

    public function jobRequirements()
    {
        return $this->hasMany(JobAdvertRequirement::class, "advert_id", "id");
    }

    public function jobResponsibilities()
    {
        return $this->hasMany(JobAdvertResponsibility::class, "advert_id", "id");
    }



    protected static function boot()
    {
        parent::boot();

        static::creating(function ($jobAdvert) {
            $jobAdvert->opened_on = now();
        });

        static::deleting(function ($jobAdvert) {
            $jobAdvert->benefits()->detach();
            $jobAdvert->requirements()->detach();
            $jobAdvert->responsibilities()->detach();
        });
    }
}
