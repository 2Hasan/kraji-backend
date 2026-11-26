<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>تسجيل الدخول — كراجي</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
<style>
  :root { --surface:#fff; --ink:#0f1d3a; --muted:#55657f; --navy:#0f1d3a; --blue:#2f5fd0; --red:#b91c1c; --red-bg:#fee2e2; }
  * { box-sizing: border-box; }
  body { margin:0; background:var(--navy); color:var(--ink); font-family:"Tajawal", system-ui, sans-serif; min-height:100vh; display:grid; place-items:center; padding:20px; }
  .card { width:100%; max-width:380px; background:var(--surface); border-radius:20px; padding:26px; display:flex; flex-direction:column; gap:6px; box-shadow:0 20px 40px rgba(0,0,0,.2); }
  .brand { display:flex; align-items:center; gap:10px; font-weight:800; font-size:16px; }
  .mark { width:30px; height:30px; border-radius:10px; background:var(--blue); display:grid; place-items:center; color:#fff; font-size:12px; font-weight:800; }
  h3 { margin:8px 0 8px; font-size:20px; font-weight:800; }
  label { font-size:12px; color:var(--muted); font-weight:700; margin-top:6px; }
  .input { border:1px solid #cdd6e4; border-radius:12px; padding:10px 12px; font-size:15px; font-weight:600; font-family:inherit; width:100%; }
  .btn { margin-top:12px; border:0; border-radius:12px; padding:11px 14px; font-weight:800; font-size:15px; min-height:44px; background:var(--blue); color:#fff; font-family:inherit; cursor:pointer; }
  .err { background:var(--red-bg); color:var(--red); border-radius:12px; padding:10px 12px; font-size:13px; font-weight:700; }
  .note { color:var(--muted); font-size:12px; text-align:center; margin-top:6px; }
</style>
</head>
<body>
  <form class="card" method="POST" action="{{ route('admin.login') }}">
    @csrf
    <div class="brand"><div class="mark">ك</div>كراجي · الإدارة</div>
    <h3>تسجيل الدخول</h3>

    @if ($errors->any())
      <div class="err">{{ $errors->first() }}</div>
    @endif

    <label for="email">البريد الإلكتروني</label>
    <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">

    <label for="password">كلمة المرور</label>
    <input class="input" id="password" name="password" type="password" required autocomplete="current-password">

    <button class="btn" type="submit">دخول</button>
    <div class="note">الدخول لحسابات الإدارة والمراجعين فقط.</div>
  </form>
</body>
</html>
