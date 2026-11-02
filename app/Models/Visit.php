<?php

namespace App\Models;

use App\Enums\VisitStageType;
use App\Enums\VisitStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A staged visit: reception, triage, consultation and dispensing. Each stage
 * is credited to the worker who recorded it and the visit locks once the
 * pharmacist has handed over every prescribed medicine.
 */
class Visit extends Model
{
    protected $fillable = [
        'patient_id',
        'facility_id',
        'reason',
        'status',
        'follow_up_on',
        'started_by',
        'started_at',
        'completed_by',
        'completed_at',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => VisitStatus::class,
            'follow_up_on' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(VisitStage::class)->orderBy('recorded_at');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(VisitObservation::class)->orderBy('recorded_at');
    }

    public function medications(): HasMany
    {
        return $this->hasMany(VisitMedication::class);
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    public function isCompleted(): bool
    {
        return $this->status === VisitStatus::Completed;
    }

    public function stage(VisitStageType $type): ?VisitStage
    {
        return $this->stages->firstWhere('type', $type);
    }

    public function isStageAvailable(VisitStageType $type): bool
    {
        return $type !== VisitStageType::Dispensing
            && $this->missingStagePrerequisite($type) === null;
    }

    /**
     * The stage types that have been recorded, in workflow order.
     *
     * @return \Illuminate\Support\Collection<int, VisitStageType>
     */
    public function recordedStages(): \Illuminate\Support\Collection
    {
        return $this->stages
            ->pluck('type')
            ->filter(fn (VisitStageType $type) => $type !== VisitStageType::Dispensing)
            ->values();
    }

    /**
     * The first stage that has not yet been recorded, or null when every
     * clinical stage is complete (dispensing is recorded separately when
     * medicines are handed over).
     */
    public function nextStage(): ?VisitStageType
    {
        $order = [VisitStageType::Reception, VisitStageType::Triage, VisitStageType::Consultation];

        foreach ($order as $type) {
            if (! $this->stages()->where('type', $type->value)->exists()) {
                return $type;
            }
        }

        return null;
    }

    /**
     * The stage recorded immediately before the given type, or null when there
     * is none. Used so the next actor can see what the last actor wrote.
     */
    public function previousStage(VisitStageType $type): ?VisitStage
    {
        $order = [VisitStageType::Reception, VisitStageType::Triage, VisitStageType::Consultation];

        $index = array_search($type, $order, true);

        if ($index === false || $index === 0) {
            return null;
        }

        foreach (array_slice($order, 0, $index) as $earlier) {
            if ($stage = $this->stage($earlier)) {
                return $stage;
            }
        }

        return null;
    }

    /**
     * Whether every clinical stage (reception, triage, consultation) has been
     * recorded, regardless of whether medicines have been dispensed.
     */
    public function clinicalStagesComplete(): bool
    {
        return $this->nextStage() === null;
    }

    public function missingStagePrerequisite(VisitStageType $type): ?VisitStageType
    {
        $prerequisites = match ($type) {
            VisitStageType::Reception, VisitStageType::Dispensing => [],
            VisitStageType::Triage => [VisitStageType::Reception],
            VisitStageType::Consultation => [VisitStageType::Reception, VisitStageType::Triage],
        };

        foreach ($prerequisites as $prerequisite) {
            if (! $this->stages()->where('type', $prerequisite->value)->exists()) {
                return $prerequisite;
            }
        }

        return null;
    }

    /**
     * The newest reading of a vital sign, or null when it was never taken.
     */
    public function latestObservation(string $type): ?VisitObservation
    {
        return $this->observations
            ->where('type', $type)
            ->sortByDesc('recorded_at')
            ->first();
    }

    /**
     * The newest reading of each vital sign, keyed by type.
     *
     * @return \Illuminate\Support\Collection<string, VisitObservation>
     */
    public function latestObservations(): \Illuminate\Support\Collection
    {
        return $this->observations
            ->sortBy('recorded_at')
            ->keyBy('type');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('locked_at');
    }
}
