<div class="space-y-6">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800/40">
        <h3 class="font-outfit font-bold text-base text-slate-800 dark:text-white">Activity Log & Changelog</h3>
        <span class="text-xs text-slate-400 dark:text-slate-500">History of status & metadata updates</span>
    </div>

    <div class="flow-root mt-6">
        <ul role="list" class="-mb-8">
            @forelse($activities as $activity)
                <li>
                    <div class="relative pb-8">
                        @if (!$loop->last)
                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-slate-100 dark:bg-slate-800/80" aria-hidden="true"></span>
                        @endif
                        <div class="relative flex space-x-3">
                            <div>
                                <span class="h-8 w-8 rounded-xl flex items-center justify-center ring-8 ring-white dark:ring-slate-900 bg-sky-50 dark:bg-sky-950/30 text-sky-500 shadow-sm border border-sky-100/50 dark:border-sky-900/30">
                                    @if ($activity->action === 'credential_viewed')
                                        <i class="fa-solid fa-eye text-xs text-indigo-500"></i>
                                    @elseif ($activity->action === 'project_updated')
                                        <i class="fa-solid fa-rotate text-xs text-sky-500"></i>
                                    @elseif ($activity->action === 'director_updated')
                                        <i class="fa-solid fa-user-tie text-xs text-amber-500"></i>
                                    @elseif ($activity->action === 'compliance_updated')
                                        <i class="fa-solid fa-shield text-xs text-emerald-500"></i>
                                    @else
                                        <i class="fa-solid fa-circle-info text-xs text-slate-500"></i>
                                    @endif
                                </span>
                            </div>
                            <div class="flex-1 min-w-0 pt-1.5 flex justify-between space-x-4">
                                <div>
                                    <p class="text-sm text-slate-600 dark:text-slate-350 leading-relaxed">
                                        {!! preg_replace("/'([^']+)'/", "'<span class=\"font-bold text-slate-800 dark:text-white\">$1</span>'", e($activity->description)) !!}
                                    </p>
                                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 flex items-center gap-1.5">
                                        <i class="fa-solid fa-user text-[10px]"></i>
                                        <span>Triggered by: <span class="font-semibold text-slate-650 dark:text-slate-400">{{ $activity->user?->name ?? ($activity->client?->name ? $activity->client->name . ' (Client Portal)' : 'System') }}</span></span>
                                    </p>
                                </div>
                                <div class="text-right text-xs whitespace-nowrap text-slate-400 dark:text-slate-500">
                                    <time datetime="{{ $activity->created_at }}">{{ $activity->created_at->diffForHumans() }}</time>
                                    <span class="block text-[10px] text-slate-300 dark:text-slate-600 mt-0.5">{{ $activity->created_at->format('d M, H:i') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            @empty
                <div class="text-center py-8">
                    <div class="inline-flex h-12 w-12 rounded-2xl bg-slate-50 dark:bg-slate-950/65 items-center justify-center text-slate-400 dark:text-slate-600 mb-3 border border-slate-100 dark:border-slate-800">
                        <i class="fa-solid fa-timeline text-lg"></i>
                    </div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">No activity logged for this company yet.</p>
                </div>
            @endforelse
        </ul>
    </div>
</div>
