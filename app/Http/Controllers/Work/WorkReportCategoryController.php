<?php

namespace App\Http\Controllers\Work;

use App\Http\Controllers\Controller;
use App\Models\Work\WorkReportCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkReportCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->isWorkAdmin()) {
                abort(403, 'Hanya admin yang dapat mengelola kategori.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $categories = WorkReportCategory::orderBy('name')->get();
        return view('work-reports.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('work-reports.categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:work_report_categories,name',
            'description' => 'nullable'
        ]);

        WorkReportCategory::create($request->only('name', 'description'));

        return redirect()->route('work-reports.categories.index')
                         ->with('success', 'Kategori ditambahkan.');
    }

    public function edit(WorkReportCategory $workReportCategory)
    {
        return view('work-reports.categories.edit', compact('workReportCategory'));
    }

    public function update(Request $request, WorkReportCategory $workReportCategory)
    {
        $request->validate([
            'name' => 'required|unique:work_report_categories,name,' . $workReportCategory->id,
            'description' => 'nullable'
        ]);

        $workReportCategory->update($request->only('name', 'description'));

        return redirect()->route('work-reports.categories.index')
                         ->with('success', 'Kategori diperbarui.');
    }

    public function destroy(WorkReportCategory $workReportCategory)
    {
        if ($workReportCategory->reports()->count() > 0) {
            return back()->with('error', 'Kategori tidak bisa dihapus karena masih memiliki laporan.');
        }

        $workReportCategory->delete();

        return redirect()->route('work-reports.categories.index')
                         ->with('success', 'Kategori dihapus.');
    }
}