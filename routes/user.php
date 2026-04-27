<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use app\Http\Controllers\User\ProjectTypeController as UserProjectTypeController;
use app\Http\Controllers\User\ProjectController as UserProjectController;
use app\Http\Controllers\User\PackageController as UserPackageController;
use app\Http\Controllers\User\IndexController as UserIndexController;
use app\Http\Controllers\User\TransactionController as UserTransactionController;
use app\Http\Controllers\User\StaffController as StaffController;
use app\Http\Controllers\User\ClientController as UserClientController;
use app\Http\Controllers\User\FileController as UserFileCOntroller;
use app\Http\Controllers\User\Client\WalletController as UserClientWalletController;
use app\Http\Controllers\User\Client\TransactionController as UserClientTransactionController;
use app\Http\Controllers\User\PostController as UserPostController;
use app\Http\Controllers\User\CommentController as UserCommentController;
use app\Http\Controllers\User\PaymentController as UserPaymentController;
use app\Http\Controllers\User\AssetController as UserAssetController;
use app\Http\Controllers\User\SiteTourController as UserSiteTourController;
use app\Http\Controllers\User\OfferController as UserOfferController;
use app\Http\Controllers\User\OfferPaymentController as UserOfferPaymentController;
use app\Http\Controllers\User\UtilityController as UserUtilityController;
use app\Http\Controllers\User\AssetSwitchController as UserAssetSwitchController;
use app\Http\Controllers\User\AssessmentController as UserAssessmentController;
use app\Http\Controllers\User\PromoController as UserPromoController;
use app\Http\Controllers\User\PromoCodeController as UserPromoCodeController;
use app\Http\Controllers\User\AssessmentQuestionController;
use app\Http\Controllers\User\AssessmentQuestionOptionController;
use app\Http\Controllers\User\AssessmentAttemptController;
use app\Http\Controllers\User\AnalyticsController;
use app\Http\Controllers\User\ReferralController;
use app\Http\Controllers\User\UserBankAccountController;
use app\Http\Controllers\User\NotificationController as UserNotificationController;
use app\Http\Controllers\User\DiscountController;
use app\Http\Controllers\VirtualTeamApplicationController;
use app\Http\Controllers\UtilityController;

//User/Admin/Staff Routes
Route::group(['middleware' => 'userAuth', 'prefix' => '/user', 'namespace' => 'User',], function () {
    Route::get('/dashboard', [UserIndexController::class, "dashboard"]);
    Route::get('/dashboard/purchase_chart', [UserIndexController::class, "purchaseSummary"]);

    Route::group(['prefix' => '/transactions'], function () {
        Route::get('', [UserTransactionController::class, "transactions"]);
    });

    Route::group(['prefix' => '/profile'], function () {
        Route::get('', 'ProfileController@index');
        Route::post('/set_password', 'ProfileController@setPassword');
        Route::post('/change_password', 'ProfileController@changePassword');
        Route::post('/update', 'ProfileController@update');
    });
    Route::post('/upload_photo', [UserFileController::class, "savePhoto"]);

    Route::group(['prefix' => '/file'], function () {
        Route::post('/delete', [UserFileController::class, "deleteFile"]);
    });

    //Staff Routes
    Route::group(['middleware' => SuperAdminAuth::class, 'prefix' => '/staffs'], function () {
        Route::get('/reset/{userId}', [StaffController::class, "reset"]);
        Route::post('/remove/{userId}', [StaffController::class, "delete"]);
    });

    Route::group(['prefix' => '/staffs'], function () {
        Route::post('', [StaffController::class, "save"]);
        Route::get('', [StaffController::class, "users"]);
        Route::get('/{userId}', [StaffController::class, "user"]);
        Route::patch('/{userId}', [StaffController::class, "update"]);
    });

    //Project Types
    Route::group(['prefix' => '/project_types'], function () {
        Route::post('/update', [UserProjectTypeController::class, "update"]);
        Route::get('', [UserProjectTypeController::class, "projectTypes"]);
        Route::get('/{id}', [UserProjectTypeController::class, "projectType"]);
    });
    // Project
    Route::group(['prefix' => '/projects'], function () {
        Route::get('', [UserProjectController::class, "allProjects"]);
        Route::post('', [UserProjectController::class, "save"]);
        Route::patch('', [UserProjectController::class, "update"]);
        Route::post('/activate', [UserProjectController::class, "activate"]);
        Route::post('/deactivate', [UserProjectController::class, "deactivate"]);
        Route::post('/delete', [UserProjectController::class, "delete"]);
        Route::post('/add_promo', [UserProjectController::class, "addPromo"]);
        Route::post('/remove_promo', [UserProjectController::class, "removePromo"]);
        // Route::post('/filter/{projectTypeId}', [UserProjectController::class, "filter"]);
        Route::get('/types', [UserProjectController::class, "types"]);
        // Route::get('/summary/{projectTypeId}', [UserProjectController::class, "summary"]);
        Route::get('/all/{projectTypeId}', [UserProjectController::class, "projects"]);
        // Route::get('/search/{projectTypeId}', [UserProjectController::class, "search"]);
        Route::get('/export/{projectTypeId}', [UserProjectController::class, "export"]);
        Route::get('/{id}', [UserProjectController::class, "project"]);
    });
    // Package
    Route::group(['prefix' => '/packages'], function () {
        Route::get('', [UserPackageController::class, "allPackages"]);
        Route::post('', [UserPackageController::class, "save"]);
        Route::post('/save_media', [UserPackageController::class, "saveMedia"]);
        Route::post('/save_multiple_media', [UserPackageController::class, "saveMultipleMedia"]);
        Route::patch('/{id}', [UserPackageController::class, "update"]);
        Route::patch('/mark_as_sold_out', [UserPackageController::class, "markAsSoldOut"]);
        Route::patch('/mark_as_in_stock', [UserPackageController::class, "markAsInStock"]);
        Route::post('/activate', [UserPackageController::class, "activate"]);
        Route::post('/deactivate', [UserPackageController::class, "deactivate"]);
        Route::post('/delete', [UserPackageController::class, "delete"]);
        Route::post('/filter/{projectId}', [UserPackageController::class, "filter"]);
        Route::post('/add_promo', [UserPackageController::class, "addPromo"]);
        Route::post('/remove_promo', [UserPackageController::class, "removePromo"]);
        Route::get('/all/{projectId}', [UserPackageController::class, "packages"]);
        Route::get('/search', [UserPackageController::class, "search"]);
        Route::get('/search/{projectId}', [UserPackageController::class, "search"]);
        Route::get('/export/{projectId}', [UserPackageController::class, "export"]);
        Route::get('/{id}', [UserPackageController::class, "package"]);
    });

    Route::group(['prefix' => '/payments'], function () {
        Route::post('/confirm', [UserPaymentController::class, 'confirm']);
        Route::post('/reject', [UserPaymentController::class, 'reject']);
        Route::post('/flag', [UserPaymentController::class, 'flag']);
        Route::post('/generate_receipt', [UserPaymentController::class, 'generateReceipt']);
    });

    //Assets Routes
    Route::group(['prefix' => '/assets'], function () {
        Route::get('', [UserAssetController::class, "assets"]);
        Route::post('/upload_doa', [UserAssetController::class, "saveDoa"]);

        Route::get('/switch_requests', [UserAssetSwitchController::class, 'assetSwitchRequests']);
        Route::post('/approve_switch_request', [UserAssetSwitchController::class, 'approve']);
        Route::post('/reject_switch_request', [UserAssetSwitchController::class, 'reject']);
    });

    //Offers Routes
    Route::group(['prefix' => '/offers'], function () {
        Route::get('', [UserOfferController::class, "offers"]);
        Route::post('/approve', [UserOfferController::class, "approve"]);
        Route::post('/reject', [UserOfferController::class, "reject"]);

        Route::get('/payments', [UserOfferPaymentController::class, "payments"]);
        Route::post('/payments/confirm', [UserOfferPaymentController::class, "confirm"]);
        Route::post('/payments/reject', [UserOfferPaymentController::class, "reject"]);
        Route::post('/payments/flag', [UserOfferPaymentController::class, "flag"]);

        Route::get('/ready', [UserOfferController::class, "readyOffers"]);

        Route::post('/complete', [UserOfferController::class, "complete"]);
    });

    Route::group(['prefix' => '/site_tour'], function () {
        Route::group(['prefix' => '/schedules'], function () {
            Route::post('', [UserSiteTourController::class, 'createSchedule']);
            Route::patch('/{id}', [UserSiteTourController::class, 'updateSchedule']);
            Route::delete('/{id}', [UserSiteTourController::class, 'deleteSchedule']);
            Route::get('', [UserSiteTourController::class, 'schedules']);
            Route::get('/booked', [UserSiteTourController::class, 'bookedSchedules']);
            Route::get('/{id}', [UserSiteTourController::class, 'schedule']);
        });
    });

    Route::group(['prefix' => '/posts'], function () {
        Route::post('', [UserPostController::class, "save"]);
        Route::post('/react', [UserPostController::class, "react"]);
        Route::post('/toggle_activate', [UserPostController::class, "toggleActivate"]);
        Route::post('/{postId}', [UserPostController::class, "update"]);
        Route::get('', [UserPostController::class, "posts"]);
        Route::get('/{postId}', [UserPostController::class, "post"]);
        Route::delete('/{postId}', [UserPostController::class, "delete"]);
    });

    Route::group(['prefix' => '/comments'], function () {
        Route::post('', [UserCommentController::class, "save"]);
        Route::delete('/{commentId}', [UserCommentController::class, "delete"]);
        Route::post('/react', [UserCommentController::class, "react"]);
    });

    Route::group(['prefix' => '/promos'], function () {
        Route::group(['prefix' => '/promo_codes'], function () {
            Route::post('', [UserPromoCodeController::class, "createPromoCode"]);
            Route::post('/toggle_activate', [UserPromoCodeController::class, "toggleActivate"]);
            Route::post('/{promoCodeId}', [UserPromoCodeController::class, "update"]);
            Route::delete('/{promoCodeId}', [UserPromoCodeController::class, "delete"]);
            Route::get('', [UserPromoCodeController::class, "promoCodes"]);
        });

        Route::post('', [UserPromoController::class, "create"]);
        Route::post('/toggle_activate', [UserPromoController::class, "toggleActivate"]);
        Route::post('/add_products', [UserPromoController::class, "addProducts"]);
        Route::post('/remove_product', [UserPromoController::class, "removeProduct"]);
        Route::post('/{promoId}', [UserPromoController::class, "update"]);
        Route::delete('/{promoId}', [UserPromoController::class, "delete"]);
        Route::get('', [UserPromoController::class, "promos"]);
        Route::get('/{promoId}', [UserPromoController::class, "promo"]);
    });

    Route::group(['prefix' => '/staff_bank_accounts'], function () {
        Route::get('', [UserBankAccountController::class, "bankAccounts"]);
        Route::post('', [UserBankAccountController::class, "addAccount"]);
    });

    Route::group(['middleware' => 'hrAuth', 'prefix' => '/staff_bank_accounts'], function () {
        Route::get('/{staffId}', [UserBankAccountController::class, "bankAccounts"]);
    });

    Route::group(['prefix' => '/referrals'], function () {
        Route::get('/earnings', [ReferralController::class, "referralEarnings"]);
        Route::post('/redeem_commission', [ReferralController::class, "redeem"]);
        Route::get('/redemptions', [ReferralController::class, "staffRedemptions"]);
    });

    Route::group(['middleware' => 'hrAuth', 'prefix' => '/admin_referrals'], function () {
        Route::get('', [ReferralController::class, "referralCommissions"]);
        Route::get('/earnings/{staffId}', [ReferralController::class, "referralEarnings"]);
        Route::post('/redemptions/complete_payment', [ReferralController::class, "completePayment"]);
        Route::get('/redemptions/{staffId}', [ReferralController::class, "staffRedemptions"]);
        Route::get('/redemptions', [ReferralController::class, "commissionRedemptions"]);
    });

    Route::group(['middleware' => 'userAuth', 'prefix' => '/notifications'], function () {
        Route::get('/unread', [UserNotificationController::class, "unreadNotifications"]);
        Route::post('mark_as_read', [UserNotificationController::class, "read"]);
    });

    Route::group(['prefix' => '/discounts'], function () {
        Route::get('full_payment', [DiscountController::class, "fullPayment"]);
        Route::post('full_payment/update', [DiscountController::class, "updateFullPayment"]);
        Route::get('/installments', [DiscountController::class, "installments"]);
        Route::post('/installments/update', [DiscountController::class, "updateInstallmentDiscounts"]);
        Route::post('/installments/add', [DiscountController::class, "addInstallmentDiscounts"]);
        Route::delete('/installments/{duration}', [DiscountController::class, "deleteInstallment"]);
    });


    // Client
    Route::group(['prefix' => '/clients'], function () {
        Route::get('', [UserClientController::class, "index"]);
        Route::get('/export', [UserClientController::class, "export"]);
        Route::get('/{clientId}', [UserClientController::class, "show"]);
        Route::post('/{clientId}', [UserClientController::class, "update"]);
        Route::delete('/{clientId}', [UserClientController::class, "delete"]);
        Route::post('/re_upload_document/{assetId}', [UserFileController::class, "saveClientDocument"]);

        // Wallet Routes
        Route::group(['prefix' => '/wallet', 'namespace' => 'Client'], function () {
            Route::post('/link_bank_account', [UserClientWalletController::class, "linkBankAccount"]);
            Route::get('/{clientId}', [UserClientWalletController::class, "index"]);
            Route::get('/transactions/{clientId}', [UserClientWalletController::class, "transactions"]);
            Route::get('/withdrawal_requests/{clientId}', [UserClientWalletController::class, "withdrawalRequests"]);
            Route::get('/withdrawal_request/{requestId}', [UserClientWalletController::class, "withdrawalRequest"]);
            Route::post('/withdrawal_requests/approve', [UserClientWalletController::class, "approveRequest"]);
            Route::post('/withdrawal_requests/reject', [UserClientWalletController::class, "rejectRequest"]);
        });

        Route::group(['prefix' => '/transactions', 'namespace' => 'Client'], function () {
            Route::get('/{clientId}', [UserClientTransactionController::class, "transactions"]);
            Route::get('/show/{transactionId}', [UserClientTransactionController::class, "transaction"]);
        });
    });

    // assessments
    Route::group(['prefix' => '/assessments'], function () {
        Route::group(['prefix' => '/questions'], function () {
            Route::group(['prefix' => '/options'], function () {
                Route::post('', [AssessmentQuestionOptionController::class, "save"]);
                Route::post('/{optionId}', [AssessmentQuestionOptionController::class, "update"]);
                Route::delete('/{optionId}', [AssessmentQuestionOptionController::class, "delete"]);
            });
            Route::post('', [AssessmentQuestionController::class, "save"]);
            Route::post('/{questionId}', [AssessmentQuestionController::class, "update"]);
            Route::get('/{assessmentId}', [AssessmentQuestionController::class, "assessmentQuestions"]);
            Route::delete('/{questionId}', [AssessmentQuestionController::class, "delete"]);
        });
        Route::post('', [UserAssessmentController::class, "create"]);
        Route::get('', [UserAssessmentController::class, "assessments"]);
        Route::get('/attempts/{assessmentId}', [UserAssessmentController::class, "attempts"]);
        Route::get('/attempt/{attemptId}', [AssessmentAttemptController::class, "attempt"]);
        Route::post('/toggle_activate', [UserAssessmentController::class, "toggleActivate"]);
        Route::post('/{assessmentId}', [UserAssessmentController::class, "update"]);
        Route::get('/{assessmentId}', [UserAssessmentController::class, "assessment"]);
        Route::delete('/{assessmentId}', [UserAssessmentController::class, "delete"]);
    });

    // analytics
    Route::group(['prefix' => '/analytics'], function () {
        Route::get('/sales_overview', [AnalyticsController::class, "salesOverview"]);
        Route::get('/project_types', [AnalyticsController::class, "projectTypes"]);
    });

    Route::group(['prefix' => '/virtual_teams'], function () {
        Route::get('/applications', [VirtualTeamApplicationController::class, "applications"]);
        Route::get('/application/{applicationId}', [VirtualTeamApplicationController::class, "application"]);
    });


    Route::get('/roles', [UserUtilityController::class, "roles"]);
    Route::get('/staff_types', [UserUtilityController::class, "staffTypes"]);
    Route::get('/bank_accounts', [UtilityController::class, "bankAccounts"]);
    Route::get('/resell_orders', [UtilityController::class, "resellOrders"]);
});