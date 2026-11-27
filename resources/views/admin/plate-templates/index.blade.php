@extends('admin.layout')
@section('title', 'قوالب اللوحات')
@section('heading', 'قوالب اللوحات')

@section('content')
  <div class="grid2">
    <div class="panel">
      <table>
        <thead><tr><th>الرمز</th><th>الاسم</th><th>نوع الهيكل</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
          @forelse ($templates as $template)
            <tr>
              <td class="mono">{{ $template->code }}</td>
              <td>{{ $template->name }}</td>
              <td class="muted">{{ $template->body_type }}</td>
              <td>
                @if ($template->status === 'active')
                  <span class="badge b-ok">مفعّل</span>
                @else
                  <span class="badge b-gray">مسودة</span>
                  @if (! empty($template->validation_errors))
                    <div class="sm2" style="color:var(--red)">{{ implode(' ', $template->validation_errors) }}</div>
                  @endif
                @endif
              </td>
              <td>
                @if ($template->status !== 'active')
                  <form method="POST" action="{{ route('admin.plate-templates.activate', $template) }}">
                    @csrf
                    <button class="btn btn-green btn-sm" type="submit">تفعيل</button>
                  </form>
                @endif
                <details>
                  <summary class="sm2">استبدال الملف</summary>
                  <form method="POST" action="{{ route('admin.plate-templates.replace-svg', $template) }}" enctype="multipart/form-data">
                    @csrf
                    <input class="input" name="svg" type="file" accept=".svg,image/svg+xml" required>
                    <button class="btn btn-primary btn-sm" type="submit">حفظ الملف</button>
                  </form>
                </details>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="empty">ما فيه قوالب بعد.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="panel">
      <div class="panel-h"><b>رفع قالب</b></div>
      <form method="POST" action="{{ route('admin.plate-templates.store') }}" enctype="multipart/form-data" class="panel-pad">
        @csrf
        <div class="field"><label class="label" for="p-code">الرمز (حروف إنجليزية وأرقام)</label><input class="input" id="p-code" name="code" required maxlength="60" value="{{ old('code') }}"></div>
        <div class="field"><label class="label" for="p-name">الاسم</label><input class="input" id="p-name" name="name" required maxlength="120" value="{{ old('name') }}"></div>
        <div class="field"><label class="label" for="p-body">نوع الهيكل</label><input class="input" id="p-body" name="body_type" required maxlength="40" value="{{ old('body_type') }}"></div>
        <div class="field"><label class="label" for="p-svg">ملف SVG (يحتوي @{{plate_number}} مرة وحدة)</label><input class="input" id="p-svg" name="svg" type="file" accept=".svg,image/svg+xml" required></div>
        <button class="btn btn-primary" type="submit">رفع كمسودة</button>
      </form>
    </div>
  </div>
@endsection
