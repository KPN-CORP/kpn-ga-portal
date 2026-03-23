<?php

namespace App\Http\Controllers\Work;

use App\Http\Controllers\Controller;
use App\Models\Work\WorkReport;
use App\Models\Work\WorkReportCategory;
use App\Models\Work\WorkReportLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;

class WorkReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->isWorkUser() && !Auth::user()->isWorkAdmin()) {
                abort(403, 'Anda tidak memiliki akses ke laporan pekerjaan.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $startDate = \Carbon\Carbon::parse($month)->startOfMonth();
        $endDate = \Carbon\Carbon::parse($month)->endOfMonth();

        $reports = WorkReport::with(['category', 'creator'])
            ->whereBetween('report_date', [$startDate, $endDate])
            ->orderBy('report_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();

        $months = collect();
        for ($i = 0; $i < 12; $i++) {
            $date = now()->subMonths($i);
            $months->put($date->format('Y-m'), $date->isoFormat('MMMM Y'));
        }

        return view('work-reports.index', compact('reports', 'month', 'months'));
    }

    public function create()
    {
        $categories = WorkReportCategory::orderBy('name')->get();
        return view('work-reports.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id'   => 'required|exists:work_report_categories,id',
            'photo_before'  => 'nullable|image|max:50000',
            'photo_after'   => 'nullable|image|max:50000',
            'floor'         => 'required|string|max:50',
            'location'      => 'required|string|max:100',
            'report_date'   => 'required|date',
            'start_time'    => 'required',
            'end_time'      => 'required|after:start_time',
            'description'   => 'required|string',
        ]);

        $data = $validated;
        $data['created_by'] = Auth::id();

        $data['photo_before'] = $this->compressAndStoreImage($request->file('photo_before'));
        $data['photo_after']  = $this->compressAndStoreImage($request->file('photo_after'));

        $report = WorkReport::create($data);

        WorkReportLog::create([
            'work_report_id' => $report->id,
            'user_id'        => Auth::id(),
            'action'         => 'created',
            'new_data'       => $report->toArray(),
        ]);

        return redirect()->route('work-reports.index')
                         ->with('success', 'Laporan berhasil ditambahkan.');
    }

    public function edit(WorkReport $workReport)
    {
        if (!$workReport->canBeModifiedBy(Auth::user())) {
            abort(403, 'Laporan tidak dapat diedit karena sudah melewati 12 jam atau Anda bukan pembuat.');
        }

        $categories = WorkReportCategory::orderBy('name')->get();
        return view('work-reports.edit', compact('workReport', 'categories'));
    }

    public function update(Request $request, WorkReport $workReport)
    {
        if (!$workReport->canBeModifiedBy(Auth::user())) {
            abort(403, 'Laporan tidak dapat diubah karena sudah melewati 12 jam atau Anda bukan pembuat.');
        }

        $validated = $request->validate([
            'category_id'   => 'required|exists:work_report_categories,id',
            'photo_before'  => 'nullable|image|max:50000',
            'photo_after'   => 'nullable|image|max:50000',
            'floor'         => 'required|string|max:50',
            'location'      => 'required|string|max:100',
            'report_date'   => 'required|date',
            'start_time'    => 'required',
            'end_time'      => 'required|after:start_time',
            'description'   => 'required|string',
        ]);

        $oldData = $workReport->toArray();
        $data    = $validated;

        if ($request->hasFile('photo_before')) {
            if ($workReport->photo_before && Storage::disk('private')->exists($workReport->photo_before)) {
                Storage::disk('private')->delete($workReport->photo_before);
            }
            $data['photo_before'] = $this->compressAndStoreImage($request->file('photo_before'));
        } else {
            $data['photo_before'] = $workReport->photo_before;
        }

        if ($request->hasFile('photo_after')) {
            if ($workReport->photo_after && Storage::disk('private')->exists($workReport->photo_after)) {
                Storage::disk('private')->delete($workReport->photo_after);
            }
            $data['photo_after'] = $this->compressAndStoreImage($request->file('photo_after'));
        } else {
            $data['photo_after'] = $workReport->photo_after;
        }

        $workReport->update($data);

        WorkReportLog::create([
            'work_report_id' => $workReport->id,
            'user_id'        => Auth::id(),
            'action'         => 'updated',
            'old_data'       => $oldData,
            'new_data'       => $workReport->fresh()->toArray(),
        ]);

        return redirect()->route('work-reports.index')
                         ->with('success', 'Laporan berhasil diperbarui.');
    }

public function destroy(WorkReport $workReport)
{
    if (!$workReport->canBeModifiedBy(Auth::user())) {
        abort(403, 'Laporan tidak dapat dihapus karena sudah melewati 12 jam atau Anda bukan pembuat.');
    }

    $oldData = $workReport->toArray();

    // Hapus file foto
    if ($workReport->photo_before && Storage::disk('private')->exists($workReport->photo_before)) {
        Storage::disk('private')->delete($workReport->photo_before);
    }
    if ($workReport->photo_after && Storage::disk('private')->exists($workReport->photo_after)) {
        Storage::disk('private')->delete($workReport->photo_after);
    }

    DB::transaction(function () use ($workReport, $oldData) {
        // 1. Catat log terlebih dahulu
        WorkReportLog::create([
            'work_report_id' => $workReport->id,
            'user_id'        => Auth::id(),
            'action'         => 'deleted',
            'old_data'       => $oldData,
        ]);
        // 2. Hapus laporan
        $workReport->delete();
    });

    return redirect()->route('work-reports.index')
                     ->with('success', 'Laporan dihapus.');
}

private function compressAndStoreImage($file)
{
    if (!$file) {
        return null;
    }

    try {
        $imgInfo = getimagesize($file);
        if (!$imgInfo) {
            return null;
        }

        switch ($imgInfo[2]) {
            case IMAGETYPE_JPEG:
                $src = imagecreatefromjpeg($file);
                break;
            case IMAGETYPE_PNG:
                $src = imagecreatefrompng($file);
                break;
            case IMAGETYPE_GIF:
                $src = imagecreatefromgif($file);
                break;
            default:
                return null;
        }

        $width = imagesx($src);
        $height = imagesy($src);
        $maxWidth = 1200;

        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = ($maxWidth / $width) * $height;
            $dst = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($src);
            $src = $dst;
        }

        $filename = 'work_report/' . uniqid() . '.jpg';
        $fullPath = storage_path('app/private/' . $filename);
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        imagejpeg($src, $fullPath, 75);
        imagedestroy($src);

        return $filename;
    } catch (\Exception $e) {
        return null;
    }
}
}