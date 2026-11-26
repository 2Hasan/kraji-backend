<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlateTemplate;
use App\Services\PlateTemplateValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PlateTemplateController extends Controller
{
    public function store(Request $request, PlateTemplateValidator $validator): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'alpha_dash', 'max:60', 'unique:plate_templates,code'],
            'name' => ['required', 'string', 'max:120'],
            'body_type' => ['required', 'string', 'max:40'],
            'svg' => ['required', 'file', 'mimetypes:image/svg+xml,text/plain,text/xml', 'max:512'],
        ]);

        $svg = file_get_contents($request->file('svg')->getRealPath());
        $key = Storage::disk(config('filesystems.attachments_disk'))->putFileAs('plate-templates', $request->file('svg'), $data['code'].'.svg');

        $template = PlateTemplate::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'body_type' => $data['body_type'],
            'svg_storage_key' => $key,
            'status' => 'draft',
            'validation_errors' => $validator->validate($svg),
        ]);

        return response()->json($template, 201);
    }

    public function activate(PlateTemplate $template): JsonResponse
    {
        if (! empty($template->validation_errors)) {
            return response()->json(['errors' => $template->validation_errors], 422);
        }

        $template->update(['status' => 'active']);

        return response()->json($template->fresh());
    }
}
