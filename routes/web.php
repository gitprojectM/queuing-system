<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\ClientQueueStatusController;
use App\Http\Controllers\MultiServiceQueueController;
use App\Http\Controllers\PublicQueueController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserAssignController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\UserListController;
use App\Http\Controllers\WindowController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ─── Home ────────────────────────────────────────────────────────────────────
Route::get('/', fn() => Inertia::render('Welcome'))->name('home');

// ─── Public: Now Serving display ─────────────────────────────────────────────
Route::get('/now-serving',                  [PublicQueueController::class, 'index'])->name('public.queue');
Route::get('/now-serving/select',           [PublicQueueController::class, 'selectServices'])->name('public.queue.select');
Route::get('/now-serving/window/{window}',  [PublicQueueController::class, 'windowView'])->name('public.queue.window');
Route::get('/now-serving/{service}',        [PublicQueueController::class, 'serviceView'])->name('public.queue.service');

// ─── Public API: polling fallback for Now Serving ───────────────────────────
Route::get('/api/now-serving', [PublicQueueController::class, 'data'])->name('api.now-serving');

// ─── Public: Queue registration (rate-limited to 15 req/min per IP) ─────────
Route::middleware('throttle:15,1')->group(function () {
    Route::get('/queue/register',      [QueueController::class, 'showRegisterForm'])->name('queue.register');
    Route::post('/queue/register',     [QueueController::class, 'register']);
    Route::post('/queue/multi-service',[MultiServiceQueueController::class, 'store'])->name('queue.multi.store');
});

// ─── Public: Queue ticket & client status ───────────────────────────────────
Route::get('/queue/ticket/{queue}',          [QueueController::class, 'ticket'])->name('queue.ticket');
Route::get('/queue/status/{queue}',          [ClientQueueStatusController::class, 'show'])->name('queue.status');
Route::get('/api/queue/status/{queue}',      [ClientQueueStatusController::class, 'data'])->name('api.queue.status');
Route::post('/queue/cancel/{queue}',         [ClientQueueStatusController::class, 'cancel'])->name('queue.cancel');

// ─── Authenticated: client queue view ───────────────────────────────────────
Route::get('/my-queue', [\App\Http\Controllers\ClientQueueController::class, 'index'])
    ->middleware('auth')
    ->name('client.queue');

// ─── Authenticated: operator actions ────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::post('/queue/complete', [QueueController::class, 'completeQueue'])->name('queue.complete');
    Route::post('/queue/next',     [QueueController::class, 'nextQueue'])->name('queue.next');
    Route::get('/queue/monitor',   [QueueController::class, 'monitor'])->name('queue.monitor');
    Route::get('/queue/list',      [QueueController::class, 'list'])->name('queue.list');
});

// ─── Admin ───────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'admin', 'verified'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/admin',     fn() => Inertia::render('AdminDashboard'))->name('admin.dashboard');

    Route::resource('services', ServiceController::class);
    Route::resource('windows',  WindowController::class);

    Route::get('/users',              [UserListController::class, 'index'])->name('users.list');
    Route::get('/users/{user}/edit',  [UserListController::class, 'edit'])->name('users.edit');
    Route::post('/users/{user}/edit', [UserListController::class, 'update'])->name('users.update');

    Route::get('/users/assign',  [UserAssignController::class, 'create'])->name('users.assign');
    Route::post('/users/assign', [UserAssignController::class, 'store']);
});

// ─── User (operator) dashboard ───────────────────────────────────────────────
Route::get('/user', [UserDashboardController::class, 'index'])
    ->middleware(['auth', 'user'])
    ->name('user.dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
