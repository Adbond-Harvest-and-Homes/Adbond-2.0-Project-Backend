<?php

namespace app\Enums;

enum BondTimeMetric: string
    {
        case DAYS = 'days';
        case WEEKS = 'weeks';
        case MONTHS = 'months';
        case YEARS = 'years';
    }