<?php

namespace App;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

use app\Services\AuthService;
use app\Models\User;
use app\Models\Client;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard\Percentage;

use app\Enums\UserType;
use app\Enums\RefererCodePrefix;

class Utilities
{
    public $guard;
    public $reference;

    public function __construct($guard, $reference)
    {
        $this->guard = $guard;
        $this->reference = $reference;
    }

    public static function error($e, $message='')
    {
        Log::stack(['project'])->info($e->getMessage().' in '.$e->getFile().' at Line '.$e->getLine());
        Log::stack(['project'])->info($e);
        return response()->json([
            'statusCode' => 500,
            'message' => ($message != '') ? $message : 'An error occurred while trying to perform this operation, Please try again later or contact support'
        ], 500);
    }

    public static function customError($message, $statusCode)
    {
        return response()->json([
            'statusCode' => $statusCode,
            'message' => $message
        ], $statusCode);
    }

    public static function logStuff($message)
    {
        Log::stack(['project'])->info($message);
    }

    public static function logSuccessMigration($message)
    {
        Log::stack(['success_migration'])->info($message);
    }

    public static function logFailedMigration($message)
    {
        Log::stack(['failed_migration'])->info($message);
    }

    public function refreshToken()
    {
        $authService = new AuthService($this->guard, $this->reference);
        return $authService->checkToRefreshToken();
    }

    public static function ok($data)
    {
        return response()->json([
            'statusCode' => 200,
            'data' => $data
        ], 200);
    }

    public static function okay($message='', $data=null, )
    {
        $responseData = ['statusCode' => 200];
        if(!empty($data) || $data != '') $responseData['data'] = $data;
        return response()->json([
            'statusCode' => 200,
            'data' => $data,
            'message' => $message
        ], 200);
    }

    public static function custom($statusCode, $res=[])
    {
        $responseData = ['statusCode' => $statusCode];
        $message = (isset($res['message'])) ? $res['message'] : '';
        $data = (isset($res['data'])) ? $res['data'] : '';
        if(!empty($data) || $data != '') $responseData['data'] = $data;
        return response()->json([
            'statusCode' => $statusCode,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    public function paginatedOk($data, $page, $perPage, $total)
    {
        $responseData = ['statusCode' => 200];
        if(!empty($data) || $data != '') $responseData['data'] = $data;
        return response()->json([
            'statusCode' => 200,
            'data' => $data,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            // 'token' => Utilities::refreshToken($this->guard)
        ], 200);
    }

    public static function paginatedOkay($data, $page, $perPage, $total)
    {
        $responseData = ['statusCode' => 200];
        if(!empty($data) || $data != '') $responseData['data'] = $data;
        return response()->json([
            'statusCode' => 200,
            'data' => $data,
            'page' => $page,
            'perPage' => $perPage,
            'pages' => ceil($total/$perPage),
            'total' => $total,
            // 'token' => Utilities::refreshToken($this->guard)
        ], 200);
    }

    public static function ok2($data='')
    {
        $responseData = ['statusCode' => 200];
        if(!empty($data) || $data != '') $responseData['data'] = $data;
        return response()->json([
            'statusCode' => 200,
            'data' => $data,
            'token' => ''
        ], 200);
    }

    public static function error402($message)
    {
        return response()->json([
            'statusCode' => 402,
            'message' => $message
        ], 402);
    }

    public static function shouldMakePayment($order)
    {
        $flag = false;
        if($order->is_installment == 1) {
            if($order->installment_count){
                $flag = ($order->installment_count > $order->installments_payed);
            }else{
                $flag = ($order->balance > 0);
            }
        }
        return $flag;
    }


    public static function isMidYear($date)
    {
        $date = strtotime($date);
        $month = date('m', $date);
        // $date = date('Y-m-d');
        $monthWeek = self::weekOfMonth($date);
        // dd($month);
        return ( (($month==6 || $month==06) && ($monthWeek==3 || $monthWeek==4 || $monthWeek==5))  ||  (($month==7 || $month==07) && ($monthWeek==1)) ) ? true : false;
    }

    public static function isEndYear($date)
    {
        $date = strtotime($date);
        $month = date('m', $date);
        // $date = date('Y-m-d');
        $monthWeek = self::weekOfMonth($date);
        return ( (($month==12 || $month==11) && ($monthWeek==3 || $monthWeek==4 || $monthWeek==5))  ||  (($month==1 || $month==01) && ($monthWeek==1)) ) ? true : false;
    }

    public static function weekOfMonth($date) {
        //Get the first day of the month.
        $firstOfMonth = strtotime(date("Y-m-01", $date));
        //Apply above formula.
        return self::weekOfYear($date) - self::weekOfYear($firstOfMonth) + 1;
    }
    
    private static function weekOfYear($date) {
        $weekOfYear = intval(date("W", $date));
        if (date('n', $date) == "1" && $weekOfYear > 51) {
            // It's the last week of the previos year.
            return 0;
        }
        else if (date('n', $date) == "12" && $weekOfYear == 1) {
            // It's the first week of the next year.
            return 53;
        }
        else {
            // It's a "normal" week.
            return $weekOfYear;
        }
    }

    public static function getDatesForAMonth()
    {
        $dates = [];
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addMonth();

        while ($startDate->lte($endDate)) {
            $dates[] = $startDate->toDateString();
            $startDate->addDay();
        }

        return $dates;
    }

    //Get all the dates that falls on a given weekday from now till next one month
    public static function getMonthDatesForWeekday($weekday)
    {
        $dates = [];
        $today = Carbon::now();
        $endDate = $today->copy()->addMonth();

        while ($today->lessThanOrEqualTo($endDate)) {
            if (Carbon::parse($today)->format('l') === $weekday) {
                $dates[] = $today->toDateString();
            }
            $today->addDay();
        }

        return $dates;
    }

    public static function getDiscount($amount, $discount)
    {
        $discounted = $amount;
        if(($discount <= 0) || $discount > 100) {
            self::logStuff("Getting discount of ".$discount." that is outside 0-100 for the amount: ".$amount);
            // return ["amount" => $amount, "discountedAmount" => 0];
            $discounted = 0;
        }elseif($discount < 100) {
            $discounted =  ($discount/100) * $amount;
        }
        // if($discount == 100) return 0;
        $discountedAmount = $amount - $discounted;
        return ["amount" => $discountedAmount, "discountedAmount" => $discounted];
    }

    /*
    * Gets the total value, from the percentage of the total value and the discount gotten by subtracting the percentage value from the total value
    i.e where, total value = 100 and percentage = 10% thus discount = 90. if only percentage and discount is given, the function calculates the initial total value 
    */
    public static function getTotalFromDiscountAndPercentage($percentage, $discount)
    {
        return (float)((float)$discount/(1-($percentage/100)));
    }

    public static function getPercentageAmount($amount, $percentage)
    {
        if($percentage <= 0) {
            return 0;
        }
        return ($percentage/100) * $amount;
    }

    public static function generateRandomNumber($length)
    {
        $min = pow(10, $length - 1); // Minimum number with the given length
        $max = pow(10, $length) - 1; // Maximum number with the given length
        return random_int($min, $max); // Generate a random number between min and max
    }

    public static function generateRandomString($length)
    {
        $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';
        return substr(str_shuffle($permitted_chars), 0, $length);
    }

    public static function generateRefererCode($userType)
    {
        $prefix = ($userType==UserType::USER->value) ? RefererCodePrefix::USER->value : RefererCodePrefix::CLIENT->value;
        do{
            $code = $prefix.self::generateRandomString(5);
            $exists = User::where('referer_code', $code)->first();
        }while($exists);
        return $code;
    }

    public static function getByRefererCode($code)
    {
        $userType = (strpos($code, RefererCodePrefix::USER->value) !== false) ? UserType::USER->value : UserType::CLIENT->value;
        $user = ($userType == UserType::USER->value) ? User::where('referer_code', $code)->first() : Client::where('referer_code', $code)->first();
        return ["user" => $user, "userType" => $userType];
    }

    public static function getOrderProcessingId()
    {
        do{
            $processingId  = rand(10000, 99999);
            $exists = Cache::has('order_processing_' . $processingId);
        }while($exists);
        return $processingId;
    }

    public static function getOfferProcessingId()
    {
        do{
            $processingId  = rand(10000, 99999);
            $exists = Cache::has('offer_processing_' . $processingId);
        }while($exists);
        return $processingId;
    }

    public static function convertTo12HrsTimeFormat($time)
    {
        $timeArr = explode(':', $time);
        $hr = $timeArr[0];
        $newHr = $hr;
        $part = 'AM';
        if((int)$hr > 12) {
            $newHr = (int)$hr - 12;
        }
        if((int)$hr > 11 && (int)$hr < 24) {
            $part = 'PM';
        }
        return $newHr.':'.$timeArr[1].' '.$part;
    }

    public static function convertTo24HrsTimeFormat($time)
    {
        $timeStringArr = explode(' ', $time);
        $timeString = $timeStringArr[0];
        $part = $timeStringArr[1];

        $timeArr = explode(':', $timeString);
        $hr = $timeArr[0];
        $newHr = $hr;
        if(strtoupper($part) == 'PM' && (int)$hr < 12) {
            $newHr = (int)$hr + 12;
        }
        if(strtoupper($part) == 'AM' && (int)$hr == 12) {
            $newHr = '00';
        }
        return $newHr.':'.$timeArr[1];
    }

    /*
    *   Gets the difference between two dates
    */
    public static function dateDiff($end=null, $start=null)
    {
        $date1 = ($start==null) ? new \DateTime() : new \DateTime($start);
        $date2 = ($end==null) ? new \DateTime() : new \DateTime($end);
        // $diff = now()->diffInDays($model->created_at);
        return $date1->diff($date2);
    }

    public static function getDates($weekPosition, $day)
    {
        $dates = [];
        $tz = date_default_timezone_get();
        date_default_timezone_set('Africa/Lagos');
        $dayName = substr($day, 0, 3);
        
        switch($weekPosition) {
            case 1:
                $times[] = strtotime('first '.$dayName.' of this month');
                $times[] = strtotime('first '.$dayName.' of next month');
                $dates = Self::returnFormattedFutureDates($times);
                break;
            case 2:
                $times[] = strtotime('first '.$dayName.' of this month +1 week');
                $times[] = strtotime('first '.$dayName.' of next month +1 week');
                $dates = Self::returnFormattedFutureDates($times);
                break;
            case 3:
                $times[] = strtotime('first '.$dayName.' of this month +2 week');
                $times[] = strtotime('first '.$dayName.' of next month +2 week');
                $dates = Self::returnFormattedFutureDates($times);
                break;
            case 4:
                $time = strtotime('first '.$dayName.' of this month +3 week');
                $month = date('n', $time);
                $currMonth = date('n', strtotime("now"));
                if($month == $currMonth) $times[] = $time;

                $time = strtotime('first '.$dayName.' of next month +3 week');
                $month = date('n', $time);
                $currMonth = date('n', strtotime("now"));
                if($month == ($currMonth+1)) $times[] = $time;
                //dd($times);
                $dates = Self::returnFormattedFutureDates($times);
                //dd($dates);
                break;
            case 5:
                $times[] = strtotime('last '.$dayName.' of this month');
                $times[] = strtotime('last '.$dayName.' of next month');
                $dates = Self::returnFormattedFutureDates($times);
                break;
        }
        return $dates;
    }

    private static function returnFormattedFutureDates($times)
    {
        $dates = [];
        $now = strtotime("now"); 
        foreach($times as $time) {
            if($time > $now) {
                $dates[] = date('F j, Y', $time);
            }
        }
        return $dates;
    }

    public static function calculateAppreciation($worth, $purchaseWorth)
    {
        // dd($purchaseWorth);
        $difference = $worth - $purchaseWorth;
        $positive = ($difference > 0) ? true : false;
        $absDifference = abs($difference);
        if($purchaseWorth == 0) $purchaseWorth = $absDifference;
        $percentage = ($purchaseWorth && $purchaseWorth > 0) ? ($absDifference/$purchaseWorth) * 100 : null;
        if($percentage) $percentage = number_format($percentage, 2);
        if($percentage) $percentage = ($positive) ? $percentage : 0 - $percentage;
        $amount = $difference;
        return ["percentage" => $percentage, "amount" => $amount];
    }

    // format seconds into minutes or just secs if its less than 1min
    public static function formatSeconds($secs) 
    {
        $formatted = '';
        if($secs >= 60) {
            $minAppendum = 'Min';
            $mins = floor($secs/60);
            if($mins > 1) $minAppendum .= 's';
            $secs = $secs%60;
        } 
        if(isset($mins)) $formatted .= $mins.$minAppendum;
        if($secs > 0) $formatted .= ' '.$secs.'Secs';
        return trim($formatted);
    }

    public static function downgradePenaltyAmount($fromPackage, $units, $penalty)
    {
        $assetAmount = $fromPackage->amount * $units;
        return round(($penalty/100) * $assetAmount, 2);
    }

    public static function getPercentage($val, $total)
    {
        return round(($val/$total) * 100, 2);
    }

    public static function getFileType($mime)
    {
        $type = 'unknown';
        switch($mime) {
        case 'application/pdf' : 
            $type = 'pdf';
            break;
        case 'application/msword':
            $type = 'doc';
            break;
        case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
            $type = 'docx';
            break;
        } 
        if (str_starts_with($mime, 'image/')) {
            $type = 'image';
        }

        return $type;
    }

}