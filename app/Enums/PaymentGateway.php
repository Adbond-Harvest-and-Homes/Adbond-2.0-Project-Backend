<?php

namespace app\Enums;

enum PaymentGateway: string
    {
        case PAYSTACK = 'paystack';
        case OPAY = 'opay';
    }