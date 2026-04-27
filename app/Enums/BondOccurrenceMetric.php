<?php

namespace app\Enums;

enum BondOccurrenceMetric: string
    {
        case WEEKLY = 'weekly';
        case MONTHLY = 'monthly';
        case QUARTERLY = 'quarterly';
        case YEARLY = 'yearly';
    }