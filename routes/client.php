<?php 

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use app\Http\Controllers\Client\PromoController;
use app\Http\Controllers\Client\OrderController;
use app\Http\Controllers\Client\PaymentController;
use app\Http\Controllers\Client\DashboardController;
use app\Http\Controllers\Client\WalletController;
use app\Http\Controllers\Client\TransactionController;
use app\Http\Controllers\Client\ProjectController as ClientProjectController;
use app\Http\Controllers\Client\PackageController as ClientPackageController;
use app\Http\Controllers\Client\AssetController;
use app\Http\Controllers\Client\AssetSwitchController;
use app\Http\Controllers\Client\OfferController;
use app\Http\Controllers\Client\OfferBidController;
use app\Http\Controllers\Client\ClientController;
use app\Http\Controllers\Client\SiteTourController;
use app\Http\Controllers\Client\OfferPaymentController;
use app\Http\Controllers\Client\CommentController;
use app\Http\Controllers\Client\ReferralController as ClientReferralController;
use app\Http\Controllers\Client\DiscountController as ClientDiscountController;
use app\Http\Controllers\Client\FileController;
use app\Http\Controllers\Client\BondController;
use app\Http\Controllers\Client\PostController;


// Client Routes
Route::group(['middleware' => ClientAuth::class, 'prefix' => '/client', 'namespace' => 'Client',], function () {
    Route::group(['prefix' => '/dashboard',], function () {
        Route::get('', [DashboardController::class, 'index']);
    });
    // Client Profile
    Route::group(['prefix' => '/profile',], function () {
        Route::get('', [ClientController::class, 'profile']);
        Route::post('/update', [ClientController::class, 'update']);
        Route::post('/save_next_of_kin', [ClientController::class, 'addNextOfKin']);
        Route::get('/generate_referer_code', [ClientController::class, 'generateRefererCode']);
        Route::get('/referral_earnings', [ClientController::class, 'referralEarnings']);
    });
    Route::group(['prefix' => '/file',], function () {
        Route::post('/upload_profile_photo', 'FileController@saveProfilePhoto');
        Route::post('/upload_payment_evidence', 'FileController@savePaymentEvidence');
    });

    // Project Routes
    Route::group(['prefix' => '/projects',], function () {
        Route::get('/summary', [ClientProjectController::class, 'summary']);
        Route::get('', [ClientProjectController::class, 'projects']);
        Route::get('/{projectTypeId}', [ClientProjectController::class, 'projects']);
        Route::get('/get_project/{projectId}', [ClientProjectController::class, 'project']);
        Route::get('/get_project/all/{projectTypeId}', [ClientProjectController::class, 'projectType']);
    });

     // Package Routes
     Route::group(['prefix' => '/packages',], function () {
        Route::get('/{packageId}', [ClientPackageController::class, 'package']);
    });

    //Order Routes
    Route::group(['prefix' => '/order',], function () {
        Route::post('/validate_promo_code', [PromoController::class, 'validate']);
        Route::post('/prepare', [OrderController::class, 'prepareOrder']);
    });

    // Discount Routes
    Route::group(['prefix' => '/discounts',], function () {
        Route::get('/installments', [ClientDiscountController::class, 'installments']);
    });

    // Payment Routes
    Route::group(['prefix' => '/payment',], function () {
        Route::post('/initialize_card_payment', [PaymentController::class, 'initializeCardPayment']);
        Route::post('/prepare_additional_payment', [PaymentController::class, 'prepareAdditionalPayment']);
        Route::post('/save', [PaymentController::class, 'save']);
        Route::post('/save_additional_payment', [PaymentController::class, 'saveAdditionalPayment']);
    });

    //Assets Routes
    Route::group(['prefix' => '/assets'], function () {
        Route::get('/summary', [AssetController::class, 'summary']);
        Route::get('', [AssetController::class, 'assets']);
        // Route::post('/update_installment', [AssetController::class, 'updateInstallment']);

        Route::get('/downgrade_packages/{assetId}', [AssetSwitchController::class, 'downgradePackages']);
        Route::get('/upgrade_packages/{assetId}', [AssetSwitchController::class, 'upgradePackages']);
        Route::post('/request_asset_switch', [AssetSwitchController::class, 'requestSwitch']);

        Route::get('/{assetId}', [AssetController::class, 'asset']);
    });

    //Offer Routes
    Route::group(['prefix' => '/offers'], function () {
        Route::post('', [OfferController::class, 'create']);
        Route::get('', [OfferController::class, 'offers']);
        Route::get('/sales', [OfferController::class, "saleOffers"]);
        Route::get('/active', [OfferController::class, "activeOffers"]);
        Route::post('/make_bid', [OfferBidController::class, 'bid']);
        Route::get('/{offerId}', [OfferController::class, 'offer']);
        Route::post('/prepare_payment', [OfferPaymentController::class, 'preparePayment']);
        Route::post('/initialize_card_payment', [OfferPaymentController::class, 'initializeCardPayment']);
        Route::post('/pay', [OfferPaymentController::class, 'makePayment']);

        Route::get('/ready', [OfferController::class, "readyOffers"]);
        Route::get('/my_ready', [OfferController::class, "myReadyOffers"]);
    });

    //Offer Bid Routes
    Route::group(['prefix' => '/bids'], function () {
        // Route::post('/{bidId}', [OfferBidController::class, 'getBid']);
        Route::get('', [OfferBidController::class, 'myBids']);
        Route::post('/accept', [OfferBidController::class, 'acceptBid']);
        Route::post('/reject', [OfferBidController::class, 'rejectBid']);
    });

    //Bonds
    Route::group(['prefix' => '/bonds',], function () {
        Route::get('', [BondController::class, 'bonds']);
        Route::post('/redeem', [BondController::class, 'redeem']);
        Route::post('/renew', [BondController::class, 'renew']);
    });

    //Wallet Routes
    Route::group(['prefix' => '/wallet',], function () {
        Route::get('', [WalletController::class, 'index']);
        Route::get('/transactions', [WalletController::class, 'transactions']);
        Route::post('/link_bank_account', [WalletController::class, 'LinkBankAccount']);
        Route::post('/set_transaction_pin', [WalletController::class, 'setTransactionPin']);
        Route::post('/validate_withdrawal', [WalletController::class, 'validateWithdrawal']);
        Route::post('/withdraw', [WalletController::class, 'withdraw']);
        Route::get('/withdrawal_requests', [WalletController::class, 'withdrawalRequests']);
    });

    //Transaction Routes
    Route::group(['prefix' => '/transactions',], function () {
        Route::get('', [TransactionController::class, 'index']);
        Route::get('/{transactionId}', [TransactionController::class, 'transaction']);
        Route::get('/export/{transactionId}', [TransactionController::class, 'export']);
    });

    //Site Tours
    Route::group(['prefix' => '/site_tours',], function () {
        Route::post('/book', [SiteTourController::class, 'book']);
        Route::get('/schedules', [SiteTourController::class, 'schedules']);
        Route::get('/filter_schedules', [SiteTourController::class, 'filterSchedules']);
        Route::get('', [SiteTourController::class, 'siteTours']);
    });

    Route::group(['prefix' => '/posts'], function () {
        Route::post('/react', [PostController::class, "react"]);
        Route::get('', [PostController::class, "posts"]);
        Route::get('/{slug}', [PostController::class, "post"]);
    });

    Route::group(['prefix' => '/comments'], function () {
        Route::post('', [CommentController::class, "save"]);
        Route::post('/react', [CommentController::class, "react"]);
    });

    Route::group(['prefix' => '/referrals'], function () {
        Route::get('', [ClientReferralController::class, "referrals"]);
    });


    Route::get('/bank_accounts', [UtilityController::class, "activeBankAccounts"]);
    Route::get('/resell_orders', [UtilityController::class, "resellOrders"]);
});