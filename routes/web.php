<?php
use App\Http\Controllers\SiteController;
use App\Http\Controllers\BackupsController;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\PluginController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;

/* ---------- Custom endpoints ---------- */

//CRUD ROUTES
Route::resource("sites","SiteController");
Route::resource("options","OptionController");
Route::resource("backups","BackupsController");

Route::resource('users', UserController::class)->except(['show']);

Route::get('dev-playground-welcome', [BaseController::class, 'devPlaygroundWelcome'])->name('base.dev.welcome');
Route::post('/validate-pete', [BaseController::class, 'validatePete']);
Route::get('register-your-license', [BaseController::class, 'registerYourLicense']);
Route::get('/security', [BaseController::class, 'security'])->name('base.security');
Route::get('/security/report', [BaseController::class, 'securityReport'])->name('base.security.report');

Route::prefix('users')->middleware('auth')->group(function () {
    Route::get('my-account', [UserController::class, 'myAccount'])->name('users.myaccount');
    Route::post('my-account', [UserController::class, 'updatePassword'])->name('users.myaccount.update');
});
     
Route::prefix('backups')->group(function () {
    Route::post('restore', [BackupsController::class, 'restore'])->name('backups.restore');
    Route::get('{backup}/download', [BackupsController::class, 'download'])->name('backups.download');
    Route::get('status/{id}', [BackupsController::class, 'status'])->name('backups.status');
});

Route::prefix('sites')->group(function () {

    Route::post('clone', [SiteController::class, 'cloneSite'])->name('sites.clone');
    Route::post('generate-ssl', [SiteController::class, 'generateSSL'])->name('sites.generate.ssl');
    Route::post('get-sites', [SiteController::class, 'getSites'])->name('sites.get.sites');
    Route::get('logs/{site}', [SiteController::class, 'logs'])->name('sites.logs');
    Route::get('clone/status/{id}', [SiteController::class, 'cloneStatus'])->name('sites.clone.status');

    Route::patch('{site}/backup-settings', [SiteController::class, 'updateBackupSettings'])
        ->name('sites.backup.settings');

});

Route::prefix('plugins')->group(function () {

    Route::post('/install', [PluginController::class, 'runPluginInstallScript'])->name('plugins.install');
    Route::post('/uninstall', [PluginController::class, 'runPluginUninstallScript'])->name('plugins.uninstall');
    Route::post('/update', [PluginController::class, 'runPluginUpdateScript'])->name('plugins.update');
    Route::get('/index', [PluginController::class, 'index'])->name('plugins.index');
    
});

Route::prefix('admin')->group(function () {
    
    // Updates
    Route::get('/version/check', [AdminController::class, 'versionCheck']);
    Route::get('/update', [AdminController::class, 'update'])->name('admin.update');
    Route::post('/update-core', [AdminController::class, 'updateCore'])->name('admin.update.core');

    // Tools
    Route::get('/phpmyadmin', [AdminController::class, 'phpmyadminPanel'])->name('admin.phpmyadmin');
    Route::post('/phpmyadmin/toggle', [AdminController::class, 'phpmyadminStatusToggle'])->name('admin.phpmyadmin.toggle');
    Route::get('/phpinfo', [AdminController::class, 'phpinfoPanel'])->name('admin.phpinfo');
    Route::get('/phpinfo/view', [AdminController::class, 'phpinfoView'])->name('admin.phpinfo.view');

    // Security
    //Route::get('/security', [AdminController::class, 'security'])->name('admin.security');
    //Route::get('/security/report', [AdminController::class, 'securityReport'])->name('admin.security.report');
    Route::post('/security/toggle', [AdminController::class, 'securityStatusToggle'])->name('admin.security.toggle');

    // Server
    Route::get('/server/status', [AdminController::class, 'serverStatus'])->name('admin.server.status');
    Route::post('/server/status/toggle', [AdminController::class, 'serverStatusToggle'])->name('admin.server.toggle');

    Route::get('/server/ssh', [AdminController::class, 'serverSSH'])->name('admin.server.ssh');

});


Route::get('/', function () {

    $tpl     = app(\App\Services\PeteOption::class)->get_meta_value('domain_template');
    
    if ($tpl === "petelocal.net") {
        return redirect('/dev-playground-welcome');
    }

    return redirect('/sites');
});

Route::post('login', 'Auth\LoginController@login')->middleware('throttle:login');

//AUTH ROUTES
Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('login', 'Auth\LoginController@login');
Route::get('/logout', 'Auth\LoginController@logout');
Route::get('/register', 'Auth\RegisterController@register');
Route::get('register', 'Auth\RegisterController@showRegistrationForm')->name('register');
Route::post('register', 'Auth\RegisterController@register');
Route::get('logout', '\App\Http\Controllers\Auth\LoginController@logout');
// WordPress Importer — migrated from peteconsuegra/wordpress-importer-plugin
Route::middleware(["web"])->group(function () {
    Route::get("/wordpress-importer", [\App\Http\Controllers\WordPressImporterController::class, "create"])->name("wpimport.create");
    Route::post("/wordpress-importer", [\App\Http\Controllers\WordPressImporterController::class, "store"])->name("wpimport.store");
    Route::get("/wordpress-importer/status/{id}", [\App\Http\Controllers\WordPressImporterController::class, "status"])->name("wpimport.status");
    Route::post("/wordpress-importer/upload-chunk", [\App\Http\Controllers\WordPressImporterController::class, "upload"])->name("wpimport.chunk.upload");
    Route::delete("/wordpress-importer/upload-chunk/abort", [\App\Http\Controllers\WordPressImporterController::class, "abort"])->name("wpimport.chunk.abort");
});
