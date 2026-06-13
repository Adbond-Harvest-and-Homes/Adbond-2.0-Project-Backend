<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Services\UserActivityLogService;
use app\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

use app\Exceptions\AppException;

use app\Http\Requests\User\ConfirmPayment;
use app\Http\Requests\User\DeclinePayment;
use app\Http\Requests\User\GenerateReceipt;

use app\Http\Resources\PaymentResource;

use app\Models\PaymentStatus;
use app\Models\Order;
use app\Models\PaymentMode;

use app\Services\PaymentService;
use app\Services\OrderService;
use app\Services\CommissionService;
use app\Services\ClientPackageService;
use app\Services\ClientInvestmentService;
use app\Services\NotificationService;

use app\Domain\Payments\Context\PaymentConfirmationContext;
use app\Domain\Payments\Events\PaymentProcessed;
use app\Domain\Payments\Events\PaymentCompleted;

use app\Domain\Payments\Pipelines\Stages\ReferralCommissionStage;
use app\Domain\Payments\Pipelines\PostConfirmationStages\OrderUpdateStage;

use app\Enums\PackageType;
use app\Enums\UserType;
use app\Enums\ClientPackageOrigin;
// use app\Enums\PaymentMode;

use app\Utilities;

class PaymentController extends Controller
{
    private $userActivityLogService;

    private $paymentService;
    private $orderService;
    private $commissionService;
    private $clientPackageService;
    private $clientInvestmentService;
    private $notificationService;

    public function __construct()
    {
        $this->userActivityLogService = new UserActivityLogService;
        $this->paymentService = new PaymentService;
        $this->orderService = new OrderService;
        $this->commissionService = new CommissionService;
        $this->clientPackageService = new ClientPackageService;
        $this->clientInvestmentService = new ClientInvestmentService;
        $this->notificationService = new NotificationService;
    }

    public function confirm(ConfirmPayment $request)
    {
        DB::beginTransaction();
        try{
            $context = new PaymentConfirmationContext($request->validated());

            Utilities::logStuff("Started confirmation process");
            $payment = $this->paymentService->confirm($context->payment);
            Utilities::logStuff("Finished confirmation process");

            event(new PaymentCompleted($context));

            Event::dispatch(new PaymentProcessed($context));

            DB::commit();

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Confirmed Payment");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::ok([
                "message" => "payment has been Confirmed",
                "payment" => new PaymentResource($payment)
            ]);
        }catch(AppException $e){
            DB::rollBack();
            throw $e;
        }catch(\Exception $e){
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to confirm payment, Please try again later or contact support');
        }
    }

    public function reject(DeclinePayment $request)
    {
        try{
            $payment = $this->paymentService->getPayment($request->validated("paymentId"));
            if(!$payment) return Utilities::error402("Payment not found");

            $payment = $this->paymentService->reject($payment, $request->validated("message"));

            if($payment->purchase_type == Order::$type && $payment->purchase->is_installment == 1) {
                $isFirstPayment = !\app\Models\Payment::where('purchase_id', $payment->purchase_id)
                    ->where('purchase_type', $payment->purchase_type)
                    ->where('id', '<', $payment->id)
                    ->exists();

                if ($isFirstPayment) {
                    $installmentsPayed = max(0, $payment->purchase->installments_payed - 1);
                    $this->orderService->update(['installmentsPayed' => $installmentsPayed], $payment->purchase);
                }
            }

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Rejected Payment");
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
                $this->userActivityLogService->log(Auth::user(), "Flagged Payment");
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

    public function generateReceipt(GenerateReceipt $request)
    {
        try{
            $payment = $this->paymentService->getPayment($request->validated("paymentId"));
            if(!$payment) return Utilities::error402("Payment not found");
            
            $oldReceiptFile = $payment->paymentReceipt;

            $file = $this->paymentService->uploadReceipt($payment);
            if($file) $this->paymentService->update(['receiptFileId' => $file->id], $payment);
            if($oldReceiptFile) $oldReceiptFile->delete();

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Generated Payment Receipt");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Payment Receipt Generated Successfully");
        } catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }
}
