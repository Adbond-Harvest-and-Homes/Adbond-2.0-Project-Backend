<?php

namespace app\Enums;

enum ClientBondStatus: string
    {
        case PENDING = 'pending';
        case ACTIVE = 'active';
        case LIQUIDATION_REQUEST = 'liquidation request';
        case RENEWAL_REQUEST = 'renewal request';
        case RENEWAL = 'renewal';
        case COMPLETED = 'completed';
        case LIQUIDATED = 'liquidated';
    }