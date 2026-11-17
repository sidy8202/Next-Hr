<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold">Importer des employés</h2>
            <p class="mt-1 text-sm text-ink-muted">Importez un fichier CSV, associez ses colonnes à vos champs, puis contrôlez les lignes avant l’enregistrement.</p>
        </div>
        <x-ui.button variant="secondary" wire:click="downloadTemplate" icon="arrow-down-tray">Télécharger le modèle CSV</x-ui.button>
    </div>

    @if ($stage === 'done')
        <x-ui.section title="Import terminé" description="Les employés ont été créés avec les contrats et salaires associés par l’application.">
            <p class="text-sm">{{ $createdCount }} employé(s) importé(s).</p>
            <div class="mt-4 flex gap-2">
                <x-ui.button :href="route('employees.index')" wire:navigate>Voir les employés</x-ui.button>
                <x-ui.button variant="secondary" :href="route('employees.import')" wire:navigate>Importer un autre fichier</x-ui.button>
            </div>
        </x-ui.section>
    @else
        <x-ui.section title="1. Choisir le fichier" description="CSV UTF-8 ou CSV exporté depuis Excel. Jusqu’à 10 Mo et 1 000 lignes par import.">
            <div class="space-y-3">
                <input type="file" wire:model="file" accept=".csv,.txt,text/csv" class="input max-w-xl" aria-label="Fichier CSV des employés">
                <div wire:loading wire:target="file" class="text-sm text-ink-muted">Lecture du fichier…</div>
                @error('file') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                <p class="text-xs text-ink-muted">Les colonnes peuvent porter des noms différents. L’application propose une correspondance que vous pourrez ajuster à l’étape suivante. Pour un fichier Excel, enregistrez-le en CSV UTF-8 avant de le sélectionner.</p>
            </div>
        </x-ui.section>

        @if ($stage === 'mapping' || $stage === 'preview')
            <x-ui.section title="2. Associer les colonnes" description="Vérifiez les propositions automatiques. Les champs obligatoires sont marqués d’un astérisque.">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($fields as $field => $meta)
                        <label class="block space-y-1 text-sm" for="mapping-{{ $field }}">
                            <span class="font-medium">{{ $meta['label'] }}@if ($meta['required']) <span class="text-rose-600">*</span>@endif</span>
                            <select id="mapping-{{ $field }}" wire:model="mapping.{{ $field }}" class="input">
                                <option value="">Ne pas importer</option>
                                @foreach ($headers as $index => $header)
                                    <option value="{{ $index }}">{{ $header }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endforeach
                </div>
                <p class="mt-4 text-xs text-ink-muted">Département : code ou nom déjà présent dans votre entreprise. Poste : intitulé existant dans le département sélectionné. Un matricule fourni est conservé ; sinon il est généré par l’application.</p>
                @error('mapping') <p class="mt-3 text-sm text-rose-600">{{ $message }}</p> @enderror
                <div class="mt-4 flex flex-wrap gap-2">
                    <x-ui.button wire:click="preview" wire:loading.attr="disabled" wire:target="preview">Prévisualiser et vérifier</x-ui.button>
                    <x-ui.button variant="secondary" :href="route('employees.index')" wire:navigate>Annuler</x-ui.button>
                </div>
            </x-ui.section>
        @endif

        @if ($stage === 'preview')
            <x-ui.section title="3. Vérifier avant import" description="Aucune donnée n’est enregistrée avant votre confirmation. Si une ligne est invalide, corrigez le fichier puis téléversez-le à nouveau.">
                @foreach ($messages as $message)
                    <p class="mb-3 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-200">{{ $message }}</p>
                @endforeach
                @error('import') <p class="mb-3 rounded-lg bg-rose-50 p-3 text-sm text-rose-700 dark:bg-rose-900/20 dark:text-rose-200">{{ $message }}</p> @enderror

                <div class="mb-4 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                    <span><strong>{{ $validCount }}</strong> ligne(s) valide(s)</span>
                    <span class="{{ $rowErrors ? 'text-rose-700 dark:text-rose-300' : 'text-emerald-700 dark:text-emerald-300' }}"><strong>{{ count($rowErrors) }}</strong> ligne(s) à corriger</span>
                </div>

                @if ($previewRows)
                    <div class="overflow-x-auto rounded-xl border border-line">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-surface-muted text-xs uppercase text-ink-muted"><tr><th class="px-3 py-2">Ligne</th><th class="px-3 py-2">Employé</th><th class="px-3 py-2">Département</th><th class="px-3 py-2">Poste</th><th class="px-3 py-2">Contrôle</th></tr></thead>
                            <tbody>
                                @foreach ($previewRows as $row)
                                    <tr class="border-t border-line">
                                        <td class="px-3 py-2">{{ $row['line'] }}</td><td class="px-3 py-2">{{ $row['name'] ?: '—' }}</td><td class="px-3 py-2">{{ $row['department'] }}</td><td class="px-3 py-2">{{ $row['position'] }}</td>
                                        <td class="px-3 py-2">@if ($row['status'] === 'ok') <x-ui.badge tone="green">Valide</x-ui.badge> @else <x-ui.badge tone="red">À corriger</x-ui.badge> @endif</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <p class="border-t border-line px-3 py-2 text-xs text-ink-muted">Aperçu des 8 premières lignes non vides.</p>
                    </div>
                @endif

                @if ($rowErrors)
                    <div class="mt-4 max-h-72 space-y-2 overflow-y-auto rounded-xl border border-rose-200 p-3 dark:border-rose-900">
                        @foreach (array_slice($rowErrors, 0, 30, true) as $line => $issues)
                            <p class="text-sm"><strong>Ligne {{ $line }} :</strong> {{ implode(' ', $issues) }}</p>
                        @endforeach
                        @if (count($rowErrors) > 30)<p class="text-xs text-ink-muted">Les autres erreurs restent visibles dans le fichier à corriger.</p>@endif
                    </div>
                @endif

                <div class="mt-4 flex flex-wrap gap-2">
                    <x-ui.button wire:click="import" wire:loading.attr="disabled" wire:target="import" :disabled="! $validCount || $rowErrors">Importer {{ $validCount }} employé(s)</x-ui.button>
                    <x-ui.button variant="secondary" wire:click="preview">Actualiser l’aperçu</x-ui.button>
                </div>
            </x-ui.section>
        @endif
    @endif
</div>