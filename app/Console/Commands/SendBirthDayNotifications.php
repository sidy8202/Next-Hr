<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Employee;
use App\Notifications\BirthdayNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendBirthdayNotifications extends Command
{
    protected $signature = 'app:send-birthday-notifications {--force-hour : Ignore l\'heure locale (utile pour les tests)}';

    protected $description = 'Envoie les messages de joyeux anniversaire du jour (idempotent, dans le fuseau horaire de chaque entreprise).';

    private const DEFAULT_MESSAGE = 'Joyeux anniversaire {PRENOM} ! Toute l\'équipe de {ENTREPRISE} vous souhaite une excellente journée.';

    public function handle(): int
    {
        $sent = 0;

        foreach (Company::where('status', '!=', 'suspended')->cursor() as $company) {
            $today = now($company->timezone ?: config('app.timezone'));

            // Chaque entreprise est traitée à partir de 7 h, dans SON fuseau horaire.
            if (! $this->option('force-hour') && $today->hour < 7) {
                continue;
            }

            $template = DB::table('settings')->where('company_id', $company->id)->where('key', 'birthday_message')->value('value')
                ?: self::DEFAULT_MESSAGE;

            // Aucun utilisateur connecté dans une commande : le filtre automatique ne s'applique pas,
            // on filtre donc explicitement par entreprise.
            $employees = Employee::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('status', 'active')
                ->whereNotNull('birth_date')
                ->where(function ($q) use ($today) {
                    $q->where(fn ($q) => $q->whereMonth('birth_date', $today->month)->whereDay('birth_date', $today->day));

                    // Le 29 février est fêté le 28 les années non bissextiles.
                    if (! $today->isLeapYear() && $today->month === 2 && $today->day === 28) {
                        $q->orWhere(fn ($q) => $q->whereMonth('birth_date', 2)->whereDay('birth_date', 29));
                    }
                })
                ->with('user')
                ->get();

            foreach ($employees as $employee) {
                $user = $employee->user;

                if (! $user || ! $user->is_active) {
                    continue;
                }

                // Idempotent : une seule notification d'anniversaire par utilisateur et par an.
                $already = $user->notifications()
                    ->where('type', BirthdayNotification::class)
                    ->whereYear('created_at', $today->year)
                    ->exists();

                if ($already) {
                    continue;
                }

                $message = str_replace(['{PRENOM}', '{ENTREPRISE}'], [$employee->first_name, $company->name], $template);
                $user->notify(new BirthdayNotification($message));
                $sent++;
            }
        }

        $this->info("{$sent} notification(s) d'anniversaire envoyée(s).");

        return self::SUCCESS;
    }
}
