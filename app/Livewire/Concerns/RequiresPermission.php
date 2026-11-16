<?php

namespace App\Livewire\Concerns;

trait RequiresPermission
{
    /**
     * Abort with 403 unless the authenticated user has at least one of the given permissions.
     *
     * Livewire actions can be invoked directly, so every mutating action must call this.
     *
     * @param  string|array<int, string>  $permissions
     */
    protected function requirePermission(string|array $permissions): void
    {
        $user = auth()->user();

        abort_unless($user !== null && $user->hasAnyPermission((array) $permissions), 403);
    }
}
