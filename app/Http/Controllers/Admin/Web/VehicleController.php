<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\OwnershipClaim;
use App\Models\PlateTemplate;
use App\Models\ServiceRecord;
use App\Models\ServiceRecordAttachment;
use App\Models\Vehicle;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class VehicleController extends Controller
{
    /** بحث بالماركة أو الموديل أو آخر ٤ من الشاصي. الشاصي الكامل ما يظهر هنا. */
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $vehicles = Vehicle::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('make', 'ilike', "%{$q}%")
                        ->orWhere('model', 'ilike', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $templates = PlateTemplate::where('status', 'active')
            ->whereIn('code', $vehicles->pluck('plate_style')->filter()->unique())
            ->get()
            ->keyBy('code');

        $disk = Storage::disk(config('filesystems.attachments_disk'));
        $svgByCode = [];
        $fontFaces = [];
        foreach ($templates as $template) {
            $svg = $disk->get($template->svg_storage_key);
            $svgByCode[$template->code] = trim(preg_replace('/<\?xml[^>]*\?>/', '', $svg));
            foreach ($template->fonts ?? [] as $file) {
                $fontFaces[] = [
                    'family' => pathinfo($file, PATHINFO_FILENAME),
                    'url' => route('admin.plate-templates.font', [$template, $file]),
                ];
            }
        }

        // اللوحة المرسومة لكل سيارة (الرقم مكان {{plate_number}}، مهرّب للعرض).
        $plateHtml = [];
        foreach ($vehicles as $vehicle) {
            if (isset($svgByCode[$vehicle->plate_style])) {
                $plateHtml[$vehicle->id] = str_replace('{{plate_number}}', e($vehicle->plate_number ?: '— —'), $svgByCode[$vehicle->plate_style]);
            }
        }

        return view('admin.vehicles.index', compact('vehicles', 'q', 'plateHtml', 'fontFaces'));
    }

    /** نموذج تعديل السيارة (المشرف فقط). الشاصي ما يتغيّر. */
    public function edit(Vehicle $vehicle): View
    {
        return view('admin.vehicles.edit', [
            'vehicle' => $vehicle,
            'templates' => PlateTemplate::where('status', 'active')->orderBy('name')->get(['code', 'name']),
        ]);
    }

    public function update(Request $request, Vehicle $vehicle, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'make' => ['required', 'string', 'max:60'],
            'model' => ['required', 'string', 'max:60'],
            'year' => ['nullable', 'integer', 'between:1950,2100'],
            'nickname' => ['nullable', 'string', 'max:60'],
            'plate_number' => ['nullable', 'string', 'max:20'],
            'plate_style' => ['required', 'string', 'max:40', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! PlateTemplate::where('code', $value)->where('status', 'active')->exists()) {
                    $fail('القالب المختار غير متوفر.');
                }
            }],
        ]);

        $data['plate_visible'] = $request->boolean('plate_visible');
        $data['body_type'] = PlateTemplate::where('code', $data['plate_style'])->value('body_type');

        $changed = collect($data)
            ->filter(fn ($value, $key) => (string) $vehicle->getAttribute($key) !== (string) $value)
            ->keys()
            ->all();

        $vehicle->update($data);

        $audit->record($this->admin(), 'vehicle.updated', Vehicle::class, $vehicle->id, implode('، ', $changed) ?: 'بدون تغيير');

        return redirect()->route('admin.vehicles.index')->with('status', 'تم حفظ بيانات السيارة.');
    }

    /** أرشفة (تخفي السيارة من التطبيق مع الحفاظ على سجلها). */
    public function archive(Vehicle $vehicle, AuditLogger $audit): RedirectResponse
    {
        $vehicle->update(['status' => 'archived']);
        $audit->record($this->admin(), 'vehicle.archived', Vehicle::class, $vehicle->id);

        return redirect()->route('admin.vehicles.index')->with('status', 'تمت أرشفة السيارة.');
    }

    public function restore(Vehicle $vehicle, AuditLogger $audit): RedirectResponse
    {
        $vehicle->update(['status' => 'active']);
        $audit->record($this->admin(), 'vehicle.restored', Vehicle::class, $vehicle->id);

        return redirect()->route('admin.vehicles.index')->with('status', 'تم إرجاع السيارة للنشط.');
    }

    /**
     * حذف نهائي: فقط للسيارة المؤرشفة، ويتطلب كتابة عبارة التأكيد. يمسح السجل وملفات المرفقات والإثباتات.
     */
    public function destroy(Request $request, Vehicle $vehicle, AuditLogger $audit): RedirectResponse
    {
        abort_unless($vehicle->status === 'archived', 422);
        $request->validate(['confirm' => ['required', 'in:حذف نهائي']]);

        $recordIds = ServiceRecord::where('vehicle_id', $vehicle->id)->pluck('id');
        $storageKeys = ServiceRecordAttachment::whereIn('service_record_id', $recordIds)->pluck('storage_key')
            ->merge(OwnershipClaim::where('vehicle_id', $vehicle->id)->pluck('proof_storage_key'))
            ->all();

        $label = trim($vehicle->make.' '.$vehicle->model).' (شاصي ...'.$vehicle->chassis_last4.')';

        DB::transaction(function () use ($vehicle, $audit, $label): void {
            $audit->record($this->admin(), 'vehicle.deleted', Vehicle::class, $vehicle->id, $label);
            $vehicle->delete();
        });

        $disk = Storage::disk(config('filesystems.attachments_disk'));
        foreach ($storageKeys as $key) {
            $disk->delete($key);
        }

        return redirect()->route('admin.vehicles.index')->with('status', 'تم حذف السيارة نهائياً.');
    }

    private function admin(): AdminUser
    {
        return Auth::guard('admin')->user();
    }
}
