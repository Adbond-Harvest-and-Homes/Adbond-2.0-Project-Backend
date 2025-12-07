<?php

namespace app\Http\Controllers\Auth;

use app\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;

use app\Mail\EmailVerification;
use app\Mail\SendPasswordResetCode as sendPasswordResetCodeMail;

use app\Http\Resources\UserBriefResource;

use app\Services\UserService;
use app\Services\StaffEmailService;
use app\Services\PasswordService;
use app\Services\UserProfileService;

use app\Http\Requests\Login;
use app\Http\Requests\SendPasswordResetCode;
use app\Http\Requests\ResetPassword;
use app\Http\Requests\User\VerifyPasswordResetToken;

use app\Enums\PasswordTypes;

use app\Utilities;

class UserAuthController extends Controller
{
    private $passwordService;
    private $userProfileService;
    private $userService;

    public function __construct()
    {
        $this->passwordService = new PasswordService;
        $this->userProfileService = new UserProfileService;
        $this->userService = new UserService;
    }
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Login $request){
        $credentials = $request->only('email', 'password');
        if (! $token = Auth::attempt($credentials)) {
            return response()->json([
                'statusCode' => 402,
                'message' => 'Wrong Email or Password'
            ], 402);
        }
        $user = new UserBriefResource(Auth::user());
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

    public function sendPasswordResetCode(SendPasswordResetCode $request)
    {
        try{
            $email = $request->validated('email');
            $user = $this->userService->getByEmail($email);
            if(!$user) return Utilities::error402("We cant find this email in our Database");

            DB::beginTransaction();
            $token = $this->passwordService->savePasswordResetToken($email, PasswordTypes::USER->value);
            Mail::to($email)->send(new SendPasswordResetCodeMail($token));
            DB::commit();
            return Utilities::okay(['message'=>'Reset Token Sent']);
        }catch(\Exception $e){
            DB::rollBack();
            return Utilities::error($e, 'An error occured while trying to send verification mail, Please try again later or contact support');
        }
    }

    public function verifyPasswordResetToken(VerifyPasswordResetToken $request)
    {
        try{
            $data = $request->validated();

            $user = $this->userService->getByEmail($data['email']);
            if(!$user) return Utilities::error402("User not found");
            
            $data['type'] = PasswordTypes::USER->value;
            $res = $this->passwordService->validateEmailToken($data);
            if($res['success']) return Utilities::okay('password verified successfully');
            return Utilities::error402($res['error']);
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occured while trying to send verification mail, Please try again later or contact support');
        }
    }

    public function resetPassword(ResetPassword $request)
    {
        try{
            $data = $request->validated();
            $resetToken = $this->passwordService->emailExists($data['email'], PasswordTypes::USER->value);
            if(!$resetToken) return Utilities::error402("You have not been cleared to reset this password, go through the password reset process");
            if(!$resetToken->verified) return Utilities::error402("Your password reset was not successful, click on the verify link sent to your mail");

            $user = $this->userService->getByEmail($data['email']);
            if(!$user) return Utilities::error402("no user exists for this email");

            $this->userProfileService->changePassword($data['password'], $user);
            return Utilities::okay('password Reset Successful');
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to send verification mail, Please try again later or contact support');
        }
    }
}
