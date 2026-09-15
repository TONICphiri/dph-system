<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Health Passport | {{ $displayFacility->name ?? config('app.name', 'Digital Health Passport') }}</title>
<meta name="description" content="One lifelong health passport for every patient. National ID + QR, works offline.">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" media="print" onload="this.media='all'" />
<noscript><link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" /></noscript>
<style>
:root{
  --primary:#0E7490; --sky-900:#0C4A6E; --sky-800:#075985; --sky-700:#0369A1; --sky-300:#7DD3FC; --sky-200:#BAE6FD; --sky-100:#E0F2FE; --sky-50:#F0F9FF;
  --cyan-100:#A5F3FC; --cyan-50:#ECFEFF;
  --teal-700:#0F766E; --teal-50:#F0FDFD;
  --green-400:#4ADE80; --green-50:#F0FDF4; --emerald-700:#047857;
  --blue-600:#1D4ED8; --blue-50:#EFF6FF;
  --indigo-600:#4338CA; --indigo-50:#EEF2FF;
  --red-700:#B91C1C; --red-50:#FEF2F2;
  --slate-900:#0F172A; --slate-600:#475569; --slate-500:#64748B; --slate-200:#E2E8F0; --slate-100:#F1F5F9; --slate-50:#F8FAFC;
  --white:#FFFFFF; --amber-700:#B45309; --amber-50:#FFFBEB;
  --sans:Figtree,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;font-family:var(--sans);background:var(--slate-50);color:var(--slate-900);-webkit-font-smoothing:antialiased}
a{color:var(--sky-700)}
:focus-visible{outline:3px solid var(--sky-700);outline-offset:2px;border-radius:0}
.wrap{max-width:1120px;margin:0 auto;padding:0 22px}
/* header — same as app navigation */
.site-header{background:var(--white);border-bottom:1px solid var(--sky-100);position:sticky;top:0;z-index:20}
.site-header .wrap{display:flex;align-items:center;justify-content:space-between;gap:16px;height:64px}
.brand{display:flex;align-items:center;gap:12px;text-decoration:none;color:var(--slate-900)}
.brand-mark{width:40px;height:40px;border-radius:0;background:var(--white);border:1px solid var(--sky-100);color:var(--white);display:grid;place-items:center;font-weight:700;font-size:24px;box-shadow:0 0 0 4px var(--sky-100);overflow:hidden}
.brand-mark img{width:100%;height:100%;object-fit:contain;padding:4px;display:block}
.brand small{display:block;font-size:11px;font-weight:700;letter-spacing:.12em;color:var(--sky-900)}
.brand strong{display:block;font-size:12px;font-weight:500;color:var(--teal-700)}
.main-nav{display:flex;gap:24px;font-size:14.5px;font-weight:600}
.main-nav a{color:var(--slate-600);text-decoration:none}
.main-nav a:hover{color:var(--sky-800)}
.btn{display:inline-flex;align-items:center;gap:8px;font-size:14px;font-weight:600;padding:12px 22px;border-radius:0;text-decoration:none;border:1px solid transparent;cursor:pointer}
.btn-primary{background:var(--sky-700);color:var(--white)}
.btn-primary:hover{background:var(--sky-800)}
.btn-outline{background:var(--white);color:var(--sky-800);border-color:var(--sky-200)}
.btn-outline:hover{background:var(--sky-50)}
/* hero band — solid primary per design system §6.2 */
.hero-band{background:var(--primary);color:var(--white)}
.hero-band .wrap{display:grid;grid-template-columns:1.05fr .95fr;gap:36px;padding:56px 0;align-items:center}
.eyebrow{font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:var(--cyan-100);font-weight:600;margin:0}
.secure-badge{display:inline-flex;align-items:center;gap:8px;font-size:11.5px;letter-spacing:.04em;text-transform:uppercase;font-weight:700;color:var(--white);background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.3);padding:6px 12px;margin-bottom:14px}
.secure-badge::before{content:"";width:7px;height:7px;background:var(--green-400);flex:none}
.hero-band h1{font-size:clamp(38px,4.8vw,60px);line-height:1.05;letter-spacing:-.02em;margin:12px 0;color:var(--white)}
.hero-band .sub{font-size:17px;line-height:1.6;color:var(--sky-100);max-width:32rem;margin:0}
.hero-meta{display:flex;gap:26px;margin-top:24px;flex-wrap:wrap}
.hero-meta span{font-size:13px;color:var(--sky-200)}
.hero-meta b{display:block;font-size:18px;color:var(--white)}
/* slideshow — one image at a time, rotating on the admin-set interval */
.hero-slideshow{background:var(--white);border:1px solid var(--sky-200);border-radius:0;padding:14px}
.hero-slideshow .slide-stage{display:grid}
.hero-slideshow .slide-stage img{width:100%;height:auto;border-radius:0;grid-area:1/1;opacity:0;transition:opacity 1.2s ease}
.hero-slideshow .slide-stage img.on{opacity:1}
.slide-dots{display:flex;gap:8px;justify-content:center;margin-top:10px}
.slide-dots button{width:12px;height:12px;border-radius:0;border:1px solid var(--primary);background:var(--white);padding:0;cursor:pointer}
.slide-dots button.on{background:var(--primary)}
.slide-caption{text-align:center;font-size:13px;color:var(--slate-600);margin:8px 0 0;min-height:20px}
/* sections */
section.block{padding:60px 0}
.kicker{font-size:12px;font-weight:700;letter-spacing:.14em;color:var(--teal-700);text-transform:uppercase;margin:0}
/* right-angled triangle motif — flat solid marker on every section kicker */
.kicker::before{content:"";display:inline-block;width:12px;height:12px;background:var(--primary);clip-path:polygon(0 0,0 100%,100% 100%);margin-right:8px}
.card h3::before{content:"";display:inline-block;width:11px;height:11px;background:var(--primary);clip-path:polygon(0 0,0 100%,100% 100%);margin-right:7px}
h2.title{font-size:clamp(28px,3.4vw,40px);line-height:1.1;letter-spacing:-.02em;margin:10px 0;color:var(--sky-900)}
.lede{color:var(--slate-600);font-size:17px;line-height:1.6;max-width:44rem;margin:0}
.grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:26px}
.card{background:var(--white);border:1px solid var(--sky-100);border-radius:0;padding:22px;box-shadow:0 1px 2px rgba(2,32,71,.05)}
.card h3{font-size:19px;margin:0 0 6px;color:var(--sky-900)}
.card p{margin:0;color:var(--slate-600);font-size:14.5px;line-height:1.6}
/* feature icon cards — square icon mark reuses the brand-mark motif */
.feature-icon{width:44px;height:44px;background:var(--primary);color:var(--white);display:grid;place-items:center;margin-bottom:14px}
.feature-icon svg{width:22px;height:22px}
/* public credential lookup — mirrors cta-band but on a light panel */
.lookup-panel{background:var(--white);border:1px solid var(--sky-100);border-radius:0;padding:40px 36px;text-align:center;box-shadow:0 1px 2px rgba(2,32,71,.05)}
.lookup-panel .kicker{display:inline-flex}
.lookup-panel .lede{margin-left:auto;margin-right:auto}
.card .tag{display:inline-block;font-size:11.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:5px 11px;border-radius:0;margin-bottom:12px}
.tag-red{background:var(--red-50);color:var(--red-700)}
.tag-amber{background:var(--amber-50);color:var(--amber-700)}
.tag-slate{background:var(--slate-100);color:var(--slate-600)}
/* journey cards — mirrors dashboard patient-journey */
.journey{background:var(--white);border:1px solid var(--sky-100);border-radius:0;padding:28px;box-shadow:0 1px 2px rgba(2,32,71,.05)}
.journey-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:20px}
.step{border-radius:0;border:1px solid var(--sky-100);padding:16px;display:block;text-decoration:none;color:inherit}
.step:hover{border-color:var(--sky-300)}
.step small{display:block;font-size:11.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;margin-bottom:4px}
.step strong{display:block;font-size:16px;color:var(--sky-900)}
.step p{margin:6px 0 0;font-size:13.5px;color:var(--slate-600);line-height:1.55}
.step-r{background:var(--sky-50)}.step-r small{color:var(--sky-700)}
.step-t{background:var(--cyan-50)}.step-t small{color:var(--primary)}
.step-c{background:var(--teal-50)}.step-c small{color:var(--teal-700)}
.step-p{background:var(--green-50)}.step-p small{color:var(--emerald-700)}
.step-l{background:var(--blue-50)}.step-l small{color:var(--blue-600)}
.step-w{background:var(--indigo-50)}.step-w small{color:var(--indigo-600)}
/* cta band */
.cta-band{background:var(--primary);border-radius:0;padding:48px 36px;text-align:center;color:var(--white)}
.cta-band h2{font-size:clamp(28px,3.6vw,44px);margin:0;letter-spacing:-.02em}
.cta-band p{color:var(--sky-100);max-width:36rem;margin:12px auto 0;line-height:1.6}
.cta-row{display:flex;gap:12px;margin-top:24px;justify-content:center;flex-wrap:wrap}
.btn-white{background:var(--white);color:var(--sky-800)}
.btn-white:hover{background:var(--sky-100)}
.btn-ghostlight{border-color:rgba(255,255,255,.5);color:var(--white)}
.btn-ghostlight:hover{background:rgba(255,255,255,.12)}
/* footer */
.site-footer{background:var(--white);border-top:1px solid var(--sky-100);margin-top:56px}
.footer-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;padding-top:36px;padding-bottom:28px}
.footer-col h4{font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--teal-700);margin:0 0 12px}
.footer-list{list-style:none;margin:0;padding:0;display:grid;gap:8px;font-size:14px;color:var(--slate-600);line-height:1.5}
.footer-list a{color:var(--slate-600);text-decoration:none}
.footer-list a:hover{color:var(--sky-800);text-decoration:underline}
.footer-base{border-top:1px solid var(--sky-100)}
.footer-base .wrap{padding-top:16px;padding-bottom:20px;font-size:13.5px;color:var(--slate-500)}
.footer-base strong{color:var(--sky-900)}
.reveal{opacity:0;transform:translateY(14px);transition:opacity .6s ease,transform .6s ease}
.reveal.in{opacity:1;transform:none}
@media (max-width:920px){
  .hero-band .wrap,.grid3,.journey-grid,.footer-grid{grid-template-columns:1fr}
  .main-nav{display:none}
}
@media (prefers-reduced-motion:reduce){
  html{scroll-behavior:auto}.reveal{opacity:1;transform:none}
}
</style>
</head>
<body>

<header class="site-header">
  <div class="wrap">
    <a class="brand" href="/">@include('partials.brand-mark')<span><small>DHP SYSTEM</small><strong>Digital Health Passport</strong></span></a>
    <nav class="main-nav" aria-label="Primary"><a href="#journey">Patient journey</a><a href="#services">Services</a><a href="{{ route('verify.scan') }}">Verify credential</a><a href="#contact">Contact</a></nav>
    <a class="btn btn-primary" href="/login">Sign in</a>
  </div>
</header>

<section class="hero-band">
  <div class="wrap">
    <div>
      <p class="secure-badge">Tamper-proof · QR verified</p>
      <p class="eyebrow">{{ $displayFacility->name ?? config('app.name', 'Digital Health Passport') }}{{ $displayFacility?->district ? ' · '.$displayFacility->district : '' }}</p>
      <h1>Health Passport</h1>
      <p class="sub">One lifelong digital record for every patient, found in seconds by National ID or QR scan, at any desk in the facility, even during internet blackouts.</p>
      <div class="cta-row" style="justify-content:flex-start">
        <a class="btn btn-white" href="/login">Open staff sign-in</a>
        <a class="btn btn-ghostlight" href="#journey">See the patient journey</a>
      </div>
      <div class="hero-meta">
        <span><b>&lt; 2 sec</b>National ID lookup</span>
        <span><b>60 sec</b>background sync</span>
        <span><b>Zero</b>duplicate files</span>
      </div>
    </div>
    <div class="hero-slideshow" aria-label="Health passport illustrations" data-interval="{{ $landingSlideInterval ?? 8 }}">
      <div class="slide-stage">
        @foreach(($landingSlides ?? []) as $i => $slide)
          <img src="{{ $slide['src'] }}" alt="{{ $slide['caption'] ?? 'Slide '.($i + 1) }}" class="{{ $i === 0 ? 'on' : '' }}">
        @endforeach
      </div>
      @if(count($landingSlides ?? []) > 1)
        <p class="slide-caption" id="slideCaption">{{ ($landingSlides[0]['caption'] ?? '') }}</p>
        <div class="slide-dots" id="slideDots">
          @foreach(($landingSlides ?? []) as $i => $slide)
            <button type="button" data-slide="{{ $i }}" class="{{ $i === 0 ? 'on' : '' }}" aria-label="Show image {{ $i + 1 }}"></button>
          @endforeach
        </div>
      @endif
    </div>
  </div>
</section>

<section class="block" style="padding-bottom:0"><div class="wrap">
  <p class="kicker reveal">A unified platform for health records</p>
  <h2 class="title reveal">Secure by design, verifiable in seconds.</h2>
  <p class="lede reveal">Every record is tied to the patient's National ID and protected by a signed QR credential, so any desk, and any authorised verifier, trusts what they see.</p>
  <div class="grid3">
    <div class="card reveal">
      <div class="feature-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="0"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg></div>
      <h3>Secure records</h3>
      <p>Every visit, vaccination and lab result is written locally and audited, then synced to the facility's central record.</p>
    </div>
    <div class="card reveal">
      <div class="feature-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect><path d="M14 14h3v3h-3zM20 14v3M14 20h3M20 20v.01"></path></svg></div>
      <h3>QR credentials</h3>
      <p>Each Health Passport ID carries a scannable QR code, printable at reception and re-issued in seconds if lost.</p>
    </div>
    <div class="card reveal">
      <div class="feature-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"></path><path d="m9 12 2 2 4-4"></path></svg></div>
      <h3>Instant verification</h3>
      <p>Anyone can check a credential's status without ever seeing the underlying medical record. See <a href="{{ route('verify.scan') }}">verify a credential</a>.</p>
    </div>
  </div>
</div></section>

<section class="block"><div class="wrap">
  <p class="kicker reveal">Why paper fails us</p>
  <h2 class="title reveal">The booklet can't travel.<br>The person does.</h2>
  <p class="lede reveal">A patient treated in one hospital arrives at the next with nothing, the history stays in a drawer at home. That means repeated tests, unsafe prescriptions, and queues that start from zero.</p>
  <div class="grid3">
    <div class="card reveal"><span class="tag tag-red">Lost</span><h3>The booklet disappears</h3><p>Lost on a minibus, taking allergies, past admissions and chronic medication with it.</p></div>
    <div class="card reveal"><span class="tag tag-amber">Damaged</span><h3>Rain and torn pages</h3><p>Faded ink and handwriting no other facility can read becomes the diagnosis.</p></div>
    <div class="card reveal"><span class="tag tag-slate">Left home</span><h3>Forgotten at home</h3><p>The clinician starts blind, repeats the labs, and guesses.</p></div>
  </div>
</div></section>

<section class="block" id="journey" style="padding-top:0"><div class="wrap"><div class="journey reveal">
  <p class="kicker">Patient journey</p>
  <h2 class="title">Follow the same flow as the paper health passport</h2>
  <p class="lede">Only actions allowed for each role are shown after sign-in.</p>
  <div class="journey-grid">
    <div class="step step-r"><small>Reception</small><strong>Register / Find Patient</strong><p>National ID lookup, Health Passport ID, and QR code.</p></div>
    <div class="step step-t"><small>Triage</small><strong>Record Vitals</strong><p>Prioritize patients before consultation.</p></div>
    <div class="step step-c"><small>Consultation</small><strong>Diagnose / Prescribe</strong><p>Review history, then outpatient or admission.</p></div>
    <div class="step step-p"><small>Pharmacy</small><strong>Dispense Medication</strong><p>Fulfill prescriptions and update stock.</p></div>
    <div class="step step-l"><small>Laboratory</small><strong>Orders &amp; Results</strong><p>Samples tracked against each visit.</p></div>
    <div class="step step-w"><small>Ward</small><strong>Inpatient Care</strong><p>Notes, vitals, and medication rounds.</p></div>
  </div>
</div></div></section>

<section class="block" style="padding-top:0"><div class="wrap"><div class="lookup-panel reveal">
  <p class="kicker">Public credential lookup</p>
  <h2 class="title">Verify a patient's health credential status</h2>
  <p class="lede">Facilities, employers and travel checkpoints can confirm a credential is valid, without ever seeing the medical record behind it. Have a QR credential code ready to check its status now.</p>
  <div class="cta-row" style="justify-content:center">
    <a class="btn btn-primary" href="{{ route('verify.scan') }}">Verify a credential</a>
  </div>
</div></div></section>

<section class="block" style="padding-top:0"><div class="wrap"><div class="cta-band reveal">
  <p class="eyebrow">{{ $displayFacility->name ?? config('app.name', 'Digital Health Passport') }}{{ $displayFacility?->district ? ' · '.$displayFacility->district : '' }}</p>
  <h2>Bring your desk onto the line.</h2>
  <p>Staff accounts are role-based, so clerks, nurses, clinicians, pharmacy and admins each see only what their job needs. Audited and ready for low-end desktops.</p>
  <div class="cta-row"><a class="btn btn-white" href="/login">Staff sign-in</a><a class="btn btn-ghostlight" href="/dashboard">Open dashboard</a></div>
</div></div></section>

<footer class="site-footer">
  <div class="wrap footer-grid">
    <div class="footer-col" id="services">
      <h4>Services</h4>
      <ul class="footer-list">
        @if(!empty($displayFacility?->services))
          @foreach($displayFacility->services as $svc)
            <li>{{ trim($svc) }}</li>
          @endforeach
        @else
          <li>Added by the facility admin once services are set up.</li>
        @endif
      </ul>
    </div>
    <div class="footer-col" id="contact">
      <h4>Contact</h4>
      <ul class="footer-list">
        @if($displayFacility?->phone_number)<li><a href="tel:{{ preg_replace('/[^+\d]/', '', $displayFacility->phone_number) }}">{{ $displayFacility->phone_number }}</a></li>@endif
        @if($displayFacility?->email)<li><a href="mailto:{{ $displayFacility->email }}">{{ $displayFacility->email }}</a></li>@endif
        @if($displayFacility?->address)<li>{{ $displayFacility->address }}</li>@endif
        @if(!$displayFacility?->phone_number && !$displayFacility?->email && !$displayFacility?->address)
          <li>Added by the facility admin once contact details are set up.</li>
        @endif
      </ul>
    </div>
    <div class="footer-col">
      <h4>Quick links</h4>
      <ul class="footer-list">
        <li><a href="/login">Sign in</a></li>
        <li><a href="#journey">Patient journey</a></li>
        <li><a href="{{ route('verify.scan') }}">Verify credential</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-base"><div class="wrap"><span><strong>Health Passport</strong> · {{ $displayFacility->name ?? config('app.name', 'Digital Health Passport') }}</span></div></div>
</footer>

<script>
(function(){
  if('serviceWorker' in navigator){navigator.serviceWorker.register('/sw.js').catch(function(){});}
  var io=new IntersectionObserver(function(es){es.forEach(function(x){if(x.isIntersecting){x.target.classList.add('in');io.unobserve(x.target);}})},{threshold:.12});
  document.querySelectorAll('.reveal').forEach(function(el){io.observe(el);});

  /* slideshow: one image at a time, next appears after the admin-set interval */
  var box=document.querySelector('.hero-slideshow');
  if(box){
    var imgs=box.querySelectorAll('img'), dots=box.querySelectorAll('#slideDots button'),
        caption=document.getElementById('slideCaption'), idx=0,
        captions=Array.prototype.map.call(imgs,function(im){return im.getAttribute('alt')||'';}),
        secs=parseInt(box.getAttribute('data-interval'),10)||5, timer=null;
    function show(n){
      idx=(n+imgs.length)%imgs.length;
      imgs.forEach(function(im,i){im.classList.toggle('on',i===idx);});
      dots.forEach(function(d,i){d.classList.toggle('on',i===idx);});
      if(caption){caption.textContent=captions[idx];}
    }
    function restart(){if(timer){clearInterval(timer);}timer=setInterval(function(){show(idx+1);},secs*1000);}
    dots.forEach(function(d){d.addEventListener('click',function(){show(parseInt(d.getAttribute('data-slide'),10));restart();});});
    if(imgs.length>1 && !window.matchMedia('(prefers-reduced-motion: reduce)').matches){restart();}
  }
})();
</script>
</body>
</html>
