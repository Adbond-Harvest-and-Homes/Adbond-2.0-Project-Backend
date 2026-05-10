<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Http\Resources\ClientBondResource;

class ClientBondRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // $this->bond->load("package");
        return [
            "id" => $this->id,
            "type" => $this->type,
            "status" => ($this->approved == null) ? "pending" : (($this->approved == 1) ? "approved" : "rejected"),
            "bond" => new ClientBondResource($this->bond)
        ];
    }
}
