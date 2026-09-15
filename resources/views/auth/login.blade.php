<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign-in | {{ $displayFacility->name ?? config('app.name', 'Digital Health Passport') }} · Health Passport</title>
<meta name="description" content="NIN sign-in for the Digital Health Passport.">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" media="print" onload="this.media='all'" />
<noscript><link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" /></noscript>
<style>
:root{
  --blue:#005EB8; --blue-dark:#003087; --navy:#0A2A5E; --teal:#007A87;
  --ink:#111111; --mut:#4B5563; --line:#D7DEE6; --bg:#FFFFFF; --panel:#F1F7FD;
  --sans:Figtree,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
  --mono:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;font-family:var(--sans);background:var(--bg);color:var(--ink);-webkit-font-smoothing:antialiased}
a{color:var(--blue)}
:focus-visible{outline:3px solid var(--blue);outline-offset:2px;border-radius:0}
.wrap{max-width:1100px;margin:0 auto;padding:0 22px}
/* utility bar */
.util{background:var(--navy);color:#fff;font-size:12.5px}
.util .wrap{display:flex;justify-content:space-between;gap:12px;padding-top:8px;padding-bottom:8px;flex-wrap:wrap}
.util span{letter-spacing:.02em}
/* header */
.site-header{background:#fff;border-bottom:1px solid var(--line);position:sticky;top:0;z-index:20}
.site-header .wrap{display:flex;align-items:center;justify-content:space-between;gap:16px;padding-top:12px;padding-bottom:12px}
.brand{display:flex;align-items:center;gap:12px;text-decoration:none;color:var(--ink)}
.brand-mark{width:44px;height:44px;border-radius:0;background:#fff;border:1px solid var(--line);color:#fff;display:grid;place-items:center;font-weight:700;font-size:26px;overflow:hidden}
.brand-mark img{width:100%;height:100%;object-fit:contain;padding:3px;display:block}
.brand small{display:block;font-size:11px;letter-spacing:.18em;color:var(--mut);font-weight:600}
.brand strong{display:block;font-size:18px;color:var(--ink)}
.main-nav{display:flex;gap:22px;font-size:15px;font-weight:600}
.main-nav a{color:var(--ink);text-decoration:none}
.main-nav a:hover{color:var(--blue);text-decoration:underline}
/* hero */
.hero{background:var(--panel);border-bottom:1px solid var(--line)}
.hero-grid{display:grid;grid-template-columns:1fr 400px;gap:44px;padding:48px 0;align-items:start}
.eyebrow{font-family:var(--mono);font-size:12px;letter-spacing:.16em;text-transform:uppercase;color:var(--teal);font-weight:600;margin:0}
.hero h1{font-size:clamp(38px,4.6vw,58px);line-height:1.05;letter-spacing:-.02em;margin:12px 0;color:var(--ink)}
.hero h1 span{color:var(--blue)}
.sub{font-size:17px;line-height:1.6;color:var(--mut);max-width:30rem;margin:0}
.hero-points{list-style:none;margin:22px 0 0;padding:0;display:grid;gap:10px}
.hero-points li{display:flex;gap:10px;align-items:flex-start;font-size:15px}
.hero-points li::before{content:"✓";color:#fff;background:var(--teal);border-radius:0;width:22px;height:22px;display:grid;place-items:center;font-size:13px;font-weight:700;flex:none;margin-top:1px}
/* login card */
.login-card{background:#fff;border:1px solid var(--line);border-top:5px solid var(--blue);border-radius:0;box-shadow:0 12px 32px rgba(0,48,135,.10);padding:28px 26px}
.login-card h2{font-size:26px;margin:0;color:var(--ink)}
.login-card .hint{color:var(--mut);font-size:14.5px;line-height:1.55;margin:6px 0 18px}
.field{margin-bottom:14px}
.field label{display:block;font-size:13px;font-weight:600;margin-bottom:6px;color:var(--ink)}
.field input[type=email],.field input[type=password],.field input[type=text]{width:100%;font-size:15px;padding:12px 13px;border-radius:0;border:1px solid #9AA7B4;background:#fff;color:#111}
.field input:focus{border-color:var(--blue);outline:3px solid rgba(0,94,184,.2)}
.pw-wrap{position:relative}
.pw-wrap button{position:absolute;right:8px;top:7px;font-size:12px;font-weight:600;border:1px solid #9AA7B4;background:#fff;color:var(--blue);border-radius:0;padding:6px 10px;cursor:pointer}
.err{background:#FEF2F2;border:1px solid #DC2626;color:#991B1B;border-radius:0;padding:10px 12px;font-size:14px;margin-bottom:12px}
.ok-status{background:#ECFDF5;border:1px solid var(--teal);color:#065F46;border-radius:0;padding:10px 12px;font-size:14px;margin-bottom:12px}
.row-between{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:14px 0;flex-wrap:wrap}
.remember{display:flex;align-items:center;gap:8px;font-size:14px}
.remember input{width:17px;height:17px;accent-color:var(--blue)}
.forgot{font-size:14px}
.submit{width:100%;font-size:16px;font-weight:700;background:var(--blue);color:#fff;border:0;border-radius:0;padding:14px;cursor:pointer}
.submit:hover{background:var(--blue-dark)}
.secure-note{margin-top:14px;font-size:12.5px;color:var(--mut);display:flex;gap:8px;align-items:flex-start;line-height:1.55}
/* info */
.info{padding:44px 0}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px}
.info-card{border:1px solid var(--line);border-radius:0;padding:26px;background:#fff}
.info-card h3{font-size:22px;margin:0 0 4px;color:var(--ink)}
.info-card .kicker{font-family:var(--mono);font-size:12px;letter-spacing:.16em;color:var(--blue);text-transform:uppercase;font-weight:600}
/* right-angled triangle motif — flat solid marker on every section kicker/title */
.info-card .kicker::before,.info-card h3::before{content:"";display:inline-block;width:11px;height:11px;background:#0E7490;clip-path:polygon(0 0,0 100%,100% 100%);margin-right:7px}
.info-card ul{list-style:none;margin:14px 0 0;padding:0;display:grid;gap:0}
.info-card li{font-size:15px;padding:10px 0 10px 26px;position:relative;border-top:1px solid #EDF1F5;line-height:1.5}
.info-card li:first-child{border-top:0}
.info-card li::before{content:"";position:absolute;left:2px;top:16px;width:9px;height:9px;border-radius:0;background:var(--blue)}
.info-card li small{display:block;color:var(--mut);margin-top:2px}
/* footer */
.site-footer{background:var(--navy);color:#DCE6F2;margin-top:8px}
.foot-grid{display:grid;grid-template-columns:1.1fr .9fr 1.2fr;gap:30px;padding:46px 0 30px}
.foot-brand{display:flex;gap:12px;align-items:center}
.foot-brand .brand-mark{background:#fff;color:var(--navy)}
.foot-grid h4{font-size:12px;letter-spacing:.16em;color:#fff;margin:0 0 14px;text-transform:uppercase}
.foot-grid p{font-size:14.5px;line-height:1.7;color:#B9C9DE;margin:14px 0 0;max-width:26rem}
.addr{line-height:1.8;font-size:14.5px;font-style:normal}
.addr strong{color:#fff}
.touch-list{list-style:none;margin:0 0 14px;padding:0;display:grid;gap:10px;font-size:14.5px}
.touch-list small{font-family:var(--mono);font-size:11px;letter-spacing:.12em;color:#8FA6C6;display:block}
.touch-list a{color:#fff}
.map-frame{border:1px solid rgba(255,255,255,.3);border-radius:0;overflow:hidden;background:#000}
.map-frame iframe{width:100%;height:200px;border:0;display:block}
.map-link{font-size:12.5px;margin:8px 0 0}
.map-link a{color:#fff}
.foot-base{border-top:1px solid rgba(255,255,255,.18);font-size:12.5px;color:#8FA6C6}
.foot-base .wrap{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;padding-top:16px;padding-bottom:20px}
.foot-base a{color:#fff}
@media (max-width:900px){
  .hero-grid,.info-grid,.foot-grid{grid-template-columns:1fr}
  .main-nav{display:none}
}
@media (prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
</style>
</head>
<body>

<div class="util"><div class="wrap"><span>{{ $displayFacility->name ?? config('app.name', 'Digital Health Passport') }}{{ $displayFacility?->district ? ' · '.$displayFacility->district.', Malawi' : ' · Malawi' }}</span>@if($displayFacility?->working_hours)<span>{{ $displayFacility->working_hours }}</span>@endif</div></div>

<header class="site-header">
  <div class="wrap">
    <a class="brand" href="/">@include('partials.brand-mark')<span><small>HEALTH PASSPORT</small><strong>{{ $displayFacility->name ?? config('app.name', 'Digital Health Passport') }}</strong></span></a>
    <nav class="main-nav" aria-label="Primary"><a href="/">Home</a><a href="#services">Departments &amp; services</a><a href="#contact">Contact</a></nav>
  </div>
</header>

<main>
<section class="hero">
  <div class="wrap hero-grid">
    <div>
      <p class="eyebrow">Staff sign-in</p>
      <h1>Health <span>Passport</span></h1>
      <p class="sub">One lifelong record for every patient{{ $displayFacility?->name ? ' at '.$displayFacility->name : '' }}. Sign in with your National ID Number to open registration, triage, consultation, pharmacy, laboratory and ward.</p>
      <ul class="hero-points">
        <li>Role-based access, you only see what your job needs</li>
        <li>National ID and QR lookup in under 2 seconds</li>
        <li>Works through internet blackouts with offline sync</li>
      </ul>
    </div>
    <div class="login-card">
      <p class="eyebrow">NIN sign-in</p>
      <h2>Welcome back</h2>
      <p class="hint">Sign in with your National ID Number. Accounts are issued at registered facilities, no self-registration.</p>

      @if (session('status'))
        <div class="ok-status" role="status">{{ session('status') }}</div>
      @endif

        <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="field">
          <label for="nin">National ID Number</label>
          <input id="nin" type="text" name="nin" value="{{ old('nin') }}" required autofocus autocomplete="username" inputmode="text" placeholder="e.g. A123456789" style="width:100%;font-size:15px;padding:12px 13px;border-radius:0;border:1px solid #9AA7B4;background:#fff;color:#111">
          @error('nin')<div class="err" role="alert">{{ $message }}</div>@enderror
          @error('email')<div class="err" role="alert">{{ $message }}</div>@enderror
        </div>
        <div class="field">
          <label for="password">Password</label>
          <div class="pw-wrap">
            <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
            <button type="button" id="pwToggle" aria-controls="password">Show</button>
          </div>
          @error('password')<div class="err" role="alert">{{ $message }}</div>@enderror
        </div>
        <div class="row-between">
          <label class="remember" for="remember_me"><input id="remember_me" type="checkbox" name="remember"> Remember me</label>
          <span class="forgot">Locked out? Wait 15 minutes and try again.</span>
        </div>
        <button class="submit" type="submit">Log in</button>
      </form>
      <p class="secure-note"><span aria-hidden="true">🔒</span><span>Patient records are confidential and audited. Never share your sign-in details.</span></p>
      <p class="secure-note"><span>Not enrolled yet? Visit a registered facility with your national ID document.</span></p>
      <p class="secure-note"><span>Public credential check: <a href="{{ route('verify.scan') }}">verify a QR credential</a>.</span></p>
    </div>
  </div>
</section>

<section class="info" id="services"><div class="wrap info-grid">
  <div class="info-card">
    <p class="kicker">Departments available</p>
    <h3>Find your desk</h3>
    <ul>
    @if(!empty($displayFacility?->departments))
      @foreach($displayFacility->departments as $dept)
      <li>{{ trim($dept) }}</li>
      @endforeach
    @else
      <li>Registration <small>National ID lookup, new profiles, QR printing</small></li>
      <li>Triage <small>Vitals and queue priority</small></li>
      <li>Consultation <small>History, diagnosis, prescriptions</small></li>
      <li>Pharmacy <small>QR-verified dispensing</small></li>
      <li>Laboratory <small>Orders and results</small></li>
      <li>Ward <small>Admissions and discharge</small></li>
    @endif
    </ul>
  </div>
  <div class="info-card">
    <p class="kicker">Services</p>
    <h3>What the passport does</h3>
    <ul>
    @if(!empty($displayFacility?->services))
      @foreach($displayFacility->services as $svc)
      <li>{{ trim($svc) }}</li>
      @endforeach
    @else
      <li>Find anyone in seconds <small>National ID or Health Passport ID</small></li>
      <li>Scan-and-go identification <small>QR at every desk</small></li>
      <li>Lifelong visit timeline <small>Encounters, vitals, prescriptions</small></li>
      <li>Safe prescribing &amp; dispensing <small>Stock updated live</small></li>
      <li>Works through blackouts <small>Offline-first sync</small></li>
    @endif
    </ul>
  </div>
</div></section>
</main>

<footer class="site-footer" id="contact">
  <div class="wrap foot-grid">
    <div>
      <div class="foot-brand">@if($displayFacility?->logo_path)<img src="{{ asset('storage/'.$displayFacility->logo_path) }}" alt="{{ $displayFacility->name }} logo" style="width:44px;height:44px;border-radius:0;object-fit:cover">@else @include('partials.brand-mark') @endif<div><div style="font-size:11px;letter-spacing:.18em;color:#8FA6C6;font-weight:600">HEALTH PASSPORT</div><div style="font-size:20px;color:#fff;font-weight:700">{{ $displayFacility->name ?? config('app.name', 'Digital Health Passport') }}</div></div></div>
      <p>One lifelong digital health record for every patient, readable at every desk in the facility.</p>
      @if($displayFacility?->working_hours)<p>{{ $displayFacility->working_hours }}</p>@endif
    </div>
    <div>
      <h4>Address</h4>
      <address class="addr">
        <strong>{{ $displayFacility->name ?? config('app.name', 'Digital Health Passport') }}</strong><br>
        @if($displayFacility?->address){!! nl2br(e($displayFacility->address)) !!}<br>@endif
        {{ $displayFacility?->district ? $displayFacility->district.', Malawi' : 'Malawi' }}
      </address>
    </div>
    <div>
      <h4>Get in touch</h4>
      <ul class="touch-list">
        @if($displayFacility?->phone_number)<li><small>PHONE</small><a href="tel:{{ preg_replace('/[^+\d]/', '', $displayFacility->phone_number) }}">{{ $displayFacility->phone_number }}</a>@if($displayFacility?->secondary_phone) · <a href="tel:{{ preg_replace('/[^+\d]/', '', $displayFacility->secondary_phone) }}">{{ $displayFacility->secondary_phone }}</a>@endif</li>@endif
        @if($displayFacility?->email)<li><small>EMAIL</small><a href="mailto:{{ $displayFacility->email }}">{{ $displayFacility->email }}</a></li>@endif
        @if(!$displayFacility?->phone_number && !$displayFacility?->email)<li>Contact your registered facility for help signing in.</li>@endif
      </ul>
      @if($displayFacility?->map_url)
      <div class="map-frame" id="mapFrame"><iframe title="Map: {{ $displayFacility->name }}" loading="lazy" src="{{ str_contains($displayFacility->map_url, 'embed') ? $displayFacility->map_url : $displayFacility->map_url }}"></iframe></div>
      <p class="map-link" id="mapLink"><a href="{{ $displayFacility->map_url }}" target="_blank" rel="noopener">Open full map →</a></p>
      <p class="map-link" id="mapOffline" style="display:none">Map unavailable offline. Contact details above still work.</p>
      @endif
    </div>
  </div>
  <div class="foot-base"><div class="wrap"><span>HEALTH PASSPORT · {{ strtoupper($displayFacility->name ?? config('app.name', 'Digital Health Passport')) }}</span><span><a href="/">Home</a></span></div></div>
</footer>

<script>
(function(){
  if('serviceWorker' in navigator){navigator.serviceWorker.register('/sw.js').catch(function(){});}
  var b=document.getElementById('pwToggle'),p=document.getElementById('password');
  if(b&&p){b.addEventListener('click',function(){var s=p.type==='password';p.type=s?'text':'password';b.textContent=s?'Hide':'Show';p.focus();});}
  /* Offline: the map needs internet — hide it instead of showing a dead frame. */
  if(!navigator.onLine){
    var f=document.getElementById('mapFrame'),l=document.getElementById('mapLink'),o=document.getElementById('mapOffline');
    if(f){f.style.display='none';}
    if(l){l.style.display='none';}
    if(o){o.style.display='block';}
  }
})();
</script>
</body>
</html>
