<div class="space-y-5">
    <x-flash-message />

    {{-- Overall progress --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/40 border border-slate-100 dark:border-slate-800/60">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Profiles created</div>
            <div class="font-outfit font-bold text-2xl text-slate-800 dark:text-white mt-1">{{ $totals['profiles'] }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/40 border border-slate-100 dark:border-slate-800/60">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Posts made</div>
            <div class="font-outfit font-bold text-2xl text-slate-800 dark:text-white mt-1">{{ $totals['posted'] }}<span class="text-sm text-slate-400"> / {{ $totals['profiles'] }}</span></div>
            <div class="mt-2 h-1.5 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden"><div class="h-full bg-sky-500 rounded-full" style="width: {{ $totals['profiles'] ? round($totals['posted'] / $totals['profiles'] * 100) : 0 }}%"></div></div>
        </div>
        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/40 border border-slate-100 dark:border-slate-800/60">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Checked</div>
            <div class="font-outfit font-bold text-2xl text-slate-800 dark:text-white mt-1">{{ $totals['checked'] }}<span class="text-sm text-slate-400"> / {{ $totals['profiles'] }}</span></div>
            <div class="mt-2 h-1.5 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden"><div class="h-full bg-emerald-500 rounded-full" style="width: {{ $totals['profiles'] ? round($totals['checked'] / $totals['profiles'] * 100) : 0 }}%"></div></div>
        </div>
    </div>

    @forelse($rows as $row)
        @php
            $website = $row['website'];
            $created = collect($row['profiles'])->where('status', 'created');
        @endphp
        <section wire:key="smm-site-{{ $website->id }}" class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3.5 border-b border-slate-100 dark:border-slate-800/60 bg-slate-50/60 dark:bg-slate-950/30">
                <div class="min-w-0">
                    <div class="font-semibold text-sm text-slate-800 dark:text-white truncate"><i class="fa-solid fa-globe text-slate-400 mr-1.5"></i>{{ $website->name ?: $website->url }}</div>
                    <a href="{{ $website->url }}" target="_blank" rel="noopener" class="text-[11px] text-indigo-600 dark:text-indigo-400 hover:underline break-all">{{ $website->url }}</a>
                </div>
                @if($created->isNotEmpty())
                    <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                        Posts {{ $created->where('post_done', true)->count() }}/{{ $created->count() }} · Checked {{ $created->where('checked', true)->count() }}/{{ $created->count() }}
                    </div>
                @endif
            </div>

            @if(empty($row['profiles']))
                <div class="px-5 py-8 text-center text-sm text-slate-400">
                    No social networks on this website yet.
                    <a href="{{ route('projects.show', $project) }}?tab=websites" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">Add them in the Websites tab</a>.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-800/60">
                                <th class="py-2.5 px-5">Network</th>
                                <th class="py-2.5 px-3">Profile</th>
                                <th class="py-2.5 px-3">Post made</th>
                                <th class="py-2.5 px-3">Checked</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40">
                            @foreach($row['profiles'] as $profile)
                                @php
                                    $isCreated = $profile['status'] === 'created';
                                    $network = $profile['network'];
                                @endphp
                                <tr wire:key="smm-{{ $website->id }}-{{ \Illuminate\Support\Str::slug($network) }}" class="{{ $isCreated ? '' : 'opacity-60' }}">
                                    <td class="py-3 px-5 whitespace-nowrap font-semibold text-slate-700 dark:text-slate-200">
                                        <i class="{{ \App\Models\Website::socialIcon($network) }} w-5 text-center text-slate-400 mr-1"></i>{{ $network }}
                                    </td>

                                    <td class="py-3 px-3">
                                        @if($isCreated)
                                            @if($profile['url'])
                                                <a href="{{ $profile['url'] }}" target="_blank" rel="noopener" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline break-all">Open profile <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i></a>
                                            @else
                                                <span class="text-xs text-slate-400">Created, no link</span>
                                            @endif
                                        @else
                                            <span class="text-xs text-slate-400">Not created yet</span>
                                        @endif
                                    </td>

                                    <td class="py-3 px-3">
                                        <div class="inline-flex items-start gap-2">
                                            <button type="button"
                                                    role="checkbox"
                                                    aria-checked="{{ $profile['post_done'] ? 'true' : 'false' }}"
                                                    aria-label="{{ $network }} post made on {{ $website->name }}"
                                                    wire:click="togglePost({{ $website->id }}, '{{ addslashes($network) }}')"
                                                    @disabled(! $isCreated || ! $canEdit)
                                                    class="mt-0.5 h-5 w-5 shrink-0 rounded-md border flex items-center justify-center transition disabled:opacity-40 disabled:cursor-not-allowed {{ $profile['post_done'] ? 'bg-sky-600 border-sky-600 text-white' : 'bg-white border-slate-300 hover:border-sky-400 dark:bg-slate-950 dark:border-slate-700' }}">
                                                @if($profile['post_done'])<i class="fa-solid fa-check text-[10px]"></i>@endif
                                            </button>
                                            <span class="text-[11px] leading-tight text-slate-500 dark:text-slate-400">
                                                @if($profile['post_done'])
                                                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $users[$profile['post_done_by']] ?? 'Someone' }}</span><br>{{ \Illuminate\Support\Carbon::parse($profile['post_done_at'])->format('d.m.Y') }}
                                                @else
                                                    {{ $isCreated ? 'Not yet' : 'Create the profile first' }}
                                                @endif
                                            </span>
                                        </div>
                                    </td>

                                    <td class="py-3 px-3">
                                        <div class="inline-flex items-start gap-2">
                                            <button type="button"
                                                    role="checkbox"
                                                    aria-checked="{{ $profile['checked'] ? 'true' : 'false' }}"
                                                    aria-label="{{ $network }} post checked on {{ $website->name }}"
                                                    wire:click="toggleChecked({{ $website->id }}, '{{ addslashes($network) }}')"
                                                    @disabled(! $isCreated || ! $profile['post_done'] || ! $canEdit)
                                                    class="mt-0.5 h-5 w-5 shrink-0 rounded-md border flex items-center justify-center transition disabled:opacity-40 disabled:cursor-not-allowed {{ $profile['checked'] ? 'bg-emerald-600 border-emerald-600 text-white' : 'bg-white border-slate-300 hover:border-emerald-400 dark:bg-slate-950 dark:border-slate-700' }}">
                                                @if($profile['checked'])<i class="fa-solid fa-check text-[10px]"></i>@endif
                                            </button>
                                            <span class="text-[11px] leading-tight text-slate-500 dark:text-slate-400">
                                                @if($profile['checked'])
                                                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $users[$profile['checked_by']] ?? 'Someone' }}</span><br>{{ \Illuminate\Support\Carbon::parse($profile['checked_at'])->format('d.m.Y') }}
                                                @else
                                                    {{ $profile['post_done'] ? 'Waiting for a check' : 'Post first' }}
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @empty
        <div class="py-12 text-center text-sm text-slate-400 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl">
            This company has no websites yet. Add a website and its social networks in the Websites tab.
        </div>
    @endforelse

    @if($legacyPosts > 0)
        <p class="text-[11px] text-slate-400">{{ $legacyPosts }} earlier scheduled {{ \Illuminate\Support\Str::plural('post', $legacyPosts) }} are kept in the <span class="font-semibold">Post log</span> below.</p>
    @endif
</div>
