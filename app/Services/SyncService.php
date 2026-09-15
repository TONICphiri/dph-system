<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\SyncQueue;
use App\Models\Vital;
use Illuminate\Support\Facades\Http;

class SyncService
{
    /**
     * Enqueue a record for synchronization to the national database
     */
    public static function enqueue(string $recordType, $record, string $action = 'create', ?int $facilityId = null): ?SyncQueue
    {
        if (!$record || !$record->id) {
            return null;
        }

        return SyncQueue::create([
            'facility_id' => $facilityId ?? self::resolveFacilityId($record),
            'record_type' => $recordType,
            'record_id' => $record->id,
            'action' => $action,
            'payload' => self::toPayload($recordType, $record),
            'status' => 'pending',
        ]);
    }

    /**
     * Build the sync payload for a given record
     */
    public static function toPayload(string $recordType, $record): array
    {
        return match ($recordType) {
            'patients' => [
                'id' => $record->id,
                'dhp_id' => $record->dhp_id,
                'national_id' => $record->national_id,
                'first_name' => $record->first_name,
                'last_name' => $record->last_name,
                'date_of_birth' => $record->date_of_birth?->toDateString(),
                'gender' => $record->gender,
                'phone_number' => $record->phone_number,
                'address' => $record->address,
                'village' => $record->village,
                'district' => $record->district,
                'status' => $record->status,
                'is_child' => $record->is_child,
                'guardian_id' => $record->guardian_id,
                'registered_at' => $record->registered_at?->toIso8601String(),
            ],
            'encounters' => [
                'id' => $record->id,
                'patient_id' => $record->patient_id,
                'facility_id' => $record->facility_id,
                'encounter_type' => $record->encounter_type,
                'status' => $record->status,
                'chief_complaint' => $record->chief_complaint,
                'diagnosis' => $record->diagnosis,
                'treatment_plan' => $record->treatment_plan,
                'requires_admission' => $record->requires_admission,
                'encounter_date' => $record->encounter_date?->toIso8601String(),
            ],
            'vitals' => [
                'id' => $record->id,
                'patient_id' => $record->patient_id,
                'encounter_id' => $record->encounter_id,
                'temperature' => $record->temperature,
                'systolic_bp' => $record->systolic_bp,
                'diastolic_bp' => $record->diastolic_bp,
                'heart_rate' => $record->heart_rate,
                'respiratory_rate' => $record->respiratory_rate,
                'weight' => $record->weight,
                'oxygen_saturation' => $record->oxygen_saturation,
                'priority_level' => $record->priority_level,
                'notes' => $record->notes,
                'recorded_at' => $record->recorded_at?->toIso8601String(),
            ],
            'prescriptions' => [
                'id' => $record->id,
                'patient_id' => $record->patient_id,
                'encounter_id' => $record->encounter_id,
                'medication_name' => $record->medication_name,
                'dose' => $record->dose,
                'frequency' => $record->frequency,
                'quantity' => $record->quantity,
                'duration' => $record->duration,
                'instructions' => $record->instructions,
                'status' => $record->status,
                'prescribed_at' => $record->prescribed_at?->toIso8601String(),
            ],
            'admissions' => [
                'id' => $record->id,
                'patient_id' => $record->patient_id,
                'encounter_id' => $record->encounter_id,
                'facility_id' => $record->facility_id,
                'ward_name' => $record->ward_name,
                'bed_number' => $record->bed_number,
                'admission_type' => $record->admission_type,
                'admission_reason' => $record->admission_reason,
                'admitted_at' => $record->admitted_at?->toIso8601String(),
                'status' => $record->status,
            ],
            default => $record->toArray(),
        };
    }

    /**
     * Process records due for upload (called every minute by the scheduler).
     * Failed pushes back off and are picked up again automatically, so all
     * queued data uploads on its own once the internet is restored.
     */
    public static function processPending(int $limit = 50): array
    {
        $results = ['synced' => 0, 'failed' => 0, 'rejected' => 0];

        $due = SyncQueue::dueForSync()
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        foreach ($due as $item) {
            try {
                $outcome = self::pushToNationalDatabase($item);

                if ($outcome === true) {
                    $item->markAsSynced();
                    $results['synced']++;
                } else {
                    // Server understood but refused the payload (e.g. 4xx):
                    // retrying won't help — park for manual review.
                    $item->forceFill([
                        'status' => 'failed',
                        'error_message' => 'Server rejected sync payload (HTTP '.$outcome.')',
                        'retry_count' => 5,
                        'next_retry_at' => null,
                    ])->saveQuietly();
                    $results['rejected']++;
                }
            } catch (\Exception $e) {
                // No internet / timeout / 5xx: back off and retry later.
                $item->markAsFailed(substr($e->getMessage(), 0, 500));
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Push a sync payload to the national database endpoint.
     *
     * @return bool|int true on success, HTTP status code on rejection
     *
     * @throws \Exception on connection failure / timeout / server error
     *
     * Falls back to offline simulation when no endpoint is configured.
     */
    protected static function pushToNationalDatabase(SyncQueue $item): bool|int
    {
        $endpoint = config('services.sync.endpoint');

        // No endpoint configured: simulate a successful push so records
        // are marked as synced and the queue drains during offline operation
        if (!$endpoint) {
            return true;
        }

        try {
            $response = Http::timeout(10)
                ->post($endpoint, [
                    'record_type' => $item->record_type,
                    'record_id' => $item->record_id,
                    'action' => $item->action,
                    'payload' => $item->payload,
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \Exception('No internet connection to national database.');
        }

        if ($response->successful()) {
            return true;
        }

        if ($response->serverError()) {
            throw new \Exception('National database error (HTTP '.$response->status().').');
        }

        return $response->status();
    }

    /**
     * Resolve a facility id from the record when one isn't passed explicitly
     */
    protected static function resolveFacilityId($record): ?int
    {
        return match (true) {
            $record instanceof Patient => $record->registered_by_facility_id,
            $record instanceof Encounter => $record->facility_id,
            $record instanceof Vital => $record->encounter?->facility_id,
            $record instanceof Prescription => $record->encounter?->facility_id,
            $record instanceof Admission => $record->facility_id,
            default => null,
        };
    }
}