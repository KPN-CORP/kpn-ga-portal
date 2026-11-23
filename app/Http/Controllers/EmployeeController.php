<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the employees.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        
        $query = DB::table('tb_pelanggan')
            // HAPUS BARIS INI: ->where('role_akses', 'Pelanggan')
            ->orderBy('id_pelanggan', 'desc');
        
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('username_pelanggan', 'like', "%{$search}%")
                  ->orWhere('email_pelanggan', 'like', "%{$search}%")
                  ->orWhere('no_hp_pelanggan', 'like', "%{$search}%")
                  ->orWhere('bisnis_unit', 'like', "%{$search}%")
                  ->orWhere('departemen', 'like', "%{$search}%");
            });
        }
        
        // Tambah perPage menjadi 20 atau lebih
        $employees = $query->paginate(20);

        return view('employees.index', compact('employees', 'search'));
    }
    
    /**
     * Display the specified employee.
     */
    public function show($id)
    {
        $employee = DB::table('tb_pelanggan')
            ->where('id_pelanggan', $id)
            ->first();
            
        if (!$employee) {
            abort(404, 'Employee not found');
        }

        return view('employees.show', compact('employee'));
    }
}