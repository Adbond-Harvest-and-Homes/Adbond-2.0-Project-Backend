<?php

namespace app\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Order;
use App\Models\Payment;
use App\Models\File;
use App\Models\ClientPackage;
use App\Models\Client;
use App\Models\OrderDiscount;
use App\Models\PaymentStatus;

use App\Enums\OrderType;
use App\Enums\OrderDiscountType;
use App\Enums\PaymentPurpose;
use App\Enums\FilePurpose;
use App\Enums\ClientPackageOrigin;

use App\Utilities;

class Migration2Controller extends Controller
{
    public function migratePayments()
    {
        $start = "2025-10-13 00:00:00";
        $end = "2025-10-17 23:59:59";
        // dd('here');
        DB::connection('db1')->table('payments')->where("created_at", ">=", $start)->where("created_at", "<=", $end)->orderBy('id')->chunk(500, function ($records) {
            if(count($records) > 0) {
                // dd('records');
                foreach ($records as $record) {
                    $v1Payment = (array) $record;

                    $paymentExists = Payment::where("created_at", $v1Payment['created_at'])->first();
                    if(!$paymentExists) {
                        $paymentMode = DB::connection('db1')->table('payment_modes')->where("id", $v1Payment['payment_mode_id'])->first();
                        if($paymentMode) {
                            $paymentMode = (array) $paymentMode;
                            $paymentMode = PaymentMode::where('name', 'LIKE', '%'.$paymentMode['name'].'%')->first();
                        }
                        $bankAccount = ($v1Payment['bank_account_id']) ? $this->getBankAccount($v1Payment['bank_account_id']) : null;
                        $user = ($v1Payment['user_id']) ? $this->getUser($v1Payment['user_id']) : null;
                        $client = $this->getClient($v1Payment['customer_id']);
                        $order = $this->getOrder($v1Payment['order_id']);

                        if(!$order) dd("Order not found", $v1Payment);
                        
                        if($client) {
                            $payment = new Payment;
                            $payment->client_id = $client->id;
                            $payment->purchase_id = $order->id;
                            $payment->purchase_type = Order::$type;
                            $payment->receipt_no = $v1Payment['receipt_no'];
                            $payment->amount = $v1Payment['amount'];
                            $payment->payment_mode_id = ($paymentMode) ? $paymentMode->id : PaymentMode::bankTransfer()->id;
                            $payment->confirmed = $v1Payment['confirmed'];
                            $payment->rejected_reason = $v1Payment['rejected_reason'];
                            $payment->payment_gateway_id = $v1Payment['card_payment_channel_id'];
                            $payment->reference = $v1Payment['reference'];
                            $payment->success = $v1Payment['success'];
                            $payment->failure_message = $v1Payment['failure_message'];
                            $payment->flag = $v1Payment['flag'];
                            $payment->flag_message = $v1Payment['flag_message'];
                            $payment->bank_account_id = ($bankAccount) ? $bankAccount->id : null;
                            $payment->payment_date = $v1Payment['payment_date'];
                            $payment->purpose = ($order->is_installment == 1) ? PaymentPurpose::INSTALLMENT_PAYMENT->value : PaymentPurpose::PACKAGE_FULL_PAYMENT->value;
                            // $payment->installment_number = ;
                            $payment->user_id = ($user) ? $user->id : null;
                            $payment->created_at = $v1Payment['created_at'];
                            $payment->updated_at = $v1Payment['updated_at'];
                            $payment->migrated = true;
                            $payment->save();

                            $evidenceFile = null;
                            if($v1Payment['evidence_file_id']) $evidenceFile = $this->getFile($v1Payment['evidence_file_id'], ['id'=>$client->id, 'type'=>Client::$userType], ['id'=>$payment->id, 'type'=>Payment::$type], FilePurpose::PAYMENT_EVIDENCE->value);

                            if(isset($v1Payment['receipt_file_id']) && $v1Payment['receipt_file_id']) {
                                $receiptFile = $this->getFile($v1Payment['receipt_file_id'], ['id'=>$client->id, 'type'=>Client::$userType], ['id'=>$payment->id, 'type'=>Payment::$type], FilePurpose::PAYMENT_RECEIPT->value);
                                $payment->receipt_file_id = $receiptFile->id;
                            }
                            if($evidenceFile) $payment->evidence_file_id = $evidenceFile->id;
                            $payment->update();

                            Utilities::logSuccessMigration("Payment Migration Successful.. PaymentId: ".$payment->id);
                        }else{
                            Utilities::logFailedMigration("Payment not Migrated.. Client not found V1PaymentId: ".$v1Payment['id']);
                        }
                    }else{
                        Utilities::logFailedMigration("Payment not Migrated.. Payment Exists: ".$v1Payment['id']);
                    }
                }
            }
            // dd('no records');
        });
        // dd('missed');
        // $this->markAsMigrated($this->paymentsMigration);
    }

    private function getBankAccount($accountId)
    {
        $account = DB::connection('db1')->table('bank_accounts')->where("id", $accountId)->first();
        if($account) {
            $account = (array) $account;
            $account = BankAccount::where('name', 'LIKE', '%'.$account['account_name'].'%')->first();
        }
        return $account;
    }

    private function getUser($userId)
    {
        $user = DB::connection('db1')->table('users')->where("id", $userId)->first();
        if($user) {
            $userData = (array) $user;
            $user = User::where("email", $userData['email'])->first();
        }
        return $user;
    }

    private function getClient($customerId)
    {
        $customer = DB::connection('db1')->table('customers')->where("id", $customerId)->first();
        $client = null;
        if($customer) {
            $customer = (array) $customer;
            $client = Client::where("email", $customer['email'])->first();
        }
        return $client;
    }

    private function getOrder($orderId)
    {
        $v1Order = DB::connection('db1')->table('orders')->where("id", $orderId)->first();
        $order = null;
        if($v1Order) {
            $client = $this->getClient($v1Order->customer_id);
            if($client) {
                $order = Order::where("client_id", $client->id)->where("units", $v1Order->units)->where("is_installment", $v1Order->installment)
                ->where("amount_payable", $v1Order->amount_payable)->where("created_at", $v1Order->created_at)->first();
            }
        }
        return $order;
    }

    private function getPackageFromPackageItem($packageItemId)
    {
        $package = null;
        $packageItem = DB::connection('db1')->table('package_items')->where("id", $packageItemId)->first();
        if($packageItem) {
            $packageItem = (array) $packageItem;
            $packageItems = DB::connection('db1')->table('package_items')->where("package_id", $packageItem['package_id'])->orderBy("id", "ASC")->get();
            $v1Package = DB::connection('db1')->table('packages')->where("id", $packageItem['package_id'])->first();
            if($v1Package) {
                $v1Package = (array) $v1Package;
                $name = ($packageItems->count() > 1) ? $this->getPackageNameFromPackageItem($packageItem['id'], $v1Package, $packageItems) : $v1Package['name'];
                $package = Package::where("name", $name)->first();
            }
        }
        return $package;
    }

    private function getPackageNameFromPackageItem($packageItemId, $package, $packageItems)
    {
        $name = null;
        $i = 0;
        foreach($packageItems as $packageItem) {
            $i = $i + 1;
            if($packageItem->id == $packageItemId) {
                $name = $package['name'].$i." ".$packageItem->size."SQM";
            }
        }
        return $name;
    }

    private function migrateOrder($v1Order)
    {
        $package = $this->getPackageFromPackageItem($v1Order['package_item_id']);
        $client = $this->getClient($v1Order['customer_id']);

        $v1PaymentStatus = DB::connection('db1')->table('payment_statuses')->where("id", $v1Order['payment_status_id'])->first();
        $paymentStatus = null;
        if($v1PaymentStatus) {
            $v1PaymentStatus = (array) $v1PaymentStatus;
            $paymentStatus = PaymentStatus::where("name", "LIKE",  "%".$v1PaymentStatus['name']."%")->first();
        }
        if(!$v1PaymentStatus) $paymentStatus = PaymentStatus::pending();
        if($client) {
            $order = new Order;
            $order->type = OrderType::PURCHASE->value;
            $order->client_id = $client->id;
            $order->package_id = $package->id;
            $order->units = $v1Order['units'];
            $order->amount_payed = $v1Order['amount_payed'];
            $order->amount_payable = $v1Order['amount_payable'];
            $order->unit_price = $package->amount;
            $order->is_installment = $v1Order['installment'];
            // $order->installment_count = ;
            // $order->installments_payed = ;
            $order->balance = $v1Order['balance'];
            $order->payment_status_id = $paymentStatus?->id;
            $order->completed = ($v1Order['balance'] <= 0);
            $order->order_date = $v1Order['order_date'];
            $order->payment_due_date = $v1Order['payment_due_date'];
            $order->grace_period_end_date = $v1Order['grace_period_end_date'];
            $order->penalty_period_end_date = $v1Order['penalty_period_end_date'];
            $order->payment_period_status_id = $v1Order['payment_period_status_id'];
            $order->created_at = $v1Order['created_at'];
            $order->updated_at = $v1Order['updated_at'];
            $order->migrated = true;
            $order->save();

            $processingId = Utilities::getOrderProcessingId();
            $order->order_number = $order->id.$processingId;
            $order->update();

            Utilities::logSuccessMigration("Order Migration Successful.. OrderId: ".$order->id);

            $this->migrateOrderDiscounts($v1Order, $order);
            $this->migratePayments($v1Order, $order);
            $this->migrateOrderClientPackages($v1Order, $order);
        }else{
            Utilities::logFailedMigration("Order not Migrated.. Client not found V1OrderId: ".$v1Order['id']);
        }
    }

    private function migrateOrderDiscounts($v1Order, $order)
    {
        $v1Discounts = DB::connection('db1')->table('order_discounts')->where('order_id', $v1Order['id'])->get();
        if($v1Discounts->count() > 0) {
            foreach($v1Discounts as $v1Discount) {
                $v1Discount = (array) $v1Discount;
                $discount = new OrderDiscount;
                $discount->order_id = $order->id;
                $discount->type = $v1Discount['type'];
                $discount->discount = $v1Discount['discount'];
                $discount->amount = Utilities::getDiscount($order->amount_payable, $v1Discount['discount'])['amount'];
                $discount->description = $v1Discount['description'];
                $discount->migrated = true;
                $discount->created_at = $v1Discount['created_at'];
                $discount->updated_at = $v1Discount['updated_at'];
                $discount->save();

                Utilities::logSuccessMigration("Order Discount Migration Successful.. OrderDiscountId: ".$discount->id);
            }
        }
    }

    private function migrateOrderClientPackages($v1Order, $order)
    {
        $customerPackage = DB::connection('db1')->table('customer_packages')->where("purchase_id", $v1Order['id'])->where("purchase_type", "LIKE", "%Order%")->first();
        if($customerPackage) {
            $customerPackage = (array) $customerPackage;
            $client = $this->getClient($customerPackage['customer_id']);
            if($client) {
                $clientPackage = new ClientPackage;
                $clientPackage->client_id = $client->id;
                $clientPackage->package_id = $order->package_id;
                $clientPackage->amount = $order->amount_payable;
                $clientPackage->units = $order->units;
                $clientPackage->unit_price = $order->unit_price;
                $clientPackage->sold = $customerPackage['sold'];
                $clientPackage->origin = ClientPackageOrigin::ORDER->value;
                $clientPackage->purchase_complete = $order->completed;
                $clientPackage->purchase_completed_at = $customerPackage['updated_at'];
                $clientPackage->purchase_type = Order::$type;
                $clientPackage->purchase_id = $order->id;
                $clientPackage->created_at = $customerPackage['created_at'];
                $clientPackage->updated_at = $customerPackage['updated_at'];
                $clientPackage->migrated = true;

                $clientPackage->save();

                $contractFile = $this->getFile($customerPackage['contract_file_id'], ['id'=>$order->client_id, 'type'=>Client::$userType], ['id'=>$clientPackage->id, 'type'=>ClientPackage::$type], FilePurpose::CONTRACT->value);
                $doaFile = $this->getFile($customerPackage['doa_file_id'], ['id'=>$order->client_id, 'type'=>Client::$userType], ['id'=>$clientPackage->id, 'type'=>ClientPackage::$type], FilePurpose::DEED_OF_ASSIGNMENT->value);
                $happinessFile = $this->getFile($v1Order['happiness_letter_id'], ['id'=>$order->client_id, 'type'=>Client::$userType], ['id'=>$clientPackage->id, 'type'=>ClientPackage::$type], FilePurpose::LETTER_OF_HAPPINESS->value);

                if($contractFile) $clientPackage->contract_file_id = $contractFile->id;
                if($doaFile) $clientPackage->doa_file_id = $doaFile->id;
                if($happinessFile) $clientPackage->happiness_letter_file_id = $happinessFile->id;
                $clientPackage->update();

                Utilities::logSuccessMigration("Order Client Package Migration Successful.. CustomerPackageId: ".$customerPackage['id']);

                // $this->migrateOrderOffers($customerPackage, $clientPackage);
            }else{
                Utilities::logFailedMigration("Order Client Package not Migrated.. Client not found CustomerPackageId: ".$customerPackage['id']);
            }
        }else{
            Utilities::logFailedMigration("Order Client Package not Migrated.. Customer Package not found PurchaseId: ".$v1Order['id']); 
            // throw("Customer Package not found");
        }
    }

    private function getFile($fileId, $user, $belongs, $purpose)
    {
        $v1File = DB::connection('db1')->table('files')->where("id", $fileId)->first();
        $file = null;
        if($v1File) {
            $v1File = (array) $v1File;
            $file = $this->migrateFile($v1File, $user, $belongs, $purpose);
        }
        return $file;
    }

    private function migrateFile($record, $user, $belongs, $purpose)
    {
        $file = new File;
        $file->user_id = $user['id'];
        $file->user_type = $user['type'];
        $file->file_type = $record['file_type'];
        $file->mime_type = $record['mime_type'];
        $file->filename = $record['filename'];
        $file->original_filename = $record['original_filename'];
        $file->extension = $record['extension'];
        $file->size = $record['size'];
        $file->formatted_size = $record['formatted_size'];
        $file->url = $record['url'];
        $file->belongs_id = $belongs['id'];
        $file->belongs_type = $belongs['type'];
        $file->purpose = $purpose;
        $file->public_id = $record['public_id'];
        $file->width = $record['width'];
        $file->height = $record['height'];
        $file->migrated = true;
        $file->save();

        return $file;
    }

}
