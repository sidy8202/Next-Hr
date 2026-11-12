<?php

namespace App\Services;

use App\Mail\PayslipMail;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\PayslipDelivery;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Envoi d'un bulletin clôturé : e-mail professionnel ET e-mail personnel (chacun avec le PDF en pièce jointe),
 * plus une notification dans l'application. Chaque canal est tracé dans payslip_deliveries (envoyé, échec, ouvert, vu).
 * Une panne d'e-mail ne bloque jamais l'action en cours, comme pour les fiches de congé et les factures.
 */
class PayslipMailer
{
    public function __construct(private EmployeeNotifier $notifier, private PayslipRenderer $renderer)
    {
    }

    /**
     * @param  bool  $onlyMissing  ne renvoie pas les canaux déjà envoyés avec succès
     * @return array{sent: int, failed: int, skipped: int, errors: array<int, string>}
     */
    public function send(Payslip $payslip, ?User $by = null, bool $onlyMissing = false): array
    {
        $result = ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []];
        $employee = Employee::find($payslip->employee_id);

        if (! $employee) {
            $result['errors'][] = 'Employé introuvable.';

            return $result;
        }

        $channels = [
            'work_email' => $this->valid($employee->work_email ?? null),
            'personal_email' => $this->valid($employee->personal_email ?? null),
        ];

        $content = $extension = $mime = null;
        $first = null;

        foreach ($channels as $channel => $address) {
            $delivery = $this->row($payslip, $employee, $channel, $by);
            $delivery->address = $address;

            if ($address === null) {
                $result['skipped']++;
                $delivery->save();

                continue;
            }

            if ($onlyMissing && $delivery->status === 'sent') {
                $result['skipped']++;

                continue;
            }

            // Même adresse en professionnel et en personnel : un seul message.
            if ($first !== null && strtolower($first['address']) === strtolower($address)) {
                $delivery->forceFill(['status' => 'sent', 'sent_at' => $first['sent_at'], 'error' => null])->save();
                $result['skipped']++;

                continue;
            }

            if ($content === null) {
                [$content, $extension, $mime] = $this->renderer->render($payslip);
            }

            $mail = new PayslipMail(
                $employee->full_name,
                (string) (\App\Models\Company::find($payslip->company_id)?->name ?? config('app.name')),
                PayslipRenderer::periodLabel($payslip->period),
                $content,
                $extension,
                $mime,
                PayslipRenderer::filename($payslip, $extension),
                route('payslips.track', $delivery->token),
                route('payslips.mine'),
            );

            $delivery->attempts++;

            try {
                Mail::to($address)->send($mail);
                $delivery->forceFill(['status' => 'sent', 'sent_at' => now(), 'error' => null])->save();
                $result['sent']++;
                $first ??= ['address' => $address, 'sent_at' => now()];
            } catch (Throwable $e) {
                report($e);
                $delivery->forceFill(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500)])->save();
                $result['failed']++;
                $result['errors'][] = $address . ' : ' . mb_substr($e->getMessage(), 0, 200);
            }
        }

        $this->notifyInApp($payslip, $employee, $by, $onlyMissing);

        return $result;
    }

    /** Enregistre que l'employé a consulté son bulletin dans l'application (signal fiable). */
    public static function markViewed(Payslip $payslip): void
    {
        try {
            $row = PayslipDelivery::where('payslip_id', $payslip->id)->where('channel', 'app')->first();

            if (! $row) {
                $row = new PayslipDelivery(['payslip_id' => $payslip->id, 'employee_id' => $payslip->employee_id, 'channel' => 'app', 'status' => 'sent', 'token' => Str::random(40)]);
                $row->company_id = $payslip->company_id;
            }

            if ($row->viewed_at === null) {
                $row->viewed_at = now();
                $row->save();
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function notifyInApp(Payslip $payslip, Employee $employee, ?User $by, bool $onlyMissing): void
    {
        $row = $this->row($payslip, $employee, 'app', $by);

        if ($onlyMissing && $row->status === 'sent' && $row->sent_at !== null) {
            return;
        }

        try {
            $label = PayslipRenderer::periodLabel($payslip->period);
            $this->notifier->notifyEmployee($employee, 'Votre bulletin de paie est disponible',
                'Votre bulletin de paie de ' . $label . ' est disponible. Vous pouvez le consulter et le télécharger dans « Mes bulletins ».',
                route('payslips.mine', [], false), false);

            $row->forceFill(['status' => 'sent', 'sent_at' => now(), 'error' => null, 'attempts' => $row->attempts + 1])->save();
        } catch (Throwable $e) {
            report($e);
            $row->forceFill(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500)])->save();
        }
    }

    private function row(Payslip $payslip, Employee $employee, string $channel, ?User $by): PayslipDelivery
    {
        $row = PayslipDelivery::where('payslip_id', $payslip->id)->where('channel', $channel)->first();

        if (! $row) {
            $row = new PayslipDelivery(['payslip_id' => $payslip->id, 'employee_id' => $employee->id, 'channel' => $channel, 'status' => 'pending', 'attempts' => 0, 'token' => Str::random(40)]);
            $row->company_id = $payslip->company_id;
        }

        $row->triggered_by = $by?->id ?? $row->triggered_by;

        return $row;
    }

    private function valid(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
}
