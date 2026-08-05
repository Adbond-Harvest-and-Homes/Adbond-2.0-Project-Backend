<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use app\Http\Controllers\Controller;

use app\Http\Requests\User\ConfirmPayment;
use app\Http\Requests\User\DeclinePayment;

use app\Http\Resources\OfferPaymentResource;

use app\Services\PaymentService;
use app\Services\OfferService;

use app\Models\PaymentStatus;

use app\Utilities;

class OfferPaymentController extends Controller
{
    private $userActivityLogService;

    private $paymentService;
    private $offerService;

    public function __construct()
    {
        $this->userActivityLogService = new UserActivityLogService;
        $this->paymentService = new PaymentService;
        $this->offerService = new OfferService;
    }

    public function payments(Request $request)
    {
        $page = ($request->query('page')) ?? 1;
        $perPage = ($request->query('perPage'));
        if(!is_int((int) $page) || $page <= 0) $page = 1;
        if(!is_int((int) $perPage) || $perPage==null) $perPage = env('PAGINATION_PER_PAGE');
        $offset = $perPage * ($page-1);

        $filter = [];
        if($request->query('status')) {
            $validStatuses = ['pending', 'successful', 'failed'];
            $validStatusesString = '';
            foreach($validStatuses as $valid) $validStatusesString .= $valid.', ';
            if(!in_array($request->query('status'), $validStatuses)) return Utilities::error402("Valid Statuses are: ".$validStatusesString);
            switch($request->query('status')) {
                case "pending" : $filter['status'] = null; break;
                case "successful" : $filter['status'] = 1; break;
                case "failed" : $filter['status'] = 0; break;
            }
        }
        $this->paymentService->filters = $filter;

        $payments = $this->paymentService->offerPayments(['purchase'], $offset, $perPage);
        $this->paymentService->count = true;

        $paymentsCount = $this->paymentService->offerPayments();

        $this->paymentService->filters = ['status'=>1];
        $successfulCount = $this->paymentService->offerPayments();

        $this->paymentService->filters = ['status'=>0];
        $failedCount = $this->paymentService->offerPayments();

        $this->paymentService->filters = ['status'=>null];
        $pendingCount = $this->paymentService->offerPayments();

        if(isset($filter['status'])) {
            // $defaultTotal = ($filter['status'] == null) ? $pendingCount : ($filter['status'] === 1) ? $successfulCount : $failedCount;
            switch($filter['status']) {
                case null : $defaultTotal = $pendingCount; break;
                case 1 : $defaultTotal = $successfulCount; break;
                case 0 : $defaultTotal = $failedCount; break;
            }
        }else{
            $defaultTotal = $paymentsCount;
        }
        // $successful = $payments->filter(fn($payment) => $payment->confirmed == 1 && $payment?->purchase?->completed == 0);
        // $pending = $payments->filter(fn($payment) => $payment->confirmed === null && $payment?->purchase?->completed == 0);
        // $failed = $payments->filter(fn($payment) => $payment->confirmed === 0);

        return Utilities::paginatedOkay([
            "payments" => OfferPaymentResource::collection($payments),
            "paymentsCount" => $paymentsCount,
            "successfulCount" => $successfulCount,
            "pendingCount" => $pendingCount,
            "failedCount" => $failedCount
        ], $page, $perPage, $defaultTotal);

    }

    public function confirm(ConfirmPayment $request)
    {
        try{
            DB::beginTransaction();
            $payment = $this->paymentService->getPayment($request->validated("paymentId"));
            if(!$payment) return Utilities::error402("Payment not found");

            $payment = $this->paymentService->confirm($payment);

            $offer = $payment->purchase;
            // dd($payment->purchase);
            $data['paymentStatusId'] = PaymentStatus::complete()->id;
            $offer = $this->offerService->update($data, $offer);

            $this->paymentService->uploadReceipt($payment, $payment->client); 

            DB::commit();

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Confirmed Offer Payment");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::ok([
                "message" => "payment has been Confirmed",
                "payment" => new PaymentResource($payment)
            ]);
        }catch(\Exception $e){
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function reject(DeclinePayment $request)
    {
        try{
            $payment = $this->paymentService->getPayment($request->validated("paymentId"));
            if(!$payment) return Utilities::error402("Payment not found");

            $payment = $this->paymentService->reject($payment, $request->validated("message"));

            

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Rejected Offer Payment");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::ok([
                "message" => "payment has been Rejected",
                "payment" => new PaymentResource($payment)
            ]);
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function flag(DeclinePayment $request)
    {
        try{
            $payment = $this->paymentService->getPayment($request->validated("paymentId"));
            if(!$payment) return Utilities::error402("Payment not found");

            $payment = $this->paymentService->flag($payment, $request->validated("message"));

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Flagged Offer Payment");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::ok([
                "message" => "payment has been Flagged",
                "payment" => new PaymentResource($payment)
            ]);
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

}
