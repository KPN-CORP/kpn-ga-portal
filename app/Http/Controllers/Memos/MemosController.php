<?php

namespace App\Http\Controllers\Memos;

use App\Http\Controllers\Controller;
use App\Models\Memos\MemoNumberSetting;
use App\Models\Memos\MemoRevision;
use App\Models\Memos\MemoTeam;
use App\Models\Memos\Memos;
use App\Models\Memos\MemosAttachments;
use App\Models\Memos\MemosItems;
use App\Services\Memos\MemoImportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Reader\Exception as SpreadsheetReaderException;

class MemosController extends Controller
{
    /** Masa berlaku draft manual (jam). Draft hasil import: lihat MemoImportService. */
    private const DRAFT_TTL_HOURS = 24;

    public function __construct()
    {
        // Memetakan index/show/create/store/edit/update/destroy ke MemosPolicy.
        // Method lain (downloadPdf, downloadAttachment, import, dst) HARUS memanggil
        // $this->authorize() sendiri - tidak tercakup oleh authorizeResource.
        $this->authorizeResource(Memos::class, 'memo');
    }

    // ========== LIST / FORM ==========

    /**
     * Daftar memo, dengan filter opsional:
     * - cari          : pencarian bebas (no memo, perihal, kepada, dari, nama pembuat)
     * - dari/sampai   : 'YYYY-MM-DD' -> rentang tanggal (dipakai juga oleh tombol Download)
     */
    public function index(Request $request)
    {
        $query = Memos::viewable(auth()->user())->with(['creator', 'attachments', 'revisions.revisedBy']);
        $this->applyDateFilter($query, $request);

        $memos = $query->latest()->paginate(15)->withQueryString();

        return view('Memos.Memos.index', compact('memos'));
    }

    public function create()
    {
        $signer = MemoNumberSetting::resolveSigner(auth()->user());
        $teamId = MemoNumberSetting::resolveTeamId(auth()->user());
        $teamLetterhead = $teamId ? optional(MemoTeam::find($teamId))->resolvedLetterhead() : null;

        return view('Memos.Memos.create', compact('signer', 'teamLetterhead'));
    }

    /**
     * Form edit - draft (belum bernomor) maupun submitted (revisi memo yang sudah
     * bernomor). Otorisasi 'update' sudah dicek lewat authorizeResource.
     */
    public function edit(Memos $memo)
    {
        if (!in_array($memo->status, Memos::EDITABLE_STATUSES, true)) {
            return redirect()->route('memos.show', $memo)->with('error', 'Memo ini tidak bisa diedit');
        }

        $memo->load('items', 'attachments');
        $signer = MemoNumberSetting::resolveSigner($memo->creator ?? auth()->user());
        $teamLetterhead = optional($memo->team)->resolvedLetterhead();

        return view('Memos.Memos.create', compact('memo', 'signer', 'teamLetterhead'));
    }

    public function show(Memos $memo)
    {
        $memo->load('items', 'attachments', 'creator', 'revisions.revisedBy');

        return view('Memos.Memos.show', compact('memo'));
    }

    // ========== SIMPAN / REVISI / HAPUS ==========

    public function store(Request $request)
    {
        $this->validateMemoRequest($request);

        $payload = $this->parsePayload($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $status = $request->input('status', 'draft');

        try {
            $memo = DB::transaction(function () use ($request, $payload, $status) {
                // Penandatangan & Jabatan diambil otomatis dari data admin tim, bukan input manual.
                $signer = MemoNumberSetting::resolveSigner(auth()->user());

                $memo = Memos::create($this->memoAttributes($request, $payload, $signer) + [
                    'status'        => $status,
                    'business_unit' => auth()->user()->getBusinessUnitAttribute(),
                    'created_by'    => auth()->id(),
                    'expires_at'    => $status === 'draft' ? now()->addHours(self::DRAFT_TTL_HOURS) : null,
                ]);

                $this->syncItems($memo, $payload['items']);
                $this->storeAttachments($request, $memo);

                return $memo;
            });
        } catch (\Throwable $e) {
            return $this->failure($e);
        }

        return response()->json(['success' => true, 'memo_id' => $memo->id, 'message' => 'Memo tersimpan']);
    }

    /**
     * Update memo.
     *
     * - Draft: bisa disimpan lagi sebagai draft atau langsung submit (nomor memo
     *   digenerate saat itu, lihat event 'updating' di model Memos).
     * - Sudah submitted: jalur REVISI. Status & nomor memo DIKUNCI (tetap 'submitted'
     *   dan pakai nomor yang sudah ada, tidak bisa diganti lewat request), field lain
     *   bisa diubah, dan versi lamanya otomatis dibackup ke memo_revisions.
     */
    public function update(Request $request, Memos $memo)
    {
        if (!in_array($memo->status, Memos::EDITABLE_STATUSES, true)) {
            return $this->jsonError('Memo ini tidak bisa diedit', 422);
        }

        $this->validateMemoRequest($request);

        $payload = $this->parsePayload($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $isRevision = $memo->status === 'submitted';
        $targetStatus = $isRevision ? 'submitted' : $request->input('status', 'draft');

        try {
            DB::transaction(function () use ($request, $memo, $payload, $targetStatus) {
                // Penandatangan mengikuti PEMBUAT memo (bukan siapa yang sedang merevisi),
                // supaya admin yang ikut merevisi tidak menimpa penandatangan asli.
                $signer = MemoNumberSetting::resolveSigner($memo->creator ?? auth()->user());

                $newValues = $this->memoAttributes($request, $payload, $signer) + [
                    'status'     => $targetStatus,
                    // Draft expired 24 jam sejak disimpan; memo submitted (termasuk saat direvisi) tidak pernah.
                    'expires_at' => $targetStatus === 'draft' ? now()->addHours(self::DRAFT_TTL_HOURS) : null,
                ];

                // Backup versi sebelumnya + catat log SEBELUM perubahan disimpan,
                // supaya kalau ada yang perlu dibalikin datanya masih utuh.
                $this->backupBeforeUpdate($memo, $newValues);

                $memo->update($newValues);

                $memo->items()->delete();
                $this->syncItems($memo, $payload['items']);
                $this->storeAttachments($request, $memo);
            });
        } catch (\Throwable $e) {
            return $this->failure($e);
        }

        return response()->json([
            'success'  => true,
            'memo_id'  => $memo->id,
            'message'  => $isRevision ? 'Revisi memo tersimpan' : 'Memo diperbarui',
        ]);
    }

    public function destroy(Memos $memo)
    {
        if ($memo->status !== 'draft') {
            return back()->with('error', 'Hanya draft yang bisa dihapus');
        }

        foreach ($memo->attachments as $attachment) {
            $attachment->deleteFile();
        }
        $memo->delete();

        return redirect()->route('memos.index')->with('success', 'Draft dihapus');
    }

    // ========== PDF & LAMPIRAN ==========

    public function downloadPdf(Memos $memo)
    {
        $this->authorize('view', $memo);

        $memo->load('items');

        $pdf = Pdf::loadView('Memos.Memos.pdf', compact('memo'))->setPaper('a4');

        // Nomor memo memuat "/" dan tidak boleh ada karakter yang merusak header.
        $filename = 'Memo-' . ($memo->memo_number ?? 'draft') . '.pdf';
        $filename = preg_replace('/[\r\n"\\\\]+/', '', $filename);

        // Tidak pakai $pdf->download() karena Laravel/Symfony menolak "/" di nama file.
        // Response dibuat manual supaya "/" tetap bisa dipakai apa adanya.
        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Lampiran disimpan di disk privat; akses hanya lewat route ini
     * (setelah dicek boleh/tidaknya user melihat memo induknya).
     */
    public function downloadAttachment(MemosAttachments $attachment)
    {
        $this->authorize('view', $attachment->memo);

        $disk = $attachment->disk();
        abort_if($disk === null, 404, 'File lampiran tidak ditemukan.');

        // Hanya PDF & gambar yang boleh dibuka langsung di browser; sisanya dipaksa download.
        $mime = (string) $attachment->mime_type;
        $inline = $mime === 'application/pdf' || str_starts_with($mime, 'image/');

        return Storage::disk($disk)->response(
            $attachment->file_path,
            $attachment->original_name,
            ['X-Content-Type-Options' => 'nosniff'],
            $inline ? 'inline' : 'attachment'
        );
    }

    public function updateChecklist(Request $request, MemosAttachments $attachment)
    {
        $this->authorize('update', $attachment->memo);

        $attachment->update(['is_checked' => $request->boolean('is_checked')]);

        return back()->with('success', 'Checklist diperbarui');
    }

    public function deleteAttachment(MemosAttachments $attachment)
    {
        $this->authorize('update', $attachment->memo);

        if ($attachment->memo->status !== 'draft') {
            return back()->with('error', 'Lampiran hanya bisa dihapus selagi masih draft');
        }

        $attachment->deleteFile();
        $attachment->delete();

        return back()->with('success', 'Lampiran dihapus');
    }

    /**
     * API endpoint untuk mendapatkan terbilang dari jumlah angka
     */
    public function terbilang($amount)
    {
        return response()->json([
            'terbilang' => terbilang($amount),
        ]);
    }

    // ========== IMPORT EXCEL (pengganti mail merge) ==========

    /**
     * Form upload file mailing (xlsx). Bukan route resource memos/{memo}, jadi
     * otorisasinya dicek manual di sini.
     */
    public function importForm()
    {
        $this->authorize('create', Memos::class);

        return view('Memos.Memos.import');
    }

    /**
     * Buat semua memo dari file Excel sekaligus berstatus DRAFT supaya bisa dicek/diedit
     * dulu sebelum di-submit satu-satu. Logika baca file ada di MemoImportService.
     */
    public function import(Request $request, MemoImportService $importer)
    {
        $this->authorize('create', Memos::class);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // maks 10 MB
        ]);

        try {
            $result = $importer->import($request->file('file')->getRealPath(), auth()->user());
        } catch (SpreadsheetReaderException $e) {
            return back()->with('error', 'File Excel tidak bisa dibaca: ' . $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Import gagal. Periksa format file lalu coba lagi.');
        }

        if ($result['created'] === 0) {
            return back()->with('error', 'Tidak ada baris valid yang bisa diimport dari file ini. ' . implode('; ', array_slice($result['skipped'], 0, 5)));
        }

        $message = "{$result['created']} memo draft berhasil dibuat dari file.";
        if ($result['needs_review'] > 0) {
            $message .= " {$result['needs_review']} di antaranya perlu dicek manual (rekening tujuan belum lengkap) sebelum di-submit.";
        }
        if (count($result['skipped'])) {
            $message .= ' ' . count($result['skipped']) . ' baris dilewati karena data tidak lengkap.';
        }

        return redirect()->route('memos.index')->with('success', $message);
    }

    // ========== EXPORT REKAP ==========

    /**
     * Download rekap memo (Excel) sesuai filter yang sedang aktif di halaman daftar memo.
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
                $this->safeCell($memo->memo_number ?? '-'),
                $memo->status === 'draft' ? 'Draf' : 'Tersimpan',
                $this->safeCell($memo->perihal),
                $this->safeCell($memo->kepada),
                $this->safeCell($memo->dari),
                $this->safeCell(optional($memo->creator)->name),
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
     * Cegah "formula injection": teks yang diawali = + - @ akan dieksekusi Excel
     * sebagai rumus saat file dibuka, jadi diberi awalan apostrof.
     */
    private function safeCell(?string $value): ?string
    {
        if ($value !== null && $value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }

        return $value;
    }

    // ========== REQUEST HELPERS ==========

    private function validateMemoRequest(Request $request): void
    {
        $request->validate([
            'perihal'       => 'required|string',
            'kepada'        => 'required|string',
            'dari'          => 'required|string',
            'items'         => 'required',
            'status'        => 'in:draft,submitted',
            'attachments'   => 'nullable|array|max:10',
            'attachments.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:5120', // maks 5 MB / file
        ]);
    }

    /**
     * Decode & validasi items + kolom dinamis dari request.
     *
     * @return array{items:array, dynamic_columns:array, tagihan_source_column:array}|JsonResponse
     *         JsonResponse (422) kalau items tidak valid.
     */
    private function parsePayload(Request $request): array|JsonResponse
    {
        $items = $this->decodeItems($request);
        if ($items === null) {
            return $this->jsonError('Items tidak valid', 422);
        }

        if ($error = $this->validateItems($items)) {
            return $this->jsonError($error, 422);
        }

        return [
            'items'                 => $items,
            'dynamic_columns'       => $this->decodeDynamicColumns($request),
            'tagihan_source_column' => $this->decodeTagihanSourceColumns($request),
        ];
    }

    /**
     * Field memo yang sama untuk store & update (status, owner, dan expiry diisi pemanggil).
     */
    private function memoAttributes(Request $request, array $payload, array $signer): array
    {
        return [
            'perihal'                    => $request->perihal,
            'kepada'                     => $request->kepada,
            'dari'                       => $request->dari,
            'instruksi'                  => $request->instruksi,
            'bank'                       => $request->bank,
            'atas_nama'                  => $request->atas_nama,
            'no_rek'                     => $request->no_rek,
            'sertakan_rekening'          => $request->boolean('sertakan_rekening', true),
            'paragraf_pembuka'           => $request->paragraf_pembuka,
            'penandatangan'              => $signer['penandatangan'],
            'jabatan'                    => $signer['jabatan'],
            'total_amount'               => collect($payload['items'])->sum('tagihan'),
            'show_total'                 => $request->boolean('show_total', true),
            'tagihan_source_column'      => $payload['tagihan_source_column'] ?: null,
            'show_letterhead'            => $request->boolean('show_letterhead', true),
            'dynamic_columns_definition' => $payload['dynamic_columns'],
            'keterangan_label'           => $request->keteranganLabel,
        ];
    }

    private function jsonError(string $message, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }

    /**
     * Error tak terduga: detail masuk log, user hanya dapat pesan umum
     * (jangan bocorkan pesan SQL/exception ke browser).
     */
    private function failure(\Throwable $e): JsonResponse
    {
        report($e);

        return $this->jsonError('Terjadi kesalahan saat menyimpan memo. Silakan coba lagi.', 500);
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

    /**
     * Daftar nama kolom dinamis yang ditandai user sebagai sumber Tagihan
     * (bisa lebih dari satu — nilainya dijumlah jadi Tagihan (Rp) tiap baris
     * di frontend, lihat create.blade.php: syncTagihanFromSource()). Balikin
     * array kosong kalau tidak ada yang ditandai (mode manual seperti biasa).
     */
    private function decodeTagihanSourceColumns(Request $request): array
    {
        $cols = $request->input('tagihan_source_column');
        if (is_string($cols)) {
            $cols = json_decode($cols, true);
        }
        if (!is_array($cols)) {
            return [];
        }
        return array_values(array_filter($cols, fn ($c) => is_string($c) && $c !== ''));
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
        if (!$request->hasFile('attachments')) {
            return;
        }

        foreach ($request->file('attachments') as $file) {
            $path = $file->store('memos/' . $memo->id, MemosAttachments::DISK);

            MemosAttachments::create([
                'memo_id'       => $memo->id,
                'file_path'     => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type'     => $file->getMimeType(),
                'is_checked'    => false,
            ]);
        }
    }

    /**
     * Field yang ditampilkan di ringkasan log revisi, beserta label untuk UI.
     * (dynamic_columns_definition & expires_at sengaja tidak dimasukkan karena
     * bukan informasi yang perlu ditonjolkan ke pengguna).
     */
    private const REVISION_LOGGED_FIELDS = [
        'perihal'           => 'Perihal',
        'kepada'            => 'Kepada',
        'dari'              => 'Dari',
        'instruksi'         => 'Instruksi',
        'bank'              => 'Bank',
        'atas_nama'         => 'Atas Nama',
        'no_rek'            => 'No. Rekening',
        'total_amount'      => 'Total Tagihan',
        'status'            => 'Status',
        'keterangan_label'  => 'Label Keterangan',
    ];

    /**
     * Simpan snapshot memo (versi SEBELUM diupdate) + ringkasan field yang
     * berubah ke tabel memo_revisions. Dipanggil di dalam transaction yang
     * sama dengan update(), sebelum $memo->update() dieksekusi.
     */
    private function backupBeforeUpdate(Memos $memo, array $newValues): void
    {
        $memo->loadMissing('items', 'attachments');

        $snapshot = collect($memo->toArray())->only([
            'memo_number', 'perihal', 'kepada', 'dari', 'instruksi', 'bank',
            'atas_nama', 'no_rek', 'sertakan_rekening', 'paragraf_pembuka',
            'penandatangan', 'jabatan', 'total_amount', 'show_total', 'status', 'business_unit',
            'dynamic_columns_definition', 'keterangan_label', 'expires_at', 'show_letterhead',
            'tagihan_source_column',
        ])->all();
        $snapshot['items'] = $memo->items->map(fn ($item) => collect($item->toArray())->only([
            'nama', 'dynamic_columns', 'tagihan', 'sort_order'
        ])->all())->values()->all();

        $changedFields = [];
        foreach (self::REVISION_LOGGED_FIELDS as $field => $label) {
            $old = $memo->getAttribute($field);
            $new = $newValues[$field] ?? null;
            // total_amount dibandingkan sebagai angka supaya "100.00" vs 100 tidak dianggap berubah.
            $isDifferent = $field === 'total_amount'
                ? round((float) $old, 2) !== round((float) $new, 2)
                : (string) $old !== (string) $new;

            if ($isDifferent) {
                $changedFields[$field] = ['label' => $label, 'old' => $old, 'new' => $new];
            }
        }

        $nextRevisionNumber = ($memo->revisions()->max('revision_number') ?? 0) + 1;

        MemoRevision::create([
            'memo_id'         => $memo->id,
            'revision_number' => $nextRevisionNumber,
            'snapshot'        => $snapshot,
            'changed_fields'  => $changedFields,
            'revised_by'      => auth()->id(),
        ]);
    }
}
