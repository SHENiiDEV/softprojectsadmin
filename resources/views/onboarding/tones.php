<?php

/*
 * Tailwind class maps for the onboarding UI (status badges, row accents, provider chips).
 *
 * They live under resources/views so Tailwind sees the full class names (see `content` in tailwind.config.js).
 * Use in Blade: $tones = include resource_path('views/onboarding/tones.php');
 */

return [
    'status' => [
        'Not started' => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
        'In progress' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/30 dark:text-sky-400 dark:border-sky-900/50',
        'KYB in process' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/30 dark:text-indigo-400 dark:border-indigo-900/50',
        'Waiting for update' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/50',
        'Waiting for website completion' => 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-950/30 dark:text-orange-400 dark:border-orange-900/50',
        'Blocked' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/30 dark:text-rose-400 dark:border-rose-900/50',
        'Under review' => 'bg-violet-50 text-violet-700 border-violet-200 dark:bg-violet-950/30 dark:text-violet-400 dark:border-violet-900/50',
        'Approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/50',
        'Rejected' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/30 dark:text-rose-400 dark:border-rose-900/50',
        'On hold' => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
        'Completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/50',
    ],

    'accent' => [
        'Not started' => 'border-l-slate-300 dark:border-l-slate-600',
        'In progress' => 'border-l-sky-400',
        'KYB in process' => 'border-l-indigo-400',
        'Waiting for update' => 'border-l-amber-400',
        'Waiting for website completion' => 'border-l-orange-400',
        'Blocked' => 'border-l-rose-500',
        'Under review' => 'border-l-violet-400',
        'Approved' => 'border-l-emerald-500',
        'Rejected' => 'border-l-rose-500',
        'On hold' => 'border-l-slate-300 dark:border-l-slate-600',
        'Completed' => 'border-l-emerald-500',
    ],

    'provider' => [
        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/50',
        'sky' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/30 dark:text-sky-400 dark:border-sky-900/50',
        'amber' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/50',
        'rose' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/30 dark:text-rose-400 dark:border-rose-900/50',
        'slate' => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
    ],
];
