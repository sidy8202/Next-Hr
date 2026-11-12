<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Services\PayslipMailer;
use App\Services\PayslipRenderer;
use Illuminate\Http\Request;

/**
 * Affiche ou télécharge le PDF d'un bulletin. Accès réservé à l'employé concerné (bulletins clôturés seulement)
 * et au personnel de paie. {payslip} est résolu avec le filtre d'entreprise : un bulletin d'une autre entreprise donne une 404.
 */
class PayslipDownloadController extends Controller
{
    public function show(Request $request, Payslip $payslip, PayslipRenderer $renderer)
    {
        $user = $request->user();

        $own = $user->employee_id !== null && (int) $user->employee_id === (int) $payslip->employee_id;
        $ownReadable = $own && $payslip->status === 'final' && $user->canDo('read', 'payslips');
        $staff = $user->canDo('read', 'payroll_periods');

        abort_unless($staff || $ownReadable, 403);

        [$content, $extension, $mime] = $renderer->render($payslip);

        if ($ownReadable) {
            PayslipMailer::markViewed($payslip);
        }

        return response($content, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline') . '; filename="' . PayslipRenderer::filename($payslip, $extension) . '"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
