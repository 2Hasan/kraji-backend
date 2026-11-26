@extends('admin.layout')
@section('title', 'السيارات')
@section('heading', 'السيارات')

@section('content')
  <form method="GET" action="{{ route('admin.vehicles.index') }}" class="actions">
    <input class="input" style="max-width:320px" type="search" name="q" value="{{ $q }}" placeholder="ابحث بالماركة أو الموديل">
    <button class="btn btn-ghost" type="submit">بحث</button>
  </form>

  <div class="panel">
    <table>
      <thead>
        <tr><th>السيارة</th><th class="hide-sm">سنة</th><th>الشاصي</th><th>رقم اللوحة</th><th>العداد</th><th>الحالة</th></tr>
      </thead>
      <tbody>
        @forelse ($vehicles as $vehicle)
          <tr>
            <td>{{ $vehicle->make }} {{ $vehicle->model }} <span class="sm2 muted">{{ $vehicle->nickname }}</span></td>
            <td class="muted hide-sm">{{ $vehicle->year }}</td>
            <td class="muted"><bdi dir="ltr">…{{ $vehicle->chassis_last4 }}</bdi></td>
            <td>
              @if ($vehicle->plate_number)
                <bdi dir="ltr" class="plate-no">{{ $vehicle->plate_number }}</bdi>
                @if (! $vehicle->plate_visible)<span class="sm2 muted">مخفية عن الآخرين</span>@endif
              @else
                <span class="muted" style="white-space:nowrap">ما أدخل رقم</span>
              @endif
            </td>
            <td class="muted" style="white-space:nowrap">{{ number_format($vehicle->current_odometer) }} كم</td>
            <td>
              @if ($vehicle->status === 'active')<span class="badge b-ok">نشطة</span>@else<span class="badge b-gray">مؤرشفة</span>@endif
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="empty">ما فيه سيارات.</td></tr>
        @endforelse
      </tbody>
    </table>
    <div class="pager">@include('admin.partials.pagination', ['paginator' => $vehicles])</div>
  </div>
@endsection
