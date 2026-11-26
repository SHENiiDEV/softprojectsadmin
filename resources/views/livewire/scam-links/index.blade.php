@php
    $card = 'bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl shadow-sm';
    $input = 'px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200';
    $kindLabels = ['exact' => 'exact match', 'subdomain' => 'subdomain of', 'keyword' => 'contains keyword'];
@endphp

<div class="space-y-6 animate-fade-in">
    <x-flash-message />

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
        <div>
            <h1 class="font-outfit font-extrabold text-2xl md:text-3xl text-slate-800 dark:text-white tracking-tight">Scam Links</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Sites we do not process. Paste the links you were sent for approval and see at once if any of them is on the list.</p>
        </div>
        <div class="text-sm text-slate-500 dark:text-slate-400"><span class="font-outfit font-bold text-xl text-slate-800 dark:text-white">{{ $total }}</span> in the list</div>
    </div>

    {{-- Check --}}
    <section class="{{ $card }} p-6 space-y-4" x-data="{ copied: null, copy(text, key) { navigator.clipboard.writeText(text); this.copied = key; setTimeout(() => this.copied = null, 1500); } }">
        <div class="flex items-center justify-between">
            <h2 class="font-outfit font-bold text-lg text-slate-800 dark:text-white"><i class="fa-solid fa-magnifying-glass-chart text-sky-500 mr-2"></i>Check links</h2>
            @if(trim($checkText) !== '')
                <button type="button" wire:click="clearCheck" class="text-xs font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"><i class="fa-solid fa-xmark mr-1"></i>Clear</button>
            @endif
        </div>

        <textarea wire:model.live.debounce.400ms="checkText" rows="6" placeholder="Paste links here: one per line, or any text with links in it (https://, www., paths and commas are fine)..." class="{{ $input }} w-full font-mono text-[13px] resize-y"></textarea>

        @if(trim($checkText) !== '')
            @if(count($results) === 0)
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 text-sm text-slate-500">No links found in the text.</div>
            @else
                @if(count($blocked) > 0)
                    <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/50 text-rose-800 dark:text-rose-300 flex items-start gap-3" role="alert">
                        <i class="fa-solid fa-ban text-xl mt-0.5"></i>
                        <div>
                            <p class="font-bold">Error: {{ count($blocked) }} of {{ count($results) }} {{ \Illuminate\Support\Str::plural('link', count($results)) }} {{ count($blocked) === 1 ? 'is' : 'are' }} on the scam list. Do not approve.</p>
                            <p class="text-xs opacity-80 mt-0.5">Matches are shown first, with the entry of the list they hit.</p>
                        </div>
                    </div>
                @else
                    <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/50 text-emerald-800 dark:text-emerald-300 flex items-center gap-3" role="status">
                        <i class="fa-solid fa-circle-check text-xl"></i>
                        <p class="font-bold">All clear: none of the {{ count($results) }} {{ \Illuminate\Support\Str::plural('link', count($results)) }} is on the scam list.</p>
                    </div>
                @endif

                <ul class="divide-y divide-slate-100 dark:divide-slate-800/40 border border-slate-100 dark:border-slate-800/60 rounded-xl overflow-hidden">
                    @foreach($results as $row)
                        <li wire:key="result-{{ md5($row['host']) }}" class="flex items-start gap-3 px-4 py-3 {{ $row['blocked'] ? 'bg-rose-50/60 dark:bg-rose-950/20' : '' }}">
                            @if($row['blocked'])
                                <i class="fa-solid fa-circle-xmark text-rose-500 mt-1"></i>
                            @else
                                <i class="fa-solid fa-circle-check text-emerald-500 mt-1"></i>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="font-mono text-[13px] break-all {{ $row['blocked'] ? 'font-bold text-rose-700 dark:text-rose-300' : 'text-slate-700 dark:text-slate-200' }}">{{ $row['input'] }}</p>
                                @if($row['blocked'])
                                    <p class="text-xs text-rose-600 dark:text-rose-400 mt-0.5">
                                        {{ $kindLabels[$row['kind']] ?? 'matches' }}
                                        <span class="font-mono font-bold">{{ $row['entry']->domain }}</span>
                                        @if($row['entry']->note) <span class="opacity-80">· {{ $row['entry']->note }}</span> @endif
                                    </p>
                                @endif
                            </div>
                            <span class="shrink-0 text-[11px] font-bold uppercase tracking-wider {{ $row['blocked'] ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">{{ $row['blocked'] ? 'Blocked' : 'OK' }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="flex flex-wrap gap-2">
                    @if(count($blocked) > 0)
                        <button type="button" x-on:click="copy(@js(collect($blocked)->pluck('input')->implode("\n")), 'blocked')" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/50 hover:opacity-80 transition">
                            <i class="fa-regular fa-copy mr-1"></i><span x-text="copied === 'blocked' ? 'Copied!' : 'Copy blocked ({{ count($blocked) }})'"></span>
                        </button>
                    @endif
                    @if(count($clean) > 0)
                        <button type="button" x-on:click="copy(@js(collect($clean)->pluck('input')->implode("\n")), 'clean')" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/50 hover:opacity-80 transition">
                            <i class="fa-regular fa-copy mr-1"></i><span x-text="copied === 'clean' ? 'Copied!' : 'Copy clean ({{ count($clean) }})'"></span>
                        </button>
                    @endif
                </div>
            @endif
        @endif
    </section>

    {{-- List --}}
    <section class="{{ $card }} p-6 space-y-5">
        <h2 class="font-outfit font-bold text-lg text-slate-800 dark:text-white"><i class="fa-solid fa-list-ul text-rose-500 mr-2"></i>The list</h2>

        @if($canManage)
            <form wire:submit.prevent="addLinks" class="grid grid-cols-1 md:grid-cols-12 gap-3 p-4 rounded-xl bg-slate-50 dark:bg-slate-950/40 border border-slate-100 dark:border-slate-800/60">
                <div class="md:col-span-7">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Add to the list</label>
                    <textarea wire:model="newLinks" rows="5" placeholder="One domain or link per line, as many as you like. https://, www. and paths are cut off. A word without a dot (e.g. highflybet) blocks every site containing it." class="{{ $input }} w-full font-mono text-[13px] resize-y"></textarea>
                    @error('newLinks') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div class="md:col-span-5 flex flex-col">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Note (optional)</label>
                    <input type="text" wire:model="newNote" placeholder="Reason or category, e.g. online casino" class="{{ $input }} w-full">
                    @error('newNote') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    <div class="mt-auto pt-3">
                        <button type="submit" wire:loading.attr="disabled" wire:target="addLinks" class="w-full px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-rose-500 to-pink-600 hover:from-rose-400 hover:to-pink-500 shadow-md shadow-rose-500/20 transition disabled:opacity-60">
                            <i class="fa-solid fa-plus mr-1"></i>Add to the list
                        </button>
                    </div>
                </div>
            </form>

            @if($addReport)
                <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950/40 text-sm space-y-2" role="status">
                    <div class="flex items-start justify-between gap-3">
                        <p class="font-semibold text-slate-700 dark:text-slate-200">
                            Added <span class="text-emerald-600 dark:text-emerald-400">{{ count($addReport['added']) }}</span>
                            @if(count($addReport['duplicates'])), already in the list: {{ count($addReport['duplicates']) }}@endif
                            @if(count($addReport['covered'])), covered by a parent domain: {{ count($addReport['covered']) }}@endif
                            @if(count($addReport['invalid'])), <span class="text-rose-600 dark:text-rose-400">not recognised: {{ count($addReport['invalid']) }}</span>@endif
                        </p>
                        <button type="button" wire:click="dismissReport" class="text-slate-400 hover:text-slate-600" aria-label="Dismiss"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    @if(count($addReport['duplicates']))
                        <p class="text-xs text-slate-500"><span class="font-semibold">Already there:</span> <span class="font-mono">{{ implode(', ', array_slice($addReport['duplicates'], 0, 20)) }}</span>@if(count($addReport['duplicates']) > 20) …@endif</p>
                    @endif
                    @if(count($addReport['covered']))
                        <p class="text-xs text-slate-500"><span class="font-semibold">Already blocked as a subdomain:</span>
                            <span class="font-mono">{{ collect($addReport['covered'])->take(10)->map(fn ($c) => $c['domain'].' (by '.$c['by'].')')->implode(', ') }}</span></p>
                    @endif
                    @if(count($addReport['invalid']))
                        <p class="text-xs text-rose-600 dark:text-rose-400"><span class="font-semibold">Not recognised (left in the box):</span> <span class="font-mono">{{ implode(', ', array_slice($addReport['invalid'], 0, 20)) }}</span></p>
                    @endif
                </div>
            @endif
        @endif

        <div class="relative">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search the list..." class="{{ $input }} w-full pl-9">
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-800/60">
                        <th class="py-2.5 px-3">Domain</th>
                        <th class="py-2.5 px-3">Note</th>
                        <th class="py-2.5 px-3 whitespace-nowrap">Added</th>
                        @if($canManage)<th class="py-2.5 px-3 w-10"></th>@endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40 text-sm">
                    @forelse($links as $link)
                        <tr wire:key="link-{{ $link->id }}">
                            <td class="py-2.5 px-3 font-mono text-[13px] text-slate-800 dark:text-slate-100 break-all">
                                {{ $link->domain }}
                                @if($link->isKeyword())<span class="ml-1 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400" title="Blocks every site that contains this word">keyword</span>@endif
                            </td>
                            <td class="py-2.5 px-3 text-slate-600 dark:text-slate-300 w-1/3">
                                @if($canManage)
                                    <input type="text" value="{{ $link->note }}" wire:change="updateNote({{ $link->id }}, $event.target.value)" placeholder="—" maxlength="500" class="w-full px-2 py-1 bg-transparent border border-transparent hover:border-slate-200 dark:hover:border-slate-700 focus:border-sky-500 rounded-lg text-xs focus:outline-none focus:bg-white dark:focus:bg-slate-950" aria-label="Note for {{ $link->domain }}">
                                @else
                                    <span class="text-xs">{{ $link->note ?: '—' }}</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-[11px] text-slate-400 whitespace-nowrap">{{ $link->created_at->format('d.m.Y') }}@if($link->creator) · {{ $link->creator->name }}@endif</td>
                            @if($canManage)
                                <td class="py-2.5 px-3 text-right">
                                    <button type="button" wire:click="deleteLink({{ $link->id }})" wire:confirm="Remove {{ $link->domain }} from the list?" class="p-1 text-slate-300 hover:text-rose-500 transition-colors" aria-label="Remove {{ $link->domain }}"><i class="fa-solid fa-trash-can text-xs"></i></button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-10 text-center text-sm text-slate-400">
                                @if(trim($search) !== '')
                                    Nothing in the list matches "{{ $search }}".
                                @else
                                    The list is empty. @if($canManage) Paste the domains above to start it. @endif
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $links->links() }}
    </section>
</div>
