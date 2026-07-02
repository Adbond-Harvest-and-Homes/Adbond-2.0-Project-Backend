<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

use app\Enums\AssetSwitchType;

use app\Services\FileService;

use app\Enums\FilePurpose;

class ClientPackage extends Model
{
    use HasFactory;

    public static $type = "app\Models\ClientPackage";

    /**
     * Get the parent purchase model (Order or Offer).
     */
    public function purchase(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get all files associated with the ClientPackage.
     */
    public function files()
    {
        return $this->morphMany(File::class, 'belongs');
    }

    public function doa()
    {
        return $this->files()->where('purpose', FilePurpose::DEED_OF_ASSIGNMENT->value)->first();
    }

    /**
     * Relationship to Client
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relationship to Package
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function assetSwitchRequests()
    {
        return $this->hasMany(DowngradeUpgradeRequest::class, "client_package_id", "id");
    }

    public function upgradeRequests()
    {
        return $this->assetSwitchRequests()->where("type", AssetSwitchType::UPGRADE->value)->whereNull("approved")->get();
    }

    public function downgradeRequests()
    {
        return $this->assetSwitchRequests()->where("type", AssetSwitchType::DOWNGRADE->value)->whereNull("approved")->get();
    }

    public function requestedSwitch()
    {
        $requested = false;
        if($this->assetSwitchRequests->count() > 0) {
            foreach($this->assetSwitchRequests as $assetSwitch) {
                if($assetSwitch->approved == null) $requested = true;
            }
        }
        return $requested;
        // return ($this->upgradeRequests()->count() > 0 || $this->downgradeRequests()->count());
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    // I want to get the offer that this asset has been offered
    public function activeOffer()
    {
        return $this->offers()->where("approved", "!=", 0);
    }

    // I want to get the offer that this asset has been offered
    public function onOffer()
    {
        return $this->activeOffer->count() > 0;
    }

    public function markDocUploaded()
    {
        $this->docs_uploaded = 1;
        $this->save();

        return $this;
    }

    public function markContractSent()
    {
        $this->contract_sent = 1;
        $this->save();

        return $this;
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($clientPackage) {
            if($clientPackage->contract_file_id) self::updateFile($clientPackage->contract_file_id, $clientPackage);
            if($clientPackage->happiness_letter_file_id) self::updateFile($clientPackage->happiness_letter_file_id, $clientPackage);
            if($clientPackage->doa_file_id) self::updateFile($clientPackage->doa_file_id, $clientPackage);
        });

    }

    private static function updateFile($fileId, $clientPackage)
    {
        $fileService = new FileService;
        $file = $fileService->getFile($fileId);
        if($file && (!$file->belongs_id || !$file->belongs_type)){
            $fileMeta = ["belongsId"=>$clientPackage->id, "belongsType"=>self::$type];
            $fileService->updateFileObj($fileMeta, $file);
        }
    }
    
}
