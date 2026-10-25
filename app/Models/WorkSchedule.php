<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/** Planning de travail : semaine standard, ou rotation (travail N jours puis repos M jours). */
class WorkSchedule extends Model
{
    use BelongsToCompany;

    protected $fillable = ['name', 'type', 'workdays', 'on_days', 'off_days', 'anchor_date', 'grants_annual_leave'];

    protected function casts(): array
    {
        return ['workdays' => 'array', 'anchor_date' => 'date', 'on_days' => 'integer', 'off_days' => 'integer', 'grants_annual_leave' => 'boolean'];
    }

    /** Planning standard non enregistré (semaine de travail de l'entreprise). */
    public static function standard(array $workdays): self
    {
        $schedule = new self();
        $schedule->forceFill(['type' => 'standard', 'workdays' => $workdays]);

        return $schedule;
    }

    /** Ce jour est-il un jour de travail PRÉVU (sans tenir compte des jours fériés) ? */
    public function isWorkday(Carbon $day): bool
    {
        if ($this->type === 'rotation' && $this->anchor_date && $this->on_days) {
            $cycle = max(1, (int) $this->on_days + (int) $this->off_days);
            $a = Carbon::create($this->anchor_date->year, $this->anchor_date->month, $this->anchor_date->day, 0, 0, 0, 'UTC');
            $d = Carbon::create($day->year, $day->month, $day->day, 0, 0, 0, 'UTC');
            $offset = (int) round(($d->getTimestamp() - $a->getTimestamp()) / 86400);

            return ((($offset % $cycle) + $cycle) % $cycle) < (int) $this->on_days;
        }

        return in_array($day->dayOfWeekIso, array_map('intval', $this->workdays ?: [1, 2, 3, 4, 5]), true);
    }

    /** Jours de repos (breaks) par an d'une rotation : 365 × repos ÷ cycle (ex. 28/14 → 122 j ; 28/7 → 73 j). */
    public function restDaysPerYear(): ?int
    {
        if ($this->type !== 'rotation' || ! $this->on_days) {
            return null;
        }

        return (int) round(365 * (int) $this->off_days / ((int) $this->on_days + (int) $this->off_days));
    }

    public function label(): string
    {
        if ($this->type !== 'rotation') {
            return $this->name.' (semaine standard)';
        }

        return "{$this->name} (rotation {$this->on_days} j travail / {$this->off_days} j repos, ≈ {$this->restDaysPerYear()} j de repos par an"
            .($this->grants_annual_leave === false ? ', sans congé annuel' : ', avec congé annuel').')';
    }
}
