<?php

use App\Http\Controllers\Api\GmailPushController;
use App\Http\Controllers\Api\GmailWebhookController;
use App\Http\Controllers\ProjectPdfController;
use App\Http\Controllers\TelegramWebhookController;
use App\Livewire\ActivityCenter;
use App\Livewire\CalendarView;
use App\Livewire\ClientPortal;
use App\Livewire\Clients\Index;
use App\Livewire\CompanyHealthScore;
use App\Livewire\CredentialVault;
use App\Livewire\DeadlineCenter;
use App\Livewire\MyWork;
use App\Livewire\Projects\Create as ProjectsCreate;
use App\Livewire\Projects\Edit as ProjectsEdit;
use App\Livewire\Projects\Index as ProjectsIndex;
use App\Livewire\Projects\Show as ProjectsShow;
use App\Livewire\Reports\ProductivityReport;
use App\Livewire\Reports\TimeReport;
use App\Livewire\Settings;
use App\Livewire\Tasks\KanbanBoard;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/clients', Index::class)->middleware(['feature:clients'])->name('clients.index');

    Route::get('/projects', ProjectsIndex::class)->middleware('permission:view_projects')->name('projects.index');
    Route::get('/projects/create', ProjectsCreate::class)->middleware('permission:manage_projects')->name('projects.create');
    Route::get('/projects/{project}', ProjectsShow::class)->middleware('permission:view_projects')->name('projects.show');
    Route::get('/projects/{project}/edit', ProjectsEdit::class)->middleware('permission:manage_projects')->name('projects.edit');
    Route::get('/projects/{project}/pdf', [ProjectPdfController::class, 'export'])->middleware(['feature:pdf_export', 'permission:view_projects'])->name('projects.pdf');

    Route::get('/tasks', KanbanBoard::class)->middleware(['feature:kanban', 'permission:view_tasks'])->name('tasks.kanban');

    Route::get('/users', App\Livewire\Users\Index::class)->middleware(['feature:users', 'permission:manage_users'])->name('users.index');

    Route::get('/reports/time', TimeReport::class)->middleware(['feature:time_tracking', 'permission:view_reports'])->name('reports.time');
    Route::get('/reports/productivity', ProductivityReport::class)->middleware(['feature:time_tracking', 'permission:view_reports'])->name('reports.productivity');

    Route::view('profile', 'profile')->name('profile');
});

Route::get('/portal/{hash}', ClientPortal::class)->middleware('feature:client_portal')->name('client.portal');

Route::post('/telegram/webhook', TelegramWebhookController::class)
    ->name('telegram.webhook');

Route::post('/api/v1/webhooks/gmail-alert', GmailWebhookController::class)->name('gmail.alert-webhook');
Route::post('/api/v1/gmail/pubsub-webhook', GmailPushController::class)->name('gmail.pubsub-webhook');

require __DIR__.'/auth.php';

// New dashboard modules routes
Route::middleware(['auth'])->group(function () {
    Route::get('/deadlines', DeadlineCenter::class)->middleware(['feature:deadline_center', 'permission:view_deadlines'])->name('deadlines');
    Route::get('/health-score', CompanyHealthScore::class)->middleware(['feature:health_score', 'permission:view_projects'])->name('health.score');
    Route::get('/my-work', MyWork::class)->middleware(['feature:my_work', 'permission:view_tasks'])->name('my.work');
    Route::get('/activity', ActivityCenter::class)->middleware(['feature:activity_center', 'permission:view_activity'])->name('activity.center');
    Route::get('/credentials', CredentialVault::class)->middleware(['feature:credential_vault'])->name('credentials');
    Route::get('/settings', Settings::class)->middleware('feature:settings_panel')->name('settings');
    Route::get('/calendar', CalendarView::class)->middleware(['feature:calendar', 'permission:view_calendar'])->name('calendar');
});
