<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · MCC FERMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f5f2f1;color:#232327;font:15px/1.5 'DM Sans',sans-serif;min-height:100vh;display:grid;grid-template-columns:minmax(0,1.05fr) minmax(360px,.95fr)}.brand-panel{background:#941c36;color:white;padding:clamp(34px,7vw,100px);display:flex;flex-direction:column;justify-content:space-between;position:relative;overflow:hidden}.brand-panel:after{content:'';position:absolute;width:480px;height:480px;border:1px solid rgba(255,255,255,.13);border-radius:50%;right:-160px;bottom:-200px;box-shadow:0 0 0 44px rgba(255,255,255,.035),0 0 0 92px rgba(255,255,255,.025)}.monogram{width:50px;height:50px;border:1px solid rgba(255,255,255,.5);border-radius:8px;display:grid;place-items:center;font:800 22px Manrope}.brand-panel h1{font:800 clamp(38px,5vw,68px)/1.06 Manrope;margin:70px 0 17px;max-width:620px;letter-spacing:0}.brand-panel p{font-size:17px;color:#f2dbe0;max-width:490px;margin:0}.brand-panel footer{font-size:12px;color:#e5bac4;z-index:1}.login-wrap{display:grid;place-items:center;padding:28px}.login{width:min(420px,100%)}.login h2{font:800 25px Manrope;margin:0}.login .muted{color:#74747b;margin:7px 0 28px}.microsoft{width:100%;height:50px;background:#a21c39;border-radius:4px;font:700 14px 'DM Sans';color:white;border:0;display:flex;align-items:center;justify-content:center;gap:12px;text-decoration:none}.ms-mark{width:17px;height:17px;display:grid;grid-template-columns:1fr 1fr;gap:2px}.ms-mark i:nth-child(1){background:#f25022}.ms-mark i:nth-child(2){background:#7fba00}.ms-mark i:nth-child(3){background:#00a4ef}.ms-mark i:nth-child(4){background:#ffb900}.note{border-top:1px solid #e4dfdd;padding-top:18px;margin-top:22px;color:#717179;font-size:12px}.errors{background:#fff0f2;color:#8e1630;padding:12px;margin-bottom:16px;border-radius:4px}@media(max-width:760px){body{grid-template-columns:1fr}.brand-panel{min-height:250px;padding:25px}.brand-panel h1{font-size:36px;margin:30px 0 12px}.brand-panel p{font-size:14px}.brand-panel footer{display:none}.login-wrap{padding:32px 22px 50px}}
    </style>
    <style>
        body{background:radial-gradient(ellipse at 82% 10%,rgba(30,90,168,.10),transparent 40%),#f5f8fc;color:#172b45}
        .brand-panel{background-color:#1e5aa8;background-image:linear-gradient(135deg,rgba(16,45,82,.72),rgba(30,90,168,.48)),url("{{ asset('images/mcc-campus-background.jpg') }}");background-position:center;background-size:cover}
        .brand-panel:after{border-color:rgba(255,255,255,.17);box-shadow:0 0 0 44px rgba(255,255,255,.045),0 0 0 92px rgba(255,255,255,.03)}
        .brand-panel p{color:#e1edff}.brand-panel footer{color:#c3d9f7}
        .microsoft{background:#1e5aa8;border-radius:8px;transition:background .16s,transform .16s,box-shadow .16s}
        .microsoft:hover{background:#15447f;transform:translateY(-1px);box-shadow:0 8px 18px rgba(30,90,168,.2)}
        .microsoft:focus-visible{outline:3px solid rgba(30,90,168,.55);outline-offset:3px}
        .note{border-color:#dbe5f0;color:#60748d}
        .login h2{letter-spacing:-.4px}
        .brand-panel .monogram{animation:login-mark-enter .65s .08s cubic-bezier(.2,.7,.25,1) both}
        .brand-panel h1,.brand-panel p,.brand-panel footer,.login>*{animation:login-content-enter .65s cubic-bezier(.2,.7,.25,1) both}
        .brand-panel h1{animation-delay:.18s}
        .brand-panel p{animation-delay:.32s}
        .brand-panel footer{animation-delay:.44s}
        .login>*:nth-child(1){animation-delay:.16s}
        .login>*:nth-child(2){animation-delay:.28s}
        .login>*:nth-child(3){animation-delay:.4s}
        .login>*:nth-child(4){animation-delay:.52s}
        @keyframes login-content-enter{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
        @keyframes login-mark-enter{from{opacity:0;transform:scale(.88)}to{opacity:1;transform:scale(1)}}
        @media(prefers-reduced-motion:reduce){*,*::before,*::after{animation-duration:.01ms!important;animation-delay:0ms!important;animation-iteration-count:1!important;transition:none!important}}
    </style>
</head>
<body>
    <section class="brand-panel"><div class="monogram">M</div><div><h1>Reserve space for what moves MCC forward.</h1><p>One place to request campus facilities and equipment for your organization or institute council.</p></div><footer>Mabalacat City College · FERMS</footer></section>
    <main class="login-wrap"><div class="login">
        <h2>Sign in to FERMS</h2><p class="muted">Use your MCC Microsoft account to continue.</p>
        @if($errors->any())<div class="errors" role="alert">{{ $errors->first() }}</div>@endif
        <a class="microsoft" href="{{ route('microsoft.redirect') }}"><span class="ms-mark"><i></i><i></i><i></i><i></i></span>Continue with Microsoft</a>
        <div class="note">Only accounts ending in <strong>@mcc.edu.ph</strong> can access the reservation system.</div>
    </div></main>
</body>
</html>