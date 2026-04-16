<?php

namespace app\Enums;

enum ClientPackageOrigin: string
    {
        case ORDER = 'order';
        case OFFER = 'offer';
        case INVESTMENT = 'investment';
        case BOND = 'bond';
    }