<?php

namespace app\Enums;

enum BondIncomeMeasurement: string
    {
        case FIXED = 'fixed';
        case PERCENTAGE = 'percentage';
    }