<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\PlateTemplate;
use App\Models\Vehicle;
use App\Services\PlateTemplateBundle;
use App\Services\PlateTemplateValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PlateTemplateController extends Controller
{
    private const UPLOAD_RULES = [
        'file', 'mimetypes:image/svg+xml,text/plain,text/xml,application/zip,application/x-zip-compressed', 'max:5120',
    ];

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
            'svg' => ['required', ...self::UPLOAD_RULES],
        ]);

        $bundle = PlateTemplateBundle::read($request->file('svg'));
        $code = PlateTemplate::newCode();
        $disk = Storage::disk(config('filesystems.attachments_disk'));

        $key = 'plate-templates/'.$code.'.svg';
        $disk->put($key, $bundle['svg']);
        $this->storeFonts($disk, $code, $bundle['fonts']);

        PlateTemplate::create([
            'code' => $code,
            'name' => $data['name'],
            'body_type' => $data['name'],
            'svg_storage_key' => $key,
            'fonts' => array_keys($bundle['fonts']),
            'status' => 'draft',
            'validation_errors' => $validator->validate($bundle['svg'], $this->fontNames($bundle['fonts'])),
        ]);

        return redirect()->route('admin.plate-templates.index')->with('status', 'تم رفع القالب كمسودة.');
    }

    public function replaceSvg(Request $request, PlateTemplate $template, PlateTemplateValidator $validator): RedirectResponse
    {
        $request->validate([
            'svg' => ['required', ...self::UPLOAD_RULES],
        ]);

        $bundle = PlateTemplateBundle::read($request->file('svg'));
        $disk = Storage::disk(config('filesystems.attachments_disk'));
        $disk->put($template->svg_storage_key, $bundle['svg']);
        $disk->deleteDirectory($this->fontsDir($template->code));
        $this->storeFonts($disk, $template->code, $bundle['fonts']);

        $errors = $validator->validate($bundle['svg'], $this->fontNames($bundle['fonts']));
        $template->update([
            'status' => 'draft',
            'fonts' => array_keys($bundle['fonts']),
            'validation_errors' => $errors,
        ]);

        return redirect()->route('admin.plate-templates.index')
            ->with('status', empty($errors)
                ? 'تم استبدال ملف القالب '.$template->code.'. فعّله من جديد.'
                : 'تم استبدال الملف لكن فيه أخطاء، القالب ما ينفعل.');
    }

    /** ملف خط القالب للمعاينة في صفحة السيارات (أي مشرف أو مراجع). */
    public function font(PlateTemplate $template, string $file): Response
    {
        abort_unless(in_array($file, $template->fonts ?? [], true), 404);

        $disk = Storage::disk(config('filesystems.attachments_disk'));
        $path = 'plate-templates/'.$template->code.'/fonts/'.$file;
        abort_unless($disk->exists($path), 404);

        $type = str_ends_with(strtolower($file), '.otf') ? 'font/otf' : 'font/ttf';

        return response($disk->get($path), 200, ['Content-Type' => $type]);
    }

    /** تعديل اسم القالب (نوع السيارة). الاسم هو body_type كذلك. */
    public function rename(Request $request, PlateTemplate $template): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:40']]);

        $template->update(['name' => $data['name'], 'body_type' => $data['name']]);

        return redirect()->route('admin.plate-templates.index')->with('status', 'تم تعديل اسم القالب.');
    }

    /** حذف نهائي للقالب، ما يُسمح إذا فيه سيارات تستخدمه. */
    public function destroy(PlateTemplate $template): RedirectResponse
    {
        $inUse = Vehicle::where('plate_style', $template->code)->count();
        if ($inUse > 0) {
            return redirect()->route('admin.plate-templates.index')
                ->withErrors(['delete' => 'ما ينحذف القالب "'.$template->name.'"، لأن '.$inUse.' سيارة تستخدمه.']);
        }

        $disk = Storage::disk(config('filesystems.attachments_disk'));
        $disk->delete($template->svg_storage_key);
        $disk->deleteDirectory($this->fontsDir($template->code));
        $name = $template->name;
        $template->delete();

        return redirect()->route('admin.plate-templates.index')->with('status', 'تم حذف القالب "'.$name.'".');
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

    /** @param  array<string, string>  $fonts  @return array<int, string> أسماء الخطوط بدون الامتداد */
    private function fontNames(array $fonts): array
    {
        return array_map(fn (string $file) => pathinfo($file, PATHINFO_FILENAME), array_keys($fonts));
    }

    private function fontsDir(string $code): string
    {
        return 'plate-templates/'.$code.'/fonts';
    }

    /** @param  array<string, string>  $fonts */
    private function storeFonts($disk, string $code, array $fonts): void
    {
        foreach ($fonts as $name => $contents) {
            $disk->put($this->fontsDir($code).'/'.$name, $contents);
        }
    }
}
