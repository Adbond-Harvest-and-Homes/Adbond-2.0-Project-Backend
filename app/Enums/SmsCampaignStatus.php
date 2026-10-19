<?php

namespace app\Enums;

enum SmsCampaignStatus: string
{
    case DRAFT = "draft";
    case QUEUED = "queued";
    case SENT = "sent";
    case PARTIALLY_SENT = "partially sent";
    case FAILED = "failed";
}
