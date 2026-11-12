<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Storage;

/**
 * Identité de l'entreprise sur le bulletin : l'en-tête de lettre officiel de l'entreprise s'il est retrouvé
 * (raison sociale, adresse, RCCM, NIF, pied de page, logo), sinon la fiche de l'entreprise.
 */
class PayslipIdentity
{
    /** @return array{legal_name: string, address: ?string, city: ?string, phone: ?string, email: ?string, website: ?string, rccm: ?string, nif: ?string, footer_text: ?string, logo: ?string} */
    public static function forCompany(int $companyId): array
    {
        $company = Company::find($companyId);
        $lh = self::letterhead($companyId);

        $pick = function (string $key, ?string $fallback = null) use ($lh): ?string {
            if ($lh !== null) {
                $value = $lh->{$key} ?? null;

                if (is_string($value) && trim($value) !== '') {
                    return trim($value);
                }
            }

            return $fallback;
        };

        return [
            'legal_name' => $pick('legal_name', $company?->name ?? config('app.name')) ?? config('app.name'),
            'address' => $pick('address'),
            'city' => $pick('city'),
            'phone' => $pick('phone', $company?->phone),
            'email' => $pick('email', $company?->email),
            'website' => $pick('website'),
            'rccm' => $pick('rccm'),
            'nif' => $pick('nif'),
            'footer_text' => $pick('footer_text'),
            'logo' => self::logo($lh, $company),
        ];
    }

    /**
     * L'en-tête de lettre officiel (celui des lettres de l'entreprise), cherché sous ses noms les plus courants.
     * Si votre projet le nomme autrement, c'est la SEULE méthode à adapter : renvoyez l'objet qui porte
     * legal_name, address, city, phone, email, website, rccm, nif, footer_text et logoDataUri().
     */
    protected static function letterhead(int $companyId): ?object
    {
        foreach (['App\\Models\\Letterhead', 'App\\Models\\CompanyLetterhead', 'App\\Support\\Letterhead', 'App\\Services\\Letterhead'] as $class) {
            if (! class_exists($class)) {
                continue;
            }

            try {
                foreach (['forCompany', 'for', 'current'] as $method) {
                    if (method_exists($class, $method)) {
                        $found = $method === 'current' ? $class::current() : $class::{$method}($companyId);

                        if (is_object($found)) {
                            return $found;
                        }
                    }
                }

                if (is_subclass_of($class, \Illuminate\Database\Eloquent\Model::class)) {
                    $found = $class::where('company_id', $companyId)->first();

                    if ($found) {
                        return $found;
                    }
                }
            } catch (\Throwable) {
                // on essaie le nom suivant
            }
        }

        return null;
    }

    protected static function logo(?object $letterhead, ?Company $company): ?string
    {
        try {
            if ($letterhead !== null && method_exists($letterhead, 'logoDataUri')) {
                $uri = $letterhead->logoDataUri();

                if (is_string($uri) && $uri !== '') {
                    return $uri;
                }
            }

            $path = $company?->logo_path;

            if (! $path) {
                return null;
            }

            foreach (['public', 'local'] as $disk) {
                if (Storage::disk($disk)->exists($path)) {
                    $mime = Storage::disk($disk)->mimeType($path) ?: 'image/png';

                    return 'data:' . $mime . ';base64,' . base64_encode(Storage::disk($disk)->get($path));
                }
            }
        } catch (\Throwable) {
            // sans logo, le bulletin reste complet
        }

        return null;
    }
}
