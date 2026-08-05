<?php

namespace app\Enums;

enum DiscountType: string
{
    case FULL_PAYMENT = 'full-payment';
    case LOYALTY = 'loyalty';
    case BOND = 'bond full-payment';
    case BOND_INSTALLMENT = 'bond installment';
}
