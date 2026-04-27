<?php

namespace app\Enums;

enum Measurement: string
    {
        case FIXED = 'fixed';
        case PERCENTAGE = 'percentage';
    }