<?php

namespace app\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;

use app\Models\Client; 
use app\Models\User;
use app\Models\File;
use app\Models\Country;
use app\Models\Assessment;
use app\Models\AssessmentAttempt;
use app\Models\Question;
use app\Models\QuestionOption;
use app\Models\AssessmentAttemptAnswer;
use app\Models\Bank;
use app\Models\BankAccount;
use app\Models\Post;
use app\Models\Comment;
use app\Models\Reaction;
use app\Models\ClientNextOfKin;
use app\Models\Project;
use app\Models\Package;
use app\Models\PackageMedia;
use app\Models\ClientPackage;
use app\Models\Offer;
use app\Models\OfferBid;
use app\Models\Order;
use app\Models\Payment;
use app\Models\PaymentStatus;
use app\Models\PaymentMode;
use app\Models\OrderDiscount;
use app\Models\SiteTourSchedule;
use app\Models\SiteTourBooking;
use app\Models\SiteTourBookedSchedule;
use app\Models\StaffCommissionEarning;
use app\Models\StaffCommissionRedemption;
use app\Models\StaffCommissionTransaction;
use app\Models\Promo;
use app\Models\PromoProduct;
use app\Models\TableMigration;

use app\Services\MigrationService;
use app\Services\ClientService;
use app\Services\UtilityService;

use app\Enums\FilePurpose;
use app\Enums\PostType;
use app\Enums\ProductCategory;
use app\Enums\PackageType;
use app\Enums\OrderType;
use app\Enums\PaymentPurpose;
use app\Enums\ClientPackageOrigin;
use app\Enums\RedemptionStatus;

use app\Utilities;

class MigrationClientController extends Controller
{
    use MigrationService;

    protected $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }
    
    // private function migratePackages()
    // {
    //     $emails = ["oluwatosinadeoya8@gmail.com"];
    //     foreach($emails as $email) {
    //         // $client = $this->clientService->getClientByEmail($email);
    //         $customer = DB::connection('db1')->table('customers')->where("email", $email)->first();
    //         if($customer) {
    //             DB::connection('db1')->table('packages')->where("project_location_id", $v1ProjectLocation['id'])->orderBy('id')->chunk(500, function ($records) use($project, $v1ProjectLocation) {
    //                 if(count($records) > 0) {
    //                     foreach ($records as $record) {
    //                         $v1Package = (array) $record;
    //                         DB::connection('db1')->table('package_items')->where("package_id", $v1Package['id'])->orderBy('id')->chunk(500, function ($itemRecords) use($v1ProjectLocation, $project, $v1Package) {
    //                             if(count($itemRecords) > 0) {
    //                                 foreach($itemRecords as $itemRecord) {
    //                                     $packageItem = (array) $itemRecord;
    //                                     $user = $this->getUser($v1Package['user_id']);
    //                                     if(!$user) $this->getUser(1);

    //                                     if($v1Package['package_brochure_file_id']) {
    //                                         $brochure = DB::connection('db1')->table('files')->where("id", $v1Package['package_brochure_file_id'])->first();
    //                                         $brochure = (array) $brochure;
    //                                     }else{
    //                                         $brochure = null;
    //                                     }
                                        
    //                                     if($user) {         
    //                                         $package = null; // new Package;
    //                                         // $package->user_id = $user->id;
    //                                         // $package->name = (count($itemRecords) > 1) ? $this->getPackageNameFromPackageItem($packageItem['id'], $v1Package, $itemRecords) : $v1Package['name'];
    //                                         $name = (count($itemRecords) > 1) ? $this->getPackageNameFromPackageItem($packageItem['id'], $v1Package, $itemRecords) : $v1Package['name'];
    //                                         $package = Package::where("name", $name)->where("category", ProductCategory::PURCHASE->value)->first();
    //                                         $package->category = ProductCategory::PURCHASE->value;
    //                                         $package->state = $project->state;
    //                                         $package->address = $v1ProjectLocation['address'];
    //                                         $package->project_id = $project->id;
    //                                         $package->size = $packageItem['size'];
    //                                         $package->amount = $packageItem['price'];
    //                                         $package->units = $packageItem['available_units'];
    //                                         $package->available_units = $packageItem['available_units'];
    //                                         $package->discount = $packageItem['discount'];
    //                                         $package->min_price = $packageItem['min_price'];
    //                                         $package->installment_duration = $packageItem['installment_duration'];
    //                                         $package->infrastructure_fee = $packageItem['infrastructure_fee'];
    //                                         $package->description = $v1Package['description'];
    //                                         $package->type = PackageType::NON_INVESTMENT->value;
    //                                         $package->installment_option = 1;
    //                                         $package->active = $v1Package['active'];
    //                                         $package->deactivated_at = $v1Package['deactivated_at'];
    //                                         $package->sold_out = ($packageItem['available_units'] <= 0) ? 1 : 0;
    //                                         $package->created_at = $v1Package['created_at'];
    //                                         $package->updated_at = $v1Package['updated_at'];
    //                                         $package->migrated = true;
    //                                         $package->save();

    //                                         $brochureFile = ($brochure) ? $this->migrateFile($brochure, ['id' => $user->id, 'type' => User::$userType], ['id'=>$package->id, 'type'=>Package::$type], FilePurpose::PACKAGE_BROCHURE) : null;
    //                                         if($brochureFile) {
    //                                             $package->package_brochure_file_id = $brochureFile->id;
    //                                             $package->update();
    //                                         }

    //                                         Utilities::logSuccessMigration("Package Migration Successful.. PackageId: ".$package->id);

    //                                         //Migrate Package Orders
    //                                         if($package) $this->migrateOrders($packageItem, $package);

    //                                         //Migrate Package Photos
    //                                         $this->migratePackagePhotos($v1Package, $package, $user);
    //                                     }else{
    //                                         Utilities::logFailedMigration("Package not Migrated.. User not found V1PackageId: ".$v1Package['id']);
    //                                     }
    //                                 }
    //                             }
    //                         });
    //                     }
    //                 }
    //             });
    //         }
    //     }
    // }

    public function migrateOrders()
    {
        $emails = ["oluwatosinadeoya8@gmail.com"];
        foreach($emails as $email) {
            // $client = $this->clientService->getClientByEmail($email);
            $customer = DB::connection('db1')->table('customers')->where("email", $email)->first();
            if($customer) {
                DB::connection('db1')->table('orders')->where("customer_id", $customer->id)->orderBy('id')->chunk(500, function ($records) use($customer) {
                    if(count($records) > 0) {
                        foreach ($records as $record) {
                            $v1Order = (array) $record;
                        
                            $client = $this->getClient($v1Order['customer_id']);
                            $package = $this->getPackageFromPackageItem($v1Order['package_item_id']);
                            // dd($package);

                            $v1PaymentStatus = DB::connection('db1')->table('payment_statuses')->where("id", $v1Order['payment_status_id'])->first();
                            $paymentStatus = null;
                            if($v1PaymentStatus) {
                                $v1PaymentStatus = (array) $v1PaymentStatus;
                                $paymentStatus = PaymentStatus::where("name", "LIKE",  "%".$v1PaymentStatus['name']."%")->first();
                            }
                            if(!$v1PaymentStatus) $paymentStatus = PaymentStatus::pending();
                            // dd($paymentStatus?->id);

                            if($client && $package) {
                                $order = Order::where("client_id", $client->id)->where("package_id", $package->id)->where("created_at", $v1Order['created_at'])->first();
                                if(!$order) {
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

                                    Utilities::logSuccessMigration("Client Order Migration success");
                                }
                            }else{
                                Utilities::logFailedMigration("Order not Migrated.. Client not found V1OrderId: ".$v1Order['id']);
                            }
                        }
                    }
                });
            }
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

    private function migratePayments($v1Order, $order)
    {
        DB::connection('db1')->table('payments')->where("order_id", $v1Order['id'])->orderBy('id')->chunk(500, function ($records) use($v1Order, $order) {
            if(count($records) > 0) {
                foreach ($records as $record) {
                    $v1Payment = (array) $record;
                    $paymentMode = DB::connection('db1')->table('payment_modes')->where("id", $v1Payment['payment_mode_id'])->first();
                    if($paymentMode) {
                        $paymentMode = (array) $paymentMode;
                        $paymentMode = PaymentMode::where('name', 'LIKE', '%'.$paymentMode['name'].'%')->first();
                    }
                    $bankAccount = ($v1Payment['bank_account_id']) ? $this->getBankAccount($v1Payment['bank_account_id']) : null;
                    $user = ($v1Payment['user_id']) ? $this->getUser($v1Payment['user_id']) : null;
                    $client = $this->getClient($v1Order['customer_id']);

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
                }
            }
        });
        // $this->markAsMigrated($this->paymentsMigration);
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

                $this->migrateOrderOffers($customerPackage, $clientPackage);
            }else{
                Utilities::logFailedMigration("Order Client Package not Migrated.. Client not found CustomerPackageId: ".$customerPackage['id']);
            }
        }else{
            Utilities::logFailedMigration("Order Client Package not Migrated.. Customer Package not found PurchaseId: ".$v1Order['id']); 
            // throw("Customer Package not found");
        }
    }

    /*
        Migrate offers that whose asset is acquired via an order
    */
    private function migrateOrderOffers($customerPackage, $clientPackage)
    {
        $v1Offers = DB::connection('db1')->table('offers')->where("customer_package_id", $customerPackage['id'])->get();
        if($v1Offers->count() > 0) {
            foreach($v1Offers as $v1Offer) {
                $v1Offer = (array) $v1Offer;
                $client = $this->getClient($v1Offer['customer_id']);
                $user = ($v1Offer['user_id']) ? $this->getUser($v1Offer['user_id']) : null;
                $paymentStatus = $this->getPaymentStatus($v1Offer['payment_status_id']);
                if($client) {
                    $offer = new Offer;
                    $offer->client_id = $client->id;
                    $offer->package_id = $clientPackage->package_id;
                    $offer->client_package_id = $clientPackage->id;
                    $offer->units = $v1Offer['units'];
                    $offer->project_id = $clientPackage->package->project->id;
                    $offer->price = $v1Offer['price'];
                    $offer->package_price = $clientPackage->package->amount;
                    // $offer->resell_order_id = ;
                    // $offer->accepted_bid_id = ;
                    $offer->active = $v1Offer['active'];
                    $offer->approved = $v1Offer['approved'];
                    $offer->rejected_reason = $v1Offer['rejected_reason'];
                    $offer->completed = $v1Offer['completed'];
                    $offer->payment_status_id = ($paymentStatus) ? $paymentStatus->id : null;
                    $offer->user_id = ($user) ? $user->id : null;
                    $offer->approval_date = $v1Offer['updated_at'];
                    $offer->created_at = $v1Offer['created_at'];
                    $offer->updated_at = $v1Offer['updated_at'];
                    $offer->migrated = true;
                    $offer->save();

                    Utilities::logSuccessMigration("Order Offer Migration Successful.. OfferId: ".$offer->id);

                    $this->migrateOfferClientPackages($v1Offer, $offer);
                    $this->migrateOfferPayments($v1Offer, $offer);
                    $this->migrateOfferBids($v1Offer, $offer);
                }else{
                    Utilities::logFailedMigration("Order Offer not Migrated.. Client not found V1OfferId: ".$v1Offer['id']);
                }
            }
        }
    }

    /*
        Migrate client packages that is generated from offer
    */
    private function migrateOfferClientPackages($v1Offer, $offer)
    {
        $customerPackage = DB::connection('db1')->table('customer_packages')->where("purchase_id", $v1Offer['id'])->where("purchase_type", "LIKE", "%".Offer::$type."%")->first();
        if($customerPackage) {
            $customerPackage = (array) $customerPackage;
            $client = $this->getClient($customerPackage['customer_id']);
            if($client) {
                $clientPackage = new ClientPackage;
                $clientPackage->client_id = $client->id;
                $clientPackage->package_id = $offer->package_id;
                $clientPackage->amount = $offer->price;
                $clientPackage->units = $offer->units;
                $clientPackage->unit_price = $offer->unit_price;
                $clientPackage->sold = $customerPackage['sold'];
                $clientPackage->origin = ClientPackageOrigin::OFFER->value;
                $clientPackage->purchase_complete = 1;
                $clientPackage->purchase_completed_at = $customerPackage['updated_at'];
                $clientPackage->purchase_type = Offer::$type;
                $clientPackage->purchase_id = $offer->id;
                $clientPackage->created_at = $customerPackage['created_at'];
                $clientPackage->updated_at = $customerPackage['updated_at'];
                $clientPackage->migrated = true;

                $clientPackage->save();

                $contractFile = $this->getFile($customerPackage['contract_file_id'], ['id'=>$offer->client_id, 'type'=>Client::$userType], ['id'=>$clientPackage->id, 'type'=>ClientPackage::$type], FilePurpose::CONTRACT->value);
                $doaFile = $this->getFile($customerPackage['doa_file_id'], ['id'=>$offer->client_id, 'type'=>Client::$userType], ['id'=>$clientPackage->id, 'type'=>ClientPackage::$type], FilePurpose::DEED_OF_ASSIGNMENT->value);
                $happinessFile = $this->getFile($v1Offer['happiness_letter_id'], ['id'=>$offer->client_id, 'type'=>Client::$userType], ['id'=>$clientPackage->id, 'type'=>ClientPackage::$type], FilePurpose::LETTER_OF_HAPPINESS->value);

                if($contractFile) $clientPackage->contract_file_id = $contractFile->id;
                if($doaFile) $clientPackage->doa_file_id = $doaFile->id;
                if($happinessFile) $clientPackage->happiness_letter_file_id = $happinessFile->id;
                $clientPackage->update();

                Utilities::logSuccessMigration("Offer Client Package Migration Successful.. CustomerPackageId: ".$customerPackage['id']);

                $this->migrateOfferOffers($customerPackage, $clientPackage);
            }else{
                Utilities::logFailedMigration("Order Client Package not Migrated.. Customer Package not found PurchaseId: ".$v1Offer['id']); 
            }
        }
    }

    /*
        Migrate offers that whose asset is acquired via an offer
    */
    private function migrateOfferOffers($customerPackage, $clientPackage)
    {
        $v1Offers = DB::connection('db1')->table('offers')->where("customer_package_id", $customerPackage['id'])->get();
        if($v1Offers->count() > 0) {
            foreach($v1Offers as $v1Offer) {
                $client = $this->getClient($v1Offer['customer_id']);
                $user = $this->getUser($customerPackage['user_id']);
                $paymentStatus = $this->getPaymentStatus($v1Offer['payment_status_id']);
                if($client) {
                    $offer = new Offer;
                    $offer->client_id = $client->id;
                    $offer->package_id = $clientPackage->package_id;
                    $offer->client_package_id = $clientPackage->id;
                    $offer->units = $customerPackage['units'];
                    $offer->project_id = $clientPackage->package->project->id;
                    $offer->price = $customerPackage['price'];
                    $offer->package_price = $clientPackage->package->amount;
                    // $offer->resell_order_id = ;
                    // $offer->accepted_bid_id = ;
                    $offer->active = $customerPackage['active'];
                    $offer->approved = $customerPackage['approved'];
                    $offer->rejected_reason = $customerPackage['rejected_reason'];
                    $offer->completed = $customerPackage['completed'];
                    $offer->payment_status_id = ($paymentStatus) ? $paymentStatus->id : null;
                    $offer->user_id = ($user) ? $user->id : null;
                    $offer->approval_date = $customerPackage['updated_at'];
                    $offer->created_at = $customerPackage['created_at'];
                    $offer->updated_at = $customerPackage['updated_at'];
                    $offer->migrated = true;
                    $offer->save();

                    $this->migrateOfferPayments($v1Offer, $offer);

                    $this->migrateOfferBids($v1Offer, $offer);

                    Utilities::logSuccessMigration("Offer Offer Migration Successful.. OfferId: ".$offer->id);
                }else{
                    Utilities::logFailedMigration("Offer Offer not Migrated.. Client not found V1OfferId: ".$v1Offer['id']);
                }
            }
        }
    }

    private function migratePackagePhotos($v1Package, $package, $user)
    {
        DB::connection('db1')->table('package_photos')->where("package_id", $v1Package['id'])->orderBy('id')->chunk(500, function ($records) use($package, $user) {
            if(count($records) > 0) {
                foreach ($records as $record) {
                    $packagePhoto = (array) $record;
                    $photo = DB::connection('db1')->table('files')->where("id", $packagePhoto['file_id'])->first();
                    if($photo) $photo = (array) $photo;

                    $media = new PackageMedia;
                    $media->package_id = $package->id;
                    $media->file_id = 1;
                    $media->created_at = $packagePhoto['created_at'];
                    $media->updated_at = $packagePhoto['updated_at'];
                    $media->migrated = true;
                    $media->save();

                    $file = ($photo) ? $this->migrateFile($photo, ['id' => $user->id, 'type' => User::$userType], ['id'=>$media->id, 'type'=>PackageMedia::$type], FilePurpose::PACKAGE_PHOTO) : null;
                    if($file) {
                        $media->file_id = $file->id;
                        $media->update();
                    }
                    Utilities::logSuccessMigration("Package Media Migration.. MediaId: ".$media->id);
                }
            }
        });
        // $this->markAsMigrated($this->packagePhotosMigration);
    }

    private function migrateOfferPayments($v1Offer, $offer)
    {
        DB::connection('db1')->table('sales_offer_payments')->where("offer_id", $v1Offer['id'])->orderBy('id')->chunk(500, function ($records) use($v1Offer, $offer) {
            if(count($records) > 0) {
                foreach ($records as $record) {
                    $v1Payment = (array) $record;
                    $paymentMode = DB::connection('db1')->table('payment_modes')->where("id", $v1Payment['payment_mode_id'])->first();
                    if($paymentMode) {
                        $paymentMode = (array) $paymentMode;
                        $paymentMode = PaymentMode::where('name', 'LIKE', '%'.$paymentMode['name'].'%')->first();
                    }
                    $bankAccount = ($v1Payment['bank_account_id']) ? $this->getBankAccount($v1Payment['bank_account_id']) : null;
                    $user = ($v1Payment['user_id']) ? $this->getUser($v1Payment['user_id']) : null;
                    $client = $this->getClient($v1Offer['customer_id']);

                    if($client) {
                        $payment = new Payment;
                        $payment->client_id = $client->id;
                        $payment->purchase_id = $offer->id;
                        $payment->purchase_type = Offer::$type;
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
                        $payment->purpose = PaymentPurpose::OFFER_PAYMENT->value;
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

                        Utilities::logSuccessMigration("Offer Payment Migration Successful.. PaymentId: ".$payment->id);
                    }else{
                        Utilities::logFailedMigration("Offer Payment not Migrated.. Client not found SalesOfferPaymentId: ".$v1Payment['id']);
                    }
                }
            }
        });
        // $this->markAsMigrated($this->paymentsMigration);
    }

    private function migrateOfferBids($v1Offer, $offer)
    {
        DB::connection('db1')->table('offer_bids')->where("offer_id", $v1Offer['id'])->orderBy('id')->chunk(500, function ($records) use($v1Offer, $offer) {
            if(count($records) > 0) {
                foreach ($records as $record) {
                    $v1Bid = (array) $record;
                    $client = $this->getClient($v1Offer['customer_id']);
                    $paymentStatus = $this->getPaymentStatus($v1Offer['payment_status_id']);
                    if(!$paymentStatus) $paymentStatus = PaymentStatus::pending();

                    if($client) {
                        $bid = new OfferBid;
                        $bid->client_id = $client->id;
                        $bid->offer_id = $offer->id;
                        $bid->price = $v1Bid['bid_price'];
                        $bid->accepted = $v1Bid['accepted'];
                        $bid->cancelled = $v1Bid['cancelled'];
                        $bid->payment_status_id = $paymentStatus->id;
                        $bid->created_at = $v1Bid['created_at'];
                        $bid->updated_at = $v1Bid['updated_at'];
                        $bid->migrated = true;
                        $bid->save();

                        Utilities::logSuccessMigration("Offer Bid Migration Successful.. BidId: ".$bid->id);
                    }else{
                        Utilities::logFailedMigration("Offer Bid not Migrated.. Client not found V1BidId: ".$v1Bid['id']);
                    }
                }
            }
        });
    }

}
