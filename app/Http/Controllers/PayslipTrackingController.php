<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

/**
 * Pixel de suivi d'ouverture de l'e-mail du bulletin. Route publique (le client de messagerie n'est pas connecté) :
 * le jeton aléatoire du canal tient lieu d'identifiant. Signal indicatif : certains clients bloquent ou préchargent les images.
 */
class PayslipTrackingController extends Controller
{
    public function pixel(string $token)
    {
        try {
            DB::table('payslip_deliveries')->where('token', $token)->whereNull('opened_at')->update(['opened_at' => now()]);
        } catch (\Throwable) {
            // un échec de suivi ne doit jamais casser l'affichage de l'e-mail
        }

        return response(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
