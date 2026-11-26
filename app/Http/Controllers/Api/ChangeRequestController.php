<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\ChangeRequest;
use App\Models\ServiceRecord;
use App\Services\ChangeRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChangeRequestController extends Controller
{
    public function store(Request $request, ServiceRecord $record, ChangeRequestService $service): JsonResponse
    {
        $this->authorize('update', $record->vehicle);

        $data = $request->validate([
            'type' => ['required', 'in:edit,delete'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'payload' => ['required_if:type,edit', 'array'],
            'payload.odometer' => ['sometimes', 'integer', 'min:0'],
            'payload.cost' => ['sometimes', 'numeric', 'min:0'],
            'payload.service_date' => ['sometimes', 'date'],
            'payload.workshop_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'payload.notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        $cr = $service->submit($record, $request->user(), $data['type'], $data['payload'] ?? null, $data['reason']);

        return response()->json($cr, 201);
    }

    public function index(): JsonResponse
    {
        return response()->json(ChangeRequest::where('status', 'pending')->orderBy('created_at')->get());
    }

    public function approve(Request $request, ChangeRequest $changeRequest, ChangeRequestService $service): JsonResponse
    {
        abort_unless($changeRequest->status === 'pending', 422);

        $service->approve($changeRequest, $request->user(), $request->input('note'));
        app(\App\Services\AuditLogger::class)->record($request->user(), 'change_request.approved', ChangeRequest::class, $changeRequest->id, $request->input('note'));

        return response()->json($changeRequest->fresh());
    }

    public function reject(Request $request, ChangeRequest $changeRequest, ChangeRequestService $service): JsonResponse
    {
        abort_unless($changeRequest->status === 'pending', 422);

        $data = $request->validate(['note' => ['required', 'string', 'min:3', 'max:1000']]);
        $service->reject($changeRequest, $request->user(), $data['note']);
        app(\App\Services\AuditLogger::class)->record($request->user(), 'change_request.rejected', ChangeRequest::class, $changeRequest->id, $data['note']);

        return response()->json($changeRequest->fresh());
    }
}
