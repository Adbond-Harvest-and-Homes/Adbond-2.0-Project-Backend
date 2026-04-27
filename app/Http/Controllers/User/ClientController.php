<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use app\Http\Controllers\Controller;

use app\Http\Resources\ClientResource;
use app\Http\Resources\ClientSummaryResource;

use app\Http\Requests\User\UpdateClient;

use app\Services\ClientService;
use app\Services\FileService;
use app\Services\UserActivityLogService;

use app\Models\Client;
use app\Models\User;

use app\Enums\ActiveToggle;
use app\Enums\FilePurpose;

use app\Utilities;

class ClientController extends Controller
{
    private $clientService;
    private $fileService;
    private $activityLogService;

    public function __construct()
    {
        $this->clientService = new ClientService;
        $this->fileService = new FileService;
        $this->activityLogService = new UserActivityLogService;
    }

    public function index(Request $request)
    {

        $summaryData = $this->clientService->summary();
        $summaryData = new ClientSummaryResource($summaryData);

        $page = ($request->query('page')) ?? 1;
        $perPage = ($request->query('perPage'));
        if(!is_int((int) $page) || $page <= 0) $page = 1;
        if(!is_int((int) $perPage) || $perPage==null) $perPage = env('PAGINATION_PER_PAGE');
        $offset = $perPage * ($page-1);

        $filter = [];
        if($request->query('text')) $filter["text"] = $request->query('text');
        if($request->query('date')) $filter["date"] = $request->query('date');
        if($request->query('status')) {
            $validStatus = ["active" => ActiveToggle::ACTIVE->value, "inactive" => ActiveToggle::INACTIVE->value];
            if(!in_array($request->query('status'), $validStatus)) return Utilities::error402("Valid Status are: ".$validStatus['active']." and ".$validStatus['inactive']);
            $filter["status"] = $request->query('status');
        }


        $clients = $this->clientService->filter($filter, [], $offset, $perPage);

        $this->clientService->count = true;
        $clientsCount = $this->clientService->filter($filter);

        $meta = [
            "page" => $page,
            "perPage" => $perPage,
            "pages" => ceil($clientsCount/$perPage),
            "total" => $clientsCount
        ];

        return Utilities::ok([
            "summary" => $summaryData,
            "clients" => ClientResource::collection($clients),
            "meta" => $meta
        ]);
    }

    public function show($clientId)
    {
        if (!is_numeric($clientId) || !ctype_digit($clientId)) return Utilities::error402("Invalid parameter clientID");

        $client = $this->clientService->getClient($clientId, ['assets', 'nextOfKins']);

        if(!$client) return Utilities::error402("Client not found");

        return Utilities::ok(new ClientResource($client));
    }

    public function update(UpdateClient $request, $clientId)
    {
        try{
            DB::beginTransaction();
            if (!is_numeric($clientId) || !ctype_digit($clientId)) return Utilities::error402("Invalid parameter clientID");

            $client = $this->clientService->getClient($clientId);

            if(!$client) return Utilities::error402("Client not found");

            $data = $request->validated();

            $oldPhotoId = ($client->photo_id) ? $client->photo_id : null;
            if($request->hasFile('photo')) {
                $photoRes = $this->savePhoto($request->file('photo'), $client);
                if($photoRes['success']) {
                    $data['photoId'] = $photoRes['fileId'];
                }else{
                    Utilities::logStuff("Client Photo Upload/Saving Error: ".$photoRes['message']);
                }
            }

            $client = $this->clientService->update($data, $client);
            if($oldPhotoId) $this->fileService->deleteFile($oldPhotoId);

            try{
                $this->activityLogService->log(Auth::user(), "Updated Client Information");
            }catch(\Exception $e){
                Utilities::logStuff('An error occurred while trying to Log Staff activity updating a client');
            }

            DB::commit();
            return Utilities::ok(new ClientResource($client));
        }catch(\Exception $e){
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function delete($clientId)
    {
        if (!is_numeric($clientId) || !ctype_digit($clientId)) return Utilities::error402("Invalid parameter clientID");

        $client = $this->clientService->getClient($clientId);

        if(!$client) return Utilities::error402("Client not found");

        if($client->assets->count() > 0) return Utilities::error402("This client already has assets, so he/she cannot be deleted");

        $this->clientService->delete($client);

        return Utilities::okay("Client Deleted Successfully");
    }


    private function savePhoto($file, $client)
    {
        $response = ["success" => false];
        $purpose = FilePurpose::CLIENT_PROFILE_PHOTO->value;
        $this->fileService->belongsId = $client->id;
        $this->fileService->belongsType = Client::$userType;
        $res = $this->fileService->save($file, 'image', Auth::user()->id, $purpose, Client::$userType, 'client-profile-photos');
        if($res['status'] != 200) {
            $response['message'] = 'Sorry Photo could not be uploaded '.$res['message'];
        }else{
            $response['success'] = true;
            $response['fileId'] = $res['file']->id;
        }
        return $response;
    }

    public function export(Request $request)
    {
        try {
            $clients = $this->clientService->getClients();

            // $clients = [$clients];
            // $clients = collect($clients);

            return $this->clientService->exportToPDF($clients);

        } catch (\Exception $e) {
            return Utilities::error($e, 'An error occurred during export');
        }
    }
}
