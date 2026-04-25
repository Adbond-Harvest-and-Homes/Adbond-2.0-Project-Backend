<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use app\Http\Resources\ClientResource;
use app\Http\Resources\PackageResource;
use app\Http\Resources\OrderResource;
use app\Http\Resources\FileResource;

use app\Enums\Measurement;

class ClientBondResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "startDate" => $this->start_date,
            "endDate" => $this->end_date,
            "started" => ($this->started == 1),
            "ended" => ($this->ended == 1),
            "nextCapitalPayout" => $this->next_capital_payout,
            "startCapital" => $this->start_capital,
            "currentCapital" => $this->current_capital,
            "countDown" => $this->count_down($this->count_down_metric),
            "duration" => $this->duration($this->duration_metric),
            "netRentalIncome" => $this->net_rental_income.($this->net_rental_income_measurement == Measurement::PERCENTAGE->value) ? "%" : "",
            "timeline" => $this->net_rental_income_timeline,
            "assetAppreciation" => $this->asset_appreciation.($this->asset_appreciation_measurement == Measurement::PERCENTAGE->value) ? "%" : "",
            "assetAppreciationTimeline" => $this->asset_appreciation_timeline,
            "mou" => new FileResource($this->whenLoaded("mou")),
            "client" => new ClientResource($this->whenLoaded("client")),
            "package" => new PackageResource($this->whenLoaded("package")),
            "order" => new OrderResource($this->whenLoaded("order")),
            "total" => $this->when($this->relationLoaded('payouts'), fn() => $this->total, $this->total())
        ];
    }

    private function total()
    {
        return $this->current_capital + $this->payouts()->sum('payout_amount');
    }
}
