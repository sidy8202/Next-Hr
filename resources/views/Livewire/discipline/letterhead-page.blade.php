@include('pdf.letter-styles')
@php($canEdit = auth()->user()->canDo('update', 'settings'))

<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold">En-tête des lettres</h2>
        <p class="text-sm text-ink-muted">Les informations de votre entreprise, le logo et le lieu apparaissent sur chaque lettre officielle générée (demande d’explication, réponse, décision).</p>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <form wire:submit="save" class="space-y-4 rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium">Nom officiel</label><input type="text" wire:model="legal_name" class="input" @disabled(! $canEdit)>@error('legal_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium">Adresse</label><input type="text" wire:model="address" class="input" @disabled(! $canEdit)></div>
                <div><label class="mb-1 block text-sm font-medium">Ville (lieu de la date)</label><input type="text" wire:model="city" class="input" placeholder="Bamako" @disabled(! $canEdit)></div>
                <div><label class="mb-1 block text-sm font-medium">Téléphone</label><input type="text" wire:model="phone" class="input" @disabled(! $canEdit)></div>
                <div><label class="mb-1 block text-sm font-medium">Email</label><input type="email" wire:model="email" class="input" @disabled(! $canEdit)>@error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                <div><label class="mb-1 block text-sm font-medium">Site web</label><input type="text" wire:model="website" class="input" @disabled(! $canEdit)></div>
                <div><label class="mb-1 block text-sm font-medium">RCCM</label><input type="text" wire:model="rccm" class="input" @disabled(! $canEdit)></div>
                <div><label class="mb-1 block text-sm font-medium">NIF</label><input type="text" wire:model="nif" class="input" @disabled(! $canEdit)></div>
                <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium">Pied de page</label><input type="text" wire:model="footer_text" class="input" @disabled(! $canEdit)></div>
                <div><label class="mb-1 block text-sm font-medium">Délai de réponse par défaut (jours ouvrés)</label><input type="number" min="1" max="30" wire:model="default_response_days" class="input" @disabled(! $canEdit)>@error('default_response_days')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror<p class="mt-1 text-xs text-ink-muted">À valider selon votre règlement intérieur.</p></div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Logo (PNG ou JPG, 1 Mo max)</label>
                    <input type="file" wire:model="logo" accept=".png,.jpg,.jpeg" class="block w-full text-sm" @disabled(! $canEdit)>
                    @error('logo')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    @if ($letterhead->logo_path)<label class="mt-1 flex items-center gap-2 text-xs"><input type="checkbox" wire:model="remove_logo"> Retirer le logo actuel</label>@endif
                </div>
            </div>
            @if ($canEdit)<div class="flex justify-end"><button type="submit" wire:loading.attr="disabled" wire:target="save,logo" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">Enregistrer</button></div>@endif
        </form>

        <div class="overflow-x-auto rounded-2xl border border-line bg-white p-6 shadow-sm" style="color: #1f2937;">
            <x-letter :letterhead="$letterhead" reference="DE-{{ now()->year }}-0001" :date="now()" subject="Exemple d’objet" body="Corps de la lettre…"
                      signatory-name="Nom du signataire" signatory-title="Fonction" :recipient="['Nom complet de l’employé', 'Matricule : 000000']" banner="Aperçu" />
        </div>
    </div>
</div>
