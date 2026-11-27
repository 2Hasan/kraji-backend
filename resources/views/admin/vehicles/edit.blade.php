@extends('admin.layout')
@section('title', 'تعديل سيارة')
@section('heading', 'تعديل سيارة')

@section('content')
  <div class="panel" style="max-width:640px">
    <div class="panel-h"><b>{{ $vehicle->make }} {{ $vehicle->model }}</b> <span class="sm2 muted">الشاصي ...{{ $vehicle->chassis_last4 }}</span></div>
    <form method="POST" action="{{ route('admin.vehicles.update', $vehicle) }}" class="panel-pad">
      @csrf
      <div class="field"><label class="label" for="v-make">الماركة</label><input class="input" id="v-make" name="make" required maxlength="60" value="{{ old('make', $vehicle->make) }}"></div>
      <div class="field"><label class="label" for="v-model">الموديل</label><input class="input" id="v-model" name="model" required maxlength="60" value="{{ old('model', $vehicle->model) }}"></div>
      <div class="field"><label class="label" for="v-year">السنة</label><input class="input" id="v-year" name="year" type="number" min="1950" max="2100" value="{{ old('year', $vehicle->year) }}"></div>
      <div class="field"><label class="label" for="v-nick">الاسم المختصر</label><input class="input" id="v-nick" name="nickname" maxlength="60" value="{{ old('nickname', $vehicle->nickname) }}"></div>
      <div class="field"><label class="label" for="v-plate">رقم اللوحة</label><input class="input" id="v-plate" name="plate_number" maxlength="20" value="{{ old('plate_number', $vehicle->plate_number) }}"></div>
      <div class="field">
        <label class="label" for="v-style">نوع السيارة (القالب)</label>
        <select class="input" id="v-style" name="plate_style" required>
          <option value="classic" @selected(old('plate_style', $vehicle->plate_style) === 'classic')>أبيض (افتراضي)</option>
          <option value="carbon" @selected(old('plate_style', $vehicle->plate_style) === 'carbon')>داكن (افتراضي)</option>
          <option value="blue" @selected(old('plate_style', $vehicle->plate_style) === 'blue')>أزرق (افتراضي)</option>
          @foreach ($templates as $template)
            <option value="{{ $template->code }}" @selected(old('plate_style', $vehicle->plate_style) === $template->code)>{{ $template->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label class="label"><input type="checkbox" name="plate_visible" value="1" @checked(old('plate_visible', $vehicle->plate_visible))> إظهار رقم اللوحة للمستخدم</label>
      </div>
      <div class="actions">
        <button class="btn btn-primary" type="submit">حفظ</button>
        <a class="btn btn-ghost" href="{{ route('admin.vehicles.index') }}">رجوع</a>
      </div>
    </form>
  </div>
@endsection
