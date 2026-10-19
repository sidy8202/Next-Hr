<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold">Utilisateurs</h2>
        <p class="text-sm text-ink-muted">Les comptes qui peuvent se connecter. Les droits de chacun se règlent dans « Droits d'accès ».</p>
    </div>

    @if ($resetInfo)
        <div x-data="{ pwd: @js($resetInfo['password']) }" class="space-y-2 rounded-xl bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
            <p><strong>Nouveau mot de passe provisoire pour {{ $resetInfo['name'] }} ({{ $resetInfo['email'] }}) — affiché une seule fois :</strong></p>
            <p class="font-mono text-base" x-text="pwd"></p>
            <div class="flex gap-2">
                <x-ui.button size="sm" variant="secondary" type="button" @click="navigator.clipboard.writeText(pwd)">Copier</x-ui.button>
                <x-ui.button size="sm" variant="secondary" wire:click="dismissReset">J'ai noté le mot de passe</x-ui.button>
            </div>
        </div>
    @endif

    <div class="rounded-2xl border border-line bg-surface shadow-sm">
        <div class="flex flex-wrap items-center gap-2 border-b border-line p-3">
            @canDo('read', 'permissions')
                <x-ui.button variant="secondary" wire:click="openAccess" :disabled="count($selected) !== 1" icon="shield-check">Droits d'accès</x-ui.button>
            @endcanDo
            @canDo('update', 'users')
                <x-ui.button variant="secondary" wire:click="resetPassword" wire:confirm="Générer un nouveau mot de passe provisoire pour cet utilisateur ?" :disabled="count($selected) !== 1" icon="key">Réinitialiser le mot de passe</x-ui.button>
                <x-ui.button variant="secondary" wire:click="setActive(true)" :disabled="! $selected">Activer</x-ui.button>
                <x-ui.button variant="secondary" wire:click="setActive(false)" wire:confirm="Désactiver les comptes sélectionnés ? Ils ne pourront plus se connecter." :disabled="! $selected">Désactiver</x-ui.button>
            @endcanDo

            <div class="flex-1"></div>

            <input type="search" wire:model.live.debounce.300ms="search" class="input w-56" placeholder="Nom, email, code…" aria-label="Rechercher un utilisateur">
            <select wire:model.live="filters.status" class="input w-40" aria-label="Filtrer par statut">
                <option value="">Tous</option><option value="active">Actifs</option><option value="inactive">Désactivés</option>
            </select>
        </div>

        @php
            $pageIds = $rows->pluck('id')->map(fn ($i) => (string) $i)->all();
            $allOnPage = $pageIds !== [] && ! array_diff($pageIds, $selected);
        @endphp

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-surface-muted text-xs text-ink-muted">
                    <tr>
                        <th class="w-10 px-4 py-3"><input type="checkbox" wire:click="togglePage({{ json_encode($rows->pluck('id')) }})" @checked($allOnPage) aria-label="Tout sélectionner sur cette page"></th>
                        <x-ui.sort-th column="name" :sort-by="$sortBy" :sort-dir="$sortDir">Utilisateur</x-ui.sort-th>
                        <x-ui.sort-th column="email" :sort-by="$sortBy" :sort-dir="$sortDir">Email</x-ui.sort-th>
                        <th class="px-4 py-3 font-semibold uppercase tracking-wide">Code employé</th>
                        <th class="px-4 py-3 font-semibold uppercase tracking-wide">Connexion</th>
                        <x-ui.sort-th column="is_active" :sort-by="$sortBy" :sort-dir="$sortDir">Statut</x-ui.sort-th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr wire:key="user-{{ $row->id }}" @class(['border-t border-line hover:bg-surface-muted/60', 'bg-brand-50/60 dark:bg-brand-900/20' => in_array((string) $row->id, $selected, true)])>
                            <td class="px-4 py-3"><input type="checkbox" wire:model.live="selected" value="{{ $row->id }}" aria-label="Sélectionner {{ $row->name }}"></td>
                            <td class="px-4 py-3 font-medium">{{ $row->name }}@if ($row->is_company_owner) <x-ui.badge tone="indigo" class="ml-1">Propriétaire</x-ui.badge>@endif</td>
                            <td class="px-4 py-3 text-ink-muted">{{ $row->email }}</td>
                            <td class="px-4 py-3">{{ $row->employee?->matricule ?? '—' }}</td>
                            <td class="px-4 py-3 text-ink-muted">{{ $row->first_login_at ? 'Effectuée' : ($row->must_change_password ? 'En attente' : '—') }}</td>
                            <td class="px-4 py-3"><x-ui.badge :tone="$row->is_active ? 'green' : 'gray'">{{ $row->is_active ? 'Actif' : 'Désactivé' }}</x-ui.badge></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-ui.empty-state title="Aucun utilisateur" icon="user" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-3 text-sm">
            <div class="flex items-center gap-2 text-ink-muted">
                <span>{{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }} sur {{ $rows->total() }}</span>
                <select wire:model.live="perPage" class="input w-20 py-1" aria-label="Lignes par page">
                    @foreach ([10, 25, 50, 100] as $n)<option value="{{ $n }}">{{ $n }}</option>@endforeach
                </select>
            </div>
            {{ $rows->links('pagination.livewire') }}
        </div>
    </div>
</div>
