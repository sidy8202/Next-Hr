<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\PayslipTemplate;
use App\Services\Payroll\NumberToWords;
use App\Services\Payroll\PayslipCumuls;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Rend un bulletin de paie en PDF (« barryvdh/laravel-dompdf » s'il est installé, sinon page HTML imprimable),
 * comme InvoiceRenderer et LeaveSheetService. Le bulletin est toujours rendu depuis son instantané figé :
 * il ne change pas si les règles, les salaires ou les rubriques évoluent après coup.
 */
class PayslipRenderer
{
    private const MONTHS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

    /** @return array{0: string, 1: string, 2: string} contenu, extension, type MIME */
    public function render(Payslip $payslip): array
    {
        $html = view('pdf.payslip', $this->data($payslip))->render();

        $pdf = class_exists(\Barryvdh\DomPDF\Facade\Pdf::class);
        $content = $pdf ? \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4')->output() : $html;

        return [$content, $pdf ? 'pdf' : 'html', $pdf ? 'application/pdf' : 'text/html'];
    }

    public static function filename(Payslip $payslip, string $extension): string
    {
        return 'bulletin-de-paie-' . $payslip->period . '-' . Str::slug((string) ($payslip->matricule ?: $payslip->employee_id)) . '.' . $extension;
    }

    public static function periodLabel(string $period): string
    {
        [$year, $month] = explode('-', $period);

        return ucfirst(self::MONTHS[(int) $month - 1]) . ' ' . $year;
    }

    /** @return array<string, mixed> */
    public function data(Payslip $payslip): array
    {
        $snapshot = $payslip->snapshot;
        $result = $snapshot['result'];
        $meta = $snapshot['meta'] ?? [];
        $employee = Employee::with(['department:id,code,name', 'position:id,title'])->find($payslip->employee_id);
        $company = Company::find($payslip->company_id);
        $template = $this->template();

        $start = Carbon::parse($payslip->period . '-01')->startOfDay();
        $end = $start->copy()->endOfMonth();
        $legal = collect($result['legal'] ?? []);

        $rows = Payslip::where('employee_id', $payslip->employee_id)
            ->where('period', '>=', substr($payslip->period, 0, 4) . '-01')->where('period', '<=', $payslip->period)
            ->where(fn ($q) => $q->where('status', 'final')->orWhere('id', $payslip->id))->get()
            ->map(fn ($p) => [
                'period' => $p->period, 'gross' => (int) $p->gross, 'its_base' => (int) ($p->snapshot['result']['totals']['its_base'] ?? 0),
                'employee_contributions' => (int) $p->employee_contributions, 'its' => (int) $p->its, 'net_to_pay' => (int) $p->net_to_pay,
            ])->all();

        $currency = $company?->currency ?: 'XOF';
        $currencyLabel = $currency === 'XOF' ? 'francs CFA' : $currency;
        $net = (int) $payslip->net_to_pay;
        $factor = (float) ($meta['factor'] ?? 1.0);

        return [
            'identity' => PayslipIdentity::forCompany((int) $payslip->company_id),
            'currencyLabel' => $currencyLabel,
            'accent' => '#0f766e',
            'payslip' => $payslip,
            'employee' => $employee,
            'meta' => $meta,
            'family' => $snapshot['family'] ?? [],
            'periodLabel' => self::periodLabel($payslip->period),
            'start' => $start,
            'end' => $end,
            'presence' => $factor < 1.0 ? (int) round($factor * 30) : null,
            'reference' => 'BP-' . $payslip->period . '-' . ($payslip->matricule ?: $payslip->employee_id),
            'draft' => $payslip->status !== 'final',
            'template' => [
                'show_employer_part' => (bool) ($template?->show_employer_part ?? false),
                'show_item_codes' => (bool) ($template?->show_item_codes ?? true),
                'group_lines' => (bool) ($template?->group_lines ?? true),
                'footer_text' => $template?->footer_text,
                'convention' => $template?->convention,
            ],
            'cols' => ($template?->show_item_codes ?? true) ? 6 : 5,
            'earnings' => $result['earnings'] ?? [],
            'grossDeductions' => $result['gross_deductions'] ?? [],
            'employeeLegal' => $legal->where('party', 'employee')->values()->all(),
            'employerLegal' => $legal->where('party', 'employer')->values()->all(),
            'netDeductions' => $result['net_deductions'] ?? [],
            'totals' => $result['totals'],
            'cumuls' => PayslipCumuls::upTo($rows, $payslip->period),
            'year' => substr($payslip->period, 0, 4),
            'words' => NumberToWords::frCapitalized($net),
            'money' => fn (int $n): string => number_format($n, 0, ',', "\u{00A0}"),
            'issuedAt' => now(),
            'checksum' => strtoupper(substr((string) $payslip->input_hash, 0, 10)),
        ];
    }

    protected function template(): ?PayslipTemplate
    {
        return PayslipTemplate::where('is_default', true)->first() ?? PayslipTemplate::orderBy('id')->first();
    }
}
