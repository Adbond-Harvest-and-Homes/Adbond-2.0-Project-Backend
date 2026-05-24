<?php

namespace app\Enums;

enum ClientBondRequestType: string
    {
        case LIQUIDATION = 'liquidation';
        case RENEWAL = 'renewal';
    }