@extends('admin.layout')
@section('title', 'المراجعون والسجل')
@section('heading', 'المراجعون والسجل')

@section('content')
  <div class="tabs">
    <a class="{{ $tab === 'reviewers' ? 'on' : '' }}" href="{{ route('admin.staff.index') }}">المراجعون</a>
    <a class="{{ $tab === 'audit' ? 'on' : '' }}" href="{{ route('admin.staff.index', ['tab' => 'audit']) }}">سجل التدقيق</a>
  </div>

  @if ($tab === 'reviewers')
    <div class="grid2">
      <div class="panel">
        <table>
          <thead><tr><th>الاسم</th><th>البريد</th><th>الحالة</th><th></th></tr></thead>
          <tbody>
            @forelse ($reviewers as $reviewer)
              <tr>
                <td>{{ $reviewer->name }}</td>
                <td class="mono">{{ $reviewer->email }}</td>
                <td>@if ($reviewer->suspended_at)<span class="badge b-late">موقوف</span>@else<span class="badge b-ok">فعّال</span>@endif</td>
                <td>
                  @unless ($reviewer->suspended_at)
                    <form method="POST" action="{{ route('admin.staff.suspend', $reviewer) }}">
                      @csrf
                      <button class="btn btn-red btn-sm" type="submit">إيقاف</button>
                    </form>
                  @endunless
                </td>
              </tr>
            @empty
              <tr><td colspan="4" class="empty">ما فيه مراجعين بعد.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="panel">
        <div class="panel-h"><b>إضافة مراجع</b></div>
        <form method="POST" action="{{ route('admin.staff.store') }}" class="panel-pad">
          @csrf
          <div class="field"><label class="label" for="r-name">الاسم</label><input class="input" id="r-name" name="name" required maxlength="120" value="{{ old('name') }}"></div>
          <div class="field"><label class="label" for="r-email">البريد</label><input class="input" id="r-email" name="email" type="email" required value="{{ old('email') }}"></div>
          <div class="field"><label class="label" for="r-pass">كلمة المرور (12 حرف على الأقل)</label><input class="input" id="r-pass" name="password" type="password" required minlength="12"></div>
          <div class="field"><label class="label" for="r-pass2">تأكيد كلمة المرور</label><input class="input" id="r-pass2" name="password_confirmation" type="password" required minlength="12"></div>
          <button class="btn btn-primary" type="submit">إضافة</button>
        </form>
      </div>
    </div>
  @else
    <div class="panel">
      <table>
        <thead><tr><th>الوقت</th><th>الإجراء</th><th>الجهة</th><th>ملاحظة</th></tr></thead>
        <tbody>
          @forelse ($logs as $log)
            <tr>
              <td class="muted">{{ $log->created_at->format('Y-m-d H:i') }}</td>
              <td class="mono">{{ $log->action }}</td>
              <td class="mono">{{ $log->subject_type }}#{{ $log->subject_id }}</td>
              <td class="muted">{{ $log->note }}</td>
            </tr>
          @empty
            <tr><td colspan="4" class="empty">السجل فاضي.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  @endif
@endsection
