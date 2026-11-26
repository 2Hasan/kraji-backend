@extends('admin.layout')
@section('title', 'طلبات الملكية')
@section('heading', 'طلبات الملكية')

@section('content')
  <div class="panel">
    <table>
      <thead>
        <tr><th>رقم</th><th>السيارة</th><th>الشاصي</th><th>التاريخ</th><th></th></tr>
      </thead>
      <tbody>
        @forelse ($claims as $claim)
          <tr>
            <td class="mono">C-{{ $claim->id }}</td>
            <td>{{ $claim->vehicle?->make }} {{ $claim->vehicle?->model }}</td>
            <td class="mono">… {{ $claim->vehicle?->chassis_last4 }}</td>
            <td class="muted">{{ $claim->created_at->diffForHumans() }}</td>
            <td><a class="btn btn-primary btn-sm" href="{{ route('admin.claims.show', $claim) }}">مراجعة</a></td>
          </tr>
        @empty
          <tr><td colspan="5" class="empty">ما فيه طلبات ملكية معلّقة.</td></tr>
        @endforelse
      </tbody>
    </table>
    <div class="pager">{{ $claims->links() }}</div>
  </div>
@endsection
