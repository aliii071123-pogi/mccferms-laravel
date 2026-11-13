<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Workspace') · MCC FERMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{color-scheme:light;--ink:#292127;--muted:#787078;--line:#eadfe1;--paper:#fff;--canvas:#fbf7f7;--red:#b4233d;--red-dark:#8f1830;--red-soft:#fff0f2;--green:#167348;--green-soft:#e7f4ed;--amber:#9a5c00;--amber-soft:#fff3dc;--shadow:0 12px 30px rgba(58,28,37,.07);--shadow-raised:0 18px 42px rgba(58,28,37,.12);--control:#fff;--table-head:#faf6f6;--hover:#fbf3f4}
        :root[data-theme="dark"]{color-scheme:dark;--ink:#f3e9ec;--muted:#b7aab0;--line:#42343a;--paper:#211b1e;--canvas:#171316;--red:#f06b80;--red-dark:#d94d66;--red-soft:#3c222a;--green:#73d4a0;--green-soft:#20392e;--amber:#f2bd61;--amber-soft:#3c3120;--shadow:0 12px 30px rgba(0,0,0,.2);--shadow-raised:0 18px 42px rgba(0,0,0,.38);--control:#211b1e;--table-head:#2a2226;--hover:#302329}
        *{box-sizing:border-box}body{margin:0;background:var(--canvas);color:var(--ink);font:15px/1.5 'DM Sans',sans-serif}a{color:inherit;text-decoration:none}button,input,select,textarea{font:inherit}button,.button{border:0;border-radius:5px;background:var(--red);color:white;padding:10px 15px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px}.button:hover,button:hover{background:var(--red-dark)}.button.light{background:white;color:var(--red);border:1px solid #e6c6cd}.button.neutral{background:#f0efee;color:var(--ink)}.button.danger{background:#8c1b31}.button.small,button.small{font-size:13px;padding:7px 10px}.app{min-height:100vh;display:grid;grid-template-columns:244px minmax(0,1fr)}.rail{background:#fff;border-right:1px solid var(--line);padding:22px 15px;display:flex;flex-direction:column;gap:28px}.brand{display:flex;align-items:center;gap:11px;padding:0 9px}.brand-mark{background:var(--red);color:#fff;font:800 18px Manrope,sans-serif;width:38px;height:38px;display:grid;place-items:center;border-radius:7px;letter-spacing:0}.brand strong{font:800 16px Manrope,sans-serif;letter-spacing:0}.brand small{display:block;color:var(--muted);font-size:11px;margin-top:1px}.nav-label{padding:0 11px;color:#a1a1a5;text-transform:uppercase;font-size:10px;font-weight:700;letter-spacing:0}.nav{display:grid;gap:4px}.nav a{padding:9px 11px;border-radius:5px;color:#55565d;font-size:14px;font-weight:600;display:flex;gap:11px;align-items:center}.nav a:hover{background:#f7f4f4;color:var(--red)}.nav a.active{background:var(--red-soft);color:var(--red)}.nav-icon{width:20px;text-align:center;font-size:15px}.rail-bottom{margin-top:auto;border-top:1px solid var(--line);padding:15px 8px 0;color:var(--muted);font-size:12px}.workspace{min-width:0}.topbar{height:74px;background:rgba(255,255,255,.92);border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:flex-end;gap:16px;padding:0 clamp(20px,4vw,48px);position:sticky;top:0;z-index:5;backdrop-filter:blur(12px)}.identity{text-align:right;font-size:13px;font-weight:700}.identity small{display:block;color:var(--muted);font-weight:500}.role-pill{border-left:1px solid var(--line);padding-left:16px;color:var(--red);font-size:11px;text-transform:uppercase;font-weight:700;letter-spacing:0}.topbar form{margin:0}.content{max-width:1380px;margin:0 auto;padding:32px clamp(18px,4vw,48px) 60px}.page-head{display:flex;justify-content:space-between;align-items:flex-end;gap:18px;margin:0 0 25px}.eyebrow{color:var(--red);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0}.page-head h1{font:800 30px/1.15 Manrope,sans-serif;margin:5px 0 0;letter-spacing:0}.page-head p{margin:7px 0 0;color:var(--muted)}.section-title{font:800 18px Manrope,sans-serif;margin:0 0 14px;letter-spacing:0}.surface{background:white;border:1px solid var(--line);border-radius:6px;padding:22px}.surface + .surface{margin-top:20px}.surface-head{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:15px}.grid-2{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(280px,.8fr);gap:20px}.stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:22px}.stat{background:white;border:1px solid var(--line);border-radius:6px;padding:17px 19px}.stat span{color:var(--muted);font-size:13px}.stat strong{display:block;font:800 28px Manrope,sans-serif;margin-top:5px;letter-spacing:0}.stat.accent{border-top:3px solid var(--red)}.catalog-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.catalog-item{border:1px solid var(--line);padding:14px;border-radius:5px}.catalog-item strong{display:block;font-size:14px}.catalog-item small{display:block;color:var(--muted);margin-top:4px}.capacity{color:var(--red);font-size:12px;font-weight:700;margin-top:8px}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.field{min-width:0}.field.full{grid-column:1/-1}.field label{display:block;font-size:13px;font-weight:700;margin-bottom:6px}.field input,.field select,.field textarea{width:100%;border:1px solid #d5d4d2;border-radius:4px;padding:10px 11px;background:white;color:var(--ink);outline:none}.field textarea{min-height:100px;resize:vertical}.field input:focus,.field select:focus,.field textarea:focus{border-color:var(--red);box-shadow:0 0 0 3px rgba(165,29,56,.1)}.field .hint{font-size:12px;color:var(--muted);margin-top:5px}.error{font-size:12px;color:#a11731;margin-top:5px}.invalid{border-color:#b21c37!important;background:#fffafb!important}.equipment-options{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.equipment-choice{border:1px solid var(--line);padding:10px;border-radius:4px;display:grid;grid-template-columns:minmax(0,1fr) 80px;gap:10px;align-items:center}.equipment-choice label{margin:0;font-size:13px}.equipment-choice input{width:100%;padding:7px}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:680px}th,td{text-align:left;padding:13px 10px;border-bottom:1px solid #eeedeb;vertical-align:top}th{font-size:10px;color:#85858b;text-transform:uppercase;letter-spacing:0;font-weight:800}td{font-size:13px}tbody tr:last-child td{border-bottom:0}.muted{color:var(--muted)}.status{display:inline-flex;padding:4px 8px;border-radius:3px;font-size:11px;font-weight:800;text-transform:capitalize;background:#f0efee;color:#62636a}.status.pending{background:var(--amber-soft);color:var(--amber)}.status.approved{background:var(--green-soft);color:var(--green)}.status.rejected,.status.cancelled{background:#fae8eb;color:#a11731}.alert{padding:12px 15px;border-radius:4px;background:var(--green-soft);color:#155d3c;margin-bottom:18px}.alert.errors{background:#fff0f2;color:#8e1630}.alert ul{margin:6px 0 0;padding-left:18px}.tabs{display:flex;gap:18px;border-bottom:1px solid var(--line);margin-bottom:16px}.tabs a{padding:0 2px 10px;color:var(--muted);font-size:13px;font-weight:700}.tabs a.active{color:var(--red);border-bottom:2px solid var(--red)}.actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.empty{text-align:center;padding:35px 18px;color:var(--muted)}.empty strong{display:block;color:var(--ink);font:700 16px Manrope,sans-serif;margin-bottom:4px}.schedule{display:grid;gap:8px}.schedule-row{display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid #efeeed;padding:8px 0;font-size:12px}.schedule-row:last-child{border:0}.modal-backdrop{position:fixed;inset:0;background:rgba(22,20,20,.48);display:grid;place-items:center;padding:18px;z-index:20;backdrop-filter:blur(5px)}.modal{background:#fff;max-width:400px;width:100%;padding:30px;border-radius:12px;box-shadow:0 24px 70px rgba(32,33,38,.22);text-align:center}.modal h2{font:800 21px Manrope,sans-serif;margin:14px 0 8px}.modal p{color:var(--muted);margin:0 0 22px}.modal-actions{display:flex;justify-content:center;gap:8px}.modal-actions .button{min-width:120px}.request-sent-icon{width:58px;height:58px;margin:0 auto;border-radius:50%;display:grid;place-items:center;background:var(--green-soft);color:var(--green);font:700 30px/1 'DM Sans',sans-serif}.inline-form{display:inline}.tagline{font-size:12px;color:var(--muted)}@media(max-width:920px){.app{grid-template-columns:1fr}.rail{padding:12px 14px;border-right:0;border-bottom:1px solid var(--line);gap:12px}.brand{padding:0 4px}.rail nav{display:flex;overflow:auto;gap:4px}.nav-label,.rail-bottom{display:none}.nav a{white-space:nowrap;padding:8px 10px}.nav-icon{display:none}.topbar{height:62px;padding:0 18px}.content{padding-top:24px}.grid-2{grid-template-columns:1fr}}@media(max-width:620px){.stats{grid-template-columns:1fr 1fr}.stat:last-child{grid-column:1/-1}.form-grid,.equipment-options,.catalog-grid{grid-template-columns:1fr}.page-head{align-items:flex-start;flex-direction:column}.page-head h1{font-size:25px}.surface{padding:16px}.identity{font-size:12px}.role-pill{font-size:10px}.topbar{gap:10px}.actions .button{flex:1}.content{padding-inline:14px}}
                .calendar-controls{display:flex;align-items:center;justify-content:center;gap:16px;margin-bottom:18px}.calendar-controls h2{font:800 20px Manrope,sans-serif;margin:0;min-width:180px;text-align:center}.calendar-scroll{overflow-x:auto}.calendar-grid{display:grid;grid-template-columns:repeat(7,minmax(115px,1fr));min-width:805px;border-top:1px solid var(--line);border-left:1px solid var(--line)}.calendar-weekday{padding:9px;text-align:center;background:#faf9f8;font-size:12px;font-weight:800;color:var(--muted);border-right:1px solid var(--line);border-bottom:1px solid var(--line)}.calendar-day{min-height:120px;padding:7px;border-right:1px solid var(--line);border-bottom:1px solid var(--line);background:#fff}.calendar-day.outside-month{background:#faf9f8;color:#999}.calendar-date{display:block;font-size:12px;margin-bottom:5px}.calendar-event{display:grid;gap:2px;margin:4px 0;padding:5px 6px;border-radius:4px;background:var(--amber-soft);color:var(--ink);font-size:11px;overflow-wrap:anywhere}.calendar-event strong{font-size:11px}.calendar-event span{color:#55565d}.calendar-event .status{width:max-content;margin-top:2px;padding:2px 5px}.schedule-days{display:grid;gap:12px;margin-top:10px}.schedule-day{border:1px solid var(--line);border-radius:5px;padding:15px}.schedule-day h3{font:700 15px Manrope,sans-serif;margin:0 0 12px}.confirm-modal-icon{width:58px;height:58px;margin:0 auto;border-radius:50%;display:grid;place-items:center;background:var(--amber-soft);color:var(--amber);font:800 30px/1 Manrope,sans-serif}.confirmation-modal[hidden]{display:none}
        body{background:radial-gradient(ellipse at 75% -15%,rgba(180,35,61,.07),transparent 42%),var(--canvas);transition:background-color .2s,color .2s}
        .rail,.surface,.stat,.catalog-item,.equipment-choice{background:var(--paper);border-color:var(--line)}
        .rail{box-shadow:4px 0 24px rgba(58,28,37,.025)}
        .brand-mark{box-shadow:0 5px 14px rgba(180,35,61,.22)}
        .nav a{border-radius:9px;transition:background .16s,color .16s,transform .16s}
        .nav a:hover{background:var(--hover);transform:translateX(2px)}
        .nav a.active{background:var(--red-soft);color:var(--red);box-shadow:inset 3px 0 var(--red)}
        .topbar{background:color-mix(in srgb,var(--paper) 92%,transparent);border-color:var(--line)}
        .button,button{border-radius:8px;transition:background .16s,transform .16s,box-shadow .16s}
        .button:not(.neutral):not(.light),button:not(.neutral){box-shadow:0 4px 10px rgba(180,35,61,.14)}
        .button:hover,button:hover{transform:translateY(-1px)}
        .button.light{background:var(--paper);color:var(--red);border-color:var(--line)}
        .button.neutral{background:var(--hover);color:var(--ink)}
        .theme-toggle{box-shadow:none!important}
        .surface,.stat,.catalog-item{border-radius:11px;box-shadow:var(--shadow)}
        .surface{padding:24px}
        .stat{transition:transform .18s,box-shadow .18s}
        .stat:hover{transform:translateY(-2px);box-shadow:var(--shadow-raised)}
        .stat.accent{border-top:3px solid var(--red)}
        .page-head h1{letter-spacing:-.7px}
        .field input,.field select,.field textarea,.equipment-choice input{background:var(--control);color:var(--ink);border-color:var(--line);border-radius:8px}
        .field input:focus,.field select:focus,.field textarea:focus{border-color:var(--red);box-shadow:0 0 0 3px color-mix(in srgb,var(--red) 16%,transparent)}
        .invalid{background:var(--red-soft)!important}
        th{background:var(--table-head);color:var(--muted)}
        th,td{border-color:var(--line)}
        tbody tr:hover{background:var(--hover)}
        .status{background:var(--hover);color:var(--muted);border-radius:999px}
        .status.pending{background:var(--amber-soft);color:var(--amber)}
        .status.approved{background:var(--green-soft);color:var(--green)}
        .status.rejected,.status.cancelled{background:var(--red-soft);color:var(--red)}
        .alert{background:var(--green-soft);color:var(--green);border:1px solid color-mix(in srgb,var(--green) 18%,transparent);border-radius:9px}
        .alert.errors{background:var(--red-soft);color:var(--red);border-color:color-mix(in srgb,var(--red) 20%,transparent)}
        .schedule-row,.tabs{border-color:var(--line)}
        .tabs a.active{color:var(--red);border-color:var(--red)}
        .calendar-weekday{background:var(--table-head);border-color:var(--line)}
        .calendar-grid{border-color:var(--line)}
        .calendar-day{background:var(--paper);border-color:var(--line)}
        .calendar-day.outside-month{background:var(--canvas);color:var(--muted)}
        .calendar-event span{color:var(--muted)}
        .schedule-day{border-color:var(--line);border-radius:9px}
        .equipment-filters{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(170px,.8fr);gap:12px;margin-bottom:8px}
        .equipment-result-count{font-size:12px;margin:0 0 8px}
        .modal{background:var(--paper);color:var(--ink);border:1px solid var(--line);box-shadow:var(--shadow-raised)}
        .modal-backdrop{background:rgba(15,10,12,.58)}
        .theme-toggle{background:var(--hover);color:var(--ink);border:1px solid var(--line);padding:8px 11px;min-width:42px}
        .theme-toggle:hover{background:var(--red-soft);color:var(--red)}
        :focus-visible{outline:3px solid color-mix(in srgb,var(--red) 65%,transparent);outline-offset:2px}
        :root{--ink:#172b45;--muted:#60748d;--line:#dbe5f0;--canvas:#f5f8fc;--red:#1e5aa8;--red-dark:#15447f;--red-soft:#eaf2ff;--table-head:#f1f6fc;--hover:#edf4fc;--shadow:0 12px 30px rgba(22,58,102,.07);--shadow-raised:0 18px 42px rgba(22,58,102,.14)}
        :root[data-theme="dark"]{--ink:#e7f0fc;--muted:#a9bdd5;--line:#34465d;--paper:#1b2839;--canvas:#111c2a;--red:#77a9f6;--red-dark:#93bbff;--red-soft:#1e3453;--control:#1b2839;--table-head:#223249;--hover:#263950}
        body{background-image:radial-gradient(ellipse at 75% -15%,rgba(30,90,168,.09),transparent 42%);background-color:var(--canvas)}
        .status.recommended{background:var(--red-soft);color:var(--red)}
        .recommendation-note{padding:13px 15px;border:1px solid color-mix(in srgb,var(--red) 22%,transparent);border-radius:9px;background:var(--red-soft);color:var(--ink)}
        .recommendation-note strong{color:var(--red)}
        .decision-timestamp{margin-top:20px;padding:12px 14px;border-radius:8px;background:var(--red-soft);color:var(--ink)}
        body,.rail,.topbar,.surface,.stat,.catalog-item,.equipment-choice,.calendar-day,.calendar-weekday,.calendar-event,.field input,.field select,.field textarea,.button,button,.nav a,.status,.tabs a,.alert{transition:background-color .65s ease,color .65s ease,border-color .65s ease,box-shadow .65s ease}
        html.theme-transition *,html.theme-transition *::before,html.theme-transition *::after{transition:background-color .65s ease,color .65s ease,border-color .65s ease,box-shadow .65s ease!important}
        html.motion-ready .content>.page-head{animation:content-enter .5s cubic-bezier(.2,.7,.25,1) both}
        html.motion-ready .content>.stats{animation:content-enter .55s .08s cubic-bezier(.2,.7,.25,1) both}
        html.motion-ready .content>.grid-2,html.motion-ready .content>.surface{animation:content-enter .55s .14s cubic-bezier(.2,.7,.25,1) both}
        html.motion-ready .content .stat{animation:content-enter .45s .14s cubic-bezier(.2,.7,.25,1) both}
        html.motion-ready .content .surface img,html.motion-ready .content .catalog-item img{animation:media-enter .7s .12s cubic-bezier(.2,.7,.25,1) both}
        @keyframes content-enter{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
        @keyframes media-enter{from{opacity:0;transform:scale(.97)}to{opacity:1;transform:scale(1)}}
        html.reveal-ready .content>.page-head[data-reveal],html.reveal-ready .content>.grid-2[data-reveal-stagger]{animation:none}
        html.reveal-ready [data-reveal]{animation:none;opacity:0;transform:translateY(22px);transition:opacity .75s cubic-bezier(.2,.7,.25,1),transform .75s cubic-bezier(.2,.7,.25,1);transition-delay:var(--reveal-delay,0ms)}
        html.reveal-ready [data-reveal].is-visible{opacity:1;transform:translateY(0)}
        @media(prefers-reduced-motion:reduce){html.reveal-ready [data-reveal]{opacity:1;transform:none;transition:none}}
        .page-loader[hidden]{display:none}
        .page-loader{position:fixed;inset:0;z-index:1000;display:grid;place-items:center;background:rgba(245,248,252,.86);backdrop-filter:blur(8px)}
        :root[data-theme="dark"] .page-loader{background:rgba(17,28,42,.86)}
        .page-loader-card{display:grid;place-items:center}
        .loader-emblem{position:relative;width:70px;height:70px;display:grid;place-items:center;border-radius:18px;background:linear-gradient(145deg,#3479c9,#15447f);color:#fff;font:800 34px/1 Manrope,sans-serif;box-shadow:0 10px 24px rgba(30,90,168,.24);animation:loader-breathe 1.1s ease-in-out infinite}
        .loader-emblem:before{content:"";position:absolute;inset:-8px;border:2px solid rgba(30,90,168,.22);border-top-color:#1e5aa8;border-radius:24px;animation:loader-spin .9s linear infinite}
        .loader-emblem:after{content:"";position:absolute;inset:-15px;border:1px solid rgba(30,90,168,.12);border-bottom-color:rgba(30,90,168,.55);border-radius:29px;animation:loader-spin 1.5s linear infinite reverse}
        @keyframes loader-spin{to{transform:rotate(360deg)}}
        @keyframes loader-breathe{50%{transform:scale(.94);box-shadow:0 6px 16px rgba(30,90,168,.16)}}
        @media(max-width:620px){.topbar{height:auto;min-height:62px;padding:10px 14px;flex-wrap:wrap}.identity{margin-right:auto}.theme-toggle{padding:7px 9px}.surface{padding:17px}.equipment-filters{grid-template-columns:1fr}}
        @media(prefers-reduced-motion:reduce){*,*::before,*::after,html.theme-transition *,html.theme-transition *::before,html.theme-transition *::after{scroll-behavior:auto!important;transition:none!important;animation-duration:.01ms!important;animation-iteration-count:1!important}}
    </style>
    <script>
        document.documentElement.classList.add('motion-ready');
        const savedTheme = localStorage.getItem('ferms-theme');
        document.documentElement.dataset.theme = savedTheme === 'dark' ? 'dark' : 'light';
    </script>
</head>
<body>
<div class="app">
    <aside class="rail">
        <a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark">M</span><span><strong>MCC FERMS</strong><small>Campus resource booking</small></span></a>
        <div>
            <div class="nav-label">Workspace</div>
            <nav class="nav">
                <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="nav-icon">◫</span>Overview</a>
                <a class="{{ request()->routeIs('calendar.*') ? 'active' : '' }}" href="{{ route('calendar.index') }}"><span class="nav-icon">▦</span>Calendar</a>
                <a class="{{ request()->routeIs('reservations.*') ? 'active' : '' }}" href="{{ route('reservations.index') }}"><span class="nav-icon">▤</span>Reservations</a>
                <a class="{{ request()->routeIs('facilities.*') ? 'active' : '' }}" href="{{ route('facilities.index') }}"><span class="nav-icon">▦</span>Facilities</a>
                <a class="{{ request()->routeIs('equipment.*') ? 'active' : '' }}" href="{{ route('equipment.index') }}"><span class="nav-icon">⌁</span>Equipment</a>
                @if(auth()->user()->role === 'super_admin')
                    <a class="{{ request()->routeIs('accounts.*') ? 'active' : '' }}" href="{{ route('accounts.index') }}"><span class="nav-icon">◎</span>Accounts</a>
                @endif
            </nav>
        </div>
        <div class="rail-bottom">Mabalacat City College<br>Facility & Equipment Reservation</div>
    </aside>
    <div class="workspace">
        <header class="topbar">
            <div class="identity">{{ auth()->user()->name }}<small>{{ auth()->user()->organization ?: auth()->user()->email }}</small></div>
            <span class="role-pill">{{ auth()->user()->role === 'super_admin' ? 'IT Administrator' : str_replace('_', ' ', auth()->user()->role) }}</span>
            <button class="theme-toggle small" id="theme-toggle" type="button" aria-label="Switch to dark mode" aria-pressed="false"><span id="theme-toggle-icon" aria-hidden="true">☾</span><span id="theme-toggle-label">Dark mode</span></button>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="button neutral small" type="submit">Sign out</button></form>
        </header>
        <main class="content">
            @if(session('success'))<div class="alert" role="status">{{ session('success') }}</div>@endif
            @yield('content')
        </main>
    </div>
</div>
<div class="page-loader" id="page-loader" role="status" aria-live="polite" aria-label="Loading page" hidden>
    <span class="loader-emblem" aria-hidden="true">M</span>
</div>
<div class="modal-backdrop confirmation-modal" id="confirmation-modal" role="presentation" hidden>
    <section class="modal" role="dialog" aria-modal="true" aria-labelledby="confirmation-title" aria-describedby="confirmation-message" tabindex="-1">
        <div class="confirm-modal-icon" aria-hidden="true">!</div>
        <h2 id="confirmation-title">Please confirm</h2>
        <p id="confirmation-message"></p>
        <div class="modal-actions">
            <button class="button neutral" id="confirmation-cancel" type="button">Cancel</button>
            <button class="button" id="confirmation-accept" type="button">Confirm</button>
        </div>
    </section>
</div>
<script>
    const themeToggle = document.getElementById('theme-toggle');
    const themeToggleIcon = document.getElementById('theme-toggle-icon');
    const themeToggleLabel = document.getElementById('theme-toggle-label');
    let themeTransitionTimeout;

    function updateThemeToggle() {
        const isDark = document.documentElement.dataset.theme === 'dark';
        themeToggle.setAttribute('aria-pressed', String(isDark));
        themeToggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        themeToggleIcon.textContent = isDark ? '☀' : '☾';
        themeToggleLabel.textContent = isDark ? 'Light mode' : 'Dark mode';
    }

    updateThemeToggle();
    themeToggle.addEventListener('click', () => {
        const theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        document.documentElement.classList.add('theme-transition');
        document.documentElement.dataset.theme = theme;
        localStorage.setItem('ferms-theme', theme);
        updateThemeToggle();
        window.clearTimeout(themeTransitionTimeout);
        themeTransitionTimeout = window.setTimeout(() => {
            document.documentElement.classList.remove('theme-transition');
        }, 700);
    });

    const confirmationModal = document.getElementById('confirmation-modal');
    const confirmationMessage = document.getElementById('confirmation-message');
    const confirmationAccept = document.getElementById('confirmation-accept');
    const confirmationCancel = document.getElementById('confirmation-cancel');
    let pendingConfirmationForm = null;
    let confirmationTrigger = null;

    function closeConfirmationModal() {
        confirmationModal.hidden = true;
        pendingConfirmationForm = null;
        if (confirmationTrigger instanceof HTMLElement) {
            confirmationTrigger.focus();
        }
    }

    document.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirmMessage) {
            return;
        }
        if (form.dataset.confirmed === 'true') {
            delete form.dataset.confirmed;
            return;
        }

        event.preventDefault();
        pendingConfirmationForm = form;
        confirmationTrigger = document.activeElement;
        confirmationMessage.textContent = form.dataset.confirmMessage;
        confirmationAccept.textContent = form.dataset.confirmLabel || 'Confirm';
        confirmationModal.hidden = false;
        confirmationAccept.focus();
    });

    confirmationAccept.addEventListener('click', () => {
        if (!pendingConfirmationForm) {
            return;
        }
        const form = pendingConfirmationForm;
        form.dataset.confirmed = 'true';
        confirmationModal.hidden = true;
        pendingConfirmationForm = null;
        form.requestSubmit();
    });

    confirmationCancel.addEventListener('click', closeConfirmationModal);
    confirmationModal.addEventListener('click', event => {
        if (event.target === confirmationModal) {
            closeConfirmationModal();
        }
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !confirmationModal.hidden) {
            closeConfirmationModal();
        }
    });
</script>
<script>
    const revealElements = document.querySelectorAll('[data-reveal]');
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
        revealElements.forEach(element => element.classList.add('is-visible'));
    } else {
        document.documentElement.classList.add('reveal-ready');
        const revealObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add('is-visible');
                revealObserver.unobserve(entry.target);
            });
        }, { threshold: 0.12 });

        revealElements.forEach(element => revealObserver.observe(element));
    }

    const pageLoader = document.getElementById('page-loader');

    document.addEventListener('click', event => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const link = event.target.closest('a[href]');
        if (!(link instanceof HTMLAnchorElement)
            || event.defaultPrevented
            || event.button !== 0
            || event.metaKey
            || event.ctrlKey
            || event.shiftKey
            || event.altKey
            || link.target
            || link.hasAttribute('download')
            || link.origin !== window.location.origin
            || (link.pathname === window.location.pathname && link.search === window.location.search)) {
            return;
        }

        pageLoader.hidden = false;
        document.documentElement.setAttribute('aria-busy', 'true');
    });

    document.addEventListener('submit', event => {
        if (event.defaultPrevented || !(event.target instanceof HTMLFormElement)) {
            return;
        }

        pageLoader.hidden = false;
        document.documentElement.setAttribute('aria-busy', 'true');
    });

    window.addEventListener('pageshow', () => {
        pageLoader.hidden = true;
        document.documentElement.removeAttribute('aria-busy');
    });
</script>
@stack('scripts')
</body>
</html>