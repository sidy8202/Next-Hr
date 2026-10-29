<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\MissionDocument;
use App\Services\MissionDocumentService;
use App\Support\FileViewer;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Ordres de mission (à signer et signés) : accès contrôlé document par document. */
class MissionFileController extends Controller
{
    public function download(Mission $mission, MissionDocument $document): StreamedResponse
    {
        return $this->serve($mission, $document, false);
    }

    public function view(Mission $mission, MissionDocument $document): StreamedResponse
    {
        return $this->serve($mission, $document, true);
    }

    private function serve(Mission $mission, MissionDocument $document, bool $inline): StreamedResponse
    {
        abort_unless((int) $document->mission_id === (int) $mission->id, 404);
        abort_unless(app(MissionDocumentService::class)->canAccess(auth()->user(), $mission, $document), 403);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        $file = ($document->kind === 'signed' ? 'ordre-signe-' : 'ordre-mission-').$document->reference.'.'.$document->extension();

        return ($inline && FileViewer::previewable($document->file_path))
            ? FileViewer::inline($document->file_path, $file)
            : Storage::disk('local')->download($document->file_path, $file);
    }
}
