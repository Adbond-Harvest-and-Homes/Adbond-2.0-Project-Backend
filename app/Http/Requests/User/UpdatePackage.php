<?php

namespace app\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use app\Http\Requests\BaseRequest;

use app\Rules\ValidPackageBrochureFile;
use app\Rules\ValidPackageMediaFile;
use app\Rules\PackageNameUnique;

use app\EnumClass;
use app\Enums\PackageType;
use app\Enums\InvestmentRedemptionOption;
use app\Rules\IsNonInvestmentPackage;


class UpdatePackage extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "projectId" => "nullable|integer|exists:projects,id",
            "name" => ["nullable","string", new PackageNameUnique()],
            "stateId" => "nullable|integer|exists:states,id",
            "address" => "nullable|string",
            "size" => "nullable|numeric",
            "amount" => "nullable|numeric",
            "units" => "nullable|integer",
            "discount" => "nullable|numeric",
            "minPrice" => "nullable|numeric",
            "installmentDuration" => "nullable|integer",
            "infrastructureFee" => "nullable|numeric",
            "description" => "nullable|string",
            "benefits" => "nullable|array",
            "benefits.*" => "integer|exists:benefits,id",
            // "brochureFileId" => ["nullable", "integer", new ValidPackageBrochureFile()],
            "brochureFile" => "nullable|file|max:10000|mimes:jpeg,png,jpg,pdf,doc,docx",
            "installmentOption" => "nullable|boolean",
            "vrUrl" => "nullable|string",
            "packageMediaIds" => "nullable|array",
            "packageMediaIds.*" => ["integer", new ValidPackageMediaFile()],
            "interestReturnDuration" => "nullable|integer",
            "interestReturnTimeline" => "nullable|integer",
            "interestReturnPercentage" => ["nullable", "integer"],
            "interestReturnAmount" => ["nullable", "integer"],
            "redemptionOptions" => "nullable|array",
            "redemptionOptions.*" => ["required", "string", Rule::in(EnumClass::investmentRedemptionOptions())],
            "redemptionPackageId" => ["integer", Rule::requiredIf(function() {
                                                    return ($this->redemptionOptions && is_array($this->redemptionOptions)) &&
                                                            (in_array(InvestmentRedemptionOption::PROFIT_ONLY->value, $this->redemptionOptions) 
                                                            || 
                                                            in_array(InvestmentRedemptionOption::PROPERTY->value, $this->redemptionOptions));
                                                }),
                                        new IsNonInvestmentPackage()
                                            ],

            "bondSlots" => "nullable|integer",
            "bondOwnershipType" => ["string", "required_if:type,".PackageType::BOND->value, Rule::in(EnumClass::bondOwnershipTypes())],
            "bondCountDown" => "integer|required_if:type,".PackageType::BOND->value,
            "bondCountDownMetric" => ["string", "required_with:bondCountDown", Rule::in(EnumClass::bondTimeMetrics())],
            "bondInvestmentDuration" => "integer|required_if:type,".PackageType::BOND->value,
            "bondInvestmentDurationMetric" => ["string", "required_with:bondInvestmentDuration", Rule::in(EnumClass::bondTimeMetrics())],
            "bondNetRentalIncome" => "integer|required_if:type,".PackageType::BOND->value,
            "bondNetRentalIncomeMeasurement" => ["string", "required_with:bondNetRentalIncome", Rule::in(EnumClass::bondIncomeMeasurements())],
            "bondNetRentalIncomeTimeline" => ["string", "required_with:bondNetRentalIncome", Rule::in(EnumClass::bondOccurrenceMetrics())],
            "bondAssetAppreciation" => "numeric|required_if:type,".PackageType::BOND->value,
            "bondAssetAppreciationMeasurement" => ["string", "required_with:bondAssetAppreciation", Rule::in(EnumClass::bondIncomeMeasurements())],
            "bondAssetAppreciationTimeline" => ["string", "required_with:bondAssetAppreciation", Rule::in(EnumClass::bondOccurrenceMetrics())],
        
        ];
    }
}
