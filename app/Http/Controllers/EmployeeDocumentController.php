<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController extends Controller
{

    public function show(Employee $employee, Document $document)
    {
        abort_unless($document->employee_id === $employee->id, 404);

        $resource = EmployeeService::documentResource($document->type);
        abort_unless(auth()->user()->canAccessRecord('read', $resource, $document), 403);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($document->file_path), 404);

        $mimeType = $disk->mimeType($document->file_path);

        return response()->file($disk->path($document->file_path), [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.basename($document->file_path).'"',
        ]);
    }
    
    public function download(Employee $employee, Document $document): StreamedResponse
    {
        // Employee et Document passent par le filtre automatique : une autre entreprise donne une 404.
        abort_unless($document->employee_id === $employee->id, 404);

        $resource = EmployeeService::documentResource($document->type);
        abort_unless(auth()->user()->canAccessRecord('read', $resource, $document), 403);

        $disk = Storage::disk('local'); // dossier PRIVÉ : jamais accessible par une adresse publique
        abort_unless($disk->exists($document->file_path), 404);

        $name = Str::slug($document->title ?: $document->type).'.'.pathinfo($document->file_path, PATHINFO_EXTENSION);

        return $disk->download($document->file_path, $name);
    }
}
