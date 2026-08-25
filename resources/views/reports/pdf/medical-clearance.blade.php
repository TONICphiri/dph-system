<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Medical Travel Clearance</title>
    <style>
        body { color: #0f172a; font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.5; }
        .header { border-bottom: 3px solid #0f766e; margin-bottom: 18px; padding-bottom: 12px; }
        .title { color: #075985; font-size: 22px; font-weight: bold; margin: 0; text-transform: uppercase; }
        .subtitle { color: #0f766e; font-size: 13px; margin: 2px 0 0; }
        .section { margin-bottom: 16px; }
        .section-title { background: #e0f2fe; border-left: 4px solid #0284c7; color: #075985; font-size: 14px; font-weight: bold; margin-bottom: 8px; padding: 6px 8px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { border: 1px solid #cbd5e1; padding: 7px; vertical-align: top; }
        .label { color: #475569; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .value { color: #0f172a; font-size: 12px; margin-top: 2px; }
        .box { border: 1px solid #cbd5e1; min-height: 44px; padding: 8px; white-space: pre-line; }
        .signature { margin-top: 26px; width: 100%; }
        .signature td { padding-top: 24px; width: 50%; }
        .line { border-top: 1px solid #0f172a; padding-top: 6px; }
        .footer { border-top: 1px solid #cbd5e1; bottom: 0; color: #64748b; font-size: 10px; left: 0; padding-top: 8px; position: fixed; right: 0; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">Medical Travel Clearance Report</p>
        <p class="subtitle">{{ $facility?->name ?? 'Digital Health Passport System' }}</p>
        <p class="subtitle">Generated from verified DHP patient record: {{ $patient->dhp_id }}</p>
    </div>

    <div class="section">
        <div class="section-title">Patient Identification</div>
        <table class="grid">
            <tr>
                <td>
                    <div class="label">Full Name</div>
                    <div class="value">{{ $patient->full_name }}</div>
                </td>
                <td>
                    <div class="label">Date of Birth</div>
                    <div class="value">{{ $patient->date_of_birth?->format('M d, Y') ?? 'Not recorded' }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="label">ID / Passport Number</div>
                    <div class="value">{{ $patient->national_id ?: 'Not recorded' }}</div>
                </td>
                <td>
                    <div class="label">DHP ID</div>
                    <div class="value">{{ $patient->dhp_id }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Medical Information</div>
        <div class="label">Clear Diagnosis</div>
        <div class="box">{{ $report['diagnosis'] }}</div>
        <br>
        <div class="label">Current Condition Summary</div>
        <div class="box">{{ $report['current_condition'] }}</div>
        <br>
        <div class="label">Active Treatment Plan</div>
        <div class="box">{{ $report['treatment_plan'] }}</div>
    </div>

    <div class="section">
        <div class="section-title">Medications and Devices</div>
        <div class="label">Prescribed Drugs with Generic Names and Dosages</div>
        <div class="box">{{ $report['medications'] ?: 'No active medications listed.' }}</div>
        <br>
        <div class="label">Necessary Medical Equipment</div>
        <div class="box">{{ $report['medical_equipment'] ?: 'No medical equipment listed.' }}</div>
    </div>

    <div class="section">
        <div class="section-title">Travel Clearance</div>
        <div class="label">Doctor's Fitness for Travel Statement</div>
        <div class="box">{{ $report['travel_clearance'] }}</div>
        <br>
        <div class="label">Specific Flight Accommodations</div>
        <div class="box">{{ $report['flight_accommodations'] ?: 'No special flight accommodations listed.' }}</div>
    </div>

    <div class="section">
        <div class="section-title">Physician Sign-off</div>
        <table class="grid">
            <tr>
                <td>
                    <div class="label">Physician</div>
                    <div class="value">{{ $report['physician_name'] }}</div>
                </td>
                <td>
                    <div class="label">Contact Information</div>
                    <div class="value">{{ $report['physician_contact'] }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="label">Date of Issue</div>
                    <div class="value">{{ \Illuminate\Support\Carbon::parse($report['issue_date'])->format('M d, Y') }}</div>
                </td>
                <td>
                    <div class="label">Facility Contact</div>
                    <div class="value">{{ $facility?->phone_number ?? 'Not recorded' }}</div>
                </td>
            </tr>
        </table>

        <table class="signature">
            <tr>
                <td><div class="line">Doctor's Signature</div></td>
                <td><div class="line">Official Facility Stamp</div></td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Generated by {{ $generatedBy->name }} through the Digital Health Passport System on {{ now()->format('M d, Y H:i') }}.
    </div>
</body>
</html>
