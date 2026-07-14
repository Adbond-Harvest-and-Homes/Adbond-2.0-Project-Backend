<?php

namespace app\Enums;

enum Roles: string
{
    case SUPER_ADMIN = "super admin";
    case ADMIN = "admin";
    case HUMAN_RESOURCE = "human resource";
    case LOGISTICS = "logistics";
    case CUSTOMER_RELATION = "customer relation";
    case OPERATION_ACCOUNTING = "operation and accounting";
    case CONTENT_MANAGEMENT = "content management";
    case SALES = "sales";
}
