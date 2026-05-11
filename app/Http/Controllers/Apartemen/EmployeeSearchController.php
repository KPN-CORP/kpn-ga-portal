<?php

namespace App\Http\Controllers\Apartemen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ApiEmpHcis;

class EmployeeSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = trim($request->get('q'));

        if (strlen($query) < 3) {
            return response()->json([]);
        }

        $employees = ApiEmpHcis::where('fullname', 'like', "%{$query}%")
            ->select('employee_id', 'fullname', 'group_company')
            ->limit(10)
            ->get();

        return response()->json($employees);
    }
}