<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Create Lab Order — Digital Health Passport</title>
<style>
body{margin:0;font-family:Arial,Helvetica,sans-serif;background:#f2f7f7;color:#222}
.top{background:#0E7490;color:#fff;padding:10px 16px;font-size:14px}
.top a{color:#fff;margin-right:14px}
.wrap{max-width:640px;margin:16px auto;padding:0 12px}
.box{background:#fff;border:1px solid #ccc;padding:16px;margin-bottom:16px}
h1{font-size:22px;margin:0 0 4px}
h1::before{content:"";display:inline-block;width:12px;height:12px;background:#0E7490;clip-path:polygon(0 0,0 100%,100% 100%);margin-right:8px}
.sub{color:#555;font-size:14px;margin:0 0 12px}
label{display:block;font-size:13px;font-weight:bold;margin:10px 0 4px}
input,select,textarea{width:100%;padding:8px;border:1px solid #999;font-size:14px;box-sizing:border-box}
button{background:#0E7490;color:#fff;border:0;padding:10px 16px;font-size:14px;cursor:pointer}
.btn-gray{background:#eee;color:#222;border:1px solid #999;padding:10px 16px;font-size:14px;text-decoration:none;display:inline-block}
.row{margin-top:14px}
.err{background:#fdecea;border:1px solid #c00;color:#900;padding:8px;margin-bottom:10px;font-size:14px}
a{color:#0E7490}
</style>
</head>
<body>
<div class="top"><a href="{{ route('dashboard') }}">Dashboard</a><a href="{{ route('patients.show', $patient) }}">Back to patient</a></div>
<div class="wrap">
@can('create_patient')
<div class="box">
    <h1>Create Lab Order</h1>
    <p class="sub">Patient: <strong>{{ $patient->full_name }}</strong> ({{ $patient->dhp_id }})</p>

    @if ($errors->any())
        <div class="err">
            <ul style="margin:0;padding-left:18px">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('lab.orders.store') }}" method="POST">
        @csrf
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">

        <label for="test_type">Test Type</label>
        <select name="test_type" id="test_type">
            <option value="">Select test type</option>
            <option value="Malaria RDT">Malaria RDT</option>
            <option value="Blood Glucose">Blood Glucose</option>
            <option value="Complete Blood Count (CBC)">CBC</option>
            <option value="Urinalysis">Urinalysis</option>
            <option value="Chest X-ray">Chest X-ray</option>
            <option value="HIV Test">HIV Test</option>
            <option value="Hepatitis B">Hepatitis B</option>
            <option value="Stool Exam">Stool Exam</option>
            <option value="Other">Other</option>
        </select>

        <label for="test_name">Test Name</label>
        <input type="text" name="test_name" id="test_name" placeholder="e.g., Malaria Rapid Diagnostic Test" required>

        <label for="description">Description (Optional)</label>
        <textarea name="description" id="description" rows="3"></textarea>

        <label for="encounter_id">Associated Encounter</label>
        <select name="encounter_id" id="encounter_id">
            <option value="">None (use latest encounter)</option>
            @foreach ($patient->encounters as $encounter)
                <option value="{{ $encounter->id }}" {{ $latestEncounter && $encounter->id == $latestEncounter->id ? 'selected' : '' }}>
                    {{ $encounter->encounter_type }} - {{ $encounter->encounter_date->format('M d, Y') }}
                </option>
            @endforeach
        </select>

        <div class="row">
            <button type="submit">Create Lab Order</button>
            <a class="btn-gray" href="{{ route('patients.show', $patient) }}">Cancel</a>
        </div>
    </form>
</div>
@endcan
</div>
</body>
</html>
