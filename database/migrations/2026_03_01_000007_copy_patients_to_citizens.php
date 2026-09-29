<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 data carry-over: copy patients into citizens.
 * The patients table is NOT dropped and no clinical columns are removed
 * (requires explicit approval). Reuses existing passport numbers so
 * printed cards keep working; new citizens get MW-DHP-YYYY-XXXXXX.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('patients') || ! Schema::hasTable('citizens')) {
            return;
        }

        $patients = DB::table('patients')->orderBy('id')->get();

        foreach ($patients as $patient) {
            $district = DB::table('districts')->where('id', $patient->district_id)->value('name') ?? 'Unknown';

            $userId = DB::table('users')->where('patient_id', $patient->id)->value('id');

            $exists = DB::table('citizens')
                ->where('passport_id', $patient->passport_number)
                ->exists();

            if ($exists) {
                continue;
            }

            if ($patient->national_id && DB::table('citizens')->where('national_id', $patient->national_id)->exists()) {
                continue;
            }

            DB::table('citizens')->insert([
                'passport_id' => $patient->passport_number,
                'national_id' => $patient->national_id,
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'sex' => $patient->sex,
                'date_of_birth' => $patient->date_of_birth,
                'district' => $district,
                'village' => $patient->village,
                'email' => $patient->email,
                'phone' => $patient->phone,
                'user_id' => $userId,
                'pin_hash' => null,
                'created_by' => $patient->registered_by,
                'created_at' => $patient->created_at ?? now(),
                'updated_at' => $patient->updated_at ?? now(),
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally left blank: never delete carried-over citizen rows on rollback.
    }
};
