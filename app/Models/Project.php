<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    public static $type = "app\Models\Project";

    // public function getCreatedAtAttribute($value)
    // {
    //     return \Carbon\Carbon::parse($value)->diffForHumans();
    // }
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function canDelete()
    {
        return ($this->packages->count() == 0);
    }

    public function projectType()
    {
        return $this->belongsTo("app\Models\ProjectType");
    }

    public function stateModel()
    {
        return $this->belongsTo(State::class);
    }

    // public function locations()
    // {
    //     return $this->hasMany("app\Models\ProjectLocation");
    // }

    public function packages($limit = null)
    {
        $query = $this->hasMany(Package::class)->orderBy('created_at', 'DESC');
        return ($limit) ? $query->limit($limit) : $query;
    }

    /**
     * Get all promos for this project
     */
    public function promoProducts()
    {
        return $this->morphMany(PromoProduct::class, 'product');
    }

    public function promos()
    {
        return $this->hasManyThrough(
            Promo::class,
            PromoProduct::class,
            'product_id',   // Foreign key on promo_products table...
            'id',           // Foreign key on promos table...
            'id',           // Local key on projects table...
            'promo_id'      // Local key on promo_products table...
        )->where('promo_products.product_type', '=', self::$type);
    }

    protected static function boot()
    {
        parent::boot();

        self::updating(function (Project $project) {
            if($project->active == 0) {
                if($project->packages->count() > 0) {
                    foreach($project->packages as $package) {
                        $package->active = 0;
                        $package->update();
                    }
                }
            }
        
        });

        self::deleting(function (Project $project) {
            if($project->packages->count() > 0) {
                foreach($project->packages as $package) {
                    $package->delete();
                }
            }

            if($project->promoProducts->count() > 0) {
                foreach($project->promoProducts as $promoProduct) {
                    $promoProduct->delete();
                }
            }
        });
    }
}
