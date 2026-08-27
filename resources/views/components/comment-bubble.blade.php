@props([
    'comment',
    'avatarGradient' => 'from-sky-400 to-indigo-500',
    'avatarSize' => '7',
    'avatarTextSize' => '10px',
    'showPrivateBadge' => true,
    'showDelete' => true,
    'deleteAction' => null,
])

@php
    $initials = substr($comment->user ? $comment->user->name : ($comment->client ? $comment->client->name : 'S'), 0, 2);
    $authorName = $comment->user ? $comment->user->name : ($comment->client ? $comment->client->name . ' (Client)' : 'System');
    $gradient = $comment->user && $comment->user->gradient ? $comment->user->gradient : $avatarGradient;
    $deleteWire = $deleteAction ?? "deleteComment({$comment->id})";
    $createdAt = $comment->created_at->isFuture() ? now() : $comment->created_at;
@endphp

<div class="flex items-start justify-between">
    <div class="flex items-center space-x-2">
        <div class="h-{{ $avatarSize }} w-{{ $avatarSize }} rounded-full bg-gradient-to-tr {{ $gradient }} text-white font-bold flex items-center justify-center text-[{{ $avatarTextSize }}] uppercase shadow-sm">
            {{ $initials }}
        </div>
        <div class="space-y-0.5">
            <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                {{ $authorName }}
            </span>
            <span class="text-[9px] text-slate-400 dark:text-slate-500 ml-1.5" title="{{ $comment->created_at->format('d.m.Y H:i:s') }}">
                {{ $createdAt->diffForHumans() }}
            </span>
        </div>
    </div>

    <div class="flex items-center space-x-1.5">
        @if($showPrivateBadge && $comment->is_private)
            <span class="text-[8px] font-bold uppercase tracking-wider bg-amber-50 dark:bg-amber-950/20 text-amber-600 dark:text-amber-400 border border-amber-200/20 dark:border-amber-900/40 px-2 py-0.5 rounded-full">🔒 Private</span>
        @endif

        @if($showDelete && (auth()->user()->hasAnyRole(['admin', 'manager']) || $comment->user_id === auth()->id()))
            <button type="button" wire:click="{{ $deleteWire }}" wire:confirm="Are you sure you want to delete this?" class="text-[10px] text-slate-400 hover:text-rose-500 transition-all opacity-0 group-hover:opacity-100 p-1">
                <i class="fa-solid fa-trash-can"></i>
            </button>
        @endif
    </div>
</div>

<div class="mt-2.5 text-xs text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-wrap">{!! $comment->formatted_content !!}</div>
