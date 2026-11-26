@extends('admin.layout')
@section('title', 'مراجعة طلب ملكية')
@section('heading', 'طلب ملكية C-'.$claim->id)

@section('content')
  <div class="grid2">
    <div class="panel">
      <div class="panel-h"><b>تفاصيل السيارة والمطالب</b><span class="badge b-soon">{{ $claim->status === 'pending' ? 'معلّق' : $claim->status }}</span></div>
      <div class="panel-pad">
        <span class="label">السيارة</span>
        <div class="box">{{ $claim->vehicle?->make }} {{ $claim->vehicle?->model }} {{ $claim->vehicle?->year }}</div>

        <span class="label" style="margin-top:12px">الشاصي (آخر ٤)</span>
        <div class="box mono">… {{ $claim->vehicle?->chassis_last4 }}</div>

        <span class="label" style="margin-top:12px">المطالب</span>
        <div class="box">{{ $claimant?->name ?? 'حساب محذوف' }}</div>

        <span class="label" style="margin-top:12px">إثبات الملكية</span>
        <a class="btn btn-ghost" href="{{ route('admin.claims.proof', $claim) }}" target="_blank" rel="noopener">فتح الملف</a>
      </div>
    </div>

    <div class="panel">
      <div class="panel-h"><b>القرار</b></div>
      <div class="panel-pad">
        @if ($claim->status === 'pending')
          <form method="POST" action="{{ route('admin.claims.approve', $claim) }}" class="field">
            @csrf
            <label class="label" for="approve-note">ملاحظة (اختيارية)</label>
            <textarea class="input" id="approve-note" name="note" maxlength="1000"></textarea>
            <div class="actions" style="margin-top:8px"><button class="btn btn-green" type="submit">موافقة</button></div>
          </form>

          <hr style="border:0;border-top:1px solid var(--line);margin:16px 0">

          <form method="POST" action="{{ route('admin.claims.reject', $claim) }}" class="field">
            @csrf
            <label class="label" for="reject-note">سبب الرفض (إلزامي)</label>
            <textarea class="input" id="reject-note" name="note" required minlength="3" maxlength="1000"></textarea>
            <div class="actions" style="margin-top:8px"><button class="btn btn-red" type="submit">رفض</button></div>
          </form>
        @else
          <div class="box">{{ $claim->status === 'approved' ? 'موافَق عليه' : 'مرفوض' }}@if ($claim->review_note) — {{ $claim->review_note }}@endif</div>
        @endif
      </div>
    </div>
  </div>
@endsection
