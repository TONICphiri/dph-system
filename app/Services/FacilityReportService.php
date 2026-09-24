<?php

namespace App\Services;

use App\Enums\AdmissionStatus;
use App\Enums\BedStatus;
use App\Enums\CareType;
use App\Enums\PrescriptionStatus;
use App\Enums\VisitStatus;
use App\Models\Admission;
use App\Models\Bed;
use App\Models\Facility;
use App\Models\PrescriptionItem;
use App\Models\Vaccination;
use App\Models\Visit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Summary figures for the facility report page, for a chosen date range.
 */
class FacilityReportService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(Facility $facility, Carbon $from, Carbon $to): array
    {
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();

        $visits = Visit::query()->where('facility_id', $facility->id)->whereBetween('checked_in_at', [$start, $end]);

        $totalBeds = Bed::query()->whereHas('ward', fn ($query) => $query->where('facility_id', $facility->id))->count();
        $occupiedBeds = Bed::query()->whereHas('ward', fn ($query) => $query->where('facility_id', $facility->id))
            ->where('status', BedStatus::Occupied)->count();

        return [
            'total_visits' => (clone $visits)->count(),
            'outpatient_visits' => (clone $visits)->where('care_type', CareType::Outpatient)->count(),
            'completed_visits' => (clone $visits)->where('status', VisitStatus::Completed)->count(),
            'admissions' => Admission::query()->where('facility_id', $facility->id)->whereBetween('admitted_at', [$start, $end])->count(),
            'discharges' => Admission::query()->where('facility_id', $facility->id)->where('status', AdmissionStatus::Discharged)
                ->whereBetween('discharged_at', [$start, $end])->count(),
            'vaccinations' => Vaccination::query()->where('facility_id', $facility->id)->whereBetween('administered_on', [$start->toDateString(), $end->toDateString()])->count(),
            'total_beds' => $totalBeds,
            'occupied_beds' => $occupiedBeds,
            'occupancy_rate' => $totalBeds > 0 ? round($occupiedBeds / $totalBeds * 100) : 0,
            'top_diagnoses' => (clone $visits)->whereNotNull('diagnosis')
                ->select('diagnosis', DB::raw('COUNT(*) as total'))
                ->groupBy('diagnosis')->orderByDesc('total')->limit(10)->get(),
            'top_medicines' => PrescriptionItem::query()
                ->whereHas('prescription', fn ($query) => $query->where('facility_id', $facility->id)
                    ->where('status', PrescriptionStatus::Dispensed)
                    ->whereBetween('dispensed_at', [$start, $end]))
                ->select('medicine_name', DB::raw('SUM(quantity_dispensed) as total'))
                ->groupBy('medicine_name')->orderByDesc('total')->limit(10)->get(),
            'daily_visits' => (clone $visits)
                ->select(DB::raw('DATE(checked_in_at) as day'), DB::raw('COUNT(*) as total'))
                ->groupBy('day')->orderBy('day')->pluck('total', 'day'),
        ];
    }
}
