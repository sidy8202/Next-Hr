<?php

namespace App\Services;

use App\Mail\LeaveApprovedMail;
use App\Models\Document;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Support\LeaveCalendar;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Fiche de congé : générée à la validation finale, rangée dans les documents de l'employé,
 * envoyée par email (avec dates de début, de fin et de reprise) et notifiée dans l'application.
 * PDF si « barryvdh/laravel-dompdf » est installé, sinon page HTML imprimable.
 */
class LeaveSheetService
{
    public function __construct(private EmployeeNotifier $notifier)
    {
    }

    public function issue(LeaveRequest $leave, ?User $approver = null): Document
    {
        $employee = $leave->employee;
        $employee->loadMissing(['department:id,code,name', 'position:id,title']);

        $html = view('pdf.leave-sheet', [
            'leave' => $leave,
            'employee' => $employee,
            'company' => config('app.name'),
            'resume' => LeaveCalendar::nextWorkday($employee->company_id, $leave->end_date, $employee),
            'steps' => $leave->steps->where('status', 'approved'),
        ])->render();

        $pdf = class_exists(\Barryvdh\DomPDF\Facade\Pdf::class);
        $content = $pdf ? \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4')->output() : $html;
        $extension = $pdf ? 'pdf' : 'html';

        $path = "companies/{$employee->company_id}/leaves/".Str::uuid().".{$extension}";
        Storage::disk('local')->put($path, $content);

        $document = Document::create([
            'employee_id' => $employee->id,
            'type' => 'leave_sheet',
            'title' => 'Fiche de congé '.$leave->periodLabel(),
            'file_path' => $path,
            'uploaded_by' => $approver?->id ?? auth()->id(),
        ]);

        $leave->update(['document_id' => $document->id]);

        $this->email($leave, $employee, $path, $extension);

        $this->notifier->notifyEmployee($employee, 'Votre congé est validé',
            'Votre congé '.$leave->periodLabel().' est validé. Votre fiche de congé est disponible. Bonne jouissance de votre congé !',
            route('leaves.show', $leave->id), false);

        return $document;
    }

    private function email(LeaveRequest $leave, $employee, string $path, string $extension): void
    {
        $to = $employee->work_email ?: $employee->personal_email
            ?: User::where('employee_id', $employee->id)->value('email');

        if (! $to) {
            return;
        }

        try {
            Mail::to($to)->send(new LeaveApprovedMail($leave, config('app.name'), $path, $extension));
        } catch (\Throwable $e) {
            report($e); // l'échec de l'email n'annule pas la validation
        }
    }
}
