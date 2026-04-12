<?php

namespace app\Enums;

enum BondOwnershipType: string
    {
        case SINGLE = 'single ownership';
        case CO_OWNERSHIP = 'co-ownership';
    }