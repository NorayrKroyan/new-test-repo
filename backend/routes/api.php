<?php

use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Admin\CarrierController;
use App\Http\Controllers\Api\Admin\CustomerController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\DialpadSmsWebhookController;
use App\Http\Controllers\Api\Admin\JobAssignmentController;
use App\Http\Controllers\Api\Admin\JobAvailableController;
use App\Http\Controllers\Api\Admin\LeadController;
use App\Http\Controllers\Api\Admin\LeadContractController;
use App\Http\Controllers\Api\Admin\LeadMapController;
use App\Http\Controllers\Api\Admin\LeadQualificationController;
use App\Http\Controllers\Api\Admin\QualificationScriptController;
use App\Http\Controllers\Api\Admin\StageController;
use App\Http\Controllers\Api\Carrier\AuthController as CarrierAuthController;
use App\Http\Controllers\Api\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\Webhooks\BoldSignWebhookController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\BoldSignTemplateController;

Route::post('/webhooks/dialpad/sms', [DialpadSmsWebhookController::class, 'store']);
Route::post('/webhooks/boldsign', [BoldSignWebhookController::class, 'store']);

Route::middleware('web')->prefix('auth/google')->group(function () {
    Route::get('/redirect/{role}', [GoogleAuthController::class, 'redirect'])
        ->where('role', 'admin|carrier|customer');

    Route::get('/callback/{role}', [GoogleAuthController::class, 'callback'])
        ->where('role', 'admin|carrier|customer');
});

Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login']);

    // Public chart endpoint for iframe rendering
    Route::get('/leads/funnel-chart', [LeadController::class, 'funnelChart']);

    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::get('/me', [AdminAuthController::class, 'me']);
        Route::get('/dashboard', [DashboardController::class, 'index']);

        Route::apiResource('/admin-users', AdminUserController::class);

        Route::get('/leads/ad-names', [LeadController::class, 'adNames']);
        Route::post('/leads/auto-dedup', [LeadController::class, 'autoDedup']);
        Route::get('/leads/funnel-summary', [LeadController::class, 'funnelSummary']);
        Route::get('/leads/{lead}/merge-preview', [LeadController::class, 'mergePreview']);
        Route::post('/leads/{lead}/merge', [LeadController::class, 'merge']);
        Route::post('/leads/{lead}/mark-duplicate', [LeadController::class, 'markDuplicate']);
        Route::post('/leads/{lead}/unmark-duplicate', [LeadController::class, 'unmarkDuplicate']);
        Route::post('/leads/{lead}/convert-to-carrier', [LeadController::class, 'convertToCarrier']);
        Route::post('/leads/{lead}/sync-contact', [LeadController::class, 'syncContact']);
        Route::get('/leads/{lead}/call-history', [LeadController::class, 'callHistory']);
        Route::get('/leads/{lead}/sms-history', [LeadController::class, 'smsHistory']);
        Route::get('/lead-contract-templates', [LeadContractController::class, 'templateOptions']);
        Route::get('/leads/{lead}/contracts', [LeadContractController::class, 'index']);
        Route::post('/leads/{lead}/contracts/send', [LeadContractController::class, 'send']);

        Route::get('/lead-map/markers', [LeadMapController::class, 'markers']);
        Route::post('/lead-map/geocode-missing', [LeadMapController::class, 'geocodeMissing']);
        Route::post('/lead-map/leads/{lead}/geocode', [LeadMapController::class, 'geocodeLead']);

        Route::post('/leads/{lead}/qualification-sessions/start', [LeadQualificationController::class, 'start']);
        Route::get('/leads/{lead}/qualification-sessions', [LeadQualificationController::class, 'index']);
        Route::get('/qualification-sessions/{session}', [LeadQualificationController::class, 'show']);
        Route::post('/qualification-sessions/{session}/answers', [LeadQualificationController::class, 'saveAnswer']);
        Route::post('/qualification-sessions/{session}/complete', [LeadQualificationController::class, 'complete']);
        Route::post('/qualification-sessions/{session}/apply-recommended-stage', [LeadQualificationController::class, 'applyRecommendedStage']);

        Route::post('/qualification-scripts/{qualification_script}/clone', [QualificationScriptController::class, 'clone']);
        Route::put('/qualification-scripts/{qualification_script}/builder', [QualificationScriptController::class, 'saveBuilder']);
        Route::apiResource('/qualification-scripts', QualificationScriptController::class);

        Route::apiResource('/leads', LeadController::class);
        Route::apiResource('/stages', StageController::class);
        Route::apiResource('/carriers', CarrierController::class);
        Route::apiResource('/customers', CustomerController::class);
        Route::apiResource('/jobs-available', JobAvailableController::class);

        Route::get('/jobs-available/{jobs_available}/roster', [JobAssignmentController::class, 'index']);
        Route::get('/jobs-available/{jobs_available}/roster-options', [JobAssignmentController::class, 'options']);
        Route::post('/jobs-available/{jobs_available}/roster', [JobAssignmentController::class, 'store']);
        Route::put('/job-assignments/{job_assignment}', [JobAssignmentController::class, 'update']);
        Route::delete('/job-assignments/{job_assignment}', [JobAssignmentController::class, 'destroy']);
        Route::post('/job-assignments/{job_assignment}/readiness-check', [JobAssignmentController::class, 'readinessCheck']);

        Route::get('/boldsign-templates', [BoldSignTemplateController::class, 'index']);
        Route::post('/boldsign-templates/sync', [BoldSignTemplateController::class, 'sync']);
        Route::get('/boldsign-templates/{templateId}', [BoldSignTemplateController::class, 'show']);
        Route::put('/boldsign-templates/{templateId}/override', [BoldSignTemplateController::class, 'saveOverride']);
    });
});

Route::prefix('carrier')->group(function () {
    Route::post('/register', [CarrierAuthController::class, 'register']);
    Route::post('/login', [CarrierAuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'role:carrier'])->group(function () {
        Route::post('/logout', [CarrierAuthController::class, 'logout']);
        Route::get('/me', [CarrierAuthController::class, 'me']);
        Route::get('/dashboard', [CarrierAuthController::class, 'dashboard']);
        Route::put('/profile', [CarrierAuthController::class, 'updateProfile']);
        Route::post('/change-password', [CarrierAuthController::class, 'changePassword']);
    });
});

Route::prefix('customer')->group(function () {
    Route::post('/register', [CustomerAuthController::class, 'register']);
    Route::post('/login', [CustomerAuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {
        Route::post('/logout', [CustomerAuthController::class, 'logout']);
        Route::get('/me', [CustomerAuthController::class, 'me']);
        Route::get('/dashboard', [CustomerAuthController::class, 'dashboard']);
        Route::put('/profile', [CustomerAuthController::class, 'updateProfile']);
        Route::post('/change-password', [CustomerAuthController::class, 'changePassword']);
    });
});
