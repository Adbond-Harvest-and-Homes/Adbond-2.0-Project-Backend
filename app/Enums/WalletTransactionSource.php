<?php

namespace app\Enums;

use app\Models\ClientInvestment;

enum WalletTransactionSource: string
    {
        case INVESTMENT = "app\Models\ClientInvestment";
        case COMMISSION = "app\Models\ClientCommissionEarning";
        case BOND = "app\Models\ClientBond";
    }