<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;

trait AuthorizesResource
{
    protected function authorizeResource(string $action, string $resource, ?Model $record = null): void
    {
        $user = auth()->user();

        abort_unless(
            $record ? $user->canAccessRecord($action, $resource, $record) : $user->canDo($action, $resource),
            403
        );
    }
}
