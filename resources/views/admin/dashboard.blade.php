@extends('admin.layout')
@section('title', 'الرئيسية')
@section('heading', 'الرئيسية')

@section('content')
  <div class="kpis">
    <div class="kpi"><div class="k">طلبات تعديل معلّقة</div><div class="v">{{ $pendingRequests }}</div></div>
    <div class="kpi"><div class="k">طلبات ملكية معلّقة</div><div class="v">{{ $pendingClaims }}</div></div>
    <div class="kpi"><div class="k">سيارات نشطة</div><div class="v">{{ $activeVehicles }}</div></div>
    <div class="kpi"><div class="k">مراجعون فعّالون</div><div class="v">{{ $reviewers }}</div></div>
  </div>

  <div class="grid2">
    <div class="panel">
      <div class="panel-h"><b>آخر الإجراءات</b>
        @if (auth('admin')->user()->role === 'admin')<a class="btn btn-ghost btn-sm" href="{{ route('admin.staff.index', ['tab' => 'audit']) }}">السجل كامل</a>@endif
      </div>
      @forelse ($recentLogs as $log)
        <div style="padding:10px 16px;border-bottom:1px solid var(--line);font-size:13px;font-weight:700">
          {{ $log->action }} <span class="muted">· {{ $log->created_at->diffForHumans() }}</span>
        </div>
      @empty
        <div class="empty">ما فيه إجراءات بعد.</div>
      @endforelse
    </div>

    <div class="panel">
      <div class="panel-h"><b>اختصارات</b></div>
      <div class="panel-pad actions">
        <a class="btn btn-primary" href="{{ route('admin.requests.index') }}">طابور الطلبات</a>
        <a class="btn btn-ghost" href="{{ route('admin.claims.index') }}">طلبات الملكية</a>
        <a class="btn btn-ghost" href="{{ route('admin.plate-templates.index') }}">قوالب اللوحات</a>
      </div>
    </div>
  </div>
@endsection
