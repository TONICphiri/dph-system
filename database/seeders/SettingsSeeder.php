<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Database\Seeder;

/**
 * Default system settings. Existing values are never overwritten, so the
 * seeder is safe to run again after a System Administrator has made changes.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['system_name', 'Digital Health Passport', 'System name', 'general', 'text', 'Shown in the page header, emails and printed reports.'],
            ['passport_number_prefix', 'MW', 'Passport number prefix', 'general', 'text', 'Two or three letters placed before every new passport number.'],
            ['child_separation_age', '18', 'Age at which a child record separates from the mother', 'general', 'number', 'Checked every night. The child keeps all records and gets an independent passport.'],
            ['email_notifications_enabled', '0', 'Send notifications by email', 'notifications', 'boolean', 'When switched off, notifications are only shown inside the system.'],
            ['facility_types', "Central Hospital\nDistrict Hospital\nCommunity Hospital\nHealth Centre\nClinic\nDispensary", 'Facility types', 'lists', 'list', 'One item per line.'],
            ['ownership_types', "Government\nChristian Health Association of Malawi\nPrivate\nNon governmental organisation", 'Ownership types', 'lists', 'list', 'One item per line.'],
            ['ward_types', "General Medical\nSurgical\nMaternity\nPaediatric\nIntensive Care\nIsolation", 'Ward types', 'lists', 'list', 'One item per line.'],
            ['relationship_types', "Mother\nFather\nSpouse\nSon\nDaughter\nBrother\nSister\nGuardian\nFriend\nOther", 'Emergency contact relationships', 'lists', 'list', 'One item per line.'],
            ['campaign_categories', "Vaccination\nDental\nNutrition\nMaternal health\nCancer screening\nGeneral health", 'Health campaign categories', 'lists', 'list', 'One item per line.'],
            ['dosage_forms', "Tablet\nCapsule\nSyrup\nSuspension\nInjection\nCream\nOintment\nDrops\nInhaler", 'Dosage forms', 'lists', 'list', 'One item per line.'],
            ['dosage_frequencies', "Once daily\nTwice daily\nThree times daily\nFour times daily\nEvery 8 hours\nAt night\nWhen required", 'Dosage frequencies', 'lists', 'list', 'One item per line.'],
            ['regions', "Northern\nCentral\nSouthern", 'Regions', 'lists', 'list', 'Used when adding districts. One item per line.'],
        ];

        foreach ($settings as [$key, $value, $label, $group, $inputType, $helpText]) {
            Setting::query()->firstOrCreate(['key' => $key], [
                'value' => $value,
                'label' => $label,
                'group' => $group,
                'input_type' => $inputType,
                'help_text' => $helpText,
            ]);
        }

        app(SettingService::class)->flush();
    }
}
