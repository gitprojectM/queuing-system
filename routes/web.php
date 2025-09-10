<?php
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\WindowController;
// use App\Http\Controllers\QueueController;
use App\Http\Controllers\UserAssignController;
use App\Http\Controllers\UserListController;
use App\Http\Controllers\ClientQueueController;
use App\Http\Controllers\PublicQueueController;
// Queue ticket page (public)
use App\Http\Controllers\QueueController;
Route::get('/queue/ticket/{queue}', [QueueController::class, 'ticket'])->name('queue.ticket');
// Public Now Serving page
Route::get('/now-serving', [PublicQueueController::class, 'index'])->name('public.queue');
// Custom service selection page (must come before the dynamic route)
Route::get('/now-serving/select', [PublicQueueController::class, 'selectServices'])->name('public.queue.select');
// Window-specific Now Serving pages
Route::get('/now-serving/window/{window}', [PublicQueueController::class, 'windowView'])->name('public.queue.window');
// Service-specific Now Serving pages (must come last due to dynamic parameter)
Route::get('/now-serving/{service}', [PublicQueueController::class, 'serviceView'])->name('public.queue.service');

Route::get('/my-queue', [ClientQueueController::class, 'index'])->middleware(['auth'])->name('client.queue');

// Complete (free) a window and mark queue as completed
Route::post('/queue/complete', [QueueController::class, 'completeQueue'])->name('queue.complete');

// Trigger next queue assignment
Route::post('/queue/next', [QueueController::class, 'nextQueue'])->name('queue.next');
// User list and edit (admin only)
Route::get('/users', [UserListController::class, 'index'])->middleware(['auth', 'admin'])->name('users.list');
Route::get('/users/{user}/edit', [UserListController::class, 'edit'])->middleware(['auth', 'admin'])->name('users.edit');
Route::post('/users/{user}/edit', [UserListController::class, 'update'])->middleware(['auth', 'admin'])->name('users.update');
// API for dashboard service summary
Route::get('/api/dashboard/services', [\App\Http\Controllers\ServiceController::class, 'dashboardServices']);

// Add user and assign to service and window (admin only)
Route::get('/users/assign', [UserAssignController::class, 'create'])->middleware(['auth', 'admin'])->name('users.assign');
Route::post('/users/assign', [UserAssignController::class, 'store'])->middleware(['auth', 'admin']);
// Queue monitoring page
Route::get('/queue/monitor', [\App\Http\Controllers\QueueController::class, 'monitor'])->name('queue.monitor');
// Client queue registration
Route::get('/queue/register', [QueueController::class, 'showRegisterForm'])->name('queue.register');
Route::post('/queue/register', [QueueController::class, 'register']);

// List of all queue registrations
Route::get('/queue/list', [QueueController::class, 'list'])->name('queue.list');

Route::resource('windows', WindowController::class)->middleware(['auth', 'admin']);

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::resource('services', ServiceController::class)->middleware(['auth', 'admin']);

// Route::resource('window', WindowController::class)->middleware(['auth']);

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'admin', 'verified'])->name('dashboard');

// Admin-only route example
Route::get('admin', function () {
    return Inertia::render('AdminDashboard');
})->middleware(['auth', 'admin'])->name('admin.dashboard');

// User-only route example
use App\Http\Controllers\UserDashboardController;

Route::get('user', [UserDashboardController::class, 'index'])
    ->middleware(['auth', 'user'])
    ->name('user.dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
