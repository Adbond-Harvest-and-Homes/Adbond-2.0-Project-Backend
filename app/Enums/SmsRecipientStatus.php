<?php

namespace app\Enums;

enum SmsRecipientStatus: string
{
    case PENDING = "pending";
    case SENT = "sent";
    case FAILED = "failed";
}
