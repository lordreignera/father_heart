<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DistrictController;
use App\Http\Controllers\Admin\SubcountyController;
use App\Http\Controllers\Admin\PersonController;
use App\Http\Controllers\Admin\AssessmentController;
use App\Http\Controllers\Admin\MediaConsentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DistributionEventController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\IntakeCodeSettingsController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\HistoricalStockController;
use App\Http\Controllers\Admin\InventoryMovementHistoryController;
use App\Http\Controllers\Admin\HistoricalRecordController;
use App\Http\Controllers\Admin\HistoricalTeamController;
use App\Http\Controllers\Admin\HistoricalFeedbackController;
use App\Http\Controllers\Admin\StaffRegistrationReviewController;
use App\Http\Controllers\Admin\TherapyChildController;
use App\Http\Controllers\Admin\TherapyServiceController;
use App\Http\Controllers\Admin\TherapyVisitController;
use App\Http\Controllers\Admin\TherapyReportController;
use App\Http\Controllers\Admin\TherapyDocumentController;
use App\Http\Controllers\Admin\TherapyOptionController;
use App\Http\Controllers\Admin\TherapyCatalogueController;
use App\Http\Controllers\Admin\TestimonyVideoController;
use App\Http\Controllers\StaffRegistrationController;
use App\Models\Subcounty;
use App\Http\Middleware\AuthorizeAdminRoute;
use App\Http\Middleware\PrivateTherapyResponse;
use Illuminate\Http\Request;

Route::view('/', 'website.home')->name('home');
// Keep existing public URLs working while the website lives on one page.
Route::redirect('/about', '/#about')->name('website.about');
Route::redirect('/our-work', '/#work')->name('website.work');
Route::redirect('/stories', '/#stories')->name('website.stories');
Route::redirect('/support', '/#give')->name('website.support');
Route::redirect('/contact', '/#contact')->name('website.contact');
Route::middleware('guest')->group(function () {
    Route::get('/staff-registration', [StaffRegistrationController::class, 'create'])->name('staff-registration.create');
    Route::post('/staff-registration', [StaffRegistrationController::class, 'store'])->middleware('throttle:5,1')->name('staff-registration.store');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'can:access-admin',
])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::view('/user-guide', 'admin.guide')->name('user-guide');
    Route::get('/user-guide/download', function () {
        return response()->view('admin.guide-download')->header('Content-Disposition', 'attachment; filename="fathers-heart-user-guide.html"')->header('Cache-Control', 'private, no-store');
    })->name('user-guide.download');
});

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified', 'can:access-admin', AuthorizeAdminRoute::class])
    ->prefix('admin')->name('admin.')->group(function () {
        Route::middleware([])->group(function () {
            Route::get('historical/team', [HistoricalTeamController::class, 'index'])->name('historical.team.index');
            Route::put('historical/team/{record}', [HistoricalTeamController::class, 'update'])->name('historical.team.update');
            Route::post('historical/team/{record}/account', [HistoricalTeamController::class, 'createAccount'])->name('historical.team.account');
            Route::post('historical/team/{record}/link', [HistoricalTeamController::class, 'linkAccount'])->name('historical.team.link');
            Route::put('historical/team/{record}/password', [HistoricalTeamController::class, 'resetPassword'])->name('historical.team.password');
            Route::delete('historical/team/{record}', [HistoricalTeamController::class, 'archive'])->name('historical.team.archive');
            Route::post('historical/team/{record}/restore', [HistoricalTeamController::class, 'restore'])->name('historical.team.restore');
        });
        Route::middleware([])->group(function () {
            Route::get('historical/feedback', [HistoricalFeedbackController::class, 'index'])->name('historical.feedback.index');
            Route::get('historical/feedback/export', [HistoricalFeedbackController::class, 'export'])->name('historical.feedback.export');
            Route::put('historical/feedback/{record}', [HistoricalFeedbackController::class, 'update'])->name('historical.feedback.update');
            Route::delete('historical/feedback/{record}', [HistoricalFeedbackController::class, 'archive'])->name('historical.feedback.archive');
            Route::post('historical/feedback/{record}/restore', [HistoricalFeedbackController::class, 'restore'])->name('historical.feedback.restore');
        });
        Route::get('historical/{category}', [HistoricalRecordController::class, 'index'])->name('historical.index');
        Route::get('location-options/subcounties', function (Request $request) {
            $request->validate(['district_id' => ['required', 'integer', 'exists:districts,id'], 'selected_id' => ['nullable', 'integer']]);
            return Subcounty::withTrashed()->where('district_id', $request->integer('district_id'))
                ->where(function ($query) use ($request) {
                    $query->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at'));
                    if ($request->filled('selected_id')) $query->orWhere('id', $request->integer('selected_id'));
                })
                ->orderBy('name')->get(['id', 'name', 'kind']);
        })->name('location-options.subcounties');
        Route::get('testimonies', [TestimonyVideoController::class, 'index'])->name('testimonies.index');
        Route::post('testimonies', [TestimonyVideoController::class, 'store'])->name('testimonies.store');
        Route::get('testimonies/{testimony}/video', [TestimonyVideoController::class, 'show'])->name('testimonies.show');
        Route::middleware([])->group(function () {
            Route::resource('districts', DistrictController::class)->except('show');
            Route::resource('subcounties', SubcountyController::class)->except('show');
            Route::get('people/export', [PersonController::class, 'export'])->name('people.export');
            Route::resource('people', PersonController::class);
            Route::resource('assessments', AssessmentController::class)->except('destroy');
            Route::get('assessment-photos/{photo}', [AssessmentController::class, 'photo'])->name('assessment-photos.show');
            Route::get('assessments/{assessment}/historical-photo/{kind}', [AssessmentController::class, 'legacyPhoto'])->name('assessments.legacy-photo');
            Route::post('assessments/{assessment}/consents', [MediaConsentController::class, 'store'])->name('assessments.consents.store');
            Route::get('media-consents/{mediaConsent}/download', [MediaConsentController::class, 'download'])->name('media-consents.download');
        });
        Route::middleware([PrivateTherapyResponse::class])->group(function () {
            Route::get('therapy/children', [TherapyChildController::class, 'index'])->name('therapy.children.index');
            Route::get('therapy/children/create', [TherapyChildController::class, 'create'])->name('therapy.children.create');
            Route::post('therapy/children', [TherapyChildController::class, 'store'])->name('therapy.children.store');
            Route::get('therapy/children/{child}', [TherapyChildController::class, 'show'])->name('therapy.children.show');
            Route::get('therapy/children/{child}/edit', [TherapyChildController::class, 'edit'])->name('therapy.children.edit');
            Route::put('therapy/children/{child}', [TherapyChildController::class, 'update'])->name('therapy.children.update');
            Route::post('therapy/children/{child}/documents', [TherapyDocumentController::class, 'store'])->name('therapy.documents.store');
            Route::get('therapy/documents/{document}/download', [TherapyDocumentController::class, 'download'])->name('therapy.documents.download');
            Route::post('therapy/children/{child}/visits', [TherapyVisitController::class, 'store'])->name('therapy.visits.store');
            Route::get('therapy/visits/{visit}', [TherapyVisitController::class, 'show'])->name('therapy.visits.show');
            Route::get('therapy/visits/{visit}/edit', [TherapyVisitController::class, 'edit'])->name('therapy.visits.edit');
            Route::put('therapy/visits/{visit}', [TherapyVisitController::class, 'update'])->name('therapy.visits.update');
            Route::get('therapy/reports', [TherapyReportController::class, 'index'])->name('therapy.reports.index');
            Route::get('therapy/reports/export', [TherapyReportController::class, 'export'])->name('therapy.reports.export');
            Route::get('therapy/services', [TherapyServiceController::class, 'index'])->name('therapy.services.index');
            Route::post('therapy/services', [TherapyServiceController::class, 'store'])->name('therapy.services.store');
            Route::put('therapy/services/{service}', [TherapyServiceController::class, 'update'])->name('therapy.services.update');
            Route::get('therapy/options', [TherapyOptionController::class, 'index'])->name('therapy.options.index');
            Route::post('therapy/options', [TherapyOptionController::class, 'store'])->name('therapy.options.store');
            Route::put('therapy/options/{option}', [TherapyOptionController::class, 'update'])->name('therapy.options.update');
            Route::get('therapy/catalogues/{catalogue}', [TherapyCatalogueController::class, 'index'])->name('therapy.catalogues.index');
            Route::post('therapy/catalogues/{catalogue}', [TherapyCatalogueController::class, 'store'])->name('therapy.catalogues.store');
            Route::put('therapy/catalogues/{catalogue}/{entry}', [TherapyCatalogueController::class, 'update'])->name('therapy.catalogues.update');
        });
        Route::middleware([])->group(function () {
            Route::resource('distributions', DistributionEventController::class);
        });
        Route::middleware([])->group(function () {
            Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
            Route::get('inventory/historical', [HistoricalStockController::class, 'index'])->name('inventory.historical.index');
            Route::get('inventory/historical/export', [HistoricalStockController::class, 'export'])->name('inventory.historical.export');
            Route::get('inventory/movements', [InventoryMovementHistoryController::class, 'index'])->name('inventory.movements.index');
            Route::get('inventory/movements/export', [InventoryMovementHistoryController::class, 'export'])->name('inventory.movements.export');
            Route::post('inventory/items', [InventoryController::class, 'storeItem'])->name('inventory.items.store');
            Route::put('inventory/items/{item}', [InventoryController::class, 'updateItem'])->name('inventory.items.update');
            Route::delete('inventory/items/{item}', [InventoryController::class, 'destroyItem'])->name('inventory.items.destroy');
            Route::post('inventory/items/{item}/movements', [InventoryController::class, 'storeMovement'])->name('inventory.movements.store');
        });
        Route::middleware([])->group(function () {
            Route::get('registration-requests', [StaffRegistrationReviewController::class, 'index'])->name('registration-requests.index');
            Route::post('registration-requests/{registration}/approve', [StaffRegistrationReviewController::class, 'approve'])->name('registration-requests.approve');
            Route::post('registration-requests/{registration}/reject', [StaffRegistrationReviewController::class, 'reject'])->name('registration-requests.reject');
            Route::resource('users', UserController::class)->parameters(['users' => 'user']);
        });
        Route::middleware([])->group(function () {
            Route::get('settings/intake-code', [IntakeCodeSettingsController::class, 'edit'])->name('settings.intake-code.edit');
            Route::put('settings/intake-code', [IntakeCodeSettingsController::class, 'update'])->name('settings.intake-code.update');
            Route::put('roles/{role}/permissions', [PermissionController::class, 'updateRolePermissions'])->name('roles.permissions.update');
            Route::resource('roles', RoleController::class)->except('show');
            Route::resource('permissions', PermissionController::class)->except('show');
        });
    });
