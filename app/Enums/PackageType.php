<?php

namespace app\Enums;

enum PackageType: string
    {
        case INVESTMENT = 'investment';
        case NON_INVESTMENT = 'non-investment';
        case BOND = 'bond';
    }