<?php

namespace App\Http\Controllers\FinGrabc;

use App\Http\Controllers\Controller;
use App\Services\FinGrab\BcrImporter;
use App\Support\FinGrabAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Halaman terpisah untuk upload zip/xlsx BCR bulanan. Hanya superadmin dan Corporate. */
class FinGrabUploadController extends Controller
{
    private const MAX_ENTRIES = 50;            // maksimal file bcr_*.xlsx per zip
    private const MAX_ENTRY_BYTES = 52428800;  // 50 MB per file di dalam zip

    public function form()
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        return view('fingrab.upload', [
            'batches' => DB::table('fingrab_batches')->orderByDesc('id')->limit(10)->get(),
        ]);
    }

    public function store(Request $request)
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'mimes:zip,xlsx,xls', 'max:51200'],
        ]);

        $paths = [];
        $names = [];
        $temps = [];

        foreach ($request->file('files') as $f) {
            if (strtolower($f->getClientOriginalExtension()) !== 'zip') {
                $paths[] = $f->getRealPath();
                $names[] = $f->getClientOriginalName();
                continue;
            }

            $zip = new \ZipArchive();
            if ($zip->open($f->getRealPath()) !== true) {
                return back()->withErrors(['files' => 'Zip tidak bisa dibuka: ' . $f->getClientOriginalName()]);
            }
            $n = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $base = basename($zip->getNameIndex($i)); // basename: nama di dalam zip tidak dipakai sebagai path
                $stat = $zip->statIndex($i);
                if (!preg_match('/^bcr_.*\.xlsx$/i', $base) || $stat['size'] > self::MAX_ENTRY_BYTES || ++$n > self::MAX_ENTRIES) {
                    continue; // invoice PDF dan file lain diabaikan
                }
                $tmp = tempnam(sys_get_temp_dir(), 'fg_');
                file_put_contents($tmp, $zip->getFromIndex($i));
                $paths[] = $tmp;
                $names[] = $base;
                $temps[] = $tmp;
            }
            $zip->close();
        }

        if (!$paths) {
            return back()->withErrors(['files' => 'Tidak ada file bcr_*.xlsx yang ditemukan.']);
        }

        try {
            $summary = (new BcrImporter())->import($paths, $names, auth()->id());
        } finally {
            foreach ($temps as $t) {
                @unlink($t);
            }
        }

        return redirect()->route('fingrab.upload')->with('fingrab_summary', $summary);
    }

    private function deny()
    {
        if (!FinGrabAccess::scope(auth()->user())['all']) {
            return FinGrabAccess::deny('Hanya Corporate yang boleh mengunggah BCR.');
        }
        return null;
    }
}
