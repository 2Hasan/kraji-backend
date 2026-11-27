<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChangeRequest;
use App\Models\ServiceRecord;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleChangeRequestController extends Controller
{
    /**
     * طلبات التعديل والحذف للسيارة اللي قدّمها المستخدم، عشان السجل يعرض الطلب المعلّق.
     */
    public function index(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorize('view', $vehicle);

        $recordIds = ServiceRecord::where('vehicle_id', $vehicle->id)->pluck('id');

        $requests = ChangeRequest::whereIn('service_record_id', $recordIds)
            ->where('requested_by_user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get(['id', 'service_record_id', 'type', 'status', 'reason', 'review_note', 'created_at']);

        return response()->json($requests);
    }
}
