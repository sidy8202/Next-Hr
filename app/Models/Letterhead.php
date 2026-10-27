<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Papier à en-tête d'UNE entreprise (lu par identifiant d'entreprise : utilisable sans utilisateur connecté). */
class Letterhead extends Model
{
    protected $fillable = [
        'legal_name', 'address', 'city', 'phone', 'email', 'website', 'rccm', 'nif', 'logo_path', 'footer_text', 'default_response_days',
    ];

    public static function for(int $companyId): self
    {
        $row = static::where('company_id', $companyId)->first();

        if ($row) {
            return $row;
        }

        try {
            $row = new static();
            $row->forceFill([
                'company_id' => $companyId,
                'legal_name' => (string) (DB::table('companies')->where('id', $companyId)->value('name') ?? config('app.name')),
                'default_response_days' => config('discipline.default_response_days', 3),
            ])->save();

            return $row->refresh();
        } catch (UniqueConstraintViolationException) {
            return static::where('company_id', $companyId)->firstOrFail();
        }
    }

    /** Logo prêt à être intégré dans la page ou le PDF (aucune adresse publique). */
    public function logoDataUri(): ?string
    {
        if (! $this->logo_path || ! Storage::disk('local')->exists($this->logo_path)) {
            return null;
        }

        $mime = str_ends_with(strtolower($this->logo_path), '.png') ? 'image/png' : 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('local')->get($this->logo_path));
    }
}
