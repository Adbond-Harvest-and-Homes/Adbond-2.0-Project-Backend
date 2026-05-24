<?php

use Doctrine\DBAL\Driver\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use app\Http\Middleware\ClientAuth;
use app\Http\Middleware\UserAuth;
use app\Http\Middleware\HRAuth;
use app\Http\Middleware\SuperAdminAuth;



//User Controllers
use app\Http\Controllers\Auth\UserAuthController;

use app\Http\Controllers\User\AssessmentAttemptController;



// Client Controllers
use app\Http\Controllers\Auth\ClientAuthController;
use app\Http\Controllers\Auth\GoogleController;

use app\Http\Controllers\Client\PostController;

//Public Controllers
use app\Http\Controllers\ProjectController;
use app\Http\Controllers\PackageController;
use app\Http\Controllers\SiteTourController as PublicSiteTourController;
use app\Http\Controllers\UtilityController;
use app\Http\Controllers\VirtualTeamApplicationController;

use app\Http\Controllers\TestController;
use app\Utilities;

use app\Http\Controllers\MigrationController;
use app\Http\Controllers\Migration2Controller;
use app\Http\Controllers\MigrationClientController;
use app\Http\Controllers\MigrationOrderPaymentsController;


// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
Route::group(['prefix' => '/v2',], function () {
    Route::get('/password', function() {
        dd(bcrypt('Dapo007.'));
    });

    // Route::get('/migrate_client_orders', [MigrationClientController::class, "migrateOrders"]);
    // Route::get('/migrate_payments', [Migration2Controller::class, "migratePayments"]);
    // Route::get('/migrate/clients', [MigrationController::class, "clients"]);
    Route::get('/migrate/order_payments', [MigrationOrderPaymentsController::class, "synchronizeOrderPayments"]);
    Route::get('/migrate/synchronize_payment_receipts', [MigrationOrderPaymentsController::class, "synchronizePaymentReceipts"]);

    //Auth URLS
    Route::group(['prefix' => '/auth', 'namespace' => 'Auth',], function () {
        Route::group(['prefix' => '/client'], function () {
            Route::post('/send_verification_mail', [ClientAuthController::class, "sendVerificationMail"]);
            Route::post('/verify_email_token', [ClientAuthController::class, "verifyEmailToken"]);
            Route::post('/register', [ClientAuthController::class, "register"]);
            Route::post('/login', [ClientAuthController::class, "login"]);
            Route::get('/google/get_url', [GoogleController::class, "getAuthUrl"]);
            Route::post('/google/login', [GoogleController::class, "postLogin"]);

            Route::post('/send_password_reset_code', [ClientAuthController::class, "sendPasswordResetCode"]);
            Route::post('/verify_password_reset_code', [ClientAuthController::class, "verifyPasswordResetToken"]);
            Route::post('/reset_password', [ClientAuthController::class, "resetPassword"]);
        });
        Route::group(['prefix' => '/user'], function () {
            Route::post('/login', 'UserAuthController@login');
            Route::post('/send_password_reset_code', [UserAuthController::class, "sendPasswordResetCode"]);
            Route::post('/verify_password_reset_code', [UserAuthController::class, "verifyPasswordResetToken"]);
            Route::post('/reset_password', [UserAuthController::class, "resetPassword"]);
        });
    });

    Route::group(['prefix' => '/assessments'], function () {
        Route::post('/start', [AssessmentAttemptController::class, "start"]);
        Route::post('/update', [AssessmentAttemptController::class, "update"]);
        Route::post('/submit', [AssessmentAttemptController::class, "submit"]);
    });

    

    /*
        Public Routes Begins here
    */
    Route::group(['prefix' => '/projects'], function () {
        // Project Routes
        Route::get('', [ProjectController::class, 'getProjects']);
        Route::get('/{projectTypeId}', [ProjectController::class, 'getProjects']);
        Route::get('/types', [ProjectController::class, 'getTypes']);
        Route::get('/view/{projectId}', [ProjectController::class, 'getProject']);
    });

    Route::group(['prefix' => '/packages'], function () {
        // Package Routes
        Route::get('/project_type/{projectTypeId}', [PackageController::class, 'getProjectTypePackages']);
        Route::get('{projectId}', [PackageController::class, 'getPackages']);
        // Route::get('/types', [ProjectController::class, 'getTypes']);
        Route::get('/view/{packageId}', [PackageController::class, 'getPackage']);
    });

    Route::group(['prefix' => '/site_tours',], function () {
        Route::post('/book', [PublicSiteTourController::class, 'book']);
        Route::get('/filter_schedules', [PublicSiteTourController::class, 'filterSchedules']);
    });

    Route::group(['prefix' => '/posts'], function () {
        Route::get('', [PostController::class, "posts"]);
        Route::get('/{slug}', [PostController::class, "post"]);
    });

    Route::group(['prefix' => '/virtual_teams'], function () {
        Route::post('/apply', [VirtualTeamApplicationController::class, "apply"]);
    });

    //Utitlity Routes
    Route::group(['prefix' => '/'], function () {    
        Route::get('benefits', [UtilityController::class, 'benefits']);
        Route::get('banks', [UtilityController::class, 'banks']);
        Route::get('identifications', [UtilityController::class, 'identifications']);
        Route::get('countries', [UtilityController::class, 'countries']);
        Route::get('states/{countryId}', [UtilityController::class, 'states']);
    });


    /*
        Client Routes Begins Here
    */

    

    // Route::get('test/benefit', [TestController::class, 'benefit']);

    // Route::get('/contract-mail', [TestController::class, "sendContract"]);

    require __DIR__ . '/user.php';
    require __DIR__ . '/client.php';
});