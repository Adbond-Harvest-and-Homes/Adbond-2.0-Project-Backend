<?php 

namespace app\Services;

use PDF;

use Illuminate\Support\Facades\DB;

use app\Services\OrderService;
use app\Services\FileService;
use app\Services\ClientPackageService;
use app\Services\ClientInvestmentService;

use app\Enums\FileTypes;
use app\Enums\FilePurpose;
use app\Enums\UserType;

use app\Models\ClientPackage;
use app\Models\ClientInvestment;
use app\Models\Order;

use app\Helpers;
use App\Utilities;

class ContractService 
{
    public function generateContract($order, $isOffer, $preparedData=null)
    {
        $file = "files/contract_{$order->id}.pdf";
        $publicFile = public_path($file);

        if(file_exists($publicFile)) return $file;
        
        if($isOffer) Helpers::$purchaseOrigin = ClientPackageOrigin::OFFER->value;
        $data = ($preparedData) ? $preparedData : Helpers::prepareContract($order);
        if(!isset($data['project']) || $data['project']==null) $data['project'] = '';
        if(!isset($data['package']) || $data['package']==null) $data['package'] = '';
        if(!isset($data['client']) || $data['client']==null) $data['client'] = '';
        if(!isset($data['address']) || $data['address']==null) $data['address'] = '';
        if(!isset($data['state']) || $data['state']==null) $data['state'] = '';
        if(!isset($data['size']) || $data['size']==null) $data['size'] = '';
        if(!isset($data['price']) || $data['price']==null) $data['price'] = '';
        if(!isset($data['installment_duration']) || $data['installment_duration']==null) $data['installment_duration'] = 12;
        $data['location'] = (!isset($data['location']) || $data['location']==null) ? '' : $data['location'];
        $pdfData = [
            'image' => public_path('images/logo.PNG'),
            'day' => date('jS'),
            'month' => date('F'),
            'year' => date('Y'),
            'project' => $data['project'],
            'product_name' => $data['package'],
            'name' => $data['client'],
            'state' => $data['state'],
            'address' => $data['address'],
            'amount' => (float)$data['price'],
            'size' => (float)$data['size'],
            'location' => $data['location'],
            'installment_duration' => $data['installment_duration'],
            'installment' => $data['installment'],
            'payment_plan' => $data['installment'] ? "Installment" : "Full Payment",
            'payment_terms' => $data['installment'] ? $data['installment_duration'] . 'Months Payment Duration' : "",
            'use_type' => $data['use_type']
        ];
        $pdf = PDF::loadView('pdf/contract', $pdfData);
        // return $pdf->stream('contract.pdf');
        
        // $path = public_path($publicFile);
        $pdf->save($publicFile);
        return $file;
    }

    public function uploadContract($purchase, $asset)
    {
        $clientPackageService = new ClientPackageService;
        $fileService = new FileService;
        $uploadedFile = "files/contract_{$purchase->id}.pdf";
        $publicFile = public_path($uploadedFile);
        // dd('generate Contract');
        if(!file_exists($publicFile)) {
            if($asset->contract_file_id) return null;

            $uploadedFile = $this->generateContract($purchase, ($asset->purchase_type != Order::$type));
        }

        DB::beginTransaction();
        try{
            $response = Helpers::moveUploadedFileToCloud($uploadedFile, FileTypes::PDF->value, $asset->client->id, 
                                FilePurpose::CONTRACT->value, UserType::CLIENT->value, "client-contracts");
            if($response['success']) {
                $fileMeta = ["belongsId"=>$asset->id, "belongsType"=>ClientPackage::$type];
                $fileService->updateFileObj($fileMeta, $response['upload']['file']);

                $clientPackageService->update(['contractFileId' => $response['upload']['file']->id], $asset);
                
                // dd("got here");
                $asset->markDocUploaded();
                DB::commit();
                return $uploadedFile;
            }
        }catch(\Exception $e) {
            DB::rollBack();

            Utilities::error($e);

            return null;
        }
    }

    public function generateMOU($order)
    {
        $file = "files/memorandum_agreement_{$order->id}.pdf";
        $publicFile = public_path($file);

        if(file_exists($publicFile)) return $file;

        $data = Helpers::prepareContract($order);
        if(!isset($data['project']) || $data['project']==null) $data['project'] = '';
        if(!isset($data['package']) || $data['package']==null) $data['package'] = '';
        if(!isset($data['client']) || $data['client']==null) $data['client'] = '';
        if(!isset($data['address']) || $data['address']==null) $data['address'] = '';
        if(!isset($data['state']) || $data['state']==null) $data['state'] = '';
        if(!isset($data['size']) || $data['size']==null) $data['size'] = '';
        if(!isset($data['price']) || $data['price']==null) $data['price'] = '';
        if(!isset($data['installment_duration']) || $data['installment_duration']==null) $data['installment_duration'] = 12;
        $data['location'] = (!isset($data['location']) || $data['location']==null) ? '' : $data['location'];
        $pdfData = [
            'image' => public_path('images/logo.PNG'),
            'day' => date('jS'),
            'month' => date('F'),
            'year' => date('Y'),
            'project' => $data['project'],
            'package' => $data['package'],
            'client' => $data['client'],
            'state' => $data['state'],
            'address' => $data['address'],
            'price' => (float)$data['price'],
            'size' => (float)$data['size'],
            'location' => $data['location'],
            'installment_duration' => $data['installment_duration'],
            'installment' => $data['installment']
        ];
        $pdf = PDF::loadView('pdf/memorandum_agreement', $pdfData);
        // return $pdf->stream('memorandum_agreement.pdf');
        // $file = "files/memorandum_agreement_{$order->id}.pdf";
        $pdf->save($publicFile);
        return $file;
    }
}