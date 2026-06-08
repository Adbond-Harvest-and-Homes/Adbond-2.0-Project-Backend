<?php

namespace app\Services;

use app\Notifications\APIPasswordResetNotification;
use app\Exceptions\UserNotFoundException;

use app\Models\User;
use app\Models\Role;
// use app\Models\Staff_type;
use app\Models\Client;
use app\Models\UserClientSalesSummaryView;

// use app\Services\StaffTypeService;

use Illuminate\Support\Facades\DB;

use app\Helpers;
use app\Utilities;

use app\Enums\UserType;

/**
 * user service class
 */
class UserService
{
    private $staffTypeService;

    public function __construct()
    {
        // $this->staffTypeService = new StaffTypeService;
    }

    public function getUser($id)
    {
        return User::find($id);
    }

    public function getByEmail($email, $with=[])
    {
        return User::with($with)->where("email", $email)->first();
    }

    public function getUsers($user)
    {
        if($user->role_id != Role::SuperAdmin()->id) return User::where("role_id", "!=", Role::SuperAdmin()->id)->get();
        return User::all();
    }

    public function get_admins()
    {
        return user::whereNotNull('role_id')->get();
    }

    public function get_staffs()
    {
        return user::whereNull('role_id')->get();
    }

    public function usersCount()
    {
        return User::count();
    }

    public function adminsCount()
    {
        return User::whereNotNull('role_id')->count();
    }

    public function staffsCount()
    {
        return User::whereNull('role_id')->count();
    }

    public function hybridStaffsCount()
    {
        return User::where('staff_type_id', Staff_type::HybridStaff()->id)->count();
    }

    public function getPaginatedUsers($page=1, $perPage=null)
    {
        if($perPage==null) $perPage = env('POSTS_PER_PAGE', 20);
        if($page <= 0) $page = 1;
        $offset = $perPage * ($page-1);
        return User::limit($perPage)->offset($offset)->orderBy('created_at', 'desc')->get();
    }

    public function getPaginatedAdmins($page=1, $perPage=null)
    {
        if($perPage==null) $perPage = env('POSTS_PER_PAGE', 20);
        if($page <= 0) $page = 1;
        $offset = $perPage * ($page-1);
        return User::whereNotNull('role_id')->limit($perPage)->offset($offset)->orderBy('created_at', 'desc')->get();
    }

    public function getPaginatedStaffs($page=1, $perPage=null)
    {
        if($perPage==null) $perPage = env('POSTS_PER_PAGE', 20);
        if($page <= 0) $page = 1;
        $offset = $perPage * ($page-1);
        return User::whereNull('role_id')->limit($perPage)->offset($offset)->orderBy('created_at', 'desc')->get();
    }

    public function userCommissions()
    {
        return User::where('role_id', '!=', Role::SuperAdmin()->id)->orderBy('commission', 'desc')->get();
    }

    public function latestPhysicalStaff()
    {
        return User::where('staff_type_id', Staff_type::PhysicalStaff()->id)->orderBy('created_at', 'desc')->first();
    }

    public function getUsersByRoleId($role_id)
    {
        return User::where('role_id', $role_id)->get();
    }

    public function getUserByEmail($email)
    {
        return User::where('email', $email)->first();
    }

    public function myVirtualStaffs($user_id)
    {
        return User::where('registered_by', $user_id)->where('staff_type_id', Staff_type::VirtualStaff()->id)->get();
    }

    public function getUserByRefererCode($code)
    {
        return User::where('referer_code', $code)->first();
    }

    public function getUserByStaffRefererCode($code)
    {
        return User::where('staff_referer_code', $code)->first();
    }

    public function getMyTeam($userId, $returnIds=false)
    {
        $team = User::with(['clients'])->where('registered_by', $userId);

        if($returnIds) return $team->pluck('id')->toArray();

        return $team->get();
    }

    public function getMyTeamSales($userId)
    {
        $teamIds = $this->getMyTeam($userId, true);
        $totalSales = UserClientSalesSummaryView::whereIn('user_id', $teamIds)->sum('total_sales');
        $salesCount = UserClientSalesSummaryView::whereIn('user_id', $teamIds)->sum('sales_count');
        $totalClients = UserClientSalesSummaryView::whereIn('user_id', $teamIds)->sum('total_clients');

        return [
            'total_sales' => $totalSales,
            'sales_count' => $salesCount,
            'total_clients' => $totalClients
        ];
    }

    public function getMyTeamSalesCount($userId)
    {
        $teamIds = $this->getMyTeam($userId, true);
        return UserClientSalesSummaryView::whereIn('user_id', $teamIds)->sum('sales_count');
    }

    // public function usersByMonth($year, $start, $end=null)
    // {
    //     $staffTypes = $this->staffTypeService->getStaff_types();
    //     $users = [];
    //     $months = [];
    //     $stop = ($end == null) ? $start : $end;
    //     for($i=$start; $i<=$stop; $i++) $months[] = $i;
    //     $query = User::select(DB::raw("MONTH(users.created_at) as month"), DB::raw("count(users.staff_type_id) as total"), 'staff_types.name')
    //                             ->join('staff_types', 'staff_types.id', '=', 'users.staff_type_id')
    //                             ->whereYear('users.created_at', $year);
    //     $query = ($end != null) ? $query->whereMonth('users.created_at', '>=', $start)->whereMonth('users.created_at', '<=', $end) : $query->whereMonth('users.created_at', '=', $start);
    //     $userData = $query->groupBy('users.staff_type_id', DB::raw("MONTH(users.created_at)"))->get();
    //     // dd($userData);
    //     foreach($staffTypes as $staffType) {
    //         $staffTypeData = [];
    //         $staffTypeData['name'] = $staffType->name;
    //         foreach($months as $month) {
    //             if($userData->count() > 0) {
    //                 $total = 0;
    //                 foreach($userData as $data) {
    //                     if($data->month==$month && $data->name == $staffType->name) $total = $data->total;
    //                 }
    //                 $staffTypeData['data'][] = $total;
    //             }else{
    //                 $staffTypeData['data'][] = 0;
    //             }
    //         }
    //         $users[] = $staffTypeData;
    //     }
    //     // dd($users);
    //     return $users;
    // }

    

    public function totalUsersCommissions()
    {
        return User::select(DB::raw("SUM(commission) as total"))->get();
    }

    public function save($data, $user_id=null)
    {
        $user = new User;
        if(isset($data['title'])) $user->title = $data['title'];
        $user->firstname = $data['firstname'];
        $user->lastname = $data['lastname'];
        $user->email = $data['email'];
        $user->password =  $data['password'];
        if(isset($data['roleId'])) $user->role_id = $data['roleId'];
        $user->staff_type_id = $data['staffTypeId'];
        if(isset($data['phoneNumber'])) $user->phone_number = $data['phoneNumber'];
        if(isset($data['email_confirmed'])) $user->email_confirmed = $data['email_confirmed'];
        if(isset($data['address'])) $user->address = $data['address'];
        if(isset($data['countryId'])) $user->country_id = $data['country_id'];
        if(isset($data['postalCode'])) $user->postal_code = $data['postal_code'];
        if(isset($data['maritalStatus'])) $user->marital_status = $data['marital_status'];
        if($user_id != null) $user->registered_by = $user_id;
        // $user->referer_code = Utilities::generateRefererCode(UserType::USER->value);
        if(isset($data['photoId'])) $user->photo_id = $data['photoId']; 
        if(isset($data['gender'])) $user->gender = $data['gender']; 
        if(isset($data['departmentId'])) $user->department_id = $data['departmentId']; 
        if(isset($data['positionId'])) $user->position_id = $data['positionId']; 
        $user->date_joined = (isset($data['dateJoined'])) ? $data['dateJoined'] : date('Y-m-d'); 
        $user->save();
        return $user;
    }

    public function update($data, $user)
    {
        if(isset($data['title'])) $user->title = $data['title'];
        if(isset($data['firstname'])) $user->firstname = $data['firstname'];
        if(isset($data['lastname'])) $user->lastname = $data['lastname'];
        if(isset($data['email'])) $user->email = $data['email'];
        if(isset($data['roleId'])) $user->role_id = $data['roleId'];
        if(isset($data['staffTypeId'])) $user->staff_type_id = $data['staffTypeId'];
        if(isset($data['phoneNumber'])) $user->phone_number = $data['phoneNumber'];
        if(isset($data['address'])) $user->address = $data['address'];
        if(isset($data['countryId'])) $user->country_id = $data['countryId'];
        if(isset($data['postalCode'])) $user->postal_code = $data['postalCode'];
        if(isset($data['maritalStatus'])) $user->marital_status = $data['maritalStatus'];
        if(isset($data['fileId'])) $user->photo_id = $data['fileId']; 
        if(isset($data['gender'])) $user->gender = $data['gender']; 
        if(isset($data['departmentId'])) $user->department_id = $data['departmentId']; 
        if(isset($data['positionId'])) $user->position_id = $data['positionId']; 
        if(isset($data['hybridStaffDrawId'])) $user->hybrid_staff_draw_id = $data['hybridStaffDrawId']; 
        if(isset($data['accountNumber'])) $user->account_number = $data['accountNumber'];
        if(isset($data['accountName'])) $user->account_name = $data['accountName'];
        if(isset($data['bankId'])) $user->bank_id = $data['bankId'];
        $user->update();
        return $user;
    }

    public function reset($user)
    {
        $user->password = '12345';
        $user->update();

        return $user;
    }

    /*
    *   Upgrade candidates that passed the e-staff assessments to become an e-staff
    */
    public function upgradeToVirtualStaff($virtualStaffAssessment)
    {
        $user = new User;
        $user->staff_type_id = Staff_type::VirtualStaff()->id;
        // $user->role_id = Role::ClientRelation()->id;
        $user->password = bcrypt('123456');
        $user->firstname = $virtualStaffAssessment->firstname;
        $user->lastname = $virtualStaffAssessment->lastname;
        $user->email = $virtualStaffAssessment->email;
        $user->phone_number = $virtualStaffAssessment->phone_number;
        $user->address = $virtualStaffAssessment->address;
        $user->gender = $virtualStaffAssessment->gender;
        // $user->occupation = $virtualStaffAssessment->occupation;
        $user->referer_code = Utilities::generateRandomString(5);
        $user->date_joined = date('Y-m-d'); 
        if($virtualStaffAssessment->referal_code && $virtualStaffAssessment->referal_code != null) {
            $referer = $this->getUserByRefererCode($virtualStaffAssessment->referal_code);
            $user->registered_by = $referer->id;
        }else{
            // Select a parent for e-staff;
            $user->registered_by = Helpers::selectVirtualStaffParent();
        }

        $user->save();
        return $user;
    }

    public function upgradeToHybridStaff($user)
    {
        $user->staff_type_id = Staff_type::HybridStaff()->id;
        $user->update();
        return $user;
    }

    /*
    *   Select virtual staffs to be drawn to assign e-staff
    */
    public function selectStaffsForDraw($draw=null)
    {
        $usersIdArr = [];
        if($draw==null) {
            // If it is a fresh draw, get all virtual staffs
            // if($this->hybridStaffsCount() > 0) {
            $usersId = User::select('id')->where('staff_type_id', Staff_type::HybridStaff()->id)->get();
            if($usersId->count() > 0) {
                foreach($usersId as $userId) $usersIdArr[] = $userId->id;
            }
        }else{
            // If there is an existing draw, select only those that joined before the draw started and those that has not been selected before
            $usersId = User::select('id')->where('staff_type_id', Staff_type::HybridStaff()->id)->where('date_joined', '<', $draw->created_at)->where('hybrid_staff_draw_id', '!=', $draw->id)->get();
            if($usersId->count() > 0) {
                foreach($usersId as $userId) $usersIdArr[] = $userId->id;
            }
        }
        return $usersIdArr;
    }

    public function changePassword($user, $password)
    {
        $user->password =  bcrypt($password);
        $user->update();
    }

    public function delete($user)
    {
        $user->delete();
    }

}
