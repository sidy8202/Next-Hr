<?php

namespace App\Http\Controllers;

use App\Models\PermissionRequest;
use App\Services\PermissionService;
use App\Support\FileViewer;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Fiche de permission et justificatif : visibles par l'employé, ses validateurs et ceux dont la portée de lecture le couvre. */
class PermissionFileController extends Controller
{
    public function sheet(PermissionRequest $permission): StreamedResponse
    {
        return $this->serve($permission, $permission->document?->file_path, 'fiche-de-permission', false);
    }

    public function sheetView(PermissionRequest $permission): StreamedResponse
    {
        return $this->serve($permission, $permission->document?->file_path, 'fiche-de-permission', true);
    }

    public function attachment(PermissionRequest $permission): StreamedResponse
    {
        return $this->serve($permission, $permission->attachment_path, 'justificatif', false);
    }

    public function attachmentView(PermissionRequest $permission): StreamedResponse
    {
        return $this->serve($permission, $permission->attachment_path, 'justificatif', true);
    }

    private function serve(PermissionRequest $permission, ?string $path, string $name, bool $inline): StreamedResponse
    {
        abort_unless(app(PermissionService::class)->canView(auth()->user(), $permission), 403);
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $file = $name.'.'.pathinfo($path, PATHINFO_EXTENSION);

        return ($inline && FileViewer::previewable($path))
            ? FileViewer::inline($path, $file)
            : Storage::disk('local')->download($path, $file);
    }
}
