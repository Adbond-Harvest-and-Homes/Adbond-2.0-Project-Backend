<?php

namespace app\Enums;

enum NotificationType: string
    {
        case ASSET_UPGRADE_REQ = 'asset upgrade request';
        case ASSET_DOWNGRADE_REQ = 'asset downgrade request';
        case ORDER_COMPLETION = 'order completion';
        case ORDER_PAYMENT_CONFIRMATION_REQ = 'order payment confirmation request';
        case NEW_OFFER_APPROVAL_REQ = 'new offer approval request';
        case OFFER_PAYMENT_CONF = 'offer payment confirmation';
        case WALLET_WITHDRAWAL_REQ = 'wallet withdrawal request';
        case BOND_LIQUIDATION_REQ = 'bond liquidation request';
        case BOND_RENEWAL_REQ = 'bond renewal request';
    }