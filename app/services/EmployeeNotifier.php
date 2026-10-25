<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use App\Notifications\HrEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Prévient un employé (ou le compte d'un employé) d'un événement qui le concerne : document, congé, absence…
 * Envoi toujours APRÈS validation de la transaction en cours : une annulation n'envoie jamais rien.
 * Un échec d'envoi n'annule jamais l'action.
 */
class EmployeeNotifier
{
    public function notifyEmployee(Employee|int $employee, string $title, string $message, ?string $url = null, bool $mail = true): void
    {
        $employee = $employee instanceof Employee ? $employee : Employee::find($employee);

        if (! $employee) {
            return;
        }

        DB::afterCommit(function () use ($employee, $title, $message, $url, $mail) {
            try {
                $user = User::where('employee_id', $employee->id)->where('is_active', true)->first();

                if ($user) {
                    $user->notify(new HrEvent($title, $message, $url, $mail));

                    return;
                }

                $email = $employee->work_email ?: $employee->personal_email;

                if ($email && $mail) {
                    Notification::route('mail', $email)->notify(new HrEvent($title, $message, $url, true));
                }
            } catch (Throwable $e) {
                report($e);
            }
        });
    }

    /** @param  iterable<User>  $users */
    public function notifyUsers(iterable $users, string $title, string $message, ?string $url = null, bool $mail = true): void
    {
        DB::afterCommit(function () use ($users, $title, $message, $url, $mail) {
            foreach ($users as $user) {
                try {
                    $user->notify(new HrEvent($title, $message, $url, $mail));
                } catch (Throwable $e) {
                    report($e);
                }
            }
        });
    }
}
