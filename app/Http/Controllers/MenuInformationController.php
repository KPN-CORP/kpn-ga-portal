<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class MenuInformationController extends Controller
{
    public function index()
    {
        $menus = DB::table('tb_menu_information')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get();

        return view('menu-information.index', compact('menus'));
    }
}
