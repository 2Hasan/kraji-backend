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
        <tr><th>السيارة</th><th>سنة</th><th>الشاصي</th><th>العداد</th><th>الحالة</th></tr>
      </thead>
      <tbody>
        @forelse ($vehicles as $vehicle)
          <tr>
            <td>{{ $vehicle->make }} {{ $vehicle->model }} <span class="sm2 muted">{{ $vehicle->nickname }}</span></td>
            <td class="muted">{{ $vehicle->year }}</td>
            <td class="mono">… {{ $vehicle->chassis_last4 }}</td>
            <td class="muted">{{ number_format($vehicle->current_odometer) }} كم</td>
            <td>
              @if ($vehicle->status === 'active')<span class="badge b-ok">نشطة</span>@else<span class="badge b-gray">مؤرشفة</span>@endif
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="empty">ما فيه سيارات.</td></tr>
        @endforelse
      </tbody>
    </table>
    <div class="pager">{{ $vehicles->links() }}</div>
  </div>
@endsection
