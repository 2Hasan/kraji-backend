<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'الإدارة') — كراجي</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #f4f6fa; --surface: #ffffff; --soft: #f7f9fc; --line: #e3e8f0;
    --ink: #0f1d3a; --muted: #55657f; --navy: #0f1d3a; --navy-2: #172a52;
    --blue: #2f5fd0; --blue-bg: #e9f0ff;
    --green: #15803d; --green-bg: #e6f4ec; --green-fill: #1f9d55;
    --amber: #92400e; --amber-bg: #fef3c7; --amber-fill: #f59e0b;
    --red: #b91c1c; --red-bg: #fee2e2;
  }
  * { box-sizing: border-box; }
  body { margin: 0; background: var(--bg); color: var(--ink); font-family: "Tajawal", system-ui, sans-serif; min-height: 100vh; }
  a { color: inherit; }
  .i { width: 18px; height: 18px; flex-shrink: 0; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; display: inline-block; vertical-align: middle; }
  .i.sm { width: 14px; height: 14px; }

  .app { display: grid; grid-template-columns: 220px 1fr; min-height: 100vh; }
  .side { background: var(--navy); color: #cfd7e6; padding: 16px 12px; display: flex; flex-direction: column; gap: 4px; }
  .brand { display: flex; align-items: center; gap: 10px; color: #fff; font-weight: 800; font-size: 16px; padding: 4px 8px 14px; }
  .brand .mark { width: 30px; height: 30px; border-radius: 10px; background: var(--blue); display: grid; place-items: center; }
  .brand .mark .i { width: 16px; height: 16px; color: #fff; }
  .nav { display: flex; align-items: center; gap: 10px; padding: 9px 10px; border-radius: 10px; font-size: 14px; font-weight: 700; color: #cfd7e6; text-decoration: none; }
  .nav:hover { background: rgba(255,255,255,.06); }
  .nav.on { background: rgba(255,255,255,.12); color: #fff; }
  .nav .count { margin-right: auto; background: var(--amber-fill); color: #fff; font-size: 11px; font-weight: 800; padding: 1px 7px; border-radius: 999px; }
  .side .foot { margin-top: auto; font-size: 12px; padding: 10px; border-top: 1px solid rgba(255,255,255,.12); display: flex; align-items: center; gap: 8px; }
  .avatar { width: 30px; height: 30px; border-radius: 50%; background: #3b4d7a; display: grid; place-items: center; color: #fff; font-weight: 800; font-size: 12px; flex-shrink: 0; }
  .logout { background: none; border: 0; color: #cfd7e6; font-family: inherit; font-size: 12px; font-weight: 700; cursor: pointer; padding: 0; margin-top: 6px; }

  .main { display: flex; flex-direction: column; min-width: 0; }
  .topbar { height: 60px; background: var(--surface); border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between; padding: 0 22px; gap: 12px; flex-wrap: wrap; }
  .topbar h2 { margin: 0; font-size: 18px; font-weight: 800; }
  .content { padding: 18px 22px 40px; display: flex; flex-direction: column; gap: 14px; min-width: 0; }

  .flash { border-radius: 12px; padding: 10px 14px; font-size: 14px; font-weight: 700; }
  .flash.ok { background: var(--green-bg); color: var(--green); }
  .flash.err { background: var(--red-bg); color: var(--red); }

  .kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
  .kpi { background: var(--surface); border: 1px solid var(--line); border-radius: 16px; padding: 14px; }
  .kpi .k { font-size: 12px; color: var(--muted); font-weight: 700; }
  .kpi .v { font-size: 26px; font-weight: 800; margin-top: 6px; }

  .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 16px; overflow: auto; min-width: 0; }
  .panel-h { padding: 12px 16px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); gap: 8px; }
  .panel-h b { font-size: 15px; font-weight: 800; }
  .panel-pad { padding: 16px; }

  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  th { text-align: right; font-size: 12px; color: var(--muted); font-weight: 700; padding: 9px 10px; background: var(--soft); border-bottom: 1px solid var(--line); white-space: nowrap; }
  td { padding: 9px 10px; border-bottom: 1px solid var(--line); font-weight: 600; vertical-align: middle; line-height: 1.5; }
  tr:last-child td { border-bottom: 0; }
  .muted { color: var(--muted); font-weight: 500; }
  .mono { font-family: Consolas, "Courier New", monospace; direction: ltr; unicode-bidi: isolate; font-size: 12px; white-space: nowrap; }
  .plate-no { font-family: Consolas, "Courier New", monospace; font-weight: 800; font-size: 13px; letter-spacing: .5px; white-space: nowrap; }
  bdi[dir="ltr"] { unicode-bidi: isolate; direction: ltr; }

  .badge { font-size: 11px; font-weight: 800; padding: 3px 9px; border-radius: 999px; white-space: nowrap; display: inline-flex; align-items: center; gap: 4px; }
  .b-soon { background: var(--amber-bg); color: var(--amber); }
  .b-ok { background: var(--green-bg); color: var(--green); }
  .b-late { background: var(--red-bg); color: var(--red); }
  .b-gray { background: var(--soft); color: var(--muted); border: 1px solid var(--line); }

  .btn { border: 0; border-radius: 12px; padding: 9px 14px; font-weight: 800; font-size: 13px; min-height: 40px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-family: inherit; white-space: nowrap; cursor: pointer; text-decoration: none; }
  .btn-primary { background: var(--blue); color: #fff; }
  .btn-green { background: var(--green); color: #fff; }
  .btn-red { background: var(--red); color: #fff; }
  .btn-ghost { background: var(--surface); color: var(--ink); border: 1px solid #cdd6e4; }
  .btn-sm { min-height: 32px; padding: 6px 10px; font-size: 12px; border-radius: 10px; }

  .tabs { display: flex; gap: 4px; background: var(--soft); border: 1px solid var(--line); border-radius: 12px; padding: 4px; width: fit-content; }
  .tabs a { padding: 7px 12px; border-radius: 9px; font-size: 13px; font-weight: 700; color: var(--muted); text-decoration: none; }
  .tabs a.on { background: var(--surface); color: var(--ink); box-shadow: 0 2px 6px rgba(15,29,58,.08); }

  .grid2 { display: grid; grid-template-columns: 1.1fr 1fr; gap: 14px; align-items: start; }
  .label { font-size: 12px; color: var(--muted); font-weight: 700; margin-bottom: 4px; display: block; }
  .box { background: var(--soft); border: 1px solid var(--line); border-radius: 12px; padding: 10px 12px; font-size: 13px; font-weight: 700; }
  .diff { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
  .old { background: var(--red-bg); border-color: #f5c2c2; color: var(--red); text-decoration: line-through; }
  .new { background: var(--green-bg); border-color: #b7e1c7; color: var(--green); }
  .reason { background: var(--amber-bg); border-color: #f7dc9b; color: var(--amber); font-weight: 600; line-height: 1.6; }

  .field { display: flex; flex-direction: column; gap: 5px; margin-bottom: 10px; }
  .input, textarea.input, select.input { border: 1px solid #cdd6e4; border-radius: 12px; padding: 10px 12px; font-size: 14px; font-weight: 600; color: var(--ink); background: var(--surface); font-family: inherit; width: 100%; }
  textarea.input { min-height: 80px; resize: vertical; }
  .field-err { color: var(--red); font-size: 12px; font-weight: 700; }
  .actions { display: flex; gap: 8px; flex-wrap: wrap; }

  .login-wrap { min-height: 100vh; display: grid; place-items: center; background: var(--navy); padding: 20px; }
  .login-card { width: 100%; max-width: 380px; background: var(--surface); border-radius: 20px; padding: 26px; display: flex; flex-direction: column; gap: 6px; box-shadow: 0 20px 40px rgba(0,0,0,.2); }
  .login-card h3 { margin: 4px 0 8px; font-size: 20px; font-weight: 800; }

  .empty { padding: 28px 16px; text-align: center; color: var(--muted); font-weight: 700; font-size: 14px; }
  .pager { padding: 10px 14px; }
  .pager-nav { display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; }
  .is-disabled { opacity: .45; cursor: default; }

  @media (max-width: 900px) {
    .app { grid-template-columns: 1fr; }
    .side { flex-direction: row; flex-wrap: nowrap; overflow-x: auto; align-items: center; gap: 2px; }
    .side .brand { flex-shrink: 0; }
    .nav { white-space: nowrap; flex-shrink: 0; }
    .hide-sm { display: none; }
    .side .foot { display: none; }
    .kpis { grid-template-columns: repeat(2, 1fr); }
    .grid2 { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>

<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <symbol id="car" viewBox="0 0 24 24"><path d="M5 17h14M3 13l2-5a2 2 0 0 1 2-1h10a2 2 0 0 1 2 1l2 5v4H3z"/><circle cx="7.5" cy="17" r="1.5"/><circle cx="16.5" cy="17" r="1.5"/></symbol>
  <symbol id="home" viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5V21h-6v-6H9v6H3z"/></symbol>
  <symbol id="list" viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></symbol>
  <symbol id="users" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2 20a7 7 0 0 1 14 0M16 4.5a3.5 3.5 0 0 1 0 7M22 20a7 7 0 0 0-4-6.3"/></symbol>
  <symbol id="shield" viewBox="0 0 24 24"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/></symbol>
  <symbol id="settings" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M4.9 19.1 7 17M17 7l2.1-2.1"/></symbol>
  <symbol id="file" viewBox="0 0 24 24"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/></symbol>
</svg>

@auth('admin')
<div class="app">
  <aside class="side">
    <div class="brand"><div class="mark"><svg class="i"><use href="#car"/></svg></div>كراجي</div>
    <a class="nav {{ request()->routeIs('admin.dashboard') ? 'on' : '' }}" href="{{ route('admin.dashboard') }}"><svg class="i"><use href="#home"/></svg>الرئيسية</a>
    <a class="nav {{ request()->routeIs('admin.requests.*') ? 'on' : '' }}" href="{{ route('admin.requests.index') }}"><svg class="i"><use href="#list"/></svg>طلبات التعديل</a>
    <a class="nav {{ request()->routeIs('admin.claims.*') ? 'on' : '' }}" href="{{ route('admin.claims.index') }}"><svg class="i"><use href="#users"/></svg>طلبات الملكية</a>
    <a class="nav {{ request()->routeIs('admin.vehicles.*') ? 'on' : '' }}" href="{{ route('admin.vehicles.index') }}"><svg class="i"><use href="#car"/></svg>السيارات</a>
    <a class="nav {{ request()->routeIs('admin.plate-templates.*') ? 'on' : '' }}" href="{{ route('admin.plate-templates.index') }}"><svg class="i"><use href="#file"/></svg>قوالب اللوحات</a>
    @if (auth('admin')->user()->role === 'admin')
      <a class="nav {{ request()->routeIs('admin.staff.*') ? 'on' : '' }}" href="{{ route('admin.staff.index') }}"><svg class="i"><use href="#shield"/></svg>المراجعون والسجل</a>
    @endif
    <div class="foot">
      <div class="avatar">{{ mb_substr(auth('admin')->user()->name, 0, 1) }}</div>
      <div>
        <div style="color:#fff">{{ auth('admin')->user()->name }}</div>
        <div style="font-size:11px;opacity:.7">{{ auth('admin')->user()->role === 'admin' ? 'مشرف' : 'مراجع' }}</div>
        <form method="POST" action="{{ route('admin.logout') }}">
          @csrf
          <button class="logout" type="submit">تسجيل الخروج</button>
        </form>
      </div>
    </div>
  </aside>

  <div class="main">
    <div class="topbar">
      <h2>@yield('heading')</h2>
      <div>@yield('actions')</div>
    </div>
    <div class="content">
      @if (session('status'))
        <div class="flash ok">{{ session('status') }}</div>
      @endif
      @if ($errors->any())
        <div class="flash err">{{ $errors->first() }}</div>
      @endif
      @yield('content')
    </div>
  </div>
</div>
@else
  @yield('content')
@endauth

</body>
</html>
