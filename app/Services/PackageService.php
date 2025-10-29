<?php

namespace app\Services;

use Maatwebsite\Excel\Facades\Excel;
use PDF;

use app\Models\Package;
use app\Models\PackageSize;
use app\Models\PackageMedia;

use app\Enums\ProjectFilter;
use app\Enums\PackageType;


use app\Exports\PackageExport;

class PackageService
{
    public $count = false;
    public $projectId = null;
    public $active = null;

    public $all = null;

    public function save($data)
    {
        $this->projectId = $data['projectId'];
        $package = $this->getByName($data['name']);
        if(!$package) {
            $package = new Package;
            $package->name = $data['name'];
            $package->project_id = $data['projectId'];
            $package->user_id = $data['userId'];

            $package->state = $data['state'];
            if(isset( $data['address'])) $package->address = $data['address'];
            if(isset($data['size'])) $package->size = $data['size'];
            $package->amount = $data['amount'];
            $package->units = $data['units'];
            $package->available_units = $data['units'];
            if(isset( $data['discount'])) $package->discount = $data['discount'];
            if(isset( $data['minPrice'])) $package->min_price = $data['minPrice'];
            if(isset( $data['installmentDuration'])) $package->installment_duration = $data['installmentDuration'];
            if(isset( $data['infrastructureFee'])) $package->infrastructure_fee = $data['infrastructureFee'];

            if(isset($data['description'])) $package->description = $data['description'];
            // if(isset($data['benefits'])) $package->benefits = $data['benefits'];
            if(isset($data['brochureFileId'])) $package->brochure_file_id = $data['brochureFileId'];
            if(isset($data['installmentOption'])) $package->installment_option = $data['installmentOption'];
            if(isset($data['vrUrl'])) $package->vr_url = $data['vrUrl'];

            if(isset($data['type'])) $package->type = $data['type'];
            if(isset($data['type']) && $data['type'] == PackageType::INVESTMENT->value) {
                $package->interest_return_duration = $data['interestReturnDuration'];
                $package->interest_return_timeline = $data['interestReturnTimeline'];
                if(isset($data['interestReturnPercentage'])) $package->interest_return_percentage = $data['interestReturnPercentage'];
                if(isset($data['interestReturnAmount'])) $package->interest_return_amount = $data['interestReturnAmount'];
                $package->redemption_options = $data['redemptionOptions'];
                $package->redemption_package_id = $data['redemptionPackageId'];
            }
            $package->save();

            if(isset($data['benefits'])) $package->benefits()->attach($data['benefits']);
            if(isset($data['packageMediaIds'])) $package->media()->attach($data['packageMediaIds']);
            return $package;
        }
        return null;
    }

    public function saveMedia($fileIds, $package)
    {
        foreach($fileIds as $fileId) {
            $packagePhoto = PackagePhoto::where("package_id", $package->id)->where("photo_id", $fileId)->first();
            if(!$packagePhoto) {
                $packagePhoto = new PackagePhoto;
                $packagePhoto->package_id = $package->id;
                $packagePhoto->photo_id = $fileId;
                $packagePhoto->save();
            }
        }
    }

    public function update($data, $package)
    {
        if(isset($data['name'])) $package->name = $data['name'];
        if(isset( $data['projectId'])) $package->project_id = $data['projectId'];
        if(isset( $data['stateId'])) $package->state_id = $data['stateId'];
        if(isset( $data['address'])) $package->address = $data['address'];
        if(isset( $data['size'])) $package->size = $data['size'];
        if(isset( $data['amount'])) $package->amount = $data['amount'];
        if(isset( $data['units'])) $package->units = $data['units'];
        if(isset( $data['availableUnits'])) $package->available_units = $data['availableUnits'];
        if(isset( $data['discount'])) $package->discount = $data['discount'];
        if(isset( $data['minPrice'])) $package->min_price = $data['minPrice'];
        if(isset( $data['installmentDuration'])) $package->installment_duration = $data['installmentDuration'];
        if(isset( $data['infrastructureFee'])) $package->infrastructure_fee = $data['infrastructureFee'];

        if(isset($data['description'])) $package->description = $data['description'];
        if(isset($data['brochureFileId'])) $package->brochure_file_id = $data['brochureFileId'];
        if(isset($data['installmentOption'])) $package->installment_option = $data['installmentOption'];
        if(isset($data['vrUrl'])) $package->vr_url = $data['vrUrl'];  

        if(isset($data['type'])) $package->type = $data['type'];
        if(isset($data['interestReturnDuration'])) $package->interest_return_duration = $data['interestReturnDuration'];
        if(isset($data['interestReturnTimeline'])) $package->interest_return_timeline = $data['interestReturnTimeline'];
        if(isset($data['interestReturnPercentage'])) $package->interest_return_percentage = $data['interestReturnPercentage'];
        if(isset($data['interestReturnAmount'])) $package->interest_return_amount = $data['interestReturnAmount'];
        if(isset($data['redemptionOptions'])) $package->redemption_options = json_encode($data['redemptionOptions']);
        if(isset($data['redemptionPackageId'])) $package->redemption_package_id = $data['redemptionPackageId'];

        $package->update();

        if(isset($data['benefits'])) $package->benefits()->sync($data['benefits']);
        if(isset($data['packageMediaIds'])) $package->media()->attach($data['packageMediaIds']);

        return $package;
    }

    public function markAsSoldOut($package)
    {
        $package->sold_out = true;
        $package->update();
        return $package;
    }

    public function deductUnits($units, $package)
    {
        $package->units = $package->units - $units;
        if($package->units < 0) $package->units = 0;
        $package->update();
        return $package;
    }

    public function markAsBackInStock($package)
    {
        $package->sold_out = false;
        $package->update();
        return $package;
    }

    public function activate($project)
    {
        $project->active = true;
        $project->update();
        return $project;
    }

    public function deactivate($project)
    {
        $project->active = false;
        $project->deactivated_at = now();
        $project->update();
        return $project;
    }

    public function delete($package)
    {
        if($package->canDelete()) {
            $package->delete();
        }
    }

    // public function removePhotos($fileIds, $package)
    // {
    //     // $packagePhotos = $package->packagePhotos()->whereIn('photo_id', $fileIds)->get();
    //     foreach ($packagePhotos as $packagePhoto) $packagePhoto->delete(); 

    // }

    public function packages($with=[], $offset=0, $perPage=null)
    {
        $query = Package::with($with);
        if($this->projectId) $query = $query->where("project_id", $this->projectId);
        if($this->active != null) $query->where("active", $this->active);
        if($this->count) return $query->count();

        if($perPage==null) return $query->orderBy("created_at", "DESC")->get(); // $perPage=env('PAGINATION_PER_PAGE');
        return $query->offset($offset)->limit($perPage)->orderBy("created_at", "DESC")->get();
    }

    public function activePackages($with=[], $offset=0, $perPage=null)
    {
        $query = Package::with($with)->where("active", 1);
        if($this->projectId) $query = $query->where("project_id", $this->projectId);
        if($this->count) return $query->count();

        if($perPage==null) $perPage=env('PAGINATION_PER_PAGE');
        return $query->offset($offset)->limit($perPage)->orderBy("created_at", "DESC")->get();
    }

    public function package($id, $with=[])
    {
        return Package::with($with)->where("id", $id)->first();
    }

    public function getByName($name, $with=[])
    {
        $query = Package::with($with)->where("name", $name);
        if($this->projectId) $query = $query->where("project_id", $this->projectId);

        return $query->first();
    }

    public function getPackageMediaIds($package)
    {
        return PackageMedia::where("package_id", $package->id)->pluck("file_id")->toArray();
    }

    public function filter($filter, $with=[], $offset=0, $perPage=null)
    {
        $query = Package::with($with);
        if($this->projectId) $query = $query->where("project_id", $this->projectId);
        if(isset($filter['text'])) $query->where("name", "LIKE", "%".$filter['text']."%");
        if(isset($filter['date'])) $query = $query->whereDate("created_at", $filter['date']);
        if(isset($filter['status'])) $query = ($filter['status'] == ProjectFilter::ACTIVE->value) ? $query->where("active", true) : $query->where("active", false);
        if($this->count) return $query->count();
        if($this->all) return $query->orderBy("created_at", "DESC")->get();
        return $query->orderBy("created_at", "DESC")->offset($offset)->limit($perPage)->get();
    }

    public function search($text, $offset=0, $perPage=null)
    {
        $query = Package::query();
        if($this->projectId) $query = Package::where("project_id", $this->projectId);
        if($text != null) $query = $query->where("name", "LIKE", "%".$text."%");
        
        if($this->count) return $query->count();
        return $query->orderBy("created_at", "DESC")->offset($offset)->limit($perPage)->get();
    }

    public function exportToExcel($packages, $headingConfig = null)
    {
        return Excel::download(
            new PackageExport($packages, $headingConfig), 
            'packages-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportToPDF($packages, $projectName, $headingConfig = null)
    {
        $export = new PackageExport($packages, $headingConfig);
        $data = [
            'headings' => $export->headings(),
            'packages' => $packages,
            'projectName' => $projectName,
            'mappedData' => $packages->map(function ($package) use ($export) {
                return $export->map($package);
            })
        ];

        $pdf = PDF::loadView('exports.pdf.packages', $data);
        return $pdf->download('packages-' . now()->format('Y-m-d') . '.pdf');
    }
}