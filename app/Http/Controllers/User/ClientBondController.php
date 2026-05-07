<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use app\Http\Controllers\Controller;

use app\Exceptions\AppException;

use app\Http\Requests\User\RejectBondRequest;
use app\Http\Requests\User\UploadBondDocuments;

use app\Http\Resources\ClientBondResource;
use app\Http\Resources\ClientBondRequestResource;

use app\Services\ClientBondService;
use app\Services\ClientBondRequestService;
use app\Services\OrderService;
use app\Services\FileService;

use app\Enums\ClientBondRequestType;
use app\Enums\ClientBondStatus;
use app\Enums\OrderType;
use app\Enums\FilePurpose;

use app\Models\User;

use app\Utilities;

class ClientBondController extends Controller
{
    public function __construct(protected ClientBondService $bondService, 
                                    protected ClientBondRequestService $requestService,
                                    protected NotificationService $notificationService,
                                    protected OrderService $orderService,
                                    protected FileService $fileService
                                )
    {
    }

    public function summary()
    {
        $summary = $this->bondService->getSummary();

        return Utilities::ok([
            "pendingRequests" => $summary->total_pending_requests,
            "activeInvestments" => $summary->active_investments,
            "totalRenewals" => $summary->total_renewals,
            "totalRenewalsThisMonth" => $summary->total_renewals_this_month,
            "totalAmountLiquidated" => $summary->total_amount_liquidated
        ]);
    }

    public function bonds(Request $request)
    {
        $status = ($request->query('status')) ?? null;
        if($status) $this->bondService->status = $status;

        $this->bondService->paginated = true;
        $this->bondService->page = (int) $request->query('page', 1);
        $this->bondService->limit = (int) $request->query('perPage', env('PAGINATION_PER_PAGE', 10));

        $bonds = $this->bondService->getBonds();

        $meta = [
            'page' => $bonds->currentPage(),
            'perPage' => $bonds->perPage(),
            'total' => $bonds->total(),
            'lastPage' => $bonds->lastPage()
        ];

        return Utilities::paginatedOk2(ClientBondResource::collection($bonds), $meta);
    }

    public function requests(Request $request)
    {
        $type = ($request->query('type')) ?? null;
        $status = ($request->query('status')) ?? null;
        
        if($type) $this->requestService->type = $type;
        switch($status) {
            case 'approved' : $this->requestService->approved = 1; break;
            case 'rejected' : $this->requestService->approved = 0; break;
        }

        $requests = $this->requestService->getRequests(['bond.client']);

        return Utilities::ok(ClientBondRequestResource::collection($requests));
    }

    public function approve($id)
    {
        $request = $this->requestService->getRequest($id);
        if(!$request) return Utilities::error402("Request not found");

        $bond = $request->bond;

        if(!$bond) return Utilities::error402("The bond for this request was not found");
        
        DB::beginTransaction();

        try{
            $this->requestService->approve($request);

            if($request->type = ClientBondRequestType::LIQUIDATION->value) {
                $this->bondService->redeem($bond);

                $this->bondService->updateStatus($bond, ClientBondStatus::LIQUIDATED->value);
            }else{
                $package = $bond->package;

                // build the new order
                $orderData = [
                    "clientId" => $bond->client_id,
                    "packageId" => $package->id,
                    "units" => $bond->order->units,
                    "amountPayable" => $bond->order->amount_payable,
                    "amountPayed" => $bond->order->amount_payable,
                    "balance" => 0,
                    "unitPrice" => $bond->order->unit_price,
                    "isInstallment" => 0,
                    "paymentStatusId" => $bond->order->payment_status_id,
                    "orderDate" => now(),
                    "type" => OrderType::BOND_REINVEST->value
                ];

                // create new order
                $order = $this->orderService->save($orderData);

                $renewedBond = $this->bondService->renew($bond, $order);

                $this->bondService->updateStatus($bond, ClientBondStatus::RENEWAL->value);
            }

            DB::commit();

            return Utilities::okay("Request Approved Successfully");
        }catch(AppException $e) {
            DB::rollBack();
            throw $e;
        } catch(\Exception $e) {
            DB::rollBack();
            return Utilities::error($e, "An Error Occurred while attempting to approve this request");
        }
    }

    public function reject(RejectBondRequest $request, $id)
    {
        $bondRequest = $this->requestService->getRequest($id);
        if(!$bondRequest) return Utilities::error402("Request not found");

        $bond = $bondRequest->bond;

        if(!$bond) return Utilities::error402("The bond for this request was not found");

        DB::beginTransaction();
        try{
            $this->requestService->reject($bondRequest, $request->validated("reason"));
            $this->bondService->updateStatus($bond, ClientBondStatus::COMPLETED->value);

            DB::commit();

            return Utilities::okay("Request has been rejected");
        } catch(\Exception $e) {
            DB::rollBack();
            return Utilities::error($e, "An Error Occurred while attempting to reject this request");
        }

    }

    public function uploadDocuments(UploadBondDocuments $request, int $id)
    {
        DB::beginTransaction();
        try{
            $bond = $this->bondService->getBond($id);
            if(!$bond) return Utilities::error402("Bond not found");

            $data = $request->validated();
            $docs = [];
            $errors = [];
            foreach($data['documents'] as $doc) {
                $fileType = Utilities::getFileType($doc['file']->getMimeType());
                $fileRes = $this->fileService->save($doc['file'], $fileType, Auth::user()->id, FilePurpose::CLIENT_BOND_DOC->value, User::$userType, 'client-bond-docs');
                if($fileRes['status'] == 200) {
                    $doc['fileId'] = $fileRes['file']->id;
                    $docs[] = $doc;
                }else{
                    $errors[] = $fileRes['message'];
                }
            }

            if(count($docs) > 0) {
                $this->bondService->saveDocuments($bond, $docs);
                
                if(count($errors) > 0) {
                    DB::commit();
                    return Utilities::ok("partially uploaded, some documents failed to upload");
                }

                DB::commit();
                return Utilities::okay("Uploaded successfully");
            }

            DB::rollBack();
            return Utilities::error402("an error occurred..".$errors[0]);

        }catch(\Exception $e){
            DB::rollBack();
            return Utilities::error($e, "An Error Occurred while attempting to upload documents");
        }
    }
}
