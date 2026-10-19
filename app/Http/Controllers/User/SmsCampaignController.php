<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use app\Http\Controllers\Controller;

use app\Http\Resources\SmsCampaignResource;

use app\Services\SmsCampaignService;

use app\Jobs\SendSmsCampaign;

use app\Models\SmsCampaign;

use app\Enums\Roles;
use app\Enums\SmsCampaignStatus;

use app\Utilities;

class SmsCampaignController extends Controller
{
    private $smsCampaignService;

    public function __construct()
    {
        $this->smsCampaignService = new SmsCampaignService;
    }

    public function index(Request $request)
    {
        if (!$this->userIsAuthorized()) return Utilities::error401("You are not authorized to view SMS campaigns");

        if ($request->query('status')) $this->smsCampaignService->filters['status'] = $request->query('status');
        if ($request->query('text')) $this->smsCampaignService->filters['text'] = $request->query('text');

        $page = ($request->query('page')) ?? 1;
        $perPage = ($request->query('perPage'));
        if (!is_int((int) $page) || $page <= 0) $page = 1;
        if (!is_int((int) $perPage) || $perPage == null) $perPage = env('PAGINATION_PER_PAGE', 50);
        $offset = $perPage * ($page - 1);

        $campaigns = $this->smsCampaignService->campaigns(['creator'], $offset, $perPage);

        $this->smsCampaignService->count = true;
        $total = $this->smsCampaignService->campaigns();

        return Utilities::paginatedOkay([
            "campaigns" => SmsCampaignResource::collection($campaigns),
        ], $page, $perPage, $total);
    }

    public function show($id)
    {
        if (!$this->userIsAuthorized()) return Utilities::error401("You are not authorized to view SMS campaigns");

        $campaign = SmsCampaign::with(['creator', 'recipients.client'])->find($id);
        if (!$campaign) return Utilities::error402("SMS campaign not found");

        return Utilities::ok(new SmsCampaignResource($campaign));
    }

    public function store(Request $request)
    {
        if (!$this->userIsAuthorized()) return Utilities::error401("You are not authorized to create SMS campaigns");

        $message = $request->input('message');
        $clientIds = $request->input('client_ids');
        $senderId = $request->input('sender_id');

        if (!$message || trim($message) == '') return Utilities::error402("message is required");
        if (!is_array($clientIds) || count($clientIds) == 0) return Utilities::error402("client_ids must be a non-empty array");

        $cap = env('SMS_CAMPAIGN_RECIPIENTS_CAP', 5000);
        if (count($clientIds) > $cap) return Utilities::error402("You can only send to up to {$cap} recipients at a time");

        $campaign = $this->smsCampaignService->createCampaign($message, $clientIds, Auth::id(), $senderId);

        if ($campaign->total_recipients == 0) return Utilities::error402("None of the selected clients have a usable phone number on file");

        return Utilities::okay("SMS campaign created as a draft", [
            "campaign" => new SmsCampaignResource($campaign),
            "segments" => $this->smsCampaignService->estimateSegments($message),
        ]);
    }

    public function send($id)
    {
        if (!$this->userIsAuthorized()) return Utilities::error401("You are not authorized to send SMS campaigns");

        $campaign = SmsCampaign::find($id);
        if (!$campaign) return Utilities::error402("SMS campaign not found");
        if ($campaign->status != SmsCampaignStatus::DRAFT->value) return Utilities::error402("This campaign has already been queued or sent");

        $campaign->update(['status' => SmsCampaignStatus::QUEUED->value]);

        SendSmsCampaign::dispatch($campaign);

        return Utilities::okay("Campaign is being sent to {$campaign->total_recipients} recipient(s)");
    }

    private function userIsAuthorized()
    {
        $user = Auth::user();
        return $user && $user->role && in_array($user->role->name, [
            Roles::SUPER_ADMIN->value,
            Roles::ADMIN->value,
            Roles::CUSTOMER_RELATION->value,
        ]);
    }
}
