<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    public static $type = "app\Models\Package";

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'active' => 'boolean',
        'installment_option' => 'boolean',
        'size' => 'double',
        'amount' => 'double',
        'discount' => 'double',
        'min_price' => 'double',
        'infrastructure_fee' => 'double',
        'redemption_options' => 'array',
    ];

    public function canDelete()
    {
        return ($this->assets->count() == 0);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function stateModel()
    {
        return $this->belongsTo(State::class, "state_id", "id");
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function brochure()
    {
        return $this->belongsTo(File::class);
    }

    public function redemptionPackage()
    {
        return $this->belongsTo(Package::class, "redemption_package_id", "id");
    }

    // public function sizes()
    // {
    //     return $this->hasMany(PackageSize::class);
    // }

    public function packagePhotos()
    {
        return $this->hasMany(PackagePhoto::class);
    }

    public function benefits()
    {
        return $this->belongsToMany(Benefit::class, "package_benefits", "package_id", "benefit_id", "id");
    }

    public function packageBenefits()
    {
        return $this->hasMany(PackageBenefit::class);
    }

    public function packageMedia()
    {
        return $this->hasMany(PackageMedia::class);
    }

    public function media()
    {
        return $this->belongsToMany(File::class, "package_media", "package_id", "file_id", "id");
    }

    public function assets()
    {
        return $this->hasMany(ClientPackage::class, "package_id", "id");
    }

    public function promoProducts()
    {
        return $this->morphMany(PromoProduct::class, 'product');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /*
        Gets the total number of units or slots(for the case of co-ownership bonds) that has been purchased for a package
    */
    public function purchasedUnits()
    {
        return $this->orders()->sum("units");
    }

    /**
     * Get all promos for this package
     */
    public function promos()
    {
        return $this->hasManyThrough(
            Promo::class,
            PromoProduct::class,
            'product_id',   // Foreign key on promo_products table...
            'id',           // Foreign key on promos table...
            'id',           // Local key on packages table...
            'promo_id'      // Local key on promo_products table...
        )->where('promo_products.product_type', '=', self::$type);
    }

    protected static function boot()
    {
        parent::boot();

        self::updating(function (Package $package) {
            //
        });

        self::deleting(function (Package $package) {
            if($package->assets->count() > 0) {
                foreach($package->assets as $asset) {
                    $asset->delete();
                }
            }

            if($package->packageMedia->count() > 0) {
                foreach($package->packageMedia as $media) {
                    $media->delete();
                }
            }

            if($package->packagePhotos->count() > 0) {
                foreach($package->packagePhotos as $photo) {
                    $photo->delete();
                }
            }

            if($package->packageBenefits()->count() > 0) {
                foreach($package->packageBenefits as $benefit) {
                    $benefit->delete();
                }
            }

            if($package->promoProducts->count() > 0) {
                foreach($package->promoProducts as $promoProduct) {
                    $promoProduct->delete();
                }
            }

            if($package->brochure) $package->brochure->delete();
        });
    }
}
