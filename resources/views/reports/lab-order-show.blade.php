<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lab Order Details — Digital Health Passport</title>
<style>
body{margin:0;font-family:Arial,Helvetica,sans-serif;background:#f2f7f7;color:#222}
.top{background:#0E7490;color:#fff;padding:10px 16px;font-size:14px}
.top a{color:#fff;margin-right:14px}
.wrap{max-width:640px;margin:16px auto;padding:0 12px}
.box{background:#fff;border:1px solid #ccc;padding:16px;margin-bottom:16px}
h1{font-size:22px;margin:0 0 12px}
h1::before{content:"";display:inline-block;width:12px;height:12px;background:#0E7490;clip-path:polygon(0 0,0 100%,100% 100%);margin-right:8px}
.field{margin-bottom:12px}
.field .k{font-size:12px;color:#555;margin:0}
.field .v{font-size:16px;margin:2px 0 0}
.field .v.big{font-size:22px}
hr{border:0;border-top:1px solid #ccc;margin:14px 0}
.badge{display:inline-block;padding:2px 8px;font-size:13px;font-weight:bold;border:1px solid #999;background:#eee}
.badge-requested{background:#fff8e1;border-color:#D97706;color:#92400e}
.badge-pending{background:#fff7ed;border-color:#D97706;color:#9a3412}
.badge-results{background:#ecfdf5;border-color:#16A34A;color:#065f46}
.badge-cancelled{background:#f3f4f6;border-color:#999;color:#555}
a{color:#0E7490}
</style>
</head>
<body>
<div class="top"><a href="{{ route('dashboard') }}">Dashboard</a><a href="{{ route('lab.orders.index') }}">All lab orders</a><a href="{{ route('patients.show', $labOrder->patient) }}">Back to patient</a></div>
<div class="wrap">
@can('view_reports')
<div class="box">
    <h1>Lab Order Details</h1>

    <div class="field"><p class="k">Patient</p><p class="v"><strong>{{ $labOrder->patient->full_name }}</strong></p><p class="k">{{ $labOrder->patient->dhp_id }}</p></div>
    <div class="field"><p class="k">Test Name</p><p class="v big"><strong>{{ $labOrder->test_name }}</strong></p></div>
    <div class="field"><p class="k">Test Type</p><p class="v">{{ ucfirst($labOrder->test_type) }}</p></div>
    <div class="field"><p class="k">Status</p><p class="v"><span class="badge badge-{{ $labOrder->status }}">{{ ucfirst($labOrder->status) }}</span></p></div>

    @if ($labOrder->status === 'results')
    <div class="field"><p class="k">Result Value</p><p class="v big"><strong>{{ $labOrder->result_value }}</strong>@if ($labOrder->result_units) <span style="font-size:13px;color:#555">{{ $labOrder->result_units }}</span>@endif</p></div>
    @endif

    @if ($labOrder->result_description)
    <div class="field"><p class="k">Result Description</p><p class="v" style="font-size:14px">{{ $labOrder->result_description }}</p></div>
    @endif

    <hr>
    <div class="field"><p class="k">Requested By</p><p class="v">{{ $labOrder->requestedBy ? $labOrder->requestedBy->full_name : 'Unknown' }}</p><p class="k">{{ $labOrder->requested_at->format('M d, Y H:i') }}</p></div>

    @if ($labOrder->completed_at)
    <div class="field"><p class="k">Completed At</p><p class="v">{{ $labOrder->completed_at->format('M d, Y H:i') }}</p></div>
    @endif

    <hr>
    <div class="field"><p class="k">Description</p>
        @if ($labOrder->description)
        <p class="v" style="font-size:14px">{{ $labOrder->description }}</p>
        @else
        <p class="v" style="font-size:14px;color:#555"><i>No description provided</i></p>
        @endif
    </div>
</div>
@endcan
</div>
</body>
</html>
