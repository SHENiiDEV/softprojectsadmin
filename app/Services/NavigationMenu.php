<?php

namespace App\Services;

use App\Models\User;

class NavigationMenu
{
    /**
     * Navigation groups the user is allowed to see (empty groups are dropped).
     *
     * @return array<int, array{label: ?string, items: array<int, array<string, mixed>>}>
     */
    public function groups(User $user): array
    {
        $groups = [];

        foreach (config('navigation.groups', []) as $group) {
            $items = [];

            foreach ($group['items'] as $item) {
                if ($this->isVisible($item, $user)) {
                    $items[] = $this->withActiveState($item);
                }
            }

            if ($items !== []) {
                $groups[] = ['label' => $group['label'], 'items' => $items];
            }
        }

        return $groups;
    }

    /**
     * Items pinned to the mobile bottom bar that the user can open.
     *
     * @return array<int, array<string, mixed>>
     */
    public function bottomItems(User $user): array
    {
        $visible = [];

        foreach ($this->groups($user) as $group) {
            foreach ($group['items'] as $item) {
                $visible[$item['key']] = $item;
            }
        }

        $items = [];

        foreach (config('navigation.bottom', []) as $key) {
            if (isset($visible[$key])) {
                $items[] = $visible[$key];
            }
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function isVisible(array $item, User $user): bool
    {
        foreach ((array) ($item['feature'] ?? []) as $feature) {
            if (! config("features.{$feature}", true)) {
                return false;
            }
        }

        if (isset($item['permission']) && ! $user->can($item['permission'])) {
            return false;
        }

        if (isset($item['role']) && ! $user->hasRole($item['role'])) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function withActiveState(array $item): array
    {
        $item['is_active'] = request()->routeIs(...(array) $item['active']);
        $item['url'] = route($item['route']);

        return $item;
    }
}
