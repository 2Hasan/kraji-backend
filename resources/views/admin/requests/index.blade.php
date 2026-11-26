@extends('admin.layout')
@section('title', 'طلبات التعديل')
@section('heading', 'طلبات التعديل والحذف')

@section('content')
  <div class="panel">
    <table>
      <thead>
        <tr><th>رقم</th><th>النوع</th><th>السيارة</th><th>السبب</th><th>التاريخ</th><th></th></tr>
      </thead>
      <tbody>
        @forelse ($requests as $cr)
          <tr>
            <td class="mono">R-{{ $cr->id }}</td>
            <td>
              @if ($cr->type === 'delete')<span class="badge b-late">حذف</span>@else<span class="badge b-soon">تعديل</span>@endif
            </td>
            <td>{{ $cr->serviceRecord?->vehicle?->make }} {{ $cr->serviceRecord?->vehicle?->model }}<span class="sm2 muted">{{ $cr->serviceRecord?->vehicle?->chassis_last4 ? '… '.$cr->serviceRecord->vehicle->chassis_last4 : '' }}</span></td>
            <td class="muted">{{ \Illuminate\Support\Str::limit($cr->reason, 60) }}</td>
            <td class="muted">{{ $cr->created_at->diffForHumans() }}</td>
            <td><a class="btn btn-primary btn-sm" href="{{ route('admin.requests.show', $cr) }}">مراجعة</a></td>
          </tr>
        @empty
          <tr><td colspan="6" class="empty">ما فيه طلبات معلّقة.</td></tr>
        @endforelse
      </tbody>
    </table>
    <div class="pager">{{ $requests->links() }}</div>
  </div>
@endsection
