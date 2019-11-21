<?php

// Auth routes
use App\Controllers\AdminController;
use App\Controllers\ClientController;
use App\Controllers\DashboardController;
use App\Controllers\LoginController;
use App\Controllers\MediaController;
use App\Controllers\ThemeController;
use App\Controllers\UpgradeController;
use App\Controllers\UploadController;
use App\Controllers\UserController;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\CheckForMaintenanceMiddleware;
use Slim\Routing\RouteCollectorProxy;

global $app;
$app->group('', function (RouteCollectorProxy $group) {
    $group->get('/home[/page/{page}]', [DashboardController::class, 'home'])->setName('home');
    $group->get('/upload', [UploadController::class, 'webUpload'])->setName('upload.web');

    $group->group('', function (RouteCollectorProxy $group) {
        $group->get('/home/switchView', [DashboardController::class, 'switchView'])->setName('switchView');

        $group->get('/system/deleteOrphanFiles', [AdminController::class, 'deleteOrphanFiles'])->setName('system.deleteOrphanFiles');

        $group->get('/system/themes', [ThemeController::class, 'getThemes'])->setName('theme');
        $group->post('/system/theme/apply', [ThemeController::class, 'applyTheme'])->setName('theme.apply');

        $group->post('/system/lang/apply', [AdminController::class, 'applyLang'])->setName('lang.apply');

        $group->post('/system/customHead', [AdminController::class, 'applyCustomHead'])->setName('customHead.apply');

        $group->post('/system/upgrade', [UpgradeController::class, 'upgrade'])->setName('system.upgrade');
        $group->get('/system/checkForUpdates', [UpgradeController::class, 'checkForUpdates'])->setName('system.checkForUpdates');

        $group->get('/system', [AdminController::class, 'system'])->setName('system');

        $group->get('/users[/page/{page}]', [UserController::class, 'index'])->setName('user.index');
    })->add(AdminMiddleware::class);

    $group->group('/user', function (RouteCollectorProxy $group) {
        $group->get('/create', [UserController::class, 'create'])->setName('user.create');
        $group->post('/create', [UserController::class, 'store'])->setName('user.store');
        $group->get('/{id}/edit', [UserController::class, 'edit'])->setName('user.edit');
        $group->post('/{id}', [UserController::class, 'update'])->setName('user.update');
        $group->get('/{id}/delete', [UserController::class, 'delete'])->setName('user.delete');
    })->add(AdminMiddleware::class);

    $group->get('/profile', [UserController::class, 'profile'])->setName('profile');
    $group->post('/profile/{id}', [UserController::class, 'profileEdit'])->setName('profile.update');
    $group->post('/user/{id}/refreshToken', [UserController::class, 'refreshToken'])->setName('refreshToken');
    $group->get('/user/{id}/config/sharex', [ClientController::class, 'getShareXConfig'])->setName('config.sharex');
    $group->get('/user/{id}/config/script', [ClientController::class, 'getBashScript'])->setName('config.script');

    $group->post('/upload/{id}/publish', [MediaController::class, 'togglePublish'])->setName('upload.publish');
    $group->post('/upload/{id}/unpublish', [MediaController::class, 'togglePublish'])->setName('upload.unpublish');
    $group->get('/upload/{id}/raw', [MediaController::class, 'getRawById'])->add(AdminMiddleware::class)->setName('upload.raw');
    $group->map(['GET', 'POST'], '/upload/{id}/delete', [MediaController::class, 'delete'])->setName('upload.delete');
})->add(App\Middleware\CheckForMaintenanceMiddleware::class)->add(AuthMiddleware::class);

$app->get('/', [DashboardController::class, 'redirects'])->setName('root');
$app->get('/login', [LoginController::class, 'show'])->setName('login.show');
$app->post('/login', [LoginController::class, 'login'])->setName('login');
$app->map(['GET', 'POST'], '/logout', [LoginController::class, 'logout'])->setName('logout');

$app->post('/upload', [UploadController::class, 'upload'])->setName('upload');

$app->get('/{userCode}/{mediaCode}', [MediaController::class, 'show'])->setName('public');
$app->get('/{userCode}/{mediaCode}/delete/{token}', [MediaController::class, 'show'])->setName('public.delete.show')->add(CheckForMaintenanceMiddleware::class);
$app->post('/{userCode}/{mediaCode}/delete/{token}', [MediaController::class, 'deleteByToken'])->setName('public.delete')->add(CheckForMaintenanceMiddleware::class);
$app->get('/{userCode}/{mediaCode}/raw[.{ext}]', [MediaController::class, 'getRaw'])->setName('public.raw');
$app->get('/{userCode}/{mediaCode}/download', [MediaController::class, 'download'])->setName('public.download');
