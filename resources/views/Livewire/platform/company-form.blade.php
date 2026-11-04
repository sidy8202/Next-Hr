<div class="space-y-6">
    <div><h2 class="text-2xl font-bold">{{ $companyId ? 'Modifier l’entreprise' : 'Nouvelle entreprise' }}</h2><p class="text-sm text-ink-muted">@if (! $companyId)Le compte du propriétaire est créé avec un mot de passe provisoire, à lui transmettre. Il devra le changer à sa première connexion.@endif</p></div>

    @error('form')<p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $message }}</p>@enderror

    <form wire:submit="save" class="space-y-5">
        <section class="grid gap-4 rounded-2xl border border-line bg-surface p-5 shadow-sm sm:grid-cols-2">
            <h3 class="text-sm font-semibold sm:col-span-2">Identité</h3>
            <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium">Nom de l’entreprise</label><input type="text" wire:model="name" class="input">@error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
            <div><label class="mb-1 block text-sm font-medium">Secteur d’activité</label><select wire:model="sector" class="input"><option value="">Choisir…</option>@foreach (config('platform.sectors') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>@error('sector')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
            <div><label class="mb-1 block text-sm font-medium">Pays</label><select wire:model="country" class="input">@foreach (config('platform.countries') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
            <div><label class="mb-1 block text-sm font-medium">E-mail de l’entreprise</label><input type="email" wire:model="email" class="input">@error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
            <div><label class="mb-1 block text-sm font-medium">Téléphone</label><input type="text" wire:model="phone" class="input"></div>
            <div><label class="mb-1 block text-sm font-medium">Fuseau horaire</label><input type="text" wire:model="timezone" class="input">@error('timezone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
            <div class="grid grid-cols-2 gap-3"><div><label class="mb-1 block text-sm font-medium">Langue</label><input type="text" wire:model="locale" class="input"></div><div><label class="mb-1 block text-sm font-medium">Devise</label><input type="text" wire:model="currency" maxlength="3" class="input uppercase"></div></div>
        </section>

        @unless ($companyId)
            <section class="grid gap-4 rounded-2xl border border-line bg-surface p-5 shadow-sm sm:grid-cols-2">
                <h3 class="text-sm font-semibold sm:col-span-2">Propriétaire du compte</h3>
                <div><label class="mb-1 block text-sm font-medium">Nom complet</label><input type="text" wire:model="owner_name" class="input">@error('owner_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                <div><label class="mb-1 block text-sm font-medium">E-mail de connexion</label><input type="email" wire:model="owner_email" class="input">@error('owner_email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
            </section>
        @endunless

        <div class="flex justify-end gap-3">
            <a href="{{ route('platform.companies') }}" wire:navigate class="inline-flex items-center rounded-xl border border-line px-4 py-2 text-sm hover:bg-surface-muted">Annuler</a>
            <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">{{ $companyId ? 'Enregistrer' : 'Créer l’entreprise' }}</button>
        </div>
    </form>
</div>
