<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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

use app\Enums\PackageType;
use app\Enums\UserType;
use app\Enums\ClientPackageOrigin;
// use app\Enums\PaymentMode;

use app\Utilities;

class PaymentController extends Controller
{
    private $paymentService;
    private $orderService;
    private $commissionService;
    private $clientPackageService;
    private $clientInvestmentService;
    private $notificationService;

    public function __construct()
    {
        $this->paymentService = new PaymentService;
        $this->orderService = new OrderService;
        $this->commissionService = new CommissionService;
        $this->clientPackageService = new ClientPackageService;
        $this->clientInvestmentService = new ClientInvestmentService;
        $this->notificationService = new NotificationService;
    }

    public function confirm(ConfirmPayment $request)
    {
        try{
            DB::beginTransaction();
            $payment = $this->paymentService->getPayment($request->validated("paymentId"));
            if(!$payment) return Utilities::error402("Payment not found");

            if($payment->confirmed === 0) return Utilities::error402("This Payment has been rejected, let another request be made");
            if($payment->confirmed === 1) return Utilities::error402("This Payment is already Confirmed");

            $payment = $this->paymentService->confirm($payment);

            $order = $payment->purchase;

            $clientPackage = $this->clientPackageService->getClientPackageByPurchase($order->id, Order::$type);
            if($clientPackage && !$clientPackage->contract_file_id) {
                $isOffer = ($clientPackage->origin == ClientPackageOrigin::OFFER->value);
                $this->clientPackageService->uploadContract($order, $clientPackage, $isOffer);
            }
            // dd($payment->purchase);
            // update order table to reflect amount_payed and balance;
            // $order = $this->orderService->saveAmountPaid($order, $payment->amount);
            // $data['paymentStatusId'] = ($order->balance <= 0) ? PaymentStatus::complete()->id : PaymentStatus::deposit()->id;
            // $data['installmentsPayed'] = $order->installments_payed+1;
            if($payment->payment_mode_id == PaymentMode::bankTransfer()->id) $data['amountPayed'] = $payment->amount;
            if($order->is_installment == 1 && $order->amount_per_installment && $payment->amount != $order->amount_per_installment) {
                $data['updateInstallment'] = true;
            }
            $order = $this->orderService->update($data, $order);

            // update staff commission
            $referrer = $payment->client->referer;
            if(($order->is_installment==0 || ($order->installments_payed < 2 || $order->payment_status_id==PaymentStatus::complete()->id)) && $payment->client->referer) {
                // calculate the bonus/commission for the referer and save it
                if($order->payment_status_id==PaymentStatus::complete()->id && $payment->client->referer_type == UserType::CLIENT->value) {
                    $this->commissionService->saveClientEarning($payment->client->referer, $order);
                }
                if($payment->client->referer_type == UserType::USER->value) {
                    $this->commissionService->save($payment->client->referer, $order);
                }
            }

            // $this->paymentService->uploadReceipt($payment, $payment->client); 
            if($order->is_installment == 0 || (($order->balance <= $payment->amount) || ($order->installments_payed == $order->installment_count))) {
                $this->orderService->completeOrder($order, $payment);
            }else{
                if($order->installments_payed == 1) {
                    if($order->package->type==PackageType::INVESTMENT->value) {
                        $clientInvestment = $this->clientInvestmentService->getByOrderId($order->id);
                        if($clientInvestment) $this->clientPackageService->saveClientPackageInvestment($clientInvestment);
                    }else{
                        $this->clientPackageService->saveClientPackageOrder($order);
                    }
                }
            }

            DB::commit();

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

            if($payment->purchase_type == Order::$type && $payment->purchase->is_installment == 1) {
                $installmentsPayed = $payment->purchase->installments_payed - 1;
                $this->orderService->update(['installmentsPayed' => $installmentsPayed], $payment->purchase);
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

            $file = $this->paymentService->uploadReceipt($payment);
            dd($file);
            if($file) $this->paymentService->update(['receiptFileId' => $file], $payment);

            return Utilities::okay("Payment Receipt Generated Successfully");
        } catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }
}
