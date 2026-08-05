<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\Auth;
use app\Http\Controllers\Controller;

use app\Http\Requests\User\ReadNotification;

use app\Http\Resources\NotificationResource;

use app\Services\NotificationService;

use app\Utilities;

class NotificationController extends Controller
{
    private $userActivityLogService;

    protected $notificationService;

    public function __construct()
    {
        $this->userActivityLogService = new UserActivityLogService;
        $this->notificationService = new NotificationService;
    }

    public function unreadNotifications(Request $request)
    {
        $this->notificationService->read = 0;
        $notifications = $this->notificationService->notifications();

        return Utilities::ok(NotificationResource::collection($notifications));
    }

    public function read(ReadNotification $request)
    {
        try{
            $notification = $this->notificationService->notification($request->validated("id"));
            if(!$notification) return Utilities::error402("Notification not found");
            
            $this->notificationService->markAsRead($notification);

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Marked Notification as Read");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Successful");
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to send verification mail, Please try again later or contact support');
        }
    }
}
