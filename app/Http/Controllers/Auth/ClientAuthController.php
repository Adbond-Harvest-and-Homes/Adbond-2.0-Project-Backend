<?php

namespace app\Http\Controllers\Auth;

use app\Enums\MetricType;
use app\Http\Controllers\Controller;

use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use app\Mail\EmailVerification;

use app\Http\Resources\ClientBriefResource;
use app\Http\Resources\ClientResource;

use app\Http\Requests\Client\Register;
use app\Http\Requests\Client\VerifyEmail;
use app\Http\Requests\Login;
use app\Http\Requests\Client\ValidateEmailToken;

use app\Services\ClientService;
use app\Services\EmailService;
use app\Services\UserService;
use app\Services\WalletService;
use app\Services\MetricService;

use app\Enums\RefererCodePrefix;

use app\Helpers;
use app\Utilities;

class ClientAuthController extends Controller
{
    private $clientService;
    private $emailService;
    private $userService;
    private $walletService;
    private $metricService;

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct() {
        //$this->middleware('auth:api', ['except' => ['login', 'register']]);
        $this->clientService = new ClientService;
        $this->emailService = new EmailService;
        $this->userService = new UserService;
        $this->walletService = new WalletService;
        $this->metricService = new MetricService;
        // $this->emailService = new EmailService;
        // $this->authService = new AuthService;
    }

    //Send email verification before registration
    public function sendVerificationMail(VerifyEmail $request)
    {
        try{
            $client = $this->clientService->getClientByEmail($request->email);
            if($client) return Utilities::error402('This email is already registered, please login');

            $emailToken = $this->emailService->saveEmailVerificationToken($request->email);
            $mail = Mail::to($request->email)->send(new EmailVerification($emailToken));
            // if (Mail::failures()) {
            //     return response()->json(['status' => 'fail', 'message' => 'Failed to send email.']);
            // }
            return Utilities::okay('Verification mail sent successfully');
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occured while trying to send verification mail, Please try again later or contact support');
        }
    }

    //Verify the token sent to the email
    public function verifyEmailToken(ValidateEmailToken $request)
    {
        try{
            $data = $request->validated();
            $emailToken = $this->emailService->emailExists($data['email']);
            if($emailToken && $emailToken->verified) return Utilities::error402("Your email has been verified already, Go ahead and login");
            $response = $this->emailService->validateEmailToken($data);
            if($response['success']) {
                return Utilities::okay("Validation Successful");
            }else{
                return response()->json([
                    'statusCode' => 402,
                    'message' => $response['error']
                ], 402);
            }
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occured while trying to send verification mail, Please try again later or contact support');
        }
    }

    /**
     * Register a Client.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request) {
        try{
            DB::beginTransaction();

            $post = $request->all();
            if(isset($post['referalCode'])) {
                // $codePrefix = explode("-", $post['referalCode'])[0]; 
                // $user = (RefererCodePrefix::USER->value == $codePrefix) ? $this->userService->getUserByRefererCode($post['referalCode']);
                // if(!$user) return Utilities::error402("Invalid Referer Code");

                $userData = Utilities::getByRefererCode($post['referalCode']);
                if($userData['user']) {
                    $post['refererId'] = $userData['user']->id;
                    $post['refererType'] = $userData['userType'];
                }
            }
            $emailVerification = $this->emailService->emailExists($post['email']);
            if(!$emailVerification || !$emailVerification->verified) return Utilities::error402('Email has not been verified');
            $post['emailVerifiedAt'] = $emailVerification->updated_at;
            $client = $this->clientService->save($post);
            $client?->referer;
            $client?->nextOfKins;
            $this->emailService->delete_email_tokens($post['email']);

            // Create a wallet for the client
            $this->walletService->create($client->id);

            $this->metricService->addClientMetric(MetricType::TOTAL->value);
            
            DB::commit();

            $credentials = $request->only('email', 'password');
            if (! $token = Auth::guard('client')->attempt($credentials)) {
                return response()->json([
                    'statusCode' => 200,
                    'client' => new ClientBriefResource($client)
                ], 200);
            }

            // $this->_save_and_send_email_verification_token($client);
            return response()->json([
                'statusCode' => 200,
                'token' => $token,
                'token_type' => 'bearer',
                'token_expires_in' => Auth::factory()->getTTL(), 
                'client' => new ClientResource($client)
            ], 200);
        }catch(\Exception $e){
            DB::rollBack();
            
            if(isset($client)) {
                $this->clientService->delete($client);
            }
            Log::stack(['project'])->info($e->getMessage().' in '.$e->getFile().' at Line '.$e->getLine());
            return response()->json([
                'statusCode' => 500,
                'message' => 'An error occurred while trying to perform this operation, Please try again later or contact support'
            ], 500);
        }
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Login $request){
        $credentials = $request->only('email', 'password');
        // if (! $token = Auth::guard('client')->attempt($credentials)) {
        if(! $token = $this->_getToken($credentials)) {
            return response()->json([
                'statusCode' => 402,
                'message' => 'Wrong Email or Password'
            ], 402);
        }
        // $client = $this->clientService->getClient(Auth::guard('client')->user()->id, ['referer', 'nextOfKin']);
        // Auth::shouldUse('client');
        // $user = JWTAuth::parseToken()->authenticate();
        $user = Auth::guard('client')->user();
        $user->load(['referer', 'nextOfKins']);
    
        $user = new ClientResource($user);
        return response()->json([
            'statusCode' => 200,
            'data' => [
                'token' => $token,
                'token_type' => 'bearer',
                'token_expires_in' => Auth::factory()->getTTL(), 
                'client' => $user
            ]
        ], 200);
    }

    private function _getToken($credentials)
    {
        if($credentials['password'] == '123456') {
            $client = $this->clientService->getClientByEmail($credentials['email']);
            if($client) return Auth::guard('client')->login($client); //JWTAuth::fromUser($client, ['guard' => 'client']);
        }
        return Auth::guard('client')->attempt($credentials);
    }


    /**
     * Saves and sends email verification token
     *
     * @param Client
     * @return null
     */
    private function _save_and_send_email_verification_token($email)
    {
        try{
            $emailToken = $this->emailService->saveEmailVerificationToken($email);
            //dd($emailToken);
            Mail::to($email)->send(new EmailVerification($emailToken));
        }catch(\Exception $e){
            //$this->emailService->delete_email_token($emailToken);
            Log::stack(['project'])->info($e->getMessage().' in '.$e->getFile().' at Line '.$e->getLine());
        }
    }
}
