<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Http\Controllers\Controller;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use app\Http\Requests\User\CreatePromo;
use app\Http\Requests\User\UpdatePromo;
use app\Http\Requests\User\Toggle;
use app\Http\Requests\User\AddPromoProduct;
use app\Http\Requests\User\DeletePromoProduct;

use app\Http\Resources\PromoResource;

use app\Services\PromoService;
use app\Services\PromoCodeService;
use app\Services\ProjectService;
use app\Services\PackageService;

use app\Models\Package;
Use app\Models\Project;

use app\Enums\PromoProductType;

use app\Utilities;

class PromoController extends Controller
{
    private $promoService;
    private $projectService;
    private $packageService;
    private $promoCodeService;

    public function __construct()
    {
        $this->promoService = new PromoService;
        $this->promoCodeService = new PromoCodeService;
        $this->projectService = new ProjectService;
        $this->packageService = new PackageService;
    }

    public function create(CreatePromo $request)
    {
        try{
            $data = $request->validated();

            if(isset($data['products'])) {
                foreach($data['products'] as $product) {
                    if($product['type'] == PromoProductType::PACKAGE->value) {
                        $package = $this->packageService->package($product['id']);
                        if(!$package) return Utilities::error402("This Package Id ".$product['id']." is not valid");
                    }else{
                        $project = $this->projectService->project($product['id']);
                        if(!$project) return Utilities::error402("This Project Id ".$product['id']." is not valid");
                    }
                }
                $data["packageLimited"] = true;
            }

            DB::beginTransaction();
            $data['userId'] = Auth::user()->id;
            $promo = $this->promoService->save($data);

            if(isset($data['promoCode'])) {
                foreach($data['promoCode'] as $promoCode) {
                    $promoCodeData = $promoCode;
                    $promoCodeData['promoId'] = $promo->id;
                    $promoCode = $this->promoCodeService->save($promoCodeData);
                }
            }

            if(isset($data['products'])) {
                $products = [];
                foreach($data['products'] as $product) {
                    $product['promoId'] = $promo->id;
                    $product['type'] = ($product['type'] == PromoProductType::PACKAGE->value) ? Package::$type : Project::$type;
                    $products[] = $product;
                }
                $this->promoService->savePromoProducts($products);
            }

            DB::commit();

            $promo = $this->promoService->getPromo($promo->id, ['packages', 'projects', 'promoProducts']);

            return Utilities::ok(new PromoResource($promo));

        }catch(\Exception $e){
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function update(UpdatePromo $request, $promoId)
    {
        try{
            if (!is_numeric($promoId) || !ctype_digit($promoId)) return Utilities::error402("Invalid parameter promoId");

            $promo = $this->promoService->getPromo($promoId);
            if(!$promo) return Utilities::error402("Promo not found");

            if($promo->active == 1) { // do not allow discount to be changed for active promos
                if(isset($data['discount'])) unset($data['discount']);
                if(isset($data['discountAmount'])) unset($data['discountAmount']);
            }else{
                if(isset($data['discount']) && $data['discount']) {
                    if(isset($data['discountAmount'])) unset($data['discountAmount']);
                }
                if(isset($data['discountAmount']) && $data['discountAmount']) {
                    if(isset($data['discount'])) unset($data['discount']);
                }
            }

            $data = $request->validated();

            $promo = $this->promoService->update($data, $promo);

            return Utilities::ok(new PromoResource($promo));
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function toggleActivate(Toggle $request)
    {
        $promo = $this->promoService->getPromo($request->validated("id"));

        if(!$promo) return Utilities::error402("Promo not found");

        $promo = $this->promoService->toggleActivate($promo);

        $action = ($promo->active) ? "Activated" : "Deactivated";

        return Utilities::okay("Promo has been ".$action);
    }

    public function addProducts(AddPromoProduct $request)
    {
        try{
            $data = $request->validated();

            $promo = $this->promoService->getPromo($data['promoId']);
            if(!$promo) return Utilities::error402("Promo not found");

            $products = [];
            foreach($data['products'] as $product) {
                if($product['type'] == PromoProductType::PACKAGE->value) {
                    $package = $this->packageService->package($product['id']);
                    if(!$package) return Utilities::error402("This Package Id ".$product['id']." is not valid");

                    $type = Project::$type;
                    $projectId = $package->project_id;
                    $existingProduct = $this->promoService->getPromoProductByDetail($data['promoId'], $type, $projectId);

                    if(!$existingProduct) {
                        $type = Package::$type;
                        $existingProduct = $this->promoService->getPromoProductByDetail($data['promoId'], $type, $product['id']);
                    }
                }else{
                    $project = $this->projectService->project($product['id']);
                    if(!$project) return Utilities::error402("This Project Id ".$product['id']." is not valid");

                    $type = Project::$type;
                    $existingProduct = $this->promoService->getPromoProductByDetail($data['promoId'], $type, $product['id']);
                }

                if(!$existingProduct) {
                    $product['promoId'] = $data['promoId'];
                    $product['type'] = $type;
                    $products[] = $product;
                }
            }

            $this->promoService->savePromoProducts($products);
            $promo = $this->promoService->getPromo($data['promoId'], ['packages', 'projects']);

            return Utilities::ok(new PromoResource($promo));

        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function removeProduct(DeletePromoProduct $request)
    {
        try{
            $data = $request->validated();
            $data['type'] = ($data['type'] == PromoProductType::PACKAGE->value) ? Package::$type : Project::$type;
            $product = $this->promoService->getPromoProductByDetail($data['promoId'], $data['type'], $data['id']);
            if(!$product) return Utilities::error402("Promo Product not found");

            $this->promoService->removePromoProduct($product);

            $promo = $this->promoService->getPromo($data['promoId'], ['packages', 'projects']);

            return Utilities::ok(new PromoResource($promo));
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function promo($promoId)
    {
        try{
            if (!is_numeric($promoId) || !ctype_digit($promoId)) return Utilities::error402("Invalid parameter promoId");

            $promo = $this->promoService->getPromo($promoId);
            if(!$promo) return Utilities::error402("Promo not found");

            return Utilities::ok(new PromoResource($promo));
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function promos()
    {
        $promos = $this->promoService->promos(['packages', 'projects', 'promoCodes']);

        return Utilities::ok(PromoResource::collection($promos));
    }

    public function delete($promoId)
    {
        try{
            if (!is_numeric($promoId) || !ctype_digit($promoId)) return Utilities::error402("Invalid parameter promoId");

            $promo = $this->promoService->getPromo($promoId);
            if(!$promo) return Utilities::error402("Promo not found");

            $this->promoService->delete($promo);

            return Utilities::okay("Promo Deleted");
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }
}
