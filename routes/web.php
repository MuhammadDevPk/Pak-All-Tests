<?php

use App\Http\Controllers\AiTutorController;
use App\Http\Controllers\ContentModuleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DomainDashboardController;
use App\Http\Controllers\McqExecutionController;
use App\Http\Controllers\SubscriptionReceiptController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'Homepage is working without database queries!';
})->name('home');

Route::get('/ping', function () {
    return 'Ping is working without database queries!';
});

Route::middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/d/{domainSlug}', DomainDashboardController::class)->name('domain.dashboard');
    Route::get('/api/modules/{module}', ContentModuleController::class)->name('modules.show');
    Route::post('/api/modules/{module}/submit', McqExecutionController::class)->name('modules.submit');
    Route::post('/subscription/receipt', SubscriptionReceiptController::class)->name('subscription.receipt.upload');

    // AI Exam Mentor Routes
    Route::get('/api/domains/{domainSlug}/ai-tutor/messages', [AiTutorController::class, 'history'])->name('ai-tutor.history');
    Route::post('/api/domains/{domainSlug}/ai-tutor/ask', [AiTutorController::class, 'ask'])->middleware('throttle:15,1')->name('ai-tutor.ask');
    Route::post('/api/domains/{domainSlug}/ai-tutor/clear', [AiTutorController::class, 'clear'])->name('ai-tutor.clear');
});

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
