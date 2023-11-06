<?php

use Illuminate\Http\Request;
use App\Models\SecondaryGroup;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\Ecommerce\OrderController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Ecommerce\Admin\ProductController;
use App\Http\Controllers\GiftController;
use App\Http\Controllers\SecondaryGroupController;
use App\Http\Controllers\Subscription\PlanController;
use App\Http\Controllers\Subscription\PointController;
use App\Http\Controllers\Subscription\SubscriptionController;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:api'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('content', function(Request $request){
    $data = DB::table('controls')->insert([
        'content' => $request['content']
        ]);
})->middleware('role:admin');

Route::put('content/{id}', function(Request $request, $id){
    $data = DB::table('controls')->where('id', $id)->update([
        'content' => $request['content']
        ]);
})->middleware('role:admin');

Route::get('content', function(Request $request){
    $data = DB::table('controls')->get();
    return $data;
});

Route::post('visit', function () {
    DB::table('visits')->updateOrInsert(
        [
            'date' => date('Y-m-d')
        ],
        [
            'visits' => DB::raw('visits+1'),
        ]
    );
});

Route::get('visit', function (Request $request) {
    $today = DB::table('visits')->where('date', $request['date'] ?? date('Y-m-d'))->sum('visits');
    $total = DB::table('visits')->sum('visits');

    return ['today' => $today, 'total' => $total];
});
Route::apiResource('campaign', CampaignController::class);
Route::middleware(['block'])->group(function () {
Route::post('update-campaign/{id}', [CampaignController::class, 'update'])->middleware('auth:api');
Route::apiResource('group', GroupController::class);
Route::apiResource('plan', PlanController::class);
Route::apiResource('gift', GiftController::class);
Route::apiResource('product', ProductController::class);
Route::post('product/{id}', [ProductController::class, 'update'])->middleware(['auth:api', 'role:admin']);
Route::apiResource('subscription', SubscriptionController::class);
Route::get('senior-plan', [PlanController::class, 'seniorUserPlan'])->middleware('auth:api');
Route::apiResource('point', PointController::class);
Route::get('join-group/{id}', [GroupController::class, 'joinGroup'])->middleware('loop');
Route::get('my-group/secondary', [SecondaryGroupController::class, 'secondryChildren']);
Route::get('my-group', [GroupController::class, 'childrenUser']);
Route::get('my-full-group', [GroupController::class, 'allChildren']);
Route::get('my-group-points/{id}', [PointController::class, 'points']);
Route::get('my-admin/secondary', [SecondaryGroupController::class, 'secondryParents']);
Route::get('my-admin', [GroupController::class, 'parents']);
// Route::get('my-users', [GroupController::class, 'childrenUser']);
Route::get('user-admin/{id}', [UserController::class, 'userAdmins']);
Route::apiResource('video', VideoController::class);
Route::apiResource('invitations', InvitationController::class);
Route::get('random-video', [VideoController::class, 'randomVideo']);
Route::apiResource('category', CategoryController::class);
Route::get('user-campaigns', [CampaignController::class, 'userCampaign'])->middleware(['auth:api']);
// Route::get('my-donations', [UserController::class, 'donations'])->middleware(['auth:api']);
Route::post('admin/update-user/{id}', [UserController::class, 'updateUser'])->middleware(['auth:api', 'role:admin']);
// Route::post('admin/approve-donation/{id}', [DonationController::class, 'adminApproveDonation'])->middleware(['auth:api', 'role:admin']);
Route::delete('admin/delete-donation/{id}', [DonationController::class, 'adminDestroy'])->middleware(['auth:api', 'role:admin']);
Route::get('admin/find-user', [UserController::class, 'findUser'])->middleware(['auth:api', 'role:admin']);
Route::put('admin/wallet-user/{id}', [AdminController::class, 'topUp'])->middleware(['auth:api', 'role:admin']);
Route::get('admin/user-logins', [AdminController::class, 'logins'])->middleware(['auth:api', 'role:admin']);
Route::get('admin/permissions', [AdminController::class, 'permissions'])->middleware(['auth:api', 'role:admin']);
Route::get('admin/user-permissions/{id?}', [AdminController::class, 'userPermissions'])->middleware(['auth:api', 'role:admin']);
Route::post('admin/assign-permissions/{id}', [AdminController::class, 'assignPermissions'])->middleware(['auth:api', 'role:admin']);
Route::get('admin/user-donations/{id}', [UserController::class, 'adminDonations'])->middleware(['auth:api', 'role:admin']);
Route::get('admin/all-donations', [DonationController::class, 'index'])->middleware(['auth:api', 'role:admin']);
Route::apiResource('donation', DonationController::class)->middleware(['auth:api']);
Route::post('approve-donation/{id}', [DonationController::class, 'approveDonation'])->middleware(['auth:api']);
Route::get('my-collections', [DonationController::class, 'authUserCollections'])->middleware(['auth:api']);
Route::get('collections-for-me', [DonationController::class, 'userCollections'])->middleware(['auth:api']);
Route::get('my-donations', [DonationController::class, 'authUserDonations'])->middleware(['auth:api']);
Route::get('admin/user-collections/{id?}', [DonationController::class, 'userDonations'])->middleware(['auth:api']);
Route::get('admin/overview', [AdminController::class, 'overview'])->middleware(['auth:api', 'role:admin']);

Route::post('admin/approve-donation/{id}', [AdminController::class, 'approveDonation'])->middleware(['auth:api', 'role:admin']);
Route::post('admin/change-role/{id}', [AdminController::class, 'changeRole'])->middleware(['auth:api', 'role:admin']);
Route::get('admin/my-group/secondary/{id}', [AdminController::class, 'secondryChildren'])->middleware(['auth:api', 'role:admin']);
Route::get('admin/my-group/{id}', [AdminController::class, 'childrenUser'])->middleware(['auth:api', 'role:admin']);
Route::get('admin/my-admin/secondary/{id}', [AdminController::class, 'secondryParents'])->middleware(['auth:api', 'role:admin']);
Route::get('admin/my-admin/{id}', [AdminController::class, 'parents'])->middleware(['auth:api', 'role:admin']);
Route::get('admin/subscription-info/{id?}', [AdminController::class, 'subscriptions'])->middleware(['auth:api', 'role:admin']);
Route::get('admin/referral-info/{id?}', [AdminController::class, 'referralInfo'])->middleware(['auth:api', 'role:admin']);
Route::post('admin/delete-attachment/{id}', [AdminController::class, 'deleteAttachment'])->middleware(['auth:api', 'role:admin']);
Route::post('join-group/secondary', [SecondaryGroupController::class, 'joinGroup'])->middleware(['auth:api', 'primary_check']);
Route::apiResource('users', UserController::class);
Route::get('admin/users-list/{role}', [UserController::class, 'userList'])->middleware(['auth:api', 'role:admin']);
Route::get('auth/google', [SocialiteController::class, 'redirect']);
Route::get('auth/callback-url', [SocialiteController::class, 'callback']);
Route::post('donate/admin', [DonationController::class, 'donateAdmin'])->middleware(['auth:api']);

//
Route::post('admin/rule', [AdminController::class, 'rules'])->middleware(['auth:api', 'role:admin']);
Route::get('tasks/{round?}', [DonationController::class, 'rules'])->middleware(['auth:api']);
Route::put('update-round', [UserController::class, 'updateRound'])->middleware(['auth:api']);
Route::post('junior-donation', [DonationController::class, 'juniorDonation'])->middleware(['auth:api']);
Route::get('junior-donations/{user_id}/{round}', [DonationController::class, 'getJuniorDonations'])->middleware(['auth:api']);
Route::get('senior-donations/{user_id}/{round}', [DonationController::class, 'getSeniorDonations'])->middleware(['auth:api']);
Route::get('campaign-donations/{round?}', [DonationController::class, 'campaignDonations'])->middleware(['auth:api']);

Route::post('campaign/update-attachment/{id}', [CampaignController::class, 'updateAttachment'])->middleware(['auth:api']);
Route::post('product/update-attachment/{id}', [ProductController::class, 'updateAttachment'])->middleware(['auth:api', 'role:admin']);
Route::post('donation/donate-virolife', [DonationController::class, 'donateVirolife'])->middleware(['auth:api', 'monthly_check']);
Route::get('user/donation/donate-virolife', [DonationController::class, 'getVirolifeUserDonation'])->middleware(['auth:api']);

Route::get('admin/donation/donate-virolife/{id?}', [AdminController::class, 'getVirolifeDonation'])->middleware(['auth:api', 'role:admin']);
Route::post('admin/user/register-user', [AdminController::class, 'storeUser'])->middleware(['auth:api', 'role:admin']);
Route::post('agent/user/register-user', [AdminController::class, 'store'])->middleware(['auth:api', 'role:agent|distributor']);

Route::get('admin/get-agents/{role}/{id?}', [AdminController::class, 'getAgents'])->middleware(['auth:api', 'role:admin']);
Route::get('user/my-users/{user_id?}', [UserController::class, 'myAgents'])->middleware(['auth:api']);

Route::post('gift/redeem/viroteam', [DonationController::class, 'redeem'])->middleware(['monthly_check']);
Route::post('gift/redeem/primary/{id}', [GroupController::class, 'redeem']);
Route::post('gift/redeem/secondary', [SecondaryGroupController::class, 'redeem']);

Route::post('send-link/forgot-password', [UserController::class, 'resetPasswordLink']);
Route::post('forgot-password', [UserController::class, 'resetPass']);

Route::get('my-health-points', [UserController::class, 'myPoints']);
Route::post('user/points/request-transfer', [UserController::class, 'transferRequest'])->middleware('points');
Route::get('user/points/my-atp', [UserController::class, 'myViroPoints'])->middleware('auth:api');
Route::post('user/points/request-withdrawal', [UserController::class, 'transferCampaignRequest']);


Route::post('admin/approve-points/{id}', [UserController::class, 'transferPoints'])->middleware(['auth:api', 'role:admin']);
Route::post('admin/approve-withrawals/{id}', [AdminController::class, 'approveWithdrawal'])->middleware(['auth:api', 'role:admin']);
Route::get('my-gifts', [UserController::class, 'myGifts']);
// Need this below api for withrawals
Route::get('admin/points/requests/{status}', [AdminController::class, 'pendingRequests'])->middleware(['auth:api', 'role:admin']);
Route::get('admin/campaign/requests/{status}', [AdminController::class, 'pendingWithdrawalRequests'])->middleware(['auth:api', 'role:admin']);

Route::get('points/my-requests', [UserController::class, 'myPointRequests']);
Route::get('campaigns/my-requests', [UserController::class, 'myCampaignRequests']);

Route::get('admin/virolife-donations', [AdminController::class, 'virolifeDonation'])->middleware(['auth:api', 'role:admin']);
Route::get('admin/all-team-donations', [AdminController::class, 'allTeamDonation'])->middleware(['auth:api', 'role:admin']);
Route::get('admin/video-views', [AdminController::class, 'videoViews'])->middleware(['auth:api', 'role:admin']);
Route::get('admin/video-history', [AdminController::class, 'videoHistory'])->middleware(['auth:api', 'role:admin']);
Route::get('admin/agent-commission/{role}/{id?}', [AdminController::class, 'agentCommission'])->middleware(['auth:api', 'role:admin']);

Route::post('admin/print-reports/{report}', [AdminController::class, 'printReports'])->middleware(['auth:api', 'role:admin']);

Route::get('user/commission-request', [UserController::class, 'getCommissionRequest'])->middleware(['auth:api', 'role:user|agent|distributor']);
Route::post('user/commission-request', [UserController::class, 'commissionRequest'])->middleware(['auth:api', 'role:user|agent|distributor']);

Route::post('admin/commission-request/{id}', [AdminController::class, 'approveCommission'])->middleware(['auth:api', 'role:admin']);
Route::get('admin/commission-request', [AdminController::class, 'getCommission'])->middleware(['auth:api', 'role:admin']);

Route::post('admin/bulk-gifts', [AdminController::class, 'bulkGifts'])->middleware(['auth:api', 'role:admin']);
Route::get('agent/my-user-points/{user_id?}', [UserController::class, 'userHealthPoints'])->middleware(['auth:api', 'role:agent|distributor']);
Route::get('admin/all-payouts/{role}', [AdminController::class, 'allPayouts'])->middleware(['auth:api', 'role:admin']);
Route::get('my-assigned-gifts', [GiftController::class, 'assignedGiftCards'])->middleware(['auth:api', 'role:agent|distributor']);
Route::get('total-donation', [UserController::class, 'donationSum'])->middleware(['auth:api']);
Route::put('admin/gift/{id}', [AdminController::class, 'editGift'])->middleware(['auth:api', 'role:admin']);
Route::get('admin/user-donation-data/{group}', [AdminController::class, 'donationData'])->middleware(['auth:api', 'role:admin']);
Route::get('admin/gateway-transaction/{purpose}', [AdminController::class, 'razorpayTransaction'])->middleware(['auth:api', 'role:admin']);
Route::get('user/direct-junior', [UserController::class, 'directJuniors'])->middleware(['auth:api']);

Route::post('donate-campaign', [DonationController::class, 'donateCampaign'])->middleware(['auth:api']);

Route::get('my-payments', [UserController::class, 'myTransactions'])->middleware(['auth:api']);

Route::get('admin/orders', [OrderController::class, 'adminIndex'])->middleware(['auth:api', 'role:admin']);
Route::post('admin/update/order/{id}', [OrderController::class, 'updateOrderStatus'])->middleware(['auth:api', 'role:admin']);

Route::get('gift-card-detail', [GiftController::class, 'cardDetails'])->middleware(['auth:api']);

Route::apiResource('orders', OrderController::class)->middleware(['auth:api']);

Route::get('my-campaign-donations', [DonationController::class, 'myCampaignDonations'])->middleware(['auth:api']);

Route::get('admin/campaign-donations', [AdminController::class, 'campaignDonations'])->middleware(['auth:api', 'role:admin']);

Route::post('create-order', [OrderController::class, 'generateOrderId']);
});
Route::post('verify-order', [Controller::class, 'order']);