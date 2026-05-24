<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use app\Http\Resources\ClientResource;
use app\Http\Resources\ClientBondResource;

class ClientBondPayoutResource extends JsonResource
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
            "amount" => $this->payout_amount,
            "interest" => $this->interest,
            "measurement" => $this->interest_measurement,
            "capital" => $this->capital,
            "bond" => new ClientBondResource($this->whenLoaded("bond")),
            "client" => new ClientResource($this->whenLoaded("client"))
        ];
    }
}
