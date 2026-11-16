<?php

/*
|--------------------------------------------------------------------------
| Application navigation
|--------------------------------------------------------------------------
|
| Single source of truth for the desktop sidebar, the mobile drawer and the
| bottom bar. Item keys:
|   key        unique id (used by `bottom`)
|   label      visible text
|   route      named route
|   active     route name pattern(s) that mark the item as current
|   short      optional shorter label for the bottom bar
|   icon       Font Awesome class
|   feature    config('features.*') key(s) that must all be enabled
|   permission Spatie permission the user must have
|   role       Spatie role the user must have
|
*/

return [
    'groups' => [
        [
            'label' => null,
            'items' => [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'fa-gauge-high'],
                ['key' => 'my-work', 'label' => 'My Work', 'route' => 'my.work', 'active' => 'my.work', 'icon' => 'fa-user-check', 'feature' => 'my_work', 'permission' => 'view_tasks'],
            ],
        ],
        [
            'label' => 'Work',
            'items' => [
                ['key' => 'tasks', 'label' => 'Kanban Board', 'short' => 'Tasks', 'route' => 'tasks.kanban', 'active' => 'tasks.*', 'icon' => 'fa-list-check', 'feature' => 'kanban', 'permission' => 'view_tasks'],
                ['key' => 'deadlines', 'label' => 'Deadlines', 'route' => 'deadlines', 'active' => 'deadlines', 'icon' => 'fa-hourglass-half', 'feature' => 'deadline_center', 'permission' => 'view_deadlines'],
                ['key' => 'calendar', 'label' => 'Calendar', 'route' => 'calendar', 'active' => 'calendar', 'icon' => 'fa-calendar-days', 'feature' => 'calendar', 'permission' => 'view_calendar'],
            ],
        ],
        [
            'label' => 'Companies',
            'items' => [
                ['key' => 'projects', 'label' => 'Companies', 'route' => 'projects.index', 'active' => 'projects.*', 'icon' => 'fa-building', 'permission' => 'view_projects'],
                ['key' => 'clients', 'label' => 'Clients', 'route' => 'clients.index', 'active' => 'clients.*', 'icon' => 'fa-users', 'feature' => 'clients', 'permission' => 'view_clients'],
                ['key' => 'health', 'label' => 'Health Score', 'route' => 'health.score', 'active' => 'health.score', 'icon' => 'fa-heart-pulse', 'feature' => 'health_score', 'permission' => 'view_projects'],
            ],
        ],
        [
            'label' => 'Compliance & Finance',
            'items' => [
                ['key' => 'pci-dss', 'label' => 'PCI DSS', 'route' => 'pci-dss.index', 'active' => 'pci-dss.*', 'icon' => 'fa-shield-halved', 'feature' => 'pci_dss', 'permission' => 'view_pci_dss'],
                ['key' => 'tariffs', 'label' => 'Tariffs & Matrix', 'route' => 'tariffs.index', 'active' => 'tariffs.*', 'icon' => 'fa-scale-balanced', 'feature' => 'tariffs', 'permission' => 'view_tariffs'],
                ['key' => 'credentials', 'label' => 'Credentials', 'route' => 'credentials', 'active' => 'credentials', 'icon' => 'fa-key', 'feature' => 'credential_vault', 'permission' => 'view_credentials'],
            ],
        ],
        [
            'label' => 'Insights',
            'items' => [
                ['key' => 'activity', 'label' => 'Activity', 'route' => 'activity.center', 'active' => 'activity.center', 'icon' => 'fa-clock-rotate-left', 'feature' => 'activity_center', 'permission' => 'view_activity'],
                ['key' => 'time-report', 'label' => 'Time Reports', 'route' => 'reports.time', 'active' => 'reports.time', 'icon' => 'fa-stopwatch', 'feature' => 'time_tracking', 'permission' => 'view_reports'],
                ['key' => 'productivity', 'label' => 'Productivity', 'route' => 'reports.productivity', 'active' => 'reports.productivity', 'icon' => 'fa-chart-line', 'feature' => ['time_tracking', 'productivity_report'], 'permission' => 'view_reports'],
            ],
        ],
        [
            'label' => 'Administration',
            'items' => [
                ['key' => 'email-templates', 'label' => 'Email Templates', 'route' => 'settings.email-templates', 'active' => 'settings.email-templates', 'icon' => 'fa-file-invoice', 'permission' => 'view_tasks'],
                ['key' => 'users', 'label' => 'Users', 'route' => 'users.index', 'active' => 'users.*', 'icon' => 'fa-user-gear', 'feature' => 'users', 'permission' => 'manage_users'],
                ['key' => 'settings', 'label' => 'Settings', 'route' => 'settings', 'active' => 'settings', 'icon' => 'fa-gear', 'feature' => 'settings_panel', 'role' => 'admin'],
            ],
        ],
    ],

    /*
    | Items pinned to the mobile bottom bar (in order). Unavailable ones are
    | skipped; a "More" button that opens the drawer is always appended.
    */
    'bottom' => ['dashboard', 'projects', 'tasks', 'my-work'],
];
