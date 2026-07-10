<?php

use App\Livewire\Projects\Index as ProjectsIndex;
use App\Livewire\Projects\Create as ProjectsCreate;
use App\Livewire\Projects\Edit as ProjectsEdit;
use App\Livewire\Projects\Show as ProjectsShow;
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
    Route::get('/clients', \App\Livewire\Clients\Index::class)->middleware(['feature:clients', 'permission:view_projects'])->name('clients.index');
    
    Route::get('/projects', ProjectsIndex::class)->middleware('permission:view_projects')->name('projects.index');
    Route::get('/projects/create', ProjectsCreate::class)->middleware('permission:manage_projects')->name('projects.create');
    Route::get('/projects/{project}', ProjectsShow::class)->middleware('permission:view_projects')->name('projects.show');
    Route::get('/projects/{project}/edit', ProjectsEdit::class)->middleware('permission:manage_projects')->name('projects.edit');
    Route::get('/projects/{project}/pdf', [\App\Http\Controllers\ProjectPdfController::class, 'export'])->middleware(['feature:pdf_export', 'permission:view_projects'])->name('projects.pdf');
    
    Route::get('/tasks', KanbanBoard::class)->middleware(['feature:kanban', 'permission:view_tasks'])->name('tasks.kanban');

    Route::get('/users', \App\Livewire\Users\Index::class)->middleware(['feature:users', 'permission:manage_users'])->name('users.index');

    Route::get('/reports/time', \App\Livewire\Reports\TimeReport::class)->middleware(['feature:time_tracking', 'permission:view_reports'])->name('reports.time');
    Route::get('/reports/productivity', \App\Livewire\Reports\ProductivityReport::class)->middleware(['feature:time_tracking', 'permission:view_reports'])->name('reports.productivity');

    Route::view('profile', 'profile')->name('profile');
});

Route::get('/portal/{hash}', \App\Livewire\ClientPortal::class)->middleware('feature:client_portal')->name('client.portal');

Route::post('/telegram/webhook', \App\Http\Controllers\TelegramWebhookController::class)
    ->name('telegram.webhook');

require __DIR__.'/auth.php';

// New dashboard modules routes
Route::middleware(['auth'])->group(function () {
    Route::get('/deadlines', \App\Livewire\DeadlineCenter::class)->middleware(['feature:deadline_center', 'permission:view_deadlines'])->name('deadlines');
    Route::get('/health-score', \App\Livewire\CompanyHealthScore::class)->middleware(['feature:health_score', 'permission:view_projects'])->name('health.score');
    Route::get('/my-work', \App\Livewire\MyWork::class)->middleware(['feature:my_work', 'permission:view_tasks'])->name('my.work');
    Route::get('/activity', \App\Livewire\ActivityCenter::class)->middleware(['feature:activity_center', 'permission:view_activity'])->name('activity.center');
    Route::get('/credentials', \App\Livewire\CredentialVault::class)->middleware(['feature:credential_vault', 'permission:view_credentials'])->name('credentials');
    Route::get('/settings', \App\Livewire\Settings::class)->middleware('feature:settings_panel')->name('settings');
    Route::get('/calendar', \App\Livewire\CalendarView::class)->middleware(['feature:calendar', 'permission:view_calendar'])->name('calendar');
});
