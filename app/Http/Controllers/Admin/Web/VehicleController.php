<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
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

        return view('admin.vehicles.index', compact('vehicles', 'q'));
    }
}
