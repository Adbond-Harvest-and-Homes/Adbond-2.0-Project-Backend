<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use app\Enums\DiscountType;

class DiscountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "type" => $this->getType(),
            "discount" => $this->discount,
            "measurement" => $this->measurement
        ];
    }

    private function getType()
    {
        switch ($this->type) {
            case DiscountType::BOND->value:
                return "Bond Full Payment";
                break;
            case DiscountType::BOND_INSTALLMENT->value:
                return "Bond Installmental Payment";
                break;
            case DiscountType::FULL_PAYMENT->value:
                return "Asset Full Payment";
                break;
            case DiscountType::LOYALTY->value:
                return "Loyalty Discount";
                break;
        }

        return "";
    }
}
