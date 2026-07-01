<?php

namespace app\Services;

use Illuminate\Support\Facades\DB;

use app\Notifications\APIPasswordResetNotification;
use app\Exceptions\UserNotFoundException;

use app\Models\Payment;
use app\Models\Payment_mode;
use app\Models\StaffCommissionEarning;
use app\Models\StaffCommissionRedemption;
use app\Models\DeductibleFee;
use app\Models\CommissionRate;
use app\Models\ClientCommissionEarning;
use app\Models\ClientCommissionRate;
use app\Models\StaffTotalEarningView;
use app\Models\StaffTotalRedemptionView;
use app\Models\ClientTotalReferralEarningsView;

use app\Enums\StaffTypes;
use app\Enums\StaffCommissionType;
use app\Enums\RedemptionStatus;

use app\Helpers;
use app\Utilities;

/**
 * Commission service class
 */
class CommissionService
{

    public string | null $type = null;
    public int | null $installment = null;

    //Client Commissions Start Here
    public function setClientCommissionRate($rate)
    {
        $clientRate = ClientCommissionRate::first() ?? new ClientCommissionRate;
        $clientRate->rate = $rate;
        $clientRate->save();

        return $clientRate;
    }

    public function saveClientEarning($client, $order)
    {
        $order->refresh();
        $fee = DeductibleFee::where("name", "commission tax")->first();
        $commissionTax = $fee ? $fee->percentage : 0;
        $clientCommissionRate = ClientCommissionRate::first();

        if ($clientCommissionRate) {
            $clientCommission = new ClientCommissionEarning;
            $clientCommission->client_id = $client->id;
            $clientCommission->order_id = $order->id;
            $clientCommission->amount = $order->amount_payable;
            $clientCommission->commission = $clientCommissionRate->rate;
            $clientCommission->commission_amount = round(($clientCommissionRate->rate / 100) * $clientCommission->amount, 2);
            $clientCommission->tax = $commissionTax;
            $taxAmount = round(($commissionTax / 100) * $clientCommission->commission_amount, 2);
            $clientCommission->amount_after_tax = $clientCommission->commission_amount - $taxAmount;
            $clientCommission->save();

            return $clientCommission;
        }
    }

    public function totalClientEarnings($clientId)
    {
        return ClientTotalReferralEarningsView::where("client_id", $clientId)->first();
    }

    /*
        Client Commissions ends here
    */

    public function setCommissionRate($data)
    {
        $installmentCommissionRate = CommissionRate::where('staff_type_id', $data['staff_type_id'])->where('installment', 1)->first();
        $fullpaymentCommissionRate = CommissionRate::where('staff_type_id', $data['staff_type_id'])->where('installment', 0)->first();
        $newCommissionRate = new CommissionRate;
        if ($installmentCommissionRate) {
            // dd('here1');
            $installmentCommissionRate->direct = $data['installment']['direct'];
            $installmentCommissionRate->indirect = $data['installment']['indirect'];
            $installmentCommissionRate->update();
        } else {
            // dd('here2');
            $newCommissionRate = new CommissionRate;
            $newCommissionRate->staff_type_id = $data['staff_type_id'];
            $newCommissionRate->installment = 1;
            $newCommissionRate->direct = $data['installment']['direct'];
            $newCommissionRate->indirect = $data['installment']['indirect'];
            $newCommissionRate->save();
        }
        if ($fullpaymentCommissionRate) {
            // dd('here3');
            $fullpaymentCommissionRate->direct = $data['fullpayment']['direct'];
            $fullpaymentCommissionRate->indirect = $data['fullpayment']['indirect'];
            $fullpaymentCommissionRate->update();
        } else {
            // dd('here4');
            $newCommissionRate = new CommissionRate;
            $newCommissionRate->staff_type_id = $data['staff_type_id'];
            $newCommissionRate->installment = 0;
            $newCommissionRate->direct = $data['fullpayment']['direct'];
            $newCommissionRate->indirect = $data['fullpayment']['indirect'];
            $newCommissionRate->save();
        }
        return CommissionRate::where('staff_type_id', $data['staff_type_id'])->get();
    }

    public function save($user, $order, $isFirstPayment = false)
    {
        $order->refresh();
        if ($user->staffType && $user->staffType->name == StaffTypes::VIRTUAL_STAFF->value) {
            $staff = $user->registerer;
        }
        $commission = $this->addDirectCommission($user, $order, $isFirstPayment);
        if (isset($staff) && $staff != null) $this->addIndirectCommission($staff, $order, $isFirstPayment);
        return true;
    }

    public function getStaffEarnings(int $userId)
    {
        return StaffCommissionEarning::where("user_id", $userId)
            ->when($this->type, fn($query) => $query->where("type", $this->type))
            ->when($this->installment != null, fn($query) => $query->whereHas("order", fn($orderQuery) => $orderQuery->where("is_installment", $this->installment)))
            ->orderBy("created_at", "DESC")->get();
    }

    public function getTotalStaffsEarnings()
    {
        return StaffTotalEarningView::get();
    }

    public function getTotalStaffEarnings($userId)
    {
        $totalEarning = StaffTotalEarningView::where("user_id", $userId)->first();
        return ($totalEarning) ? $totalEarning->total_earnings : 0;
    }

    public function getTotalStaffIndirectEarnings($userId)
    {
        $totalEarning = StaffTotalEarningView::where("user_id", $userId)->first();
        return ($totalEarning) ? $totalEarning->total_indirect_earnings : 0;
    }

    public function getTotalStaffRedemptions($userId)
    {
        $totalRedemption = StaffTotalRedemptionView::where("user_id", $userId)->first();
        return ($totalRedemption) ? $totalRedemption->total_redemptions : 0;
    }

    public function pendingRedemption($userId)
    {
        return StaffCommissionRedemption::where("user_id", $userId)->where("status", RedemptionStatus::PENDING->value)->first();
    }

    public function commissionRedemptions($with = [], $userId = null)
    {
        $query = StaffCommissionRedemption::with($with);
        if ($userId) $query = $query->where("user_id", $userId);
        return $query->orderBy("created_at", "DESC")->get();
    }

    public function commissionRedemption($id, $with = [])
    {
        return StaffCommissionRedemption::with($with)->where("id", $id)->first();
    }

    public function redeemCommission($data)
    {
        $redemption = new StaffCommissionRedemption;
        $redemption->amount = $data['amount'];
        $redemption->bank_account_id = $data['bankAccountId'];
        $redemption->user_id = $data['userId'];

        $redemption->save();

        return $redemption;
    }

    public function completeRedemption($redemption)
    {
        $redemption->status = RedemptionStatus::COMPLETED->value;
        $redemption->update();

        return $redemption;
    }

    // public function completeCommissionPayment($user, $commission)
    // {

    //     $commissionPayment = new UserCommissionPayment;
    //     $commissionPayment->user_id = $user->id;
    //     $commissionPayment->user_commission_id = $commission->id;
    //     $commissionPayment->amount = $commission->balance;
    //     $commissionPayment->balance = 0;
    //     $commissionPayment->save();

    //     $commission->balance = 0;
    //     $commission->update();

    //     $this->updateUserCommission($user, $commissionPayment);

    //     return $commission;
    // }

    // public function getUserPayments($user_id)
    // {
    //     return UserCommissionPayment::where('user_id', $user_id)->get();
    // }

    // public function getLatestUserPayments($user_id)
    // {
    //     return UserCommissionPayment::where('user_id', $user_id)->orderBy('created_at', 'desc')->first();
    // }

    // public function getTotalUserCommissionBalance($user_id)
    // {
    //     return UserCommission::select(DB::raw("SUM(balance) as total"))->where('user_id', $user_id)->get();
    // }

    // public function getUserCommissionByOrderId($order_id)
    // {
    //     return UserCommission::where('order_id', $order_id)->first();
    // }

    // public function getUserCommissionsByMonth($year, $start, $end=null, $page=null, $perPage=null)
    // {
    //     if($page != null) {
    //         if($page <= 0) $page = 1;
    //         $offset = $perPage * ($page-1);
    //     }
    //     $query = ($end != null) ? UserCommissionPayment::whereYear('created_at', $year)->whereMonth('created_at', '>=', $start)->whereMonth('created_at', '<=', $end) 
    //                                 : 
    //                                 UserCommissionPayment::whereMonth('created_at', '=', $start);
    //     return $query->limit($perPage)->offset($offset)->orderBy('created_at', 'desc')->get();
    // }

    // public function getUserCommissionsByMonthCount($year, $start, $end=null)
    // {
    //     $query = ($end != null) ? UserCommissionPayment::whereYear('created_at', $year)->whereMonth('created_at', '>=', $start)->whereMonth('created_at', '<=', $end) 
    //                                 : 
    //                                 UserCommissionPayment::whereMonth('created_at', '=', $start);
    //     return $query->count();
    // }

    // public function totalPayments($user_id)
    // {
    //     $total = 0;
    //     $userPayments = $this->getUserPayments($user_id);
    //     if($userPayments->count() > 0) {
    //         foreach($userPayments as $userPayment) {
    //             $total += $userPayment->amount;
    //         }
    //     }
    //     return $total;
    // }

    private function taxCommission($commissionPercentage, $amount)
    {
        $tax = DeductibleFee::commissionTax()->percentage;
        $commissionBeforeTax = (float)($commissionPercentage / 100) * (float)$amount;
        $commissionTax = (float)$commissionBeforeTax * ((float)$tax / 100);
        $commissionAmount = (float)$commissionBeforeTax - (float)$commissionTax;
        return [
            "beforeTax" => $commissionBeforeTax,
            "afterTax" => $commissionAmount,
            "tax" => $tax
        ];
    }

    private function addDirectCommission($user, $order, $isFirstPayment = false)
    {
        $staffType = $user->staffType;
        $amount = $order->amount_payable;
        $commissionPercentage = ($order->is_installment == 0) ? $this->getCommissionPercentage($staffType) : $this->getCommissionInstallmentPercentage($staffType);
        // $commissionBeforeTax = (float)($commissionPercentage/100) * (float)$amount;
        // $commissionTax = (float)$commissionBeforeTax * ((float)$tax/100);
        // $commissionAmount = (float)$commissionBeforeTax - (float)$commissionTax;

        $taxCommission = $this->taxCommission($commissionPercentage, $amount);

        $commission = new StaffCommissionEarning;

        $commission->user_id = $user->id;
        $commission->order_id = $order->id;
        $commission->amount = $amount;
        $commission->tax = $taxCommission['tax'];
        $commission->commission_amount = $taxCommission['beforeTax'];
        $commission->commission = $commissionPercentage;
        $commission->commission_after_tax = (float)$taxCommission['afterTax'];
        $commission->type = StaffCommissionType::DIRECT->value;
        if ($order->is_installment == 1 && $isFirstPayment) {
            $commission->balance = 0;
        }
        $commission->save();

        return $commission;
    }

    private function addIndirectCommission($user, $order, $isFirstPayment = false)
    {
        $staffType = ($user->staffType) ? $user->staffType : null;
        $amount = $order->amount_payed;
        $commissionPercentage = ($order->is_installment == 0) ? $this->getCommissionPercentage($staffType, 0) : $this->getCommissionInstallmentPercentage($staffType, 0);

        $taxCommission = $this->taxCommission($commissionPercentage, $amount);

        $commission = new StaffCommissionEarning;

        $commission->user_id = $user->id;
        $commission->order_id = $order->id;
        $commission->amount = $amount;
        $commission->tax = $taxCommission['tax'];
        $commission->commission_amount = $taxCommission['beforeTax'];
        $commission->commission = $commissionPercentage;
        $commission->commission_after_tax = (float)$taxCommission['afterTax'];
        $commission->type = StaffCommissionType::INDIRECT->value;
        if ($order->is_installment == 1 && $isFirstPayment) {
            $commission->balance = 0;
        }
        $commission->save();

        return $commission;
    }

    private function getCommissionPercentage($staffType, $direct = 1)
    {
        $commission = 0;
        if ($staffType) {
            if ($direct == 1) {
                $commissionRate = CommissionRate::where('staff_type_id', $staffType->id)->where('installment', 0)->first();
                $commission = $commissionRate->direct;
            } else {
                $commissionRate = CommissionRate::where('staff_type_id', $staffType->id)->where('installment', 0)->first();
                $commission = $commissionRate->indirect;
            }
        }
        return $commission;
    }

    private function getCommissionInstallmentPercentage($staffType, $direct = 1)
    {
        $commission = 0;
        if ($staffType) {
            if ($direct == 1) {
                $commissionRate = CommissionRate::where('staff_type_id', $staffType->id)->where('installment', 1)->first();
                $commission = $commissionRate->direct;
            } else {
                $commissionRate = CommissionRate::where('staff_type_id', $staffType->id)->where('installment', 1)->first();
                $commission = $commissionRate->indirect;
            }
        }
        return $commission / 2;
    }
}
