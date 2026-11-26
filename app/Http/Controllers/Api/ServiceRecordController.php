<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\OdometerRegressionException;
use App\Http\Controllers\Controller;
use App\Models\ServiceRecord;
use App\Models\ServiceRecordAttachment;
use App\Models\Vehicle;
use App\Services\OdometerGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ServiceRecordController extends Controller
{
    public function store(Request $request, Vehicle $vehicle, OdometerGuard $guard): JsonResponse
    {
        $this->authorize('update', $vehicle);

        $data = $request->validate([
            'service_type_id' => ['required', 'exists:service_types,id'],
            'service_date' => ['required', 'date'],
            'odometer' => ['required', 'integer', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'workshop_name' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'confirm_reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $guard->check($vehicle, $data['odometer'], $data['confirm_reason'] ?? null);
        } catch (OdometerRegressionException $e) {
            throw ValidationException::withMessages(['odometer' => [$e->getMessage()]]);
        }

        $record = ServiceRecord::create(collect($data)->except('confirm_reason')->all() + [
            'vehicle_id' => $vehicle->id,
            'created_by_user_id' => $request->user()->id,
        ]);

        if ($data['odometer'] > $vehicle->current_odometer) {
            $vehicle->update(['current_odometer' => $data['odometer']]);
        }

        return response()->json($record->fresh(), 201);
    }

    public function index(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorize('view', $vehicle);

        return response()->json($vehicle->serviceRecords()->orderByDesc('service_date')->get());
    }

    public function show(Request $request, ServiceRecord $record): JsonResponse
    {
        $this->authorize('view', $record->vehicle);

        return response()->json($record);
    }

    public function storeAttachment(Request $request, ServiceRecord $record): JsonResponse
    {
        $this->authorize('update', $record->vehicle);

        $request->validate(['file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192']]);

        $file = $request->file('file');
        $key = Storage::disk(config('filesystems.attachments_disk'))->putFile('attachments/'.$record->id, $file);

        $attachment = ServiceRecordAttachment::create([
            'service_record_id' => $record->id,
            'storage_key' => $key,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        return response()->json($attachment, 201);
    }
}
