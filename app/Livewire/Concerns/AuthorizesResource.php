<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;

trait AuthorizesResource
{
    /**
     * Autorise une action sur une ressource, côté serveur, à CHAQUE action.
     * Nommée authorizeAction : « authorizeResource » existe déjà dans la classe de base de Livewire.
     */
    protected function authorizeAction(string $action, string $resource, ?Model $record = null): void
    {
        $user = auth()->user();

        abort_unless(
            $record ? $user->canAccessRecord($action, $resource, $record) : $user->canDo($action, $resource),
            403
        );
    }
}