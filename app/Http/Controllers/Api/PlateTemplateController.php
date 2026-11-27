<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlateTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class PlateTemplateController extends Controller
{
    /** القوالب المفعّلة، وتقدر تُفلتر بنوع الهيكل. */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['body_type' => ['nullable', 'string', 'max:40']]);

        $query = PlateTemplate::where('status', 'active');
        if (! empty($data['body_type'])) {
            $query->where('body_type', $data['body_type']);
        }

        return response()->json($query->orderBy('body_type')->get(['id', 'code', 'name', 'body_type']));
    }

    /** ملف SVG للقالب المفعّل فقط، والتطبيق يستبدل {{plate_number}} بالرقم. */
    public function svg(string $code): Response
    {
        $template = PlateTemplate::where('code', $code)->where('status', 'active')->firstOrFail();

        $svg = Storage::disk(config('filesystems.attachments_disk'))->get($template->svg_storage_key);

        return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }
}
