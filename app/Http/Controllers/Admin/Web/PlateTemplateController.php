<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\PlateTemplate;
use App\Services\PlateTemplateValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PlateTemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.plate-templates.index', [
            'templates' => PlateTemplate::orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request, PlateTemplateValidator $validator): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'alpha_dash', 'max:60', 'unique:plate_templates,code'],
            'name' => ['required', 'string', 'max:120'],
            'body_type' => ['required', 'string', 'max:40'],
            'svg' => ['required', 'file', 'mimetypes:image/svg+xml,text/plain,text/xml', 'max:512'],
        ]);

        $svg = file_get_contents($request->file('svg')->getRealPath());
        $key = Storage::disk(config('filesystems.attachments_disk'))
            ->putFileAs('plate-templates', $request->file('svg'), $data['code'].'.svg');

        PlateTemplate::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'body_type' => $data['body_type'],
            'svg_storage_key' => $key,
            'status' => 'draft',
            'validation_errors' => $validator->validate($svg),
        ]);

        return redirect()->route('admin.plate-templates.index')->with('status', 'تم رفع القالب كمسودة.');
    }

    public function activate(PlateTemplate $template): RedirectResponse
    {
        if (! empty($template->validation_errors)) {
            return redirect()->route('admin.plate-templates.index')
                ->withErrors(['activate' => 'القالب '.$template->code.' فيه أخطاء ولا يمكن تفعيله.']);
        }

        $template->update(['status' => 'active']);

        return redirect()->route('admin.plate-templates.index')->with('status', 'تم تفعيل القالب.');
    }
}
