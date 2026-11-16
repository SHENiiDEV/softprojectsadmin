<?php

namespace App\View\Components;

use App\Services\NavigationMenu;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AppNavigation extends Component
{
    /**
     * @param  string  $variant  "sidebar" (desktop), "drawer" (mobile menu) or "bottom" (mobile bottom bar)
     */
    public function __construct(public string $variant = 'sidebar') {}

    public function render(): View
    {
        $menu = app(NavigationMenu::class);
        $user = auth()->user();

        if ($this->variant === 'bottom') {
            return view('components.app-navigation-bottom', ['items' => $menu->bottomItems($user)]);
        }

        return view('components.app-navigation', [
            'groups' => $menu->groups($user),
            'isDrawer' => $this->variant === 'drawer',
        ]);
    }
}
