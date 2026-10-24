<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\LegalDocumentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;
use App\Models\Plan;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
Route::post('/contato', [ContactController::class, 'send'])->middleware('throttle:5,1')->name('contact.send');
Route::get('/termos-de-uso/{version?}', [LegalDocumentController::class, 'terms'])->name('legal.terms');
Route::get('/privacidade/{version?}', [LegalDocumentController::class, 'privacy'])->name('legal.privacy');
Route::get('/assinar/pro', [SubscriptionController::class, 'start'])->name('subscription.start');
Route::get('/assinar/{slug}', [SubscriptionController::class, 'start'])->name('subscription.plan.start');
Route::get('/checkout/options/pro', [SubscriptionController::class, 'options'])->name('subscription.options');
Route::get('/checkout/options/{slug}', [SubscriptionController::class, 'options'])->name('subscription.plan.options');

Route::get('/dashboard', [\App\Http\Controllers\SystemController::class, 'dashboard'])->middleware(['auth', 'verified', 'subscription'])->name('dashboard');

Route::middleware(['auth', 'verified', 'subscription'])->group(function () {
    Route::get('/account/entitlements', [WorkspaceController::class, 'entitlements'])->name('account.entitlements');
    Route::get('/account/team-support', [WorkspaceController::class, 'teamSupport'])->name('account.team-support');
    Route::get('/projects', [WorkspaceController::class, 'index'])->name('projects.index');
    Route::post('/projects', [WorkspaceController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [WorkspaceController::class, 'show'])->name('projects.show');
    Route::patch('/projects/{project}', [WorkspaceController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [WorkspaceController::class, 'destroy'])->name('projects.destroy');
    Route::get('/projects/{project}/tasks', [WorkspaceController::class, 'tasks'])->name('projects.tasks.index');
    Route::post('/projects/{project}/tasks', [WorkspaceController::class, 'createTask'])->name('projects.tasks.store');
    Route::patch('/projects/{project}/tasks/{task}', [WorkspaceController::class, 'updateTask'])->name('projects.tasks.update');
    Route::delete('/projects/{project}/tasks/{task}', [WorkspaceController::class, 'deleteTask'])->name('projects.tasks.destroy');
    Route::post('/projects/{project}/members', [WorkspaceController::class, 'addMember'])->name('projects.members.store');
    Route::patch('/projects/{project}/members/{member}', [WorkspaceController::class, 'updateMember'])->name('projects.members.update');
    Route::delete('/projects/{project}/members/{member}', [WorkspaceController::class, 'removeMember'])->name('projects.members.destroy');
    Route::post('/projects/{project}/tasks/{task}/checklist', [WorkspaceController::class, 'createChecklist'])->name('projects.checklist.store');
    Route::patch('/projects/{project}/tasks/{task}/checklist/{item}', [WorkspaceController::class, 'updateChecklist'])->name('projects.checklist.update');
    Route::delete('/projects/{project}/tasks/{task}/checklist/{item}', [WorkspaceController::class, 'deleteChecklist'])->name('projects.checklist.destroy');
    Route::get('/projects/{project}/report', [WorkspaceController::class, 'report'])->name('projects.report');
    Route::get('/projects/{project}/history', [WorkspaceController::class, 'history'])->name('projects.history');
});

// Grupo protegido por autenticação
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read', [\App\Http\Controllers\NotificationController::class, 'read'])->name('notifications.read');
    // Rotas de Perfil/login
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // [FRONT-END GUI]: Rotas de Assinatura e Checkout

    // Rotas de checkout e assinatura
    Route::get('/checkout/success', [SubscriptionController::class, 'success'])->name('subscription.success');
    Route::get('/payments/{payment}', [SubscriptionController::class, 'status'])->middleware('throttle:20,1')->name('subscription.status');
    Route::post('/payments/{payment}/simulate', [SubscriptionController::class, 'simulate'])->middleware('throttle:10,1')->name('subscription.simulate');
    Route::get('/checkout/{plan}', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::post('/checkout/process', [SubscriptionController::class, 'process'])->middleware('throttle:10,1')->name('subscription.process');
});

// Rotas do Painel Administrativo
Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/', \App\Http\Controllers\Admin\DashboardController::class)->name('dashboard');
    Route::resource('plans', AdminPlanController::class);
    Route::get('/subscribers', [\App\Http\Controllers\Admin\SubscriberController::class, 'index'])->name('subscribers.index');
    Route::patch('/subscribers/{user}', [\App\Http\Controllers\Admin\SubscriberController::class, 'update'])->name('subscribers.update');
});

Route::middleware(['auth', 'verified', 'subscription'])->group(function () {
    Route::get('/teste-projeto', function () {
        return "Parabéns! Tens uma subscrição ativa e conseguiste aceder.";
    });
});

require __DIR__.'/auth.php';

// Compatibility URLs used by the preserved system interface. All actions remain
// inside Laravel authentication, CSRF middleware and workspace policies.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/system/index.php', [\App\Http\Controllers\SystemController::class, 'legacy'])->middleware('subscription');
    Route::get('/system/actions/{group}/{action}.php', [\App\Http\Controllers\SystemActionController::class, 'read'])->middleware('subscription');
    Route::post('/system/actions/{group}/{action}.php', [\App\Http\Controllers\SystemActionController::class, 'write']);
});
