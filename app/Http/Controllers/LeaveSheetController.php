<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Services\LeaveService;
use App\Support\FileViewer;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Fiche de congé et justificatif d'une demande : visibles par l'employé, ses validateurs et ceux qui ont le droit de lecture. */
class LeaveSheetController extends Controller
{
    public function sheet(LeaveRequest $leave, string $mode = 'download')
    {
        $this->authorizeView($leave);

        $path = $leave->document?->file_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $name = 'fiche-de-conge.'.pathinfo($path, PATHINFO_EXTENSION);

        if ($mode === 'view' && FileViewer::previewable($path)) {
            return FileViewer::inline($path, $name);
        }

        return Storage::disk('local')->download($path, $name);
    }

    public function attachment(LeaveRequest $leave, string $mode = 'download'): StreamedResponse
    {
        $this->authorizeView($leave);

        abort_unless($leave->attachment_path && Storage::disk('local')->exists($leave->attachment_path), 404);

        $name = $leave->attachment_name ?: 'justificatif.'.pathinfo($leave->attachment_path, PATHINFO_EXTENSION);

        return $mode === 'view'
            ? FileViewer::inline($leave->attachment_path, $name)
            : Storage::disk('local')->download($leave->attachment_path, $name);
    }

    public function sheetView(LeaveRequest $leave)
    {
        return $this->sheet($leave, 'view');
    }

    public function attachmentView(LeaveRequest $leave): StreamedResponse
    {
        return $this->attachment($leave, 'view');
    }

    private function authorizeView(LeaveRequest $leave): void
    {
        abort_unless(app(LeaveService::class)->canView(auth()->user(), $leave), 403);
    }
}
