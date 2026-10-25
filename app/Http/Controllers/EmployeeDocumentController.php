<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Employee;
use App\Services\EmployeeService;
use App\Support\FileViewer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController extends Controller
{
    public function download(Employee $employee, Document $document): StreamedResponse
    {
        $this->authorizeDocument($employee, $document);

        $disk = Storage::disk('local'); // dossier PRIVÉ : jamais accessible par une adresse publique
        abort_unless($disk->exists($document->file_path), 404);

        return $disk->download($document->file_path, $this->fileName($document));
    }

    /** Affichage dans le navigateur (PDF, images) : mêmes contrôles que le téléchargement. */
    public function view(Employee $employee, Document $document): StreamedResponse
    {
        $this->authorizeDocument($employee, $document);

        return FileViewer::inline($document->file_path, $this->fileName($document));
    }

    private function authorizeDocument(Employee $employee, Document $document): void
    {
        // Employee et Document passent par le filtre automatique : une autre entreprise donne une 404.
        abort_unless($document->employee_id === $employee->id, 404);

        $resource = EmployeeService::documentResource($document->type);
        abort_unless(auth()->user()->canAccessRecord('read', $resource, $document), 403);
    }

    private function fileName(Document $document): string
    {
        return Str::slug($document->title ?: $document->type).'.'.pathinfo($document->file_path, PATHINFO_EXTENSION);
    }
}
