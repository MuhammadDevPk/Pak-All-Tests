<?php

use App\Http\Controllers\AiTutorController;
use App\Http\Controllers\ContentModuleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DomainDashboardController;
use App\Http\Controllers\McqExecutionController;
use App\Http\Controllers\SubscriptionReceiptController;
use App\Http\Controllers\Teams\TeamInvitationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('/ping', function () {
    return 'Ping is working without database queries!';
});

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function (Request $request) {
        $user = $request->user();
        if ($user && $user->currentTeam && $user->belongsToTeam($user->currentTeam)) {
            return app(DashboardController::class)($request);
        }

        return redirect('/#exams');
    })->name('dashboard');

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
