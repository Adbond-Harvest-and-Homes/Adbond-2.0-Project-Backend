<?php

namespace app\Services;

use app\Models\UserHistory;

class UserHistoryService
{
    public function addHistory($userId, $action)
    {
        return UserHistory::create([
            "user_id" => $userId,
            "action" => $action,
        ]);
    }
}
