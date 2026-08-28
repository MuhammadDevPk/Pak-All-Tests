<?php

use App\Http\Controllers\ContentModuleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DomainDashboardController;
use App\Http\Controllers\McqExecutionController;
use App\Http\Controllers\SubscriptionReceiptController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/d/{domainSlug}', DomainDashboardController::class)->name('domain.dashboard');
    Route::get('/api/modules/{module}', ContentModuleController::class)->name('modules.show');
    Route::post('/api/modules/{module}/submit', McqExecutionController::class)->name('modules.submit');
    Route::post('/subscription/receipt', SubscriptionReceiptController::class)->name('subscription.receipt.upload');
});

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
