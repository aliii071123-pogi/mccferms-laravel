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