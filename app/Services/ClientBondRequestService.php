<?php 

namespace app\Services;

use app\Models\ClientBond;
use app\Models\ClientBondRequest;

use app\Enums\ClientBondStatus;
use app\Enums\ClientBondRequestType;

class ClientBondRequestService
{
    public $approved = null;
    public $clientId = null;
    public $type = null;

    public function getRequests($with=[])
    {
        $query = ClientBondRequest::with($with);
        $query = ($this->approved === null) ? ClientBondRequest::whereNull("approved") : ClientBondRequest::where("approved", $this->approved);
        return $query->when($this->type, fn($q1) => $q1->where("type", $this->type))
                        ->when($this->clientId, fn($q2) => $q2->whereHas("bond", fn($bondQuery) 
                                                    => $bondQuery->where("client_id", $this->clientId)))
                                                    ->orderBy("created_at", "DESC")->get();
    }

    public function getRequest(int $id)
    {
        return ClientBondRequest::find($id);
    }

    public function makeRequest(ClientBond $bond, string $requestType)
    {
        $request = new ClientBondRequest;
        $request->client_bond_id = $bond->id;
        $request->type = $requestType;
        $request->save();

        return $request;
    }

    public function approve(ClientBondRequest $request)
    {
        $request->approved = 1;

        $request->save();

        return $request;
    }

    public function reject(ClientBondRequest $request, string $reason)
    {
        $request->approved = 0;
        $request->rejected_reason = $reason;

        $request->save();

        return $request;
    }
}