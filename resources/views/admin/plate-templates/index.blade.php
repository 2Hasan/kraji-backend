@extends('admin.layout')
@section('title', 'قوالب اللوحات')
@section('heading', 'قوالب اللوحات')

@section('content')
  <div class="grid2">
    <div class="panel">
      <table>
        <thead><tr><th>نوع السيارة</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
          @forelse ($templates as $template)
            <tr>
              <td>{{ $template->name }}</td>
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
            <tr><td colspan="3" class="empty">ما فيه قوالب بعد.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="panel">
      <div class="panel-h"><b>رفع قالب</b></div>
      <form method="POST" action="{{ route('admin.plate-templates.store') }}" enctype="multipart/form-data" class="panel-pad">
        @csrf
        <div class="field"><label class="label" for="p-name">نوع السيارة (مثل: خصوصي، نقل خاص)</label><input class="input" id="p-name" name="name" required maxlength="40" value="{{ old('name') }}"></div>
        <div class="field"><label class="label" for="p-svg">ملف SVG (يحتوي @{{plate_number}} مرة وحدة)</label><input class="input" id="p-svg" name="svg" type="file" accept=".svg,image/svg+xml" required></div>
        <button class="btn btn-primary" type="submit">رفع كمسودة</button>
      </form>
    </div>
  </div>
@endsection
