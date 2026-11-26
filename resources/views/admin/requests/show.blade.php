@extends('admin.layout')
@section('title', 'مراجعة طلب')
@section('heading', 'طلب '.($cr->type === 'delete' ? 'حذف' : 'تعديل').' R-'.$cr->id)

@section('content')
  @php
    $labels = ['service_date' => 'التاريخ', 'odometer' => 'العداد (كم)', 'cost' => 'التكلفة (BHD)', 'workshop_name' => 'الورشة', 'notes' => 'ملاحظات'];
  @endphp

  <div class="grid2">
    <div class="panel">
      <div class="panel-h"><b>المقارنة</b><span class="badge b-soon">{{ $cr->status === 'pending' ? 'معلّق' : $cr->status }}</span></div>
      <div class="panel-pad">
        @if ($cr->type === 'delete')
          <div class="box">السجل الحالي: {{ $cr->serviceRecord?->service_date?->format('Y-m-d') }} · {{ number_format($cr->serviceRecord?->odometer ?? 0) }} كم</div>
          <div class="muted" style="font-size:12px;margin-top:8px">الحذف يخفي السجل بحالة "محذوف بموافقة"، والسجل نفسه يبقى.</div>
        @else
          @foreach ($labels as $key => $label)
            @if (array_key_exists($key, $cr->payload_json ?? []))
              <div style="margin-bottom:12px">
                <span class="label">{{ $label }}</span>
                <div class="diff">
                  <div class="box old">{{ $cr->original_json[$key] ?? '—' }}</div>
                  <div class="box new">{{ $cr->payload_json[$key] }}</div>
                </div>
              </div>
            @endif
          @endforeach
        @endif

        <span class="label">سبب الطلب</span>
        <div class="box reason">{{ $cr->reason }}</div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-h"><b>القرار</b></div>
      <div class="panel-pad">
        @if ($cr->status === 'pending')
          <form method="POST" action="{{ route('admin.requests.approve', $cr) }}" class="field">
            @csrf
            <label class="label" for="approve-note">ملاحظة (اختيارية)</label>
            <textarea class="input" id="approve-note" name="note" maxlength="1000"></textarea>
            <div class="actions" style="margin-top:8px"><button class="btn btn-green" type="submit">موافقة</button></div>
          </form>

          <hr style="border:0;border-top:1px solid var(--line);margin:16px 0">

          <form method="POST" action="{{ route('admin.requests.reject', $cr) }}" class="field">
            @csrf
            <label class="label" for="reject-note">سبب الرفض (إلزامي)</label>
            <textarea class="input" id="reject-note" name="note" required minlength="3" maxlength="1000"></textarea>
            <div class="actions" style="margin-top:8px"><button class="btn btn-red" type="submit">رفض</button></div>
          </form>
        @else
          <div class="box">{{ $cr->status === 'approved' ? 'موافَق عليه' : 'مرفوض' }}@if ($cr->review_note) — {{ $cr->review_note }}@endif</div>
        @endif
      </div>
    </div>
  </div>
@endsection
