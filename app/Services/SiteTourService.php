<?php

namespace app\Services;

use Carbon\Carbon;

use app\Models\SiteTourSchedule;
use app\Models\SiteTourBooking;
use app\Models\SiteTourBookedSchedule;

use app\Enums\Weekday;

class SiteTourService
{
    /*
        Site Tour Schedule Services
    */
    public $count = null;
    public $cancelled = null;
    public $future = null;
    public $filter = null;
    public $clientId = null;
    public $booked = null;

    public function save($data)
    {
        $siteTourSchedule = new SiteTourSchedule;
        $siteTourSchedule->project_type_id = $data['projectTypeId'];
        $siteTourSchedule->project_id = $data['projectId'];
        $siteTourSchedule->package_id = $data['packageId'];
        $siteTourSchedule->fee = $data['fee'];
        $siteTourSchedule->slots = $data['slots'];
        if(isset($data['availableDate'])) $siteTourSchedule->available_date = $data['availableDate'];
        $siteTourSchedule->available_time = Carbon::createFromFormat('h:i A', $data['availableTime'])->format('H:i:s');
        if(isset($data['recurrent']) && $data['recurrent'] == true) {
            $siteTourSchedule->recurrent = true;
            $siteTourSchedule->recurrent_day = $data['recurrentDay'];
        }

        $siteTourSchedule->save();

        return $siteTourSchedule;
    }

    public function update($data, $siteTourSchedule)
    {
        if(isset($data['projectTypeId'])) $siteTourSchedule->project_type_id = $data['projectTypeId'];
        if(isset($data['projectId'])) $siteTourSchedule->project_id = $data['projectId'];
        if(isset($data['packageId'])) $siteTourSchedule->package_id = $data['packageId'];
        if(isset($data['fee'])) $siteTourSchedule->fee = $data['fee'];
        if(isset($data['slots'])) $siteTourSchedule->slots = $data['slots'];
        if(isset($data['availableDate'])) $siteTourSchedule->available_date = $data['availableDate'];
        if(isset($data['availableTime'])) $siteTourSchedule->available_time = Carbon::createFromFormat('h:i A', $data['availableTime'])->format('H:i:s');
        if(isset($data['recurrent']) && $data['recurrent'] == true) {
            $siteTourSchedule->recurrent = true;
            $siteTourSchedule->recurrent_day = $data['recurrentDay'];
        }

        $siteTourSchedule->update();

        return $siteTourSchedule;
    }

    public function delete($siteTourSchedule)
    {
        $siteTourSchedule->delete();
    }

    public function getScheduleByDate($packageId, $date, $time)
    {
        $time = Carbon::createFromFormat('h:i A', $time)->format('H:i:s');
        return SiteTourSchedule::where("package_id", $packageId)->where("available_time", $time)->where(function($query) use($date) {
            $query->where("available_date", $date)->orWhere(function($query2) use($date) {
                $query2->where("recurrent", 1)->where(function($query3) use($date) {
                    $query3->where("recurrent_day", Weekday::ALL->value)->orWhere("recurrent_day", Carbon::parse($date)->format('l'));
                });
            });
        })->first();
    }

    public function getRecurrent($packageId, $time, $recurrentDay=Weekday::ALL->value)
    {
        return SiteTourSchedule::where("package_id", $packageId)->where("available_time", $time)->where("recurrent", 1)
        ->where("recurrent_day", $recurrentDay)->first();
    }

    public function schedules($with=[], $offset=0, $perPage=null)
    {
        $query = SiteTourSchedule::with($with);
        // if($this->booked === true) {
        //     // if we are getting schedules that are booked
        //     if($this->clientId) {
        //         // if the booking we are looking for is for a particular client
        //         $query->whereHas("clients", function($clientQuery) {
        //             $clientQuery->where("client_id", $this->clientId);
        //         });
        //     }else{
        //         // if its not for a particular client
        //         $query->whereHas("clients");
        //     }
        // }
        // if($this->booked === false) {
        //     // if we are getting schedules that are not booked
        //     if($this->clientId) {
        //         // if we are looking for schedules not booked by a particular client
        //         $query->whereDoesntHave("clients", function($clientQuery) {
        //             $clientQuery->where("client_id", $this->clientId);
        //         });
        //     }else{
        //         // if its not for a particular client
        //         $query->whereDoesntHave("clients");
        //     }
        // }
        if($this->future) $query->where(function($q) { 
            $q->where("available_date", ">", Carbon::now())->orWhere("recurrent", 1);
        });
        if($this->filter) {
            // dd($this->filter);
            $filter = $this->filter;
            if(isset($filter['projectTypeId'])) $query->where("project_type_id", $filter['projectTypeId']);
            if(isset($filter['projectId'])) $query->where("project_id", $filter['projectId']);
            if(isset($filter['packageId'])) $query->where("package_id", $filter['packageId']);
            // if(isset($filter['date'])) $query = $query->where(function($q) use($filter) {
            //     $q = $q->whereDate("available_date", $filter['date']);
            //     if(isset($filter['time'])) {
            //         $time = Carbon::createFromFormat('h:i A', $filter['time'])->format('H:i:s');
            //         $q->whereTime("available_time", $time);
            //     }
            //     })->orWhere(function($q1) use($filter) {
            //         $q1->where("recurrent", 1)->where(function($q2) use($filter) {
            //             $q2->where("recurrent_day", Weekday::ALL->value)->orWhere("recurrent_day", Carbon::parse($filter['date'])->format('l'));
            //         });
            // });

            if(isset($filter['date'])) $query->where(function($q) use ($filter) {
                $q->where(function($q1) use($filter) {
                    $q1->whereDate("available_date", $filter['date']);
                    if (isset($filter['time'])) {
                        $time = Carbon::createFromFormat('h:i A', $filter['time'])->format('H:i:s');
                        $q1->whereTime("available_time", $time);
                    }
                })->orWhere(function($q2) use($filter) {
                    $q2->where("recurrent", 1)
                        ->where(function($q3) use($filter) {
                            $q3->where("recurrent_day", Weekday::ALL->value)
                                ->orWhere("recurrent_day", Carbon::parse($filter['date'])->format('l'));
                        });
                    if (isset($filter['time'])) {
                        $time = Carbon::createFromFormat('h:i A', $filter['time'])->format('H:i:s');
                        $q2->whereTime("available_time", $time);
                    }
                });
            });
            
            // if(isset($filter['time'])) {
            //     $time = Carbon::createFromFormat('h:i A', $filter['time'])->format('H:i:s');
            //     $query = $query->whereTime("available_time", $time);
            // }
            if(isset($filter['text'])) {
                $query->whereHas('package', function($packageQuery) use($filter) {
                    $packageQuery->where("name", "LIKE", "%".$filter['text']."%");
                })->whereHas('project', function($projectQuery) use($filter) {
                    $projectQuery->where("name", "LIKE", "%".$filter['text']."%");
                })->whereHas('projectType', function($projectTypeQuery) use($filter) {
                    $projectTypeQuery->where("name", "LIKE", "%".$filter['text']."%");
                });
            }
        }
        if($this->cancelled) $query->where("cancelled", $this->cancelled);

        if($this->count) return $query->count();
        $query =  $query->orderBy("created_at", "DESC");
        // dd($query->toSql());
        return ($perPage) ? $query->offset($offset)->limit($perPage)->get() : $query->get();
        // return $query->orderBy("available_date", "DESC")->get();
    }



    // public function unbookedSchedules($with=[])
    // {
    //     SiteTourSchedule::whereDoesntHave('clients', function ($query) {
    //         $query->where('client_id', $clientId);
    //     });
    // }

    public function schedule($id, $with=[])
    {
        return SiteTourSchedule::with($with)->where("id", $id)->first();
    }

    public function deductSlots($siteTourSchedule, $slots = 1)
    {
        $remainingSlots = $siteTourSchedule->slots - $slots;
        $siteTourSchedule->slots = ($remainingSlots >= 0) ? $remainingSlots : 0;

        $siteTourSchedule->update();

        return $siteTourSchedule;
    }

    /*
        Site Tour Booked Schedules
    */
    public function saveBookedSchedule($scheduleId, $date, $bookedSchedule=null)
    {
        // $bookedSchedule = SiteTourBookedSchedule::where("site_tour_schedule_id", $scheduleId)->where("booked_date", $date)->first();
        if(!$bookedSchedule) {
            $bookedSchedule = new SiteTourBookedSchedule;
            $bookedSchedule->site_tour_schedule_id = $scheduleId;
            $bookedSchedule->booked_date = $date;
            $bookedSchedule->total = 1;
        }else{
            $bookedSchedule->total = $bookedSchedule->total + 1;
        }

        $bookedSchedule->save();

        return $bookedSchedule;
    }

    public function getBookedSchedule($scheduleId, $date)
    {
        return SiteTourBookedSchedule::where("site_tour_schedule_id", $scheduleId)->where("booked_date", $date)->first();
    }

    public function bookedSchedules($with=[])
    {
        return SiteTourBookedSchedule::with($with)->whereHas("schedule")->orderBy("booked_date", "ASC")->get();
    }


    /*
        Site Tour Bookings
    */

    public function book($bookedSchedule, $data)
    {
        $booking = new SiteTourBooking;

        if(isset($data['clientId'])) $booking->client_id = $data['clientId'];
        $booking->firstname = $data['firstname'];
        $booking->lastname = $data['lastname'];
        $booking->email = $data['email'];
        if(isset($data['phoneNumber'])) $booking->phone_number = $data['phoneNumber'];
        $booking->booked_schedules_id = $bookedSchedule->id;
        $booking->save();

        return $booking;
    }

    public function cancelBooking($booking)
    {
        $booking->delete();
    }

    public function clientBookings($clientId)
    {
        return SiteTourBooking::with("schedule")->where("client_id", $clientId)->get();
    }
}