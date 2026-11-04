<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/** Identité de l'éditeur de la plateforme. Mêmes noms de champs que Letterhead : le composant x-letterhead-header la lit tel quel. */
class PlatformProfile extends Model
{
    protected $table = 'platform_profile';

    protected $fillable = ['legal_name', 'address', 'city', 'phone', 'email', 'website', 'rccm', 'nif', 'bank_details', 'tax_note', 'footer_text', 'logo_path'];

    public static function current(): self
    {
        return self::firstOrCreate(['id' => 1], ['legal_name' => config('app.name', 'Next HR')]);
    }

    /** Logo intégré à la page (nécessaire pour le PDF). */
    public function logoDataUri(): ?string
    {
        if (! $this->logo_path || ! Storage::disk('local')->exists($this->logo_path)) {
            return null;
        }

        $ext = strtolower(pathinfo($this->logo_path, PATHINFO_EXTENSION));
        $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('local')->get($this->logo_path));
    }
}
