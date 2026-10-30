<?php

namespace App\Http\Controllers;

use App\Enums\MissionStatus;
use App\Models\Letterhead;
use App\Models\Mission;
use App\Models\MissionParticipant;
use App\Models\MissionPayment;
use App\Services\MissionPaymentService;
use App\Services\PerdiemSheetRenderer;
use App\Support\FileViewer;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Fiche de perdiem (générée à la demande) et reçus de paiement : accès contrôlé par participant. */
class MissionPaymentFileController extends Controller
{
    public function sheet(Mission $mission, MissionParticipant $participant): Response
    {
        abort_unless((int) $participant->mission_id === (int) $mission->id, 404);
        abort_unless(in_array($mission->status, [MissionStatus::Approved, MissionStatus::Cancelled], true), 404);
        abort_unless(app(MissionPaymentService::class)->canSeeParticipant(auth()->user(), $mission, $participant), 403);

        $participant->load(['employee.position', 'department']);
        $payments = $participant->payments()->whereIn('status', ['planned', 'paid'])->orderBy('sequence')->get();

        [$content, $ext, $type] = app(PerdiemSheetRenderer::class)->render($mission, $participant, $payments, Letterhead::for($mission->company_id));

        $name = 'fiche-perdiem-'.$mission->reference.'-'.$participant->employee->matricule.'.'.$ext;
        $disposition = request()->boolean('dl') ? 'attachment' : 'inline';

        return response($content, 200, ['Content-Type' => $type, 'Content-Disposition' => $disposition.'; filename="'.$name.'"']);
    }

    public function receipt(Mission $mission, MissionPayment $payment): StreamedResponse
    {
        return $this->serveReceipt($mission, $payment, false);
    }

    public function receiptView(Mission $mission, MissionPayment $payment): StreamedResponse
    {
        return $this->serveReceipt($mission, $payment, true);
    }

    private function serveReceipt(Mission $mission, MissionPayment $payment, bool $inline): StreamedResponse
    {
        abort_unless((int) $payment->mission_id === (int) $mission->id, 404);
        abort_unless(app(MissionPaymentService::class)->canAccessReceipt(auth()->user(), $mission, $payment), 403);
        abort_unless(Storage::disk('local')->exists($payment->receipt_path), 404);

        $file = 'recu-'.$mission->reference.'-'.$payment->id.'.'.$payment->receiptExtension();

        return ($inline && FileViewer::previewable($payment->receipt_path))
            ? FileViewer::inline($payment->receipt_path, $file)
            : Storage::disk('local')->download($payment->receipt_path, $file);
    }
}
