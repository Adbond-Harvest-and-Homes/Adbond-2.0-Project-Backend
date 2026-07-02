<?php

namespace app\Enums;

enum StaffTypes: string
    {
        case PHYSICAL_STAFF = "physical-staff";
        case HYBRID_STAFF = "hybrid-staff";
        case VIRTUAL_STAFF = "virtual-staff";
    }