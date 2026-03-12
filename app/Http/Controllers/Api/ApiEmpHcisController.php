<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiEmpHcis;
use Illuminate\Http\Request;

class ApiEmpHcisController extends Controller
{
    /**
     * Tampilkan daftar karyawan dari database lokal.
     */
    public function index(Request $request)
    {
        $query = ApiEmpHcis::query();

        // Filter opsional
        if ($request->has('employee_id')) {
            $query->where('employee_id', 'like', '%' . $request->employee_id . '%');
        }

        if ($request->has('fullname')) {
            $query->where('fullname', 'like', '%' . $request->fullname . '%');
        }

        if ($request->has('office_area')) {
            $query->where('office_area', 'like', '%' . $request->office_area . '%');
        }

        $employees = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $employees->items(),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
            ]
        ]);
    }

    /**
     * Endpoint untuk memicu sinkronisasi secara manual (via HTTP).
     * Harus dilindungi dengan middleware otorisasi.
     */
    public function sync()
    {
        // Contoh: hanya user dengan role tertentu (sesuaikan)
        // Misal dengan middleware 'can:sync-hcis' di route

        try {
            \Artisan::call('hcis:sync-employees');
            $output = \Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Sinkronisasi dijalankan',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}