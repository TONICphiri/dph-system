<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Verify credential | Digital Health Passport</title>
<meta name="description" content="Verify a Digital Health Passport credential by code or QR scan.">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" media="print" onload="this.media='all'" />
<noscript><link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" /></noscript>
<style>
:root{
  --primary:#0E7490; --sky-900:#0C4A6E; --sky-800:#075985; --sky-700:#0369A1; --sky-200:#BAE6FD; --sky-100:#E0F2FE; --sky-50:#F0F9FF;
  --amber-700:#B45309; --amber-50:#FFFBEB;
  --emerald-700:#047857; --green-50:#F0FDF4;
  --red-700:#B91C1C; --red-50:#FEF2F2;
  --slate-900:#0F172A; --slate-600:#475569; --slate-500:#64748B; --slate-200:#E2E8F0; --slate-50:#F8FAFC;
  --white:#FFFFFF;
  --sans:Figtree,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
}
*{box-sizing:border-box}
body{margin:0;font-family:var(--sans);background:var(--slate-50);color:var(--slate-900);-webkit-font-smoothing:antialiased}
a{color:var(--sky-700)}
:focus-visible{outline:3px solid var(--sky-700);outline-offset:2px;border-radius:0}
.wrap{max-width:760px;margin:0 auto;padding:0 22px}
/* header — mirrors the public landing page */
.site-header{background:var(--white);border-bottom:1px solid var(--sky-100)}
.site-header .wrap{display:flex;align-items:center;justify-content:space-between;gap:16px;height:64px;max-width:1120px}
.brand{display:flex;align-items:center;gap:12px;text-decoration:none;color:var(--slate-900)}
.brand-mark{width:36px;height:36px;background:var(--white);border:1px solid var(--sky-100);color:var(--white);display:grid;place-items:center;font-weight:700;font-size:20px;box-shadow:0 0 0 4px var(--sky-100);overflow:hidden}
.brand-mark img{width:100%;height:100%;object-fit:contain;padding:3px;display:block}
.brand strong{display:block;font-size:14px;color:var(--sky-900)}
.top-nav{display:flex;align-items:center;gap:18px}
.top-nav a.link{color:var(--slate-600);text-decoration:none;font-size:14.5px;font-weight:600}
.top-nav a.link:hover{color:var(--sky-800)}
.btn{display:inline-flex;align-items:center;gap:8px;font-size:14px;font-weight:600;padding:11px 20px;border-radius:0;text-decoration:none;border:1px solid transparent;cursor:pointer}
.btn-primary{background:var(--sky-700);color:var(--white)}
.btn-primary:hover{background:var(--sky-800)}
/* lookup panel */
main{padding:56px 0}
.badge{display:inline-block;font-size:11.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:6px 14px;border-radius:0;background:var(--amber-50);color:var(--amber-700);margin:0}
h1{font-size:clamp(26px,3.6vw,36px);line-height:1.15;letter-spacing:-.02em;margin:16px 0 10px;color:var(--slate-900);text-align:center}
.lede{color:var(--slate-600);font-size:16px;line-height:1.6;text-align:center;max-width:34rem;margin:0 auto}
.search-row{display:flex;margin-top:28px;border:1px solid var(--slate-200);background:var(--white)}
.search-row textarea{flex:1;resize:vertical;min-height:52px;max-height:160px;border:0;padding:14px 16px;font-family:inherit;font-size:14.5px;color:var(--slate-900);background:transparent}
.search-row textarea:focus{outline:none}
.search-row textarea::placeholder{color:var(--slate-500)}
.search-row button{flex:none;background:var(--primary);color:var(--white);border:0;border-radius:0;padding:0 24px;font-weight:700;cursor:pointer;font-size:14.5px}
.search-row button:hover{background:var(--sky-800)}
.err{background:var(--red-50);border:1px solid var(--red-700);color:var(--red-700);border-radius:0;padding:10px 12px;font-size:14px;margin-top:10px}
.note{color:var(--slate-500);font-size:13px;text-align:center;margin:12px 0 0}
.result{margin-top:20px;border-radius:0;padding:18px 20px;font-size:15px}
.valid{background:var(--green-50);border:1px solid var(--emerald-700);color:#14532D}
.invalid{background:var(--red-50);border:1px solid var(--red-700);color:#7F1D1D}
.result strong.status{display:block;font-size:16px;letter-spacing:.04em}
.meta{margin:8px 0 0;font-size:14px}
@media (max-width:560px){
  .top-nav a.link{display:none}
  .search-row{flex-direction:column}
  .search-row button{padding:14px;width:100%}
}
</style>
</head>
<body>

<header class="site-header">
  <div class="wrap">
    <a class="brand" href="/">@include('partials.brand-mark')<strong>Digital Health Passport</strong></a>
    <nav class="top-nav" aria-label="Primary">
      <a class="link" href="/">Home</a>
      <a class="btn btn-primary" href="/login">NIN sign-in</a>
    </nav>
  </div>
</header>

<main>
  <div class="wrap" style="text-align:center">
    <p class="badge">Public credential lookup</p>
  </div>
  <div class="wrap">
    <h1>Verify a patient's health credential status</h1>
    <p class="lede">Enter the credential code from a QR scan below to check its status instantly. Only verification-level data is shown, never the full medical record.</p>

    <form method="POST" action="{{ route('verify.check') }}">
      @csrf
      <div class="search-row">
        <textarea id="credential" name="credential" required placeholder="Paste or scan a QR credential code">{{ old('credential') }}</textarea>
        <button type="submit">Verify status</button>
      </div>
      @error('credential')<p class="err" role="alert">{{ $message }}</p>@enderror
    </form>
    <p class="note">Not enrolled yet? Visit a registered facility with your national ID document.</p>

    @if (!empty($result))
      <div class="result {{ !empty($result['valid']) ? 'valid' : 'invalid' }}" role="status">
        <strong class="status">{{ !empty($result['valid']) ? 'Valid credential' : strtoupper($result['status'] ?? 'Invalid credential') }}</strong>
        @if (!empty($result['holder_name']))
          <p class="meta">Holder: {{ $result['holder_name'] }}</p>
          <p class="meta">Type: {{ $result['credential_type'] ?? 'N/A' }} &middot; Expires: {{ $result['expires_at'] ?? 'N/A' }}</p>
          <p class="meta">Issuer: {{ $result['issuer'] ?? 'N/A' }}</p>
        @else
          <p class="meta">This credential cannot be trusted. Do not accept it.</p>
        @endif
      </div>
    @endif
  </div>
</main>

</body>
</html>
