<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Affichage « en ligne » (dans le navigateur) des fichiers PRIVÉS.
 * Seuls les formats sûrs et affichables sont servis ainsi ; les autres restent en téléchargement.
 */
final class FileViewer
{
    private const MIMES = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
    ];

    public static function previewable(?string $path): bool
    {
        return $path !== null && $path !== '' && isset(self::MIMES[strtolower(pathinfo($path, PATHINFO_EXTENSION))]);
    }

    /** Réponse « inline ». À appeler APRÈS les contrôles de droits et d'entreprise. */
    public static function inline(string $path, string $name, string $disk = 'local'): StreamedResponse
    {
        abort_unless(self::previewable($path), 415, 'Ce format ne peut pas être affiché : téléchargez le fichier.');
        abort_unless(Storage::disk($disk)->exists($path), 404);

        $mime = self::MIMES[strtolower(pathinfo($path, PATHINFO_EXTENSION))];

        return Storage::disk($disk)->response($path, $name, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Content-Security-Policy' => "frame-ancestors 'self'",
        ], 'inline');
    }
}
