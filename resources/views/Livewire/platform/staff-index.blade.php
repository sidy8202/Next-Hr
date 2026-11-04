<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h2 class="text-2xl font-bold">Équipe plateforme</h2><p class="text-sm text-ink-muted">Les personnes qui travaillent avec vous sur la plateforme, et ce qu’elles ont le droit de faire. Vous, propriétaire, avez toujours tous les droits.</p></div>
        <button type="button" wire:click="openCreate" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Nouveau compte</button>
    </div>

    @if ($temp)
        <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <p class="font-semibold">Identifiants à transmettre maintenant, ils ne seront plus affichés.</p>
            <p class="mt-1">E-mail : <strong>{{ $temp['email'] }}</strong> · Mot de passe provisoire : <strong class="font-mono">{{ $temp['password'] }}</strong></p>
        </div>
    @endif

    @if ($showForm)
        <section class="space-y-4 rounded-2xl border border-indigo-300 bg-surface p-5 shadow-sm">
            <h3 class="text-sm font-semibold">{{ $editingId ? 'Modifier le compte' : 'Nouveau compte' }}</h3>
            @php($editingOwner = $editingId && $staff->firstWhere('id', $editingId)?->isPlatformOwner())
            <div class="grid gap-3 sm:grid-cols-2">
                <div><label class="mb-1 block text-xs font-medium">Nom</label><input type="text" wire:model="name" class="input py-1.5">@error('name')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                <div><label class="mb-1 block text-xs font-medium">E-mail</label><input type="email" wire:model="email" class="input py-1.5">@error('email')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror</div>
            </div>
            @unless ($editingOwner)
                <div>
                    <label class="mb-1 block text-xs font-medium">Rôle (modèle de droits)</label>
                    <select wire:model.live="role" class="input max-w-sm py-1.5">@foreach (config('platform.roles') as $k => $r)@if ($k !== 'owner')<option value="{{ $k }}">{{ $r['label'] }}</option>@endif @endforeach</select>
                    <p class="mt-1 text-xs text-ink-muted">Le rôle pré-coche les droits ci-dessous ; vous pouvez ensuite les ajuster un par un.</p>
                </div>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach (config('platform.permissions') as $key => $label)
                        <label class="flex items-start gap-2 text-sm"><input type="checkbox" wire:model="perms" value="{{ $key }}" class="mt-0.5"><span>{{ $label }}</span></label>
                    @endforeach
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active"> Compte actif</label>
            @else
                <p class="rounded-lg bg-surface-muted px-3 py-2 text-sm text-ink-muted">Le propriétaire a tous les droits en permanence : son rôle ne peut pas être modifié.</p>
            @endunless
            <div class="flex justify-end gap-2"><button type="button" wire:click="$set('showForm', false)" class="rounded-lg border border-line px-3 py-1.5 text-sm hover:bg-surface-muted">Annuler</button><button type="button" wire:click="save" class="rounded-lg bg-indigo-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500">Enregistrer</button></div>
        </section>
    @endif

    <div class="overflow-x-auto rounded-2xl border border-line bg-surface shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-muted text-xs text-ink-muted"><tr><th class="px-4 py-2">Compte</th><th class="px-4 py-2">Rôle</th><th class="px-4 py-2">Droits</th><th class="px-4 py-2">État</th><th class="px-4 py-2"></th></tr></thead>
            <tbody class="divide-y divide-line">
                @foreach ($staff as $m)
                    <tr wire:key="st-{{ $m->id }}">
                        <td class="px-4 py-3 font-medium">{{ $m->name }}<span class="block text-xs font-normal text-ink-muted">{{ $m->email }}</span></td>
                        <td class="px-4 py-3">{{ config('platform.roles.'.$m->platform_role.'.label', '—') }}</td>
                        <td class="px-4 py-3 text-xs text-ink-muted">{{ $m->isPlatformOwner() ? 'Tous les droits' : count($m->platformPermissionList()).' droit(s)' }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs {{ $m->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">{{ $m->is_active ? 'Actif' : 'Désactivé' }}</span></td>
                        <td class="px-4 py-3 text-right"><div class="flex justify-end gap-2">
                            <button type="button" wire:click="openEdit({{ $m->id }})" class="rounded border border-line px-2 py-1 text-xs hover:bg-surface-muted">Modifier</button>
                            @if (! $m->isPlatformOwner() || $m->id === auth()->id())<button type="button" wire:click="resetPassword({{ $m->id }})" wire:confirm="Réinitialiser le mot de passe de ce compte ?" class="rounded border border-line px-2 py-1 text-xs hover:bg-surface-muted">Mot de passe</button>@endif
                        </div></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
