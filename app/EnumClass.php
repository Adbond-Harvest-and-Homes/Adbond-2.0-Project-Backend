<?php

namespace app;

use app\Enums\EmploymentStatus;
use app\Enums\MaritalStatus;
use app\Enums\KYCStatus;
use app\Enums\Genders;
use app\Enums\StaffTypes;
use app\Enums\FileTypes;
use app\Enums\FilePurpose;
use app\Enums\PaymentMode;
use app\Enums\CommissionTransactionType;
use app\Enums\StaffCommissionType;
use app\Enums\ProductCategory;
use app\Enums\PostType;
use app\Enums\PackageType;
use app\Enums\InvestmentRedemptionOption;
use app\Enums\OfferApprovalStatus;
use app\Enums\PurchaseSummaryDuration;
use app\Enums\AssetSwitchType;
use app\Enums\Weekday;
use app\Enums\PromoProductType;
use app\Enums\NotificationType;

//bonds
use app\Enums\Measurement;
use app\Enums\BondOccurrenceMetric;
use app\Enums\BondOwnershipType;
use app\Enums\BondTimeMetric;

class EnumClass
{
    public static function employmentStatuses()
    {
        return array_column(EmploymentStatus::cases(), 'value');  
    }

    public static function maritalStatus()
    {
        return array_column(MaritalStatus::cases(), 'value');  
    }

    public static function kycStatus()
    {
        return [
            KYCStatus::NOTSTARTED->value,
            KYCStatus::STARTED->value,
            KYCStatus::COMPLETED->value
        ];
    }

    public static function genders()
    {
        return [
            Genders::FEMALE->value,
            Genders::MALE->value
        ];
    }

    public static function fileTypes()
    {
        return array_column(FileTypes::cases(), 'value');
    }

    public static function filePurposes()
    {
        return array_column(FilePurpose::cases(), 'value');
    }

    public static function paymentModes()
    {
        return [
            PaymentMode::BANK_TRANSFER->value,
            PaymentMode::CARD_PAYMENT->value,
            PaymentMode::CASH->value
        ];
    }

    public static function commissionTransactionTypes()
    {
        return [
            CommissionTransactionType::EARNING->value,
            CommissionTransactionType::REDEMPTION->value
        ];
    }

    public static function staffCommissionTypes()
    {
        return [
            StaffCommissionType::DIRECT->value,
            StaffCommissionType::INDIRECT->value
        ];
    }

    public static function ClientPackageFiles()
    {
        return [
            FilePurpose::CONTRACT->value,
            FilePurpose::DEED_OF_ASSIGNMENT->value,
            FilePurpose::LETTER_OF_HAPPINESS->value
        ];
    }

    // public static function productCategories()
    // {
    //     return [
    //         ProductCategory::PURCHASE->value,
    //         ProductCategory::INVESTMENT->value
    //     ];
    // }

    public static function postTypes()
    {
        return array_column(PostType::cases(), 'value');
    }

    public static function packageTypes()
    {
        return array_column(PackageType::cases(), 'value');
    }

    public static function investmentRedemptionOptions()
    {
        return [
            InvestmentRedemptionOption::CASH->value,
            InvestmentRedemptionOption::PROFIT_ONLY->value,
            InvestmentRedemptionOption::PROPERTY->value
        ];
    } 

    public static function offerApprovalStatuses()
    {
        return [
            OfferApprovalStatus::PENDING->value,
            OfferApprovalStatus::APPROVED->value,
            OfferApprovalStatus::REJECTED->value
        ];
    }

    public static function purchaseSummaryDurations()
    {
        return [
            PurchaseSummaryDuration::ALL->value,
            PurchaseSummaryDuration::CUSTOM->value,
            PurchaseSummaryDuration::MONTH->value,
            PurchaseSummaryDuration::WEEK->value,
            PurchaseSummaryDuration::TODAY->value,
            PurchaseSummaryDuration::YEAR->value
        ];
    }

    public static function assetSwitchTypes()
    {
        return [
            AssetSwitchType::DOWNGRADE->value,
            AssetSwitchType::UPGRADE->value
        ];
    }

    public static function weekdays()
    {
        return [
            Weekday::MONDAY->value,
            Weekday::TUESDAY->value,
            Weekday::WEDNESDAY->value,
            Weekday::THURSDAY->value,
            Weekday::FRIDAY->value,
            Weekday::SATURDAY->value,
            Weekday::SUNDAY->value,
            Weekday::ALL->value
        ];
    }

    public static function promoProductTypes()
    {
        return [
            PromoProductType::PROJECT->value,
            PromoProductType::PACKAGE->value
        ];
    }

    public static function notificationTypes()
    {
        return array_column(NotificationType::cases(), 'value');
    }

    public static function Measurements()
    {
        return array_column(Measurement::cases(), 'value');
    }

    public static function bondOccurrenceMetrics()
    {
        return array_column(BondOccurrenceMetric::cases(), 'value');
    }

    public static function bondOwnershipTypes()
    {
        return array_column(BondOwnershipType::cases(), 'value');
    }

    public static function bondTimeMetrics()
    {
        return array_column(BondTimeMetric::cases(), 'value');
    }
}