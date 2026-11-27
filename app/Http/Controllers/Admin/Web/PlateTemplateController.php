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
            'name' => ['required', 'string', 'max:40'],
            'svg' => ['required', 'file', 'mimetypes:image/svg+xml,text/plain,text/xml', 'max:512'],
        ]);

        $code = PlateTemplate::newCode();
        $svg = file_get_contents($request->file('svg')->getRealPath());
        $key = Storage::disk(config('filesystems.attachments_disk'))
            ->putFileAs('plate-templates', $request->file('svg'), $code.'.svg');

        PlateTemplate::create([
            'code' => $code,
            'name' => $data['name'],
            'body_type' => $data['name'],
            'svg_storage_key' => $key,
            'status' => 'draft',
            'validation_errors' => $validator->validate($svg),
        ]);

        return redirect()->route('admin.plate-templates.index')->with('status', 'تم رفع القالب كمسودة.');
    }

    public function replaceSvg(Request $request, PlateTemplate $template, PlateTemplateValidator $validator): RedirectResponse
    {
        $request->validate([
            'svg' => ['required', 'file', 'mimetypes:image/svg+xml,text/plain,text/xml', 'max:512'],
        ]);

        $svg = file_get_contents($request->file('svg')->getRealPath());
        $disk = Storage::disk(config('filesystems.attachments_disk'));
        $disk->put($template->svg_storage_key, $svg);

        $errors = $validator->validate($svg);
        $template->update([
            'status' => 'draft',
            'validation_errors' => $errors,
        ]);

        return redirect()->route('admin.plate-templates.index')
            ->with('status', empty($errors)
                ? 'تم استبدال ملف القالب '.$template->code.'. فعّله من جديد.'
                : 'تم استبدال الملف لكن فيه أخطاء، القالب ما ينفعل.');
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
