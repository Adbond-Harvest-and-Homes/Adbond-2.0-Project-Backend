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

class SavePackage extends BaseRequest
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
            "projectId" => "required|integer|exists:projects,id",
            "name" => ["required","string", new PackageNameUnique()],
            "type" => ["nullable", "string", Rule::in(EnumClass::packageTypes())],
            "stateId" => "required|integer|exists:states,id",
            "address" => "nullable|string",
            "size" => ["numeric", Rule::requiredIf(function () {
                                        return $this->type && $this->type === PackageType::NON_INVESTMENT->value;
                                    }),
                        ],
            "amount" => "required|numeric",
            "units" => "required|integer",
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
            "interestReturnDuration" => "integer|required_if:type,".PackageType::INVESTMENT->value,
            "interestReturnTimeline" => "integer|required_if:type,".PackageType::INVESTMENT->value,
            "interestReturnPercentage" => ["integer", Rule::requiredIf(function () {
                                                    return $this->type === PackageType::INVESTMENT->value && is_null($this->interestReturnAmount);
                                                }),
                                            ],
            "interestReturnAmount" => ["integer", Rule::requiredIf(function () {
                                                    return $this->type === PackageType::INVESTMENT->value && is_null($this->interestReturnPercentage);
                                                }),
                                            ],
            "redemptionOptions" => "array|required_if:type,".PackageType::INVESTMENT->value,
            "redemptionOptions.*" => ["string", Rule::in(EnumClass::investmentRedemptionOptions())],
            "redemptionPackageId" => ["integer", Rule::requiredIf(function() {
                                                    return ($this->redemptionOptions && is_array($this->redemptionOptions)) &&
                                                            (in_array(InvestmentRedemptionOption::PROFIT_ONLY->value, $this->redemptionOptions) 
                                                            || 
                                                            in_array(InvestmentRedemptionOption::PROPERTY->value, $this->redemptionOptions));
                                                }),
                                        new IsNonInvestmentPackage()
                                    ]
        ];
    }
}
