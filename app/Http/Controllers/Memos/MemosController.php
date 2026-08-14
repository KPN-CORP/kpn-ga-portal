<?php

namespace App\Http\Controllers\Memos;

use App\Http\Controllers\Controller;
use App\Models\Memos\Memos;
use App\Models\Memos\MemosItems;
use App\Models\Memos\MemosAttachments;
use App\Models\Memos\MemoNumberSetting;
use App\Models\ApiEmpHcis;
use App\Support\Memos\MemoImportProfiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MemosController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Memos::class, 'memo');
    }

    /**
     * Daftar memo, dengan filter opsional:
     * - bulan     : 'YYYY-MM' -> tampilkan memo di bulan tsb (berdasarkan created_at)
     * - dari/sampai : 'YYYY-MM-DD' -> rentang tanggal (dipakai juga oleh tombol Download)
     */
    public function index(Request $request)
    {
        $query = Memos::viewable(auth()->user())->with(['creator', 'attachments']);
        $this->applyDateFilter($query, $request);

        $memos = $query->latest()->paginate(15)->withQueryString();

        return view('Memos.Memos.index', compact('memos'));
    }

    public function create()
    {
        $employees = ApiEmpHcis::limit(100)->get(['employee_id', 'fullname', 'group_company']);
        $signer = MemoNumberSetting::resolveSigner(auth()->user());
        return view('Memos.Memos.create', compact('employees', 'signer'));
    }

    /**
     * Form edit, hanya untuk memo berstatus draft milik user yang bersangkutan
     * (otorisasi 'update' sudah dicek lewat authorizeResource di constructor).
     */
    public function edit(Memos $memo)
    {
        if ($memo->status !== 'draft') {
            return redirect()->route('memos.show', $memo)->with('error', 'Hanya draft yang bisa diedit');
        }

        $memo->load('items', 'attachments');
        $employees = ApiEmpHcis::limit(100)->get(['employee_id', 'fullname', 'group_company']);
        $signer = MemoNumberSetting::resolveSigner(auth()->user());
        return view('Memos.Memos.create', compact('memo', 'employees', 'signer'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'perihal' => 'required|string',
            'kepada'  => 'required|string',
            'dari'    => 'required|string',
            'items'   => 'required',
            'status'  => 'in:draft,submitted'
        ]);

        $items = $this->decodeItems($request);
        if ($items === null) {
            return response()->json(['success' => false, 'message' => 'Items tidak valid'], 422);
        }
        $itemsError = $this->validateItems($items);
        if ($itemsError) {
            return response()->json(['success' => false, 'message' => $itemsError], 422);
        }

        $dynamicColumns = $this->decodeDynamicColumns($request);

        DB::beginTransaction();
        try {
            $total = collect($items)->sum('tagihan');
            $businessUnit = auth()->user()->getBusinessUnitAttribute();

            // Penandatangan & Jabatan diambil otomatis dari data admin tim, bukan input manual.
            $signer = MemoNumberSetting::resolveSigner(auth()->user());

            $memo = Memos::create([
                'perihal'       => $request->perihal,
                'kepada'        => $request->kepada,
                'dari'          => $request->dari,
                'instruksi'     => $request->instruksi,
                'bank'          => $request->bank,
                'atas_nama'     => $request->atas_nama,
                'no_rek'        => $request->no_rek,
                'sertakan_rekening' => $request->boolean('sertakan_rekening', true),
                'paragraf_pembuka' => $request->paragraf_pembuka,
                'penandatangan' => $signer['penandatangan'],
                'jabatan'       => $signer['jabatan'],
                'total_amount'  => $total,
                'status'        => $request->status,
                'business_unit' => $businessUnit,
                'dynamic_columns_definition' => $dynamicColumns,
                'keterangan_label' => $request->keteranganLabel,
                'created_by'    => auth()->id(),
                'expires_at'    => $request->status === 'draft' ? now()->addHours(24) : null
            ]);

            $this->syncItems($memo, $items);
            $this->storeAttachments($request, $memo);

            DB::commit();
            return response()->json(['success' => true, 'memo_id' => $memo->id, 'message' => 'Memo tersimpan']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update draft yang sudah ada. Kalau statusnya diubah jadi 'submitted' di sini,
     * nomor memo otomatis digenerate (lihat event 'updating' di model Memos).
     */
    public function update(Request $request, Memos $memo)
    {
        if ($memo->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Hanya draft yang bisa diedit'], 422);
        }

        $request->validate([
            'perihal' => 'required|string',
            'kepada'  => 'required|string',
            'dari'    => 'required|string',
            'items'   => 'required',
            'status'  => 'in:draft,submitted'
        ]);

        $items = $this->decodeItems($request);
        if ($items === null) {
            return response()->json(['success' => false, 'message' => 'Items tidak valid'], 422);
        }
        $itemsError = $this->validateItems($items);
        if ($itemsError) {
            return response()->json(['success' => false, 'message' => $itemsError], 422);
        }

        $dynamicColumns = $this->decodeDynamicColumns($request);

        DB::beginTransaction();
        try {
            $total = collect($items)->sum('tagihan');
            $signer = MemoNumberSetting::resolveSigner(auth()->user());

            $memo->update([
                'perihal'       => $request->perihal,
                'kepada'        => $request->kepada,
                'dari'          => $request->dari,
                'instruksi'     => $request->instruksi,
                'bank'          => $request->bank,
                'atas_nama'     => $request->atas_nama,
                'no_rek'        => $request->no_rek,
                'sertakan_rekening' => $request->boolean('sertakan_rekening', true),
                'paragraf_pembuka' => $request->paragraf_pembuka,
                'penandatangan' => $signer['penandatangan'],
                'jabatan'       => $signer['jabatan'],
                'total_amount'  => $total,
                'status'        => $request->status,
                'dynamic_columns_definition' => $dynamicColumns,
                'keterangan_label' => $request->keteranganLabel,
                'expires_at'    => $request->status === 'draft' ? now()->addHours(24) : null
            ]);

            $memo->items()->delete();
            $this->syncItems($memo, $items);
            $this->storeAttachments($request, $memo);

            DB::commit();
            return response()->json(['success' => true, 'memo_id' => $memo->id, 'message' => 'Memo diperbarui']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show(Memos $memo)
    {
        $memo->load('items', 'attachments', 'creator');
        return view('Memos.Memos.show', compact('memo'));
    }

    public function downloadPdf(Memos $memo)
    {
        $memo->load('items');

        $pdf = Pdf::loadView('Memos.Memos.pdf', compact('memo'))->setPaper('a4');

        $filename = 'Memo-' . ($memo->memo_number ?? 'draft') . '.pdf';

        // Tidak pakai $pdf->download() karena Laravel/Symfony menolak "/" di nama file.
        // Response dibuat manual supaya "/" tetap bisa dipakai apa adanya.
        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function updateChecklist(Request $request, MemosAttachments $attachment)
    {
        $this->authorize('update', $attachment->memo);
        $attachment->update(['is_checked' => $request->has('is_checked')]);
        return back()->with('success', 'Checklist diperbarui');
    }

    public function deleteAttachment(MemosAttachments $attachment)
    {
        $this->authorize('update', $attachment->memo);
        if ($attachment->memo->status !== 'draft') {
            return back()->with('error', 'Lampiran hanya bisa dihapus selagi masih draft');
        }
        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();
        return back()->with('success', 'Lampiran dihapus');
    }

    public function destroy(Memos $memo)
    {
        if ($memo->status === 'draft') {
            foreach ($memo->attachments as $att) {
                Storage::disk('public')->delete($att->file_path);
            }
            $memo->delete();
            return redirect()->route('memos.index')->with('success', 'Draft dihapus');
        }
        return back()->with('error', 'Hanya draft yang bisa dihapus');
    }

    /**
     * API endpoint untuk mendapatkan terbilang dari jumlah angka
     */
    public function terbilang($amount)
    {
        return response()->json([
            'terbilang' => terbilang($amount)
        ]);
    }

    // ========== IMPORT EXCEL (pengganti mail merge) ==========

    /**
     * Form upload file mailing (xlsx). Tidak dicek lewat authorizeResource
     * karena ini bukan route resource memos/{memo}, jadi otorisasinya cukup
     * middleware 'auth' + 'can:create,Memos' (dicek manual di sini).
     */
    public function importForm()
    {
        $this->authorize('create', Memos::class);
        return view('Memos.Memos.import');
    }

    /**
     * Baca file Excel mailing (1 sheet = 1 jenis surat, 1 baris = 1 memo),
     * lalu buat semua memo sekaligus berstatus DRAFT supaya bisa dicek/diedit
     * dulu sebelum di-submit satu-satu (nomor memo baru digenerate saat submit).
     */
    public function import(Request $request)
    {
        $this->authorize('create', Memos::class);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $path = $request->file('file')->getRealPath();
        $spreadsheet = IOFactory::load($path);

        $created = 0;
        $skipped = [];
        $needsReviewCount = 0;
        $createdMemoIds = [];

        DB::beginTransaction();
        try {
            foreach ($spreadsheet->getSheetNames() as $sheetName) {
                if (MemoImportProfiles::shouldSkipSheet($sheetName)) {
                    continue;
                }

                $sheet = $spreadsheet->getSheetByName($sheetName);
                // formatData=false: ambil nilai numerik APA ADANYA, jangan dibungkus
                // format tampilan Excel-nya (kalau formatData=true, sel yang di Excel
                // sononya diformat "#,##0.00" ikut kebawa jadi string "668,000.00" -
                // itu penyebab nominal jadi salah waktu diimport).
                $rows = $sheet->toArray(null, true, false, true);
                if (count($rows) < 2) {
                    continue; // cuma header / kosong
                }

                $headerRow = array_shift($rows);
                $columns = $this->mapHeaderColumns($headerRow);
                $profile = MemoImportProfiles::forSheet($sheetName);

                foreach ($rows as $rowIndex => $row) {
                    if ($this->isRowEmpty($row)) {
                        continue;
                    }

                    $nama = $this->cell($row, $columns, 'Nama Karyawan');
                    $tagihanRaw = $this->cell($row, $columns, 'Tagihan');

                    if (empty($nama) || !is_numeric($this->toNumber($tagihanRaw))) {
                        $skipped[] = "Sheet \"{$sheetName}\" baris {$rowIndex}: nama/tagihan tidak lengkap, dilewati";
                        continue;
                    }

                    $tagihan = $this->toNumber($tagihanRaw);
                    $dynamicValues = array_map(
                        fn ($colLabel) => $this->formatDynamicValue($colLabel, $this->cell($row, $columns, $colLabel)),
                        $profile['dynamic_columns']
                    );

                    $nomorSuratAsal = $this->cell($row, $columns, 'Nomor Surat');
                    $tanggalAsal = $this->cell($row, $columns, 'Tanggal');
                    $catatanAsal = trim(collect([
                        $nomorSuratAsal ? "Ref. nomor surat asal: {$nomorSuratAsal}" : null,
                        $tanggalAsal ? "Tanggal asal: {$tanggalAsal}" : null,
                    ])->filter()->implode(' | '));

                    $bank = null;
                    $atasNama = null;
                    $noRek = null;
                    $sertakanRekening = false;

                    if ($profile['bank_source'] === 'fixed') {
                        $bank = $profile['bank'];
                        $atasNama = $profile['atas_nama'];
                        $noRek = $profile['no_rek'];
                        $sertakanRekening = true;
                    } elseif ($profile['bank_source'] === 'row') {
                        $bank = $this->cell($row, $columns, 'Nama Account');
                        $atasNama = $this->cell($row, $columns, 'Nama Rekening');
                        $noRek = $this->cell($row, $columns, 'No. Rekening');
                        $sertakanRekening = !empty($bank);
                    }

                    $needsReview = $profile['needs_review'] || empty($bank);
                    if ($needsReview) {
                        $needsReviewCount++;
                    }

                    $signer = MemoNumberSetting::resolveSigner(auth()->user());

                    $instruksi = trim(collect([
                        $profile['instruksi'],
                        $needsReview ? '[PERLU DICEK] Rekening tujuan pembayaran belum bisa dipastikan otomatis dari sheet "' . $sheetName . '", mohon lengkapi sebelum submit.' : null,
                        $catatanAsal,
                    ])->filter()->implode("\n"));

                    $memo = Memos::create([
                        'perihal'                    => $profile['perihal'] . ' an. ' . $nama,
                        'kepada'                      => $profile['kepada'],
                        'dari'                        => $profile['dari'],
                        'instruksi'                   => $instruksi,
                        'bank'                        => $bank,
                        'atas_nama'                   => $atasNama,
                        'no_rek'                      => $noRek,
                        'sertakan_rekening'           => $sertakanRekening,
                        'paragraf_pembuka'            => $profile['paragraf_pembuka'],
                        'penandatangan'               => $signer['penandatangan'],
                        'jabatan'                     => $signer['jabatan'],
                        'total_amount'                => $tagihan,
                        'status'                      => 'draft',
                        'business_unit'               => auth()->user()->getBusinessUnitAttribute(),
                        'dynamic_columns_definition'  => $profile['dynamic_columns'],
                        'keterangan_label'            => $profile['keterangan_label'],
                        'created_by'                  => auth()->id(),
                        // Draft hasil import dikasih waktu lebih panjang dari draft manual (24 jam)
                        // karena biasanya diimport dalam jumlah banyak sekaligus untuk dicek bertahap.
                        'expires_at'                  => now()->addDays(7),
                    ]);

                    MemosItems::create([
                        'memo_id'         => $memo->id,
                        'nama'            => $nama,
                        'dynamic_columns' => $dynamicValues,
                        'tagihan'         => $tagihan,
                        'sort_order'      => 0,
                    ]);

                    $created++;
                    $createdMemoIds[] = $memo->id;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Import gagal: ' . $e->getMessage());
        }

        if ($created === 0) {
            return back()->with('error', 'Tidak ada baris valid yang bisa diimport dari file ini. ' . implode('; ', array_slice($skipped, 0, 5)));
        }

        $message = "{$created} memo draft berhasil dibuat dari file.";
        if ($needsReviewCount > 0) {
            $message .= " {$needsReviewCount} di antaranya perlu dicek manual (rekening tujuan belum lengkap) sebelum di-submit.";
        }
        if (count($skipped)) {
            $message .= ' ' . count($skipped) . ' baris dilewati karena data tidak lengkap.';
        }

        return redirect()->route('memos.index')->with('success', $message);
    }

    // ========== FILTER PER BULAN & DOWNLOAD RENTANG TANGGAL ==========

    /**
     * Download rekap memo (Excel) sesuai filter bulan / rentang tanggal yang
     * sedang aktif di halaman daftar memo.
     */
    public function export(Request $request)
    {
        $query = Memos::viewable(auth()->user())->with(['creator', 'items']);
        $this->applyDateFilter($query, $request);
        $memos = $query->latest()->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Memo');

        $headers = ['No', 'Tanggal Dibuat', 'No Memo', 'Status', 'Perihal', 'Kepada', 'Dari', 'Pembuat', 'Total Tagihan'];
        $sheet->fromArray($headers, null, 'A1');

        $rowNum = 2;
        foreach ($memos as $i => $memo) {
            $sheet->fromArray([
                $i + 1,
                optional($memo->created_at)->format('d-m-Y'),
                $memo->memo_number ?? '-',
                $memo->status === 'draft' ? 'Draf' : 'Tersimpan',
                $memo->perihal,
                $memo->kepada,
                $memo->dari,
                optional($memo->creator)->name,
                (float) $memo->total_amount,
            ], null, 'A' . $rowNum);
            $rowNum++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Rekap-Memo-' . now()->format('Ymd-His') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Terapkan filter ke query memo:
     * - 'cari' -> pencarian bebas: no memo, perihal, kepada, dari, atau nama pembuat.
     * - 'dari' & 'sampai' (format YYYY-MM-DD) -> rentang tanggal dibuat, bisa dipakai
     *   bareng 'cari' sekaligus.
     */
    private function applyDateFilter($query, Request $request): void
    {
        if ($request->filled('cari')) {
            $keyword = trim($request->input('cari'));
            $query->where(function ($q) use ($keyword) {
                $q->where('memo_number', 'like', "%{$keyword}%")
                    ->orWhere('perihal', 'like', "%{$keyword}%")
                    ->orWhere('kepada', 'like', "%{$keyword}%")
                    ->orWhere('dari', 'like', "%{$keyword}%")
                    ->orWhereHas('creator', function ($cq) use ($keyword) {
                        $cq->where('name', 'like', "%{$keyword}%");
                    });
            });
        }

        if ($request->filled('dari')) {
            $query->whereDate('created_at', '>=', $request->input('dari'));
        }
        if ($request->filled('sampai')) {
            $query->whereDate('created_at', '<=', $request->input('sampai'));
        }
    }

    // ========== HELPERS ==========

    private function decodeItems(Request $request): ?array
    {
        $items = $request->items;
        if (is_string($items)) {
            $items = json_decode($items, true);
        }
        if (!is_array($items) || count($items) === 0) {
            return null;
        }
        return $items;
    }

    private function decodeDynamicColumns(Request $request): array
    {
        $dynamicColumns = $request->dynamicColumns;
        if (is_string($dynamicColumns)) {
            $dynamicColumns = json_decode($dynamicColumns, true);
        }
        return is_array($dynamicColumns) ? $dynamicColumns : [];
    }

    private function validateItems(array $items): ?string
    {
        foreach ($items as $index => $item) {
            if (empty($item['keterangan']) || !isset($item['tagihan']) || !is_numeric($item['tagihan'])) {
                return "Item ke-" . ($index + 1) . " tidak lengkap";
            }
        }
        return null;
    }

    private function syncItems(Memos $memo, array $items): void
    {
        foreach ($items as $index => $item) {
            MemosItems::create([
                'memo_id'         => $memo->id,
                'nama'            => $item['keterangan'],
                'dynamic_columns' => $item['dynamic_columns'] ?? [],
                'tagihan'         => $item['tagihan'],
                'sort_order'      => $index
            ]);
        }
    }

    private function storeAttachments(Request $request, Memos $memo): void
    {
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('memos/' . $memo->id, 'public');
                MemosAttachments::create([
                    'memo_id'       => $memo->id,
                    'file_path'     => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type'     => $file->getMimeType(),
                    'is_checked'    => false
                ]);
            }
        }
    }

    /**
     * Cocokkan header kolom Excel (misal "Nama Karyawan", "No. Rekening") ke huruf
     * kolom-nya (A, B, C, ...) supaya pembacaan baris tidak bergantung posisi tetap.
     */
    private function mapHeaderColumns(array $headerRow): array
    {
        $map = [];
        foreach ($headerRow as $colLetter => $label) {
            $label = trim((string) $label);
            if ($label !== '') {
                $map[$label] = $colLetter;
            }
        }
        return $map;
    }

    /**
     * Kolom dinamis yang isinya nominal (mis. "Pembebanan Perusahaan", "Pembebanan
     * Karyawan") diformat pakai rupiah() supaya tampil rapi ("668.000"), bukan angka
     * mentah dari Excel. Kolom teks biasa (Beban PT, Unit Kerja, No. Invoice, dst)
     * dibiarkan apa adanya.
     */
    private function formatDynamicValue(string $colLabel, $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $isMoneyColumn = str_contains(strtolower($colLabel), 'pembebanan');
        if ($isMoneyColumn) {
            $number = $this->toNumber($value);
            if ($number !== null) {
                return $number == 0 ? '-' : rupiah($number);
            }
        }

        return (string) $value;
    }

    private function cell(array $row, array $columns, string $label)
    {
        $colLetter = $columns[$label] ?? null;
        if (!$colLetter) {
            return null;
        }
        $value = $row[$colLetter] ?? null;
        return is_string($value) ? trim($value) : $value;
    }

    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }

    private function toNumber($value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        if (is_string($value)) {
            $clean = preg_replace('/[^0-9,.-]/', '', $value);
            $clean = str_replace(['.', ','], ['', '.'], $clean);
            return is_numeric($clean) ? (float) $clean : null;
        }
        return null;
    }
}
