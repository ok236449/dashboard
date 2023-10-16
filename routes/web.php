<?php

use App\Classes\Cloudflare;
use App\Classes\Pterodactyl;
use App\Classes\Settings\Invoices;
use App\Classes\Settings\Language;
use App\Classes\Settings\Misc;
use App\Classes\Settings\Payments;
use App\Classes\Settings\System;
use App\Console\Commands\ChargeForVPS;
use App\Console\Commands\LogPLayersCommand;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ApplicationApiController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\LegalController;
use App\Http\Controllers\Admin\OverViewController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ServerController as AdminServerController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ShopProductController;
use App\Http\Controllers\Admin\UsefulLinkController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Moderation\TicketCategoryController;
use App\Http\Controllers\Moderation\TicketsController as ModTicketsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController as FrontProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\TicketsController;
use App\Http\Controllers\TranslationController;
use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Pricing;
use App\Http\Controllers\Admin\VirtualPrivateServerController;
use App\Models\PlayerLog;
use App\Models\Product;
use App\Models\Server;
use App\Models\User;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware('guest')->get('/', function () {
    return redirect('login');
})->name('welcome');

Auth::routes(['verify' => true]);

Route::get('/privacy', function () {
    return view('information.privacy');
})->name('privacy');
Route::get('/imprint', function () {
    return view('information.imprint');
})->name('imprint');
Route::get('/tos', function () {
    return view('information.tos');
})->name('tos');

Route::post('payment/StripeWebhooks', [PaymentController::class, 'StripeWebhooks'])->name('payment.StripeWebhooks');

Route::middleware(['auth', 'checkSuspended'])->group(function () {
    //resend verification email
    Route::get('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Verification link sent!');
    })->middleware(['auth', 'throttle:3,1'])->name('verification.send');

    //normal routes
    Route::get('notifications/readAll', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::resource('notifications', NotificationController::class);
    Route::resource('servers', ServerController::class);
    if (config('SETTINGS::SYSTEM:ENABLE_UPGRADE')) {
        Route::post('servers/{server}/upgrade', [ServerController::class, 'upgrade'])->name('servers.upgrade');
    }

    Route::post('profile/selfdestruct', [ProfileController::class, 'selfDestroyUser'])->name('profile.selfDestroyUser');
    Route::resource('profile', ProfileController::class);
    Route::resource('store', StoreController::class);

    //server create utility routes (product)
    //routes made for server create page to fetch product info
    Route::get('/products/nodes/egg/{egg?}', [FrontProductController::class, 'getNodesBasedOnEgg'])->name('products.nodes.egg');
    Route::get('/products/locations/egg/{egg?}', [FrontProductController::class, 'getLocationsBasedOnEgg'])->name('products.locations.egg');
    Route::get('/products/products/{egg?}/{node?}', [FrontProductController::class, 'getProductsBasedOnNode'])->name('products.products.node');

    //payments
    Route::get('payment/PaypalPay', [PaymentController::class, 'PaypalPay'])->name('payment.PaypalPay');
    Route::get('payment/PaypalSuccess', [PaymentController::class, 'PaypalSuccess'])->name('payment.PaypalSuccess');
    Route::get('payment/StripePay', [PaymentController::class, 'StripePay'])->name('payment.StripePay');
    Route::get('payment/StripeSuccess', [PaymentController::class, 'StripeSuccess'])->name('payment.StripeSuccess');
    Route::get('payment/GopayPay', [PaymentController::class, 'GopayPay'])->name('payment.GopayPay');
    Route::get('payment/Cancel', [PaymentController::class, 'Cancel'])->name('payment.Cancel');
    Route::get('payment/pay', [PaymentController::class, 'Pay'])->name('payment.Pay');

    Route::get('users/logbackin', [UserController::class, 'logBackIn'])->name('users.logbackin');

    //discord
    Route::get('/auth/redirect', [SocialiteController::class, 'redirect'])->name('auth.redirect');
    Route::get('/auth/callback', [SocialiteController::class, 'callback'])->name('auth.callback');

    //voucher redeem
    Route::post('/voucher/redeem', [VoucherController::class, 'redeem'])->middleware('throttle:5,1')->name('voucher.redeem');

    //switch language
    Route::post('changelocale', [TranslationController::class, 'changeLocale'])->name('changeLocale');

    //ticket user
    if (config('SETTINGS::TICKET:ENABLED')) {
        Route::get('ticket', [TicketsController::class, 'index'])->name('ticket.index');
        Route::get('ticket/datatable', [TicketsController::class, 'datatable'])->name('ticket.datatable');
        Route::get('ticket/new', [TicketsController::class, 'create'])->name('ticket.new');
        Route::post('ticket/new', [TicketsController::class, 'store'])->middleware(['throttle:ticket-new'])->name('ticket.new.store');
        Route::get('ticket/show/{ticket_id}', [TicketsController::class, 'show'])->name('ticket.show');
        Route::post('ticket/reply', [TicketsController::class, 'reply'])->middleware(['throttle:ticket-reply'])->name('ticket.reply');
        Route::post('ticket/status/{ticket_id}', [TicketsController::class, 'changeStatus'])->name('ticket.changeStatus');
    }

    //please commend the next line
    Route::post('/domain', [DomainController::class, 'link'])->name('domain');

    //domain and subdomain linking
    Route::prefix('subdomain')->name('subdomain.')->group(function () {
        Route::prefix('minecraft')->name('minecraft.')->group(function () {
            Route::post('link', [DomainController::class, 'linkMinecraftSubdomain'])->name('link');
            Route::post('unlink', [DomainController::class, 'unlinkMinecraftSubdomain'])->name('unlink');
            Route::post('refresh', [DomainController::class, 'refreshMinecraftSubdomain'])->name('refresh');
        });
        Route::prefix('web')->name('web.')->group(function () {
            Route::post('link', [DomainController::class, 'linkWebSubdomain'])->name('link');
            Route::post('unlink', [DomainController::class, 'unlinkWebSubdomain'])->name('unlink');
        });
    });
    Route::prefix('domain')->name('domain.')->group(function () {
        Route::prefix('minecraft')->name('minecraft.')->group(function () {
            Route::post('link', [DomainController::class, 'linkMinecraftDomain'])->name('link');
            Route::post('unlink', [DomainController::class, 'unlinkMinecraftDomain'])->name('unlink');
            Route::post('refresh', [DomainController::class, 'refreshMinecraftDomain'])->name('refresh');
        });
        Route::prefix('web')->name('web.')->group(function () {
            Route::post('link', [DomainController::class, 'linkWebDomain'])->name('link');
            Route::post('unlink', [DomainController::class, 'unlinkWebDomain'])->name('unlink');
        });
    });

    Route::post('/domain/checkAvailability', [DomainController::class, 'checkAvailability'])->name('domain.checkAvailability');
    //Route::patch('/domain/update/protection', [DomainController::class, 'update'])->name('domain.update.protection');
    //Route::patch('servers/settings/update/domains', [Domain::class, 'updateSettingsDomains'])->name('servers.settings.update.domains');
    Route::patch('servers/settings/update/protection', [DomainController::class, 'updateProtection'])->name('servers.settings.update.protection');
    Route::patch('servers/settings/update/lobby', [DomainController::class, 'updateLobby'])->name('servers.settings.update.lobby');
    /*Route::get('test', function(){
        return ChargeForVPS::handle();
        //DomainController::uploadBungeeGuard('a7eb5624');
        //DomainController::deleteBungeeGuard('a7eb5624');
    });*/

    //admin
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {

        //overview
        Route::get('legal', [OverViewController::class, 'index'])->name('overview.index');

        Route::get('overview', [OverViewController::class, 'index'])->name('overview.index');
        Route::get('overview/sync', [OverViewController::class, 'syncPterodactyl'])->name('overview.sync');

        Route::resource('activitylogs', ActivityLogController::class);

        //users
        Route::get('users.json', [UserController::class, 'json'])->name('users.json');
        Route::get('users/loginas/{user}', [UserController::class, 'loginAs'])->name('users.loginas');
        Route::get('users/verifyEmail/{user}', [UserController::class, 'verifyEmail'])->name('users.verifyEmail');
        Route::get('users/datatable', [UserController::class, 'datatable'])->name('users.datatable');
        Route::get('users/notifications', [UserController::class, 'notifications'])->name('users.notifications');
        Route::post('users/notifications', [UserController::class, 'notify'])->name('users.notifications');
        Route::post('users/togglesuspend/{user}', [UserController::class, 'toggleSuspended'])->name('users.togglesuspend');
        Route::resource('users', UserController::class);

        //servers
        Route::get('servers/datatable', [AdminServerController::class, 'datatable'])->name('servers.datatable');
        Route::post('servers/togglesuspend/{server}', [AdminServerController::class, 'toggleSuspended'])->name('servers.togglesuspend');
        Route::get('servers/sync', [AdminServerController::class, 'syncServers'])->name('servers.sync');
        Route::resource('servers', AdminServerController::class);

        //products
        Route::get('products/datatable', [ProductController::class, 'datatable'])->name('products.datatable');
        Route::get('products/clone/{product}', [ProductController::class, 'clone'])->name('products.clone');
        Route::patch('products/disable/{product}', [ProductController::class, 'disable'])->name('products.disable');
        Route::resource('products', ProductController::class);

        //store
        Route::get('store/datatable', [ShopProductController::class, 'datatable'])->name('store.datatable');
        Route::patch('store/disable/{shopProduct}', [ShopProductController::class, 'disable'])->name('store.disable');
        Route::resource('store', ShopProductController::class)->parameters([
            'store' => 'shopProduct',
        ]);

        //payments
        Route::get('payments/datatable', [PaymentController::class, 'datatable'])->name('payments.datatable');
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');

        //settings
        Route::get('settings/datatable', [SettingsController::class, 'datatable'])->name('settings.datatable');
        Route::patch('settings/updatevalue', [SettingsController::class, 'updatevalue'])->name('settings.updatevalue');
        Route::get('settings/checkPteroClientkey', [System::class, 'checkPteroClientkey'])->name('settings.checkPteroClientkey');
        Route::redirect('settings#system', 'system')->name('settings.system');

        //settings
        Route::patch('settings/update/invoice-settings', [Invoices::class, 'updateSettings'])->name('settings.update.invoicesettings');
        Route::patch('settings/update/language', [Language::class, 'updateSettings'])->name('settings.update.languagesettings');
        Route::patch('settings/update/payment', [Payments::class, 'updateSettings'])->name('settings.update.paymentsettings');
        Route::patch('settings/update/misc', [Misc::class, 'updateSettings'])->name('settings.update.miscsettings');
        Route::patch('settings/update/system', [System::class, 'updateSettings'])->name('settings.update.systemsettings');
        Route::resource('settings', SettingsController::class)->only('index');

        //invoices
        Route::get('invoices/download-invoices', [InvoiceController::class, 'downloadAllInvoices'])->name('invoices.downloadAllInvoices');
        Route::get('invoices/download-single-invoice', [InvoiceController::class, 'downloadSingleInvoice'])->name('invoices.downloadSingleInvoice');

        //usefullinks
        Route::get('usefullinks/datatable', [UsefulLinkController::class, 'datatable'])->name('usefullinks.datatable');
        Route::resource('usefullinks', UsefulLinkController::class);

        //legal
        Route::get('legal', [LegalController::class, 'index'])->name('legal.index');
        Route::patch('legal', [LegalController::class, 'update'])->name('legal.update');

        //vouchers
        Route::get('vouchers/datatable', [VoucherController::class, 'datatable'])->name('vouchers.datatable');
        Route::get('vouchers/{voucher}/usersdatatable', [VoucherController::class, 'usersdatatable'])->name('vouchers.usersdatatable');
        Route::get('vouchers/{voucher}/users', [VoucherController::class, 'users'])->name('vouchers.users');
        Route::resource('vouchers', VoucherController::class);

        //partners
        Route::get('partners/datatable', [PartnerController::class, 'datatable'])->name('partners.datatable');
        Route::get('partners/{voucher}/users', [PartnerController::class, 'users'])->name('partners.users');
        Route::resource('partners', PartnerController::class);

        //vps
        Route::get('vps/datatable', [VirtualPrivateServerController::class, 'datatable'])->name('vps.datatable');
        //Route::get('vps/{vp}/edit', [VpsController::class, 'edit'])->name('vps.edit');
        //Route::get('vps/{vps}/users', [VpsController::class, 'users'])->name('vps.users');
        Route::resource('vps', VirtualPrivateServerController::class);

        //api-keys
        Route::get('api/datatable', [ApplicationApiController::class, 'datatable'])->name('api.datatable');
        Route::resource('api', ApplicationApiController::class)->parameters([
            'api' => 'applicationApi',
        ]);
    });

    //mod
    Route::prefix('moderator')->name('moderator.')->middleware('moderator')->group(function () {
        //ticket moderation
        Route::get('ticket', [ModTicketsController::class, 'index'])->name('ticket.index');
        Route::get('ticket/datatable', [ModTicketsController::class, 'datatable'])->name('ticket.datatable');
        Route::get('ticket/show/{ticket_id}', [ModTicketsController::class, 'show'])->name('ticket.show');
        Route::post('ticket/reply', [ModTicketsController::class, 'reply'])->name('ticket.reply');
        Route::post('ticket/status/{ticket_id}', [ModTicketsController::class, 'changeStatus'])->name('ticket.changeStatus');
        Route::post('ticket/delete/{ticket_id}', [ModTicketsController::class, 'delete'])->name('ticket.delete');
        //ticket moderation blacklist
        Route::get('ticket/blacklist', [ModTicketsController::class, 'blacklist'])->name('ticket.blacklist');
        Route::post('ticket/blacklist', [ModTicketsController::class, 'blacklistAdd'])->name('ticket.blacklist.add');
        Route::post('ticket/blacklist/delete/{id}', [ModTicketsController::class, 'blacklistDelete'])->name('ticket.blacklist.delete');
        Route::post('ticket/blacklist/change/{id}', [ModTicketsController::class, 'blacklistChange'])->name('ticket.blacklist.change');
        Route::get('ticket/blacklist/datatable', [ModTicketsController::class, 'dataTableBlacklist'])->name('ticket.blacklist.datatable');


        Route::get('ticket/category/datatable', [TicketCategoryController::class, 'datatable'])->name('ticket.category.datatable');
        Route::resource("ticket/category", TicketCategoryController::class,['as' => 'ticket']);

    });

    Route::get('/home', [HomeController::class, 'index'])->name('home');
});

Route::get('payment/GopayReturn', [PaymentController::class, 'GopayReturn'])->name('payment.GopayReturn');

Route::prefix('api')->name('api.')->group(function(){
    Route::get('pricing', [Pricing::class, 'index']);
    Route::get('favourites', [Pricing::class, 'favourites']);
    Route::get('stats', [PlayerLog::class, 'index']);
});

//require __DIR__ . '/extensions_web.php';

/*Route::get('giveCredits', function(){
    $cpServers = Server::get();
    $products = Product::get();
    $users = User::get();
    //dd($cpServers[0]);
    $counter = ["servers" => 0, "credits" => 0];
    foreach(Pterodactyl::getServers() as $server){
        if($server["attributes"]["node"]!=12) continue;
        //if($server["attributes"]["pterodactyl_id"]<=500) continue;
        //dd($server["attributes"]["identifier"]);
        $cpServer = $cpServers->where("identifier", $server["attributes"]["identifier"])->first();
        if(!isset($cpServer->product_id)) continue;
        //if(!$cpServer->suspended) continue;
        //dd(Carbon::createFromTimeString($cpServer->suspended));
        //dd(Carbon::createFromTimeString("2023-09-02 5:00:00"));
        if($cpServer->suspended&&Carbon::createFromTimeString($cpServer->suspended)->unix()<Carbon::createFromTimeString("2023-09-02 5:00:00")->unix()) continue;
        //dd($cpServer);
        $counter["servers"]++;
        $product = $products->where("id", $cpServer->product_id)->first();
        $user = $users->where("id", $cpServer->user_id)->first();
        //dd($product);
        //dd($user);
        $user->credits = $user->credits + $product->price/30*3.45*3;
        $counter["credits"] += $product->price/30*3.45*3;
        $user->save();
        //dd($server);
    }
    dd($counter);
});*/
