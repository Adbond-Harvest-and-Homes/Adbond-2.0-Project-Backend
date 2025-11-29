<?php

namespace app\Services;

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

/**
 * AgeGroup service class
 */
trait MigrationService
{

    protected function migrateFile($record, $user, $belongs, $purpose)
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

    protected function getFile($fileId, $user, $belongs, $purpose)
    {
        $v1File = DB::connection('db1')->table('files')->where("id", $fileId)->first();
        $file = null;
        if($v1File) {
            $v1File = (array) $v1File;
            $file = $this->migrateFile($v1File, $user, $belongs, $purpose);
        }
        return $file;
    }

    protected function getUser($userId)
    {
        $user = DB::connection('db1')->table('users')->where("id", $userId)->first();
        if($user) {
            $userData = (array) $user;
            $user = User::where("email", $userData['email'])->first();
        }
        return $user;
    }

    protected function getClient($customerId)
    {
        $customer = DB::connection('db1')->table('customers')->where("id", $customerId)->first();
        $client = null;
        if($customer) {
            $customer = (array) $customer;
            $client = Client::where("email", $customer['email'])->first();
        }
        return $client;
    }

    protected function getPackages($packageId)
    {
        $v1Package = DB::connection('db1')->table('packages')->where("id", $packageId)->first();
        $package = null;
        if($v1Package) {
            $v1Package = (array) $v1Package;
            $packages = Package::where("name", "LIKE",  "%".$v1Package['name']."%")->get();
        }
        return $packages;
    }

    protected function getProjectFromProjectLocation($projectLocationId)
    {
        $project = null;
        $projectLocation = DB::connection('db1')->table('project_locations')->where("id", $projectLocationId)->first();
        if($projectLocation) {
            $projectLocation = (array) $projectLocation;
            $state = DB::connection('db1')->table('states')->where("id", $projectLocation['state_id'])->first();
            $projectLocations = DB::connection('db1')->table('project_locations')->where("project_id", $projectLocation['project_id'])->get();
            $v1Project = DB::connection('db1')->table('projects')->where("id", $projectLocation['project_id'])->first();
            if($v1Project && $state) {
                $v1Project = (array) $v1Project;
                $state = (array) $state;
                $name = ($projectLocations->count() > 1) ? $v1Project['name']." ".$state['name'] : $v1Project['name'];
                $project = Project::where("name", $name)->first();
            }
        }
        return $project;
    }

    protected function getPackageFromPackageItem($packageItemId)
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
                dd($name);
                $package = Package::where("name", $name)->first();
            }
        }
        return $package;
    }

    protected function getPackageNameFromPackageItem($packageItemId, $package, $packageItems)
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

    protected function getBankAccount($accountId)
    {
        $account = DB::connection('db1')->table('bank_accounts')->where("id", $accountId)->first();
        if($account) {
            $account = (array) $account;
            $account = BankAccount::where('name', 'LIKE', '%'.$account['account_name'].'%')->first();
        }
        return $account;
    }

    protected function getPaymentStatus($paymentStatusId)
    {
        $paymentStatus = DB::connection('db1')->table('payment_statuses')->where("id", $paymentStatusId)->first();
        if($paymentStatus) {
            $paymentStatus = (array) $paymentStatus;
            $paymentStatus = PaymentStatus::where("name", "LIKE",  "%".$paymentStatus['name']."%")->first();
        }
        return $paymentStatus;
    }

    protected function getPreview($content)
    {
        // Split into words
        $words = explode(" ", $content);

        // Take the first 20
        $first20 = array_slice($words, 0, 20);

        // Join them back into a string
        $result = implode(" ", $first20);

        return $result;
    }

    function isValidDate($date, $format = 'Y-m-d') {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    function extractDate($createdAt)
    {
        $arr = explode(" ", $createdAt);
        return $arr['0'];
    }

}
