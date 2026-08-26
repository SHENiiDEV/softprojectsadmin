<?php

namespace App\Models\Concerns;

/**
 * Provides formatted content rendering with Jira-like features.
 *
 * Expects the model to have a `content` attribute.
 * Renders: @mentions as blue badges, **bold**, *italic*, `code`.
 */
trait HasFormattedContent
{
    /**
     * Get the content with formatted mentions and text styling.
     *
     * Escapes HTML first, then applies:
     * - @mention highlighting (blue badge like Jira)
     * - **bold** → <strong>
     * - *italic* → <em>
     * - `code` → <code>
     */
    public function getFormattedContentAttribute(): string
    {
        $content = e($this->content);

        // Convert @mentions to styled badge spans
        $content = (string) preg_replace(
            '/\B@([a-zA-Z0-9_]+)\b/',
            '<span class="inline-flex items-center px-1.5 py-0.5 rounded-md bg-sky-100 dark:bg-sky-950/40 text-sky-700 dark:text-sky-400 font-semibold text-[10px] ring-1 ring-sky-200/60 dark:ring-sky-800/60">@$1</span>',
            $content
        );

        // Convert `code` to styled inline code
        $content = (string) preg_replace(
            '/`([^`]+)`/',
            '<code class="px-1.5 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-rose-600 dark:text-rose-400 font-mono text-[10px] ring-1 ring-slate-200/60 dark:ring-slate-700/60">$1</code>',
            $content
        );

        // Convert **bold** to <strong>
        $content = (string) preg_replace(
            '/\*\*(.+?)\*\*/',
            '<strong class="font-bold">$1</strong>',
            $content
        );

        // Convert *italic* to <em> (but not inside ** sequences)
        $content = (string) preg_replace(
            '/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/',
            '<em class="italic">$1</em>',
            $content
        );

        return $content;
    }
}
