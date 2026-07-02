<?php

namespace app\Enums;

enum OrderDiscountType: string
    {
        case FULL_PAYMENT = 'full-payment';
        case INSTALLMENT_PAYMENT = 'installment-payment';
        case PROMO = 'promo';
    }