<?php

namespace app;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

use app\Models\HybridStaffDraw;
use app\Models\User;
use app\Models\Notification;
use app\Models\Company_info;
use app\Models\Payment;
use app\Models\SalesOfferPayment;
use app\Models\Client_package;

use Illuminate\Support\Facades\Http;
use app\Services\HybridStaffDrawService;
use app\Services\UserService;
usE app\Services\LoyaltyService;
use app\Services\FileService;
use app\Services\ClientService;

use app\Http\Resources\MonthlyWeekDaysResource; 
use app\Http\Resources\InspectionDayMinResource;
use app\Http\Resources\VirtualStaffAssessmentResource;
use app\Http\Resources\OfferMinResource;
use app\Http\Resources\OfferBidMinResource;
use app\Http\Resources\PaymentMinResource;
use app\Http\Resources\OfferPaymentResource;
use app\Http\Resources\OrderMinResource;

use PDF;

use app\Enums\ClientPackageOrigin;
use app\Enums\PackageType;
use app\Enums\ProjectType;
use app\Utilities;

Class Helpers
{
    public static $purchaseOrigin = ClientPackageOrigin::ORDER->value;

    public static function companyInfo()
    {
        return Company_info::first();
    }

    /*
    * Determine whether the client Package is on active offer
    */
    public static function ClientPackageOnOffer($clientPackageOffers)
    {
        $onOffer = false;
        if($clientPackageOffers->count() > 0) {
            foreach($clientPackageOffers as $offer) {
                if($offer->approved != -1) $onOffer = true;
            }
        }
        return $onOffer;
    }

    public static function active_promos($packageItem)
    {
        $package = $packageItem->package;
        $projectLocation = $packageItem->package?->projectLocation;
        $project = $packageItem->package?->projectLocation?->project;
        $category = $packageItem->package?->projectLocation?->project?->category;

        $promos = [];
        if($package && $package->promoProducts->count() > 0) {
            foreach($packageItem->package->promoProducts as $promoProduct) {
                if($promoProduct->promo && $promoProduct->promo->active == 1) {
                    $promos[] =  $promoProduct->promo;
                }
            }
        }
        if($projectLocation && $projectLocation->promoProducts->count() > 0) {
            foreach($projectLocation->promoProducts as $promoProduct) {
                if($promoProduct->promo && $promoProduct->promo->active == 1) {
                    $promos[] =  $promoProduct->promo;
                }
            }
        }
        if($project && $project->promoProducts->count() > 0) {
            foreach($project->promoProducts as $promoProduct) {
                if($promoProduct->promo && $promoProduct->promo->active == 1) {
                    $promos[] =  $promoProduct->promo;
                }
            }
        }
        if($category && $category->promoProducts->count() > 0) {
            foreach($category->promoProducts as $promoProduct) {
                if($promoProduct->promo && $promoProduct->promo->active == 1) {
                    $promos[] =  $promoProduct->promo;
                }
            }
        }
        return $promos;
    }

    public static function package_promos($package)
    {
        $projectLocation = $package?->projectLocation;
        $project = $package?->projectLocation?->project;
        $category = $package?->projectLocation?->project?->category;

        $promos = [];
        if($package->promoProducts && $package->promoProducts->count() > 0) {
            foreach($package->promoProducts as $promoProduct) {
                $promos[] =  $promoProduct->promo;
            }
        }
        if($projectLocation && $projectLocation->promoProducts->count() > 0) {
            foreach($projectLocation->promoProducts as $promoProduct) {
                $promos[] =  $promoProduct->promo;
            }
        }
        if($project && $project->promoProducts->count() > 0) {
            foreach($project->promoProducts as $promoProduct) {
                $promos[] =  $promoProduct->promo;
            }
        }
        if($category && $category->promoProducts->count() > 0) {
            foreach($category->promoProducts as $promoProduct) {
                $promos[] =  $promoProduct->promo;
            }
        }
        return $promos;
    }

    public static function item_price($packageItem, $client_id=null)
    {
        $installmentDiscounts = 0;
        $fullPaymentDiscounts = 0;
        $installmentDiscount = 0;
        $fullPaymentDiscount = 0;
        $onPromo = false;
        if($packageItem->discount > 0) {
           $fullPaymentDiscounts = $packageItem->discount; 
        }
        // Get the promos attached to packageItem package
        $promos = self::active_promos($packageItem);
        if(count($promos) > 0) {
            $onPromo = true;
            foreach($promos as $promo) {
                $fullPaymentDiscounts += $promo->discount;
                $installmentDiscounts += $promo->discount;
            }
        }

        if($client_id != null) {
            //Check for loyalty discount
            $loyaltyService = new LoyaltyService;
            $loyalty = $loyaltyService->unredeemedClientLoyalty($client_id);
            if($loyalty) {
                $company = Company_info::company();
                $fullPaymentDiscounts += $company->loyalty_discount;
                $installmentDiscounts += $company->loyalty_discount; 
            }
        }

        if($fullPaymentDiscounts > 0) {
            $fullPaymentDiscount = ($fullPaymentDiscounts/100) * $packageItem->price;
        }
        if($installmentDiscounts > 0) {
            $installmentDiscount = ($installmentDiscounts/100) * $packageItem->price;
        }
        $fullPaymentAmount = $packageItem->price - $fullPaymentDiscount;
        $installmentAmount = $packageItem->price - $installmentDiscount;
        return ["fullPaymentAmount" => $fullPaymentAmount, "installmentAmount"=>$installmentAmount, "onPromo"=>$onPromo];
    }

    public static function amount_payable($packageItem, $units=1)
    {
        $amount = self::item_price($packageItem);
        // Log::stack(['project'])->info('Units：'.$units);
        $amount["fullPaymentAmount"] = ($amount["fullPaymentAmount"] * $units) + ($packageItem->infrastructure_fee * $units);
        $amount["installmentAmount"] = ($amount["installmentAmount"] * $units) + ($packageItem->infrastructure_fee * $units);
        return $amount;
    }

    public static function packageSoldOut($package)
    {
        $soldOut = true;
        if($package->items->count() > 0) {
           foreach($package->items as $item) {
               if($item->available_units > 0) $soldOut = false;
           } 
        }
        return $soldOut;
    }

    public static function kycCompleted($client)
    {
        $completed = true;
        if(
            // $client->photo_id == '' || $client->photo_id == null ||
            $client->gender == '' || $client->gender == null ||
            $client->marital_status == '' || $client->marital_status == null ||
            $client->employment_status == '' || $client->employment_status == null ||
            // $client->occupation == '' || $client->occupation == null ||
            // $client->postal_code == '' || $client->postal_code == null ||
            $client->phone_number == '' || $client->phone_number == null ||
            $client->address == '' || $client->address == null ||
            $client->dob == '' || $client->dob == null ||
            // $client->country_id == '' || $client->country_id == null ||
            // $client->state_id == '' || $client->state_id == null ||
            !$client->nextOfKins
        ) {
            $completed = false;
        }
        return $completed;
    }

    public static function kycStarted($client)
    {
        $started = false;
        if(
            // $client->photo_id == '' || $client->photo_id == null ||
            ($client->gender != '' && $client->gender != null) ||
            ($client->marital_status != '' || $client->marital_status != null) ||
            ($client->employment_status != '' || $client->employment_status != null) ||
            // ($client->occupation != '' || $client->occupation != null) ||
            // ($client->postal_code != '' || $client->postal_code != null) ||
            ($client->phone_number != '' || $client->phone_number != null) ||
            ($client->address != '' || $client->address != null) ||
            ($client->dob != '' || $client->dob != null) ||
            // $client->country_id == '' || $client->country_id == null ||
            // $client->state_id == '' || $client->state_id == null ||
            ($client->nextOfKins)
        ) {
            $started = true;
        }
        return $started;
    }

    public static function updateKYC($clientId, $status)
    {
        $clientService = new ClientService;

        $client = $clientService->getClient($clientId);
        if($client) $client = $clientService->update(["kycStatus" => $status], $client);
        return $client;
    }

    public static function clientPackageUnits($clientPackage)
    {
        $units = $clientPackage->order?->units;
        if($clientPackage->purchase_type==Client_package::$offerPurchase) {
            $units = self::clientPackageUnits($clientPackage->offer->clientPackage);
        }
        return $units;
    }

    public static function selectVirtualStaffParent()
    {
        $hybridStaffDrawService = new HybridStaffDrawService;
        $userService = new UserService;
        // Check for open draw
        $openDraw = $hybridStaffDrawService->getOpenDraw();
        // Select staffs for draw
        $idArr = $userService->selectStaffsForDraw($openDraw);

        //Select the lates fullStaff as a fall back for if there is no virtual staff to be selected from
        $staff = $userService->latestFullStaff();
        // Use the full Staff's id as default incase no virtual staff exists in the DB
        $selectedId = $staff?->id;
        if(!empty($idArr)) {
            // if there was ids to be drawn
            // Randomely select an id
            $selectedId = $idArr[array_rand($idArr)];
            $selectedUser = $userService->getUser($selectedId);
            $openDraw = ($openDraw) ? $hybridStaffDrawService->IncreaseSelected($openDraw) : $hybridStaffDrawService->openDraw(count($idArr));
            
            // Update the selected user to reflect that he/she has been selected
            $userService->update(['hybrid_staff_draw_id'=>$openDraw->id], $selectedUser);
        }
        return $selectedId;
    }

    // public static function getTarget($target_type, $target)
    // {
    //     switch($target_type) {
    //         case Notification::$customInspection : return new MonthlyWeekDaysResource($target); 
    //         case Notification::$generalInspection : return new InspectionDayMinResource($target);
    //         case Notification::$assessment : return new VirtualStaffAssessmentResource($target);
    //         case Notification::$offer : return new OfferMinResource($target);
    //         case Notification::$bid : return new OfferBidMinResource($target);
    //         case Notification::$payment : return new PaymentMinResource($target);
    //         case Notification::$offerPayment : return new OfferPaymentResource($target);
    //         case Notification::$order : return new OrderMinResource($target);
    //         default : return null;
    //     }
    // }

    public static function generateReceiptNo($purchaseId, $clientId, $processingId=null)
    {
        $i = 0;
        do{
            if(!$processingId || $i > 0) $processingId = Utilities::generateRandomNumber(5);
            $receiptNo = $purchaseId.$processingId.$clientId;
            $exists = Payment::where("receipt_no", $receiptNo)->first();
            $i++;
        }while($exists);
        return $receiptNo;
    }

    public static function generateOfferReceiptNo($payment)
    {
        $start = false;
        $count = 1;
        $receiptNo = 00;
        do{
            if(!$start) {
                $receiptNo = $payment->offer_id.$payment->id;
                $start = true;
            }else{
                $zeros = '';
                for($i=0; $i<$count; $i++) {
                    $zeros += '0';
                }
                $receiptNo = (int)$payment->purchase_id.$zeros.$payment->id;
                $count++;
            }
            $exists = SalesOfferPayment::where('receipt_no', $receiptNo)->first();
        }while($exists);
        return $receiptNo;
    }

    public static function getAge($dob)
    {
        $tz  = new \DateTimeZone('Africa/Lagos');
        $age = \DateTime::createFromFormat('Y-m-d', $dob, $tz)
            ->diff(new \DateTime('now', $tz))
            ->y;
        return $age;
    }

    

    public static function optimizePhoto($url)
    {
        return 'https://res.cloudinary.com/demo/image/fetch/f_auto/'.$url;
    }

    public static function eventInvite($event)
    {
        // $defaultTimeZone = date_default_timezone_get();
        //date_default_timezone_set("Africa/Lagos");
        if(!empty($event) && isset($event['name']) && isset($event['start']) && isset($event['venue']) && isset($event['description']) && isset($event['organizer_email'])) {
            $startTime = strtotime('-1 hour',strtotime($event['start']));
            $endTime = (isset($event['end'])) ? strtotime('-1 hour',strtotime($event['end'])) : null;
            // dd(date('H:i', $event['event_start']));
            $name = $event['name'];
            $location = $event['venue']; 
            $start = date('Ymd', $startTime) . 'T' . date('His', $startTime) . 'Z';
            $end = ($endTime) ? date('Ymd', $endTime) . 'T' . date('His', $endTime) . 'Z' : null;
            $description = $event['description'];
            $slug = strtolower(str_replace(array(' ', "'", '.'), array('_', '', ''), $name));

            $ical =  "BEGIN:VCALENDAR\n";
            $ical .= "VERSION:2.0\n";
            $ical .= "PRODID:-//LearnPHP.co//NONSGML {$name}//EN\n";
            $ical .= "METHOD:REQUEST\n"; // requied by Outlook
            // $ical .= "BEGIN:VTIMEZONE";
            // $ical .= "TZID:Africa/Lagos";
            // $ical .= "END:VTIMEZONE";
            $ical .= "BEGIN:VEVENT\n";
            $ical .= "UID:".date('Ymd').'T'.date('His')."-".rand()."-learnphp.co\n"; // required by Outlok
            $ical .= "DTSTAMP:".date('Ymd').'T'.date('His')."\n"; // required by Outlook
            $ical .= "DTSTART:{$start}\n"; 
            if($end) $ical .= "DTEND:{$end}\n";
            $ical .= "LOCATION:{$location}\n";
            $ical .= "SUMMARY:{$name}\n";
            $ical .= "ORGANIZER;CN=ADBOND:MAILTO:{$event['organizer_email']}\n";
            $ical .= "DESCRIPTION: {$description}\n";
            $ical .= "END:VEVENT\n";
            $ical .= "END:VCALENDAR\n";
            header("Content-Type: text/Calendar; charset=utf-8");
            header("Content-Disposition: inline; filename={$slug}.ics");
            return $ical;
        }
        return null;
    }

    public static function copy_external_file($file, $filename)
    {
        // $filename = $name.'.'.$file->extension;;
        $tempImage = tempnam(sys_get_temp_dir(), $filename);
        copy($file->secure_url, $tempImage);
        return $tempImage;
    }

    public static function removeSpecialCharacters($string)
    {
        $sanitizedString = preg_replace('/[^A-Za-z0-9 ]/', '', $string);
        return str_replace(" ", "-", $sanitizedString);
    }

    public static function getPaystackCommission($amount)
    {
        return (env('PAYSTACK_COMMISSION', 1.5)/100) * $amount;
    }

    public static function moveUploadedFileToCloud($filePath, $fileType, $userId, $purpose, $userType, $folder)
    {
        $filePath = public_path($filePath);
        $fileService = new FileService;
        $success = false;
        $uploadRes = null;
        if (file_exists($filePath)) {
            $uploadRes = $fileService->save($filePath, $fileType, $userId, $purpose, $userType, $folder);
            if($uploadRes['status'] == 200) {
                $success = true;
                // unlink($filePath);
            }else{
                Utilities::logStuff("Receipt could not be uploaded... ".$uploadRes['message']);
            }
        } else {
            Utilities::logStuff("receipt generated cannot be found");
        }
        return ($success) ? ['success'=>$success, 'upload' => $uploadRes, 'path' => $filePath] : ['success' => $success];
    }

    /*
        // $ical = " BEGIN:VCALENDAR VERSION:2.0 CALSCALE:GREGORIAN BEGIN:VEVENT UID:" 
        //         . md5(uniqid(mt_rand(), true)) ."".$domain." DTSTAMP:" . gmdate('Ymd').'T'. gmdate('His') 
        //         . " DTSTART;TZID=".$dealer->timezone.":".str_replace('-', '', $date)."T".str_replace(':', '', $startTime)
        //         ." DTEND;TZID=".$dealer->timezone.":".str_replace('-', '', $date)."T".str_replace(':', '', $endTime)
        //         ." SUMMARY:".$subject
        //         ." LOCATION:".$video->FromAddress.", ".$video->FromCity." ".$video->FromPostal
        //         ." DESCRIPTION:".$desc." URL:http://www.somesite.com" 
        //         BEGIN:VALARM TRIGGER:-PT30M DESCRIPTION:Appointment Reminder ACTION:DISPLAY END:VALARM END:VEVENT END:VCALENDAR"; 
        //         header('Content-type: text/Calendar; charset=utf-8'); header('Content-Disposition: inline; filename=calendar.ics'); 
        //         echo $ical;
        // $event = array(
        //     'event_name' => 'Test Event',
        //     'event_description' => 'This is a test event. This is the description.',
        //     // mktime(hour, minute, second, month, day, year)
        //     'event_start' => strtotime("2022-10-22 11:30"),
        //     // 'event_start' => mktime(11, 00, 0, 10, 23, 2022),
        //     // 'event_end' => time() + 60*60*2,
        //     'event_venue' => array(
        //         'venue_name' => 'Test Venue',
        //         'venue_address' => '123 Test Drive',
        //         'venue_address_two' => 'Suite 555',
        //         'venue_city' => 'Some City',
        //         'venue_state' => 'Iowa',
        //         'venue_postal_code' => '12345'
        //     )
        // );
        $startTime = strtotime('-1 hour',strtotime($event['start']));
        // dd(date('H:i', $event['event_start']));
        $name = $event['event_name'];
        $venue = $event['event_venue'];
        $location = $venue['venue_name'] . ', ' . $venue['venue_address'] . ', ' . $venue['venue_address_two'] . ', ' . $venue['venue_city'] . ', ' . $venue['venue_state'] . ' ' . $venue['venue_postal_code']; 
        $start = date('Ymd', $event['event_start']) . 'T' . date('His', $event['event_start']) . 'Z';
        // $end = date('Ymd', $event['event_end']+18000) . 'T' . date('His', $event['event_end']+18000) . 'Z';
        $description = $event['event_description'];
        $slug = strtolower(str_replace(array(' ', "'", '.'), array('_', '', ''), $name));

        $ical =  "BEGIN:VCALENDAR\n";
        $ical .= "VERSION:2.0\n";
        $ical .= "PRODID:-//LearnPHP.co//NONSGML {$name}//EN\n";
        $ical .= "METHOD:REQUEST\n"; // requied by Outlook
        // $ical .= "BEGIN:VTIMEZONE";
        // $ical .= "TZID:Africa/Lagos";
        // $ical .= "END:VTIMEZONE";
        $ical .= "BEGIN:VEVENT\n";
        $ical .= "UID:".date('Ymd').'T'.date('His')."-".rand()."-learnphp.co\n"; // required by Outlok
        $ical .= "DTSTAMP:".date('Ymd').'T'.date('His')."\n"; // required by Outlook
        $ical .= "DTSTART:{$start}\n"; 
        // $ical .= "DTEND:{$end}\n";
        $ical .= "LOCATION:{$location}\n";
        $ical .= "SUMMARY:{$name}\n";
        $ical .= "DESCRIPTION: {$description}\n";
        $ical .= "END:VEVENT\n";
        $ical .= "END:VCALENDAR\n";
        header("Content-Type: text/Calendar; charset=utf-8");
        header("Content-Disposition: inline; filename={$slug}.ics");
        date_default_timezone_set($defaultTimeZone);
        return $ical;

    */

    public static function request($url, $headers=[], $post=[])
    {
        return (count($post)==0) ? Http::withHeaders($headers)->get($url)->json() : Http::withHeaders($headers)->post($url, $post)->json();
        // dd($res);
    }

    public static function wordDoc($data)
    {
        // dd(file_exists("files/WEB CONTRACT SAMPLE.doc"));
        // dd(is_readable("files/WEB CONTRACT SAMPLE.doc"));
        if(!isset($data['package']) || $data['package']==null) $data['package'] = '';
        if(!isset($data['client']) || $data['client']==null) $data['client'] = '';
        if(!isset($data['address']) || $data['address']==null) $data['address'] = '';
        if(!isset($data['size']) || $data['size']==null) $data['size'] = '';
        if(!isset($data['price']) || $data['price']==null) $data['price'] = '';
        $data['location'] = (!isset($data['location']) || $data['location']==null) ? '' : $data['location'];
        // $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor("files/WEB CONTRACT SAMPLE TEMPLATE.docx");
        // $templateProcessor->setValue('day', date('jS'));
        // $templateProcessor->setValue('month', date('F'));
        // $templateProcessor->setValue('year', date('Y'));
        // $templateProcessor->setValue('package', $data['package']);
        // $templateProcessor->setValue('client', $data['client']);
        // $templateProcessor->setValue('address', $data['address']);
        // $templateProcessor->setValue('size', $data['size']);
        // $templateProcessor->setValue('price', $data['price']);
        // $templateProcessor->setValue('location', $data['location']);
        // $templateProcessor->saveAs('files/contract.docx');

        // $addressArr = [];
        // $addressArr = self::formatAddress($payment?->client?->address);
        // $pdfData = [
        //     'image' => public_path('images/logo.PNG'),
        //     'day' => date('jS'),
        //     'month' => date('F'),
        //     'year' => date('Y'),
        //     'package' => $data['package'],
        //     'client' => $data['client'],
        //     'address' => $data['address'],
        //     'price' => $data['price'],
        //     'size' => $data['size'],
        //     'location' => $data['location']
        // ];
        // $pdf = PDF::loadView('pdf/letter_of_happiness', $pdfData);
        // // return $pdf->stream('letter_of_happiness.pdf');
        // $pdf->save('files/letter_of_happiness.pdf');
    }



    private static function prepareContract($purchase)
    {
        // $itemPrice = Helpers::item_price($purchase->packageItem);
        $projectType = $purchase?->package?->project?->projectType?->name;
        $client = (self::$purchaseOrigin==ClientPackageOrigin::OFFER->value) ? $purchase?->acceptedBid?->client : $purchase->client;
        $data['package'] = $purchase->package?->name;
        $data['project'] = $purchase?->package?->project?->name;
        $data['use_type'] = ($projectType == ProjectType::AGRO->value) ? "AGRICULTURAL" : "RESIDENTIAL";
        $data['client'] =  ucfirst($purchase->client->full_name);
        $data['address'] = $client?->address;
        // $data['location'] = $purchase?->packageItem?->package?->project_location?->location?->name;
        $projectAddress = $purchase?->package?->address;
        $state = $purchase?->package?->state.' State';
        $data['location'] = ($projectAddress) ? $projectAddress.', '.$state : $state;
        $data['state'] = $state;
        $data['price'] = (self::$purchaseOrigin==ClientPackageOrigin::OFFER->value) ? $purchase?->acceptedBid?->price : $purchase->amount_payable;
        $data['installment'] = (self::$purchaseOrigin != ClientPackageOrigin::OFFER->value && $purchase->is_installment == 1) ? true : false;
        $data['installment_duration'] = $purchase?->package->installment_duration;
        // if the units purchaseed is more than 1, multiply by units
        // if($purchase->units && $purchase->units > 1) $data['price'] = $data['price'] * $purchase->units; 
        $data['size'] = ($purchase?->package?->size && $purchase->units > 0) ? $purchase?->package?->size * $purchase->units : null;
        // Helpers::wordDoc($data);
        // return Helpers::generateContract($data);
        return $data;
        // dd($data);
    }

    public static function generateContract($order, $preparedData=null)
    {
        $data = ($preparedData) ? $preparedData : self::prepareContract($order);
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
        $file = "files/contract_{$order->id}.pdf";
        $path = public_path($file);
        $pdf->save($path);
        return $file;
    }

    public static function generateLetterOfHappiness($payment, $isPayment=true)
    {
        $date = ($isPayment) ? $payment->payment_date : now();
        $addressArr = [];
        $addressArr = self::formatAddress($payment?->client?->address);
        $unitSize = $payment?->purchase?->package->size;
        $size = ($unitSize != null && $payment?->purchase?->units != null && $payment?->purchase?->units > 0) ? $unitSize * $payment?->purchase?->units : $unitSize;
        $pdfData = [
            'image' => 'logo.jpg',
            'name' => $payment?->client?->full_name,
            'addressArr' => $addressArr,
            'date' => date('jS F, Y'),
            'package' => $payment?->purchase?->package?->package?->name,
            'project' => $payment?->purchase?->package?->package?->project?->project?->name,
            'location' => $payment?->purchase?->package?->package?->project?->location?->name,
            'price' => $payment?->purchase?->amount_payable,
            'amount_paid' => ($isPayment) ? $payment->amount : 0,
            'units' => $payment?->purchase?->units,
            'size' => $size,
            'payment_date' => date('d/m/Y', strtotime($date))
        ];
        // dd($pdfData);
        $pdf = PDF::loadView('pdf/letter_of_happiness', $pdfData);

        $file = "files/letter_of_happiness_{$payment->purchase->id}.pdf";
        // return $pdf->stream('letter_of_happiness.pdf');
        $pdf->save($file);
        return $file;
        // dd('done');
    }

    public static function generateHomesLetterOfHappiness($payment, $isPayment=true)
    {
        $addressArr = [];
        $addressArr = self::formatAddress($payment?->client?->address);
        $pdfData = [
            'image' => 'logo.jpg',
            'name' => $payment?->client?->full_name,
            'addressArr' => $addressArr,
            'date' => date('jS F, Y'),
        ];
        // dd($pdfData);
        $pdf = PDF::loadView('pdf/letter_of_happiness_homes', $pdfData);
        // return $pdf->stream('letter_of_happiness.pdf');
        // $pdf->setPaper('A4', 'portrait');
        $file = "files/letter_of_happiness_{$payment->purchase->id}.pdf";
        $pdf->save($file);
        // dd('done');
        return $file;
    }

    public static function generateMemorandumAgreement($order)
    {
        $data = self::prepareContract($order);
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
        $file = "files/memorandum_agreement_{$order->id}.pdf";
        $pdf->save($file);
        return $file;
    }

    public static function generateReceipt($payment)
    {
        $address1 = '';
        $address2 = '';
        $address3 = '';
        $addressArr = self::formatAddress($payment?->client?->address);
        if(count($addressArr) > 0) {
            if(isset($addressArr[0])) $address1 = $addressArr[0];
            if(isset($addressArr[1])) $address2 = $addressArr[1];
            if(isset($addressArr[2])) $address3 = $addressArr[2];
        }
        $discount = 0;
        if($payment?->purchase?->discounts && $payment?->purchase?->discounts->count() > 0) {
            foreach($payment?->purchase->discounts as $orderDiscount) {
                $discount += $orderDiscount->discount;
            }
        }
        $unitSize = $payment?->purchase?->package?->size;
        $size = ($unitSize != null && $payment?->purchase?->units != null && $payment?->purchase?->units > 0) ? $unitSize * $payment?->purchase?->units : $unitSize;
        $purchaseBalance = $payment?->purchase?->balance ?? 0;
        $amount = $payment?->amount ?? 0;
        $totalPayed = $payment?->purchase?->amount_payed + $amount;
        $balance = $purchaseBalance - $amount;
        $balance = ($balance >= 0) ? $balance : 0;
        $pdfData = [
            'image' => 'logo.png', 
            'name' => ucfirst($payment?->client?->full_name),
            'clientState' => $payment?->client?->state?->name ?? '',
            'clientCountry' => $payment?->client?->country?->name ?? '',
            'receiptNo' => $payment->receipt_no,
            'address1' => $address1,
            'address2' => $address2,
            'address3' => $address3,
            'date' => date('jS F, Y'),
            'package' => $payment?->purchase?->package?->name,
            'project' => $payment?->purchase?->package?->project?->name,
            'paymentMethod' => ucfirst($payment?->paymentMode?->name),
            'price' => $payment?->purchase?->package?->amount,
            'amount' => $payment->purchase->amount_payable,
            'currentAmount' => $payment->amount,
            'amountPaid' => $totalPayed,
            'units' => $payment?->purchase?->units,
            'size' => $size,
            'discount' => $discount,
            'balance' => $balance,
            'project_name_state' => $payment?->purchase?->package?->name . " " . $payment?->purchase?->package?->stateModel?->name
        ];

        // dd($pdfData);
        $pdf = PDF::loadView('pdf/receipt', $pdfData);

        $pdf->setOptions(array('isRemoteEnabled' => true));
        // return $pdf->stream('receipt.pdf');
        $pdf->save('files/receipt'.$payment->receipt_no.'.pdf');
        return 'files/receipt'.$payment->receipt_no.'.pdf';
        // dd('done');
    }

    // public static function generateOfferContract($data)
    // {
    //     if(!isset($data['project']) || $data['project']==null) $data['project'] = '';
    //     if(!isset($data['package']) || $data['package']==null) $data['package'] = '';
    //     if(!isset($data['client']) || $data['client']==null) $data['client'] = '';
    //     if(!isset($data['address']) || $data['address']==null) $data['address'] = '';
    //     if(!isset($data['state']) || $data['state']==null) $data['state'] = '';
    //     if(!isset($data['size']) || $data['size']==null) $data['size'] = '';
    //     if(!isset($data['price']) || $data['price']==null) $data['price'] = '';
    //     $data['location'] = (!isset($data['location']) || $data['location']==null) ? '' : $data['location'];
    //     $pdfData = [
    //         'image' => public_path('images/logo.PNG'),
    //         'day' => date('jS'),
    //         'month' => date('F'),
    //         'year' => date('Y'),
    //         'project' => $data['project'],
    //         'package' => $data['package'],
    //         'client' => $data['client'],
    //         'state' => $data['state'],
    //         'address' => $data['address'],
    //         'price' => $data['price'],
    //         'size' => $data['size'],
    //         'location' => $data['location']
    //     ];
    //     $pdf = PDF::loadView('pdf/contract', $pdfData);
    //     // return $pdf->stream('contract.pdf');
    //     $pdf->save('files/contract.pdf');
    // }

    // public static function generateOfferLetterOfHappiness($payment)
    // {
    //     $addressArr = [];
    //     $addressArr = self::formatAddress($payment?->client?->address);
    //     $size = $payment?->offer?->packageItem?->size;
    //     $purchaseType = $payment?->offer->clientPackage->purchase_type;
    //     $units = $payment?->offer->clientPackage->order->units;
    //     if($purchaseType == Client_package::$orderPurchase && $size != null && $units != null && $units > 0) {
    //         // if the item on offer was gotten by order and the size is not null and the units is greater than zero
    //         $size = $size * $units;
    //     }
    //     $pdfData = [
    //         'image' => 'logo.jpg',
    //         'name' => $payment?->client?->full_name,
    //         'addressArr' => $addressArr,
    //         'date' => date('jS F, Y'),
    //         'package' => $payment?->offer?->packageItem?->package?->name,
    //         'project' => $payment?->offer?->packageItem?->package?->projectLocation?->project?->name,
    //         'location' => $payment?->offer?->packageItem?->package?->projectLocation?->location?->name,
    //         'price' => $payment?->amount,
    //         'amount_paid' => $payment->amount,
    //         'units' => $payment?->offer?->units,
    //         'size' => $size,
    //         'payment_date' => date('d/m/Y', strtotime($payment->payment_date))
    //     ];
    //     $pdf = PDF::loadView('pdf/letter_of_happiness', $pdfData);
    //     // return $pdf->stream('letter_of_happiness.pdf');
    //     $pdf->save('files/letter_of_happiness.pdf');
    // }

    // public static function generateOfferReceipt($payment)
    // {
    //     $address1 = '';
    //     $address2 = '';
    //     $address3 = '';
    //     $addressArr = self::formatAddress($payment?->client?->address);
    //     if(count($addressArr) > 0) {
    //         if(isset($addressArr[0])) $address1 = $addressArr[0];
    //         if(isset($addressArr[1])) $address2 = $addressArr[1];
    //         if(isset($addressArr[2])) $address3 = $addressArr[2];
    //     }
    //     $discount = 0;
    //     // if($payment?->order->discounts && $payment?->order->discounts->count() > 0) {
    //     //     foreach($payment?->order->discounts as $orderDiscount) {
    //     //         $discount += $orderDiscount->discount;
    //     //     }
    //     // }
    //     $pdfData = [
    //         'image' => 'logo.jpg',
    //         'name' => ucfirst($payment?->client?->full_name),
    //         'receiptNo' => $payment->receipt_no,
    //         'address1' => $address1,
    //         'address2' => $address2,
    //         'address3' => $address3,
    //         'date' => date('jS F, Y'),
    //         'package' => $payment?->offer?->packageItem?->package?->name,
    //         'project' => $payment?->offer?->packageItem?->package?->projectLocation?->project?->name,
    //         'paymentMethod' => ucfirst($payment?->mode?->name),
    //         // 'price' => $payment?->offer?->packageItem->price,
    //         'amount' => $payment->amount,
    //         'currentAmount' => $payment->amount,
    //         'amountPaid' => $payment->amount,
    //         'units' => $payment?->offer?->units,
    //         'size' => $payment?->offer?->packageItem->size,
    //         'discount' => $discount,
    //         'balance' => 0,
    //     ];

    //     $pdf = PDF::loadView('pdf/offer_receipt', $pdfData);
    //     // return $pdf->stream('receipt.pdf');
    //     $pdf->save('files/receipt'.$payment->receipt_no.'.pdf');
    // }

    private static function formatAddress($address)
    {
        $res = [];
        $addressArr = explode(' ', $address);
        $cutOff = 2;
        $reset = 0;
        $string = '';
        for($i=0; $i < count($addressArr); $i++) {
            $string .= $addressArr[$i];
            if($reset < $cutOff) {
                $string .= ' ';
            }
            if($reset >= $cutOff || $i == count($addressArr)) {
                $string .= ',';
                $res[] = $string;
                $string = '';
                $reset = 0;
            }
            // dd($string);
            $reset++;
        }
        return $res;
    }

    public static function createSlug($title)
    {
        $titleArr = explode(" ", $title);
        return implode("-", $titleArr);
    }

    public static function curl($url, $options=[], $posts=[])
    {

    }

    /**
     * Get the first few words or paragraph from an HTML string (plain text only)
     *
     * @param string $html The HTML string
     * @param int $wordLimit The number of words to extract (default: 20)
     * @return string The truncated plain-text preview
     */
    public static function getTextPreview(string $html, int $wordLimit = 20): string
    {
        // 1. Remove all HTML tags
        $text = strip_tags($html);

        // 2. Decode HTML entities (&nbsp;, &amp;, etc.)
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 3. Normalize whitespace
        $text = trim(preg_replace('/\s+/', ' ', $text));

        // 4. Split into words
        $words = explode(' ', $text);

        // 5. If the text has fewer than $wordLimit words, return it all
        if (count($words) <= $wordLimit) {
            return implode(' ', $words);
        }

        // 6. Otherwise, truncate and add ellipsis
        $preview = implode(' ', array_slice($words, 0, $wordLimit)) . '...';

        return $preview;
    }
}




?>