<?php

namespace App\Services;

use App\Mail\PermissionApprovedMail;
use App\Models\Document;
use App\Models\PermissionRequest;
use App\Models\User;
use App\Support\LeaveCalendar;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Fiche de permission : dates de début et de fin, reprise, message ; PDF si dompdf est installé, sinon page HTML imprimable. */
class PermissionSheetService
{
    public function __construct(private EmployeeNotifier $notifier)
    {
    }

    public function issue(PermissionRequest $request, ?User $approver = null): Document
    {
        $employee = $request->employee;
        $employee->loadMissing(['department:id,code,name', 'position:id,title']);

        $resume = $request->isHours() ? null : LeaveCalendar::nextWorkday($employee->company_id, $request->end_date, $employee);

        $html = view('pdf.permission-sheet', [
            'request' => $request, 'employee' => $employee, 'company' => config('app.name'), 'resume' => $resume,
            'message' => $request->type?->sheet_message ?: config('permission_rules.sheet_message'),
            'steps' => $request->steps->where('status', 'approved'),
        ])->render();

        $pdf = class_exists(\Barryvdh\DomPDF\Facade\Pdf::class);
        $content = $pdf ? \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4')->output() : $html;
        $extension = $pdf ? 'pdf' : 'html';

        $path = "companies/{$employee->company_id}/permissions/".Str::uuid().".{$extension}";
        Storage::disk('local')->put($path, $content);

        $document = Document::create([
            'employee_id' => $employee->id,
            'type' => 'permission_sheet',
            'title' => 'Fiche de permission '.$request->periodLabel(),
            'file_path' => $path,
            'uploaded_by' => $approver?->id ?? auth()->id(),
        ]);

        $request->update(['document_id' => $document->id]);

        $to = $employee->work_email ?: $employee->personal_email ?: User::where('employee_id', $employee->id)->value('email');

        if ($to) {
            try {
                Mail::to($to)->send(new PermissionApprovedMail($request, config('app.name'), $path, $extension, $resume, $request->type?->sheet_message ?: config('permission_rules.sheet_message')));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->notifier->notifyEmployee($employee, 'Votre permission est accordée',
            'Votre permission '.$request->periodLabel().' est accordée. Votre fiche de permission est disponible.', route('permissions.show', $request->id), false);

        return $document;
    }
}
