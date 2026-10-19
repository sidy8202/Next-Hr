{{-- Récapitulatif de TOUTES les erreurs : plus aucune erreur invisible. --}}
@if ($errors->any())
    <div role="alert" class="rounded-xl bg-rose-50 p-4 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-200">
        <p class="font-semibold">Le formulaire contient {{ $errors->count() }} erreur(s) :</p>
        <ul class="mt-1 list-disc space-y-0.5 pl-5">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif
