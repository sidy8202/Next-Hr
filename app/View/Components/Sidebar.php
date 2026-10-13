<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Facades\Route;

class Sidebar extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.sidebar');
    }

    public function groups(): array
    {
        $user = auth()->user();

        return collect(config('navigation.groups'))
            ->map(function (array $group) use ($user) {
                $group['items'] = collect($group['items'])
                    ->filter(fn (array $item) => Route::has($item['route'])
                        && (empty($item['resource']) || $user->canDo('read', $item['resource'])))
                    ->values()
                    ->all();

                return $group;
            })
            ->filter(fn (array $group) => count($group['items']) > 0)
            ->values()
            ->all();
    }
}
