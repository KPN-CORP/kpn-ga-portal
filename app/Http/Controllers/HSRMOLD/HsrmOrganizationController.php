<?php

namespace App\Http\Controllers\HSRM;

use App\Http\Controllers\Controller;
use App\Models\BisnisUnit;
use App\Models\AreaKerja;

class HsrmOrganizationController extends Controller
{
    public function index()
    {
        $businessUnits = BisnisUnit::with(['areas' => function($q) {
            $q->withCount('pics');
        }])->get();

        return view('hsrm.organization.index', compact('businessUnits'));
    }
}