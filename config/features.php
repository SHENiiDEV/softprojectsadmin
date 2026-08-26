<?php

return [
    'kanban' => env('ENABLE_KANBAN', true),
    'client_portal' => env('ENABLE_CLIENT_PORTAL', true),
    'time_tracking' => env('ENABLE_TIME_TRACKING', true),
    'credential_vault' => env('ENABLE_CREDENTIAL_VAULT', true),
    'health_score' => env('ENABLE_HEALTH_SCORE', true),
    'deadline_center' => env('ENABLE_DEADLINE_CENTER', true),
    'activity_center' => env('ENABLE_ACTIVITY_CENTER', false),
    'profile_settings' => env('ENABLE_PROFILE_SETTINGS', true),
    'pdf_export' => env('ENABLE_PDF_EXPORT', true),
    'settings_panel' => env('ENABLE_SETTINGS_PANEL', true),
    'company_changelog' => env('ENABLE_COMPANY_CHANGELOG', false),
    'global_search_notes' => env('ENABLE_GLOBAL_SEARCH_NOTES', true),
    'calendar' => env('ENABLE_CALENDAR', false),

    // New Feature Toggles
    'clients' => env('ENABLE_CLIENTS', true),
    'users' => env('ENABLE_USERS', true),
    'my_work' => env('ENABLE_MY_WORK', false),
    'websites_tab' => env('ENABLE_WEBSITES_TAB', true),
    'compliance_tab' => env('ENABLE_COMPLIANCE_TAB', true),
    'reports_tab' => env('ENABLE_REPORTS_TAB', true),
    'operations_tab' => env('ENABLE_OPERATIONS_TAB', true),
    'notes_tab' => env('ENABLE_NOTES_TAB', false),
    'smm' => env('ENABLE_SMM', false),
    'company_comments' => env('ENABLE_COMPANY_COMMENTS', false),
    'productivity_report' => env('ENABLE_PRODUCTIVITY_REPORT', false),

    // Kanban-specific Feature Toggles
    'task_comments' => env('ENABLE_TASK_COMMENTS', true),
    'client_portal_comments' => env('ENABLE_CLIENT_PORTAL_COMMENTS', true),
    'task_history' => env('ENABLE_TASK_HISTORY', false),
    'task_time_logs' => env('ENABLE_TASK_TIME_LOGS', false),
    'session_log_history' => env('ENABLE_SESSION_LOG_HISTORY', true),
    'task_attachments' => env('ENABLE_TASK_ATTACHMENTS', true),
];
