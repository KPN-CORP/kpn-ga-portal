<?php

namespace App\Http\Controllers\Memos;

use App\Http\Controllers\Controller;
use App\Models\Memos\MemoTemplate;
use App\Models\Memos\MemoTeam;
use App\Models\Memos\MemoNumberSetting;
use Illuminate\Http\Request;

/**
 * Template memo milik TIM (bukan milik satu user). Semua anggota tim yang sama
 * (admin maupun member biasa) boleh melihat, memakai, mengedit, dan menghapus
 * template ini — otorisasi murni berdasarkan keanggotaan tim, sama seperti scope
 * "viewable" di Memos::scopeViewable().
 *
 * Superadmin (User::isMemoSuperadmin()) adalah pengecualian: bisa lihat & kelola
 * template SEMUA tim, sama seperti dia bisa lihat semua memo di Memos::scopeViewable().
 *
 * Template BUKAN memo sungguhan: tidak dapat memo_number, tidak kena expired 24 jam.
 * Snapshot-nya LENGKAP (header + rincian/items + rekening), persis sesuai kondisi
 * form saat disimpan/diedit. Maksimal 15 template per tim (limit dihitung PER TIM,
 * bukan gabungan semua tim, termasuk untuk superadmin).
 */
class MemoTemplateController extends Controller
{
    public function index()
    {
        if (auth()->user()->isMemoSuperadmin()) {
            $templates = MemoTemplate::with('team')->orderBy('team_id')->orderBy('name')->get();
            return response()->json(['success' => true, 'templates' => $templates]);
        }

        $teamId = $this->resolveTeamId();
        if (!$teamId) {
            return response()->json(['success' => true, 'templates' => []]);
        }

        $templates = MemoTemplate::forTeam($teamId)->orderBy('name')->get();
        return response()->json(['success' => true, 'templates' => $templates]);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);

        // Superadmin: kalau dia sendiri tidak terdaftar di tim manapun, dia harus
        // pilih tim tujuan template lewat field 'team_id' di request. User biasa
        // tetap otomatis pakai tim dia sendiri (tidak bisa pilih tim lain).
        if (auth()->user()->isMemoSuperadmin()) {
            $teamId = $this->resolveTeamId() ?? $request->integer('team_id');
            if (!$teamId || !MemoTeam::whereKey($teamId)->exists()) {
                return response()->json(['success' => false, 'message' => 'Pilih tim tujuan template terlebih dahulu'], 422);
            }
        } else {
            $teamId = $this->resolveTeamId();
            if (!$teamId) {
                return response()->json(['success' => false, 'message' => 'Anda belum terdaftar di tim manapun, tidak bisa menyimpan template'], 422);
            }
        }

        $count = MemoTemplate::forTeam($teamId)->count();
        if ($count >= MemoTemplate::MAX_PER_TEAM) {
            return response()->json([
                'success' => false,
                'message' => 'Maksimal ' . MemoTemplate::MAX_PER_TEAM . ' template per tim. Hapus salah satu template lama dulu untuk menambah yang baru.'
            ], 422);
        }

        $template = MemoTemplate::create(array_merge(
            $this->extractSnapshot($request),
            [
                'team_id' => $teamId,
                'name' => $request->name,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        ));

        return response()->json(['success' => true, 'template' => $template, 'message' => 'Template tersimpan']);
    }

    /**
     * Aksi "Edit Template": menimpa data template yang sudah ada (bukan bikin memo).
     * Boleh dilakukan siapa saja di tim yang sama dengan template ini, atau superadmin.
     */
    public function update(Request $request, MemoTemplate $memoTemplate)
    {
        $this->authorizeTeamAccess($memoTemplate);

        $request->validate(['name' => 'required|string|max:255']);

        $memoTemplate->update(array_merge(
            $this->extractSnapshot($request),
            [
                'name' => $request->name,
                'updated_by' => auth()->id(),
            ]
        ));

        return response()->json(['success' => true, 'template' => $memoTemplate, 'message' => 'Template diperbarui']);
    }

    public function show(MemoTemplate $memoTemplate)
    {
        $this->authorizeTeamAccess($memoTemplate);
        return response()->json(['success' => true, 'template' => $memoTemplate]);
    }

    public function destroy(MemoTemplate $memoTemplate)
    {
        $this->authorizeTeamAccess($memoTemplate);
        $memoTemplate->delete();
        return response()->json(['success' => true, 'message' => 'Template dihapus']);
    }

    // ========== HELPERS ==========

    private function resolveTeamId(): ?int
    {
        return MemoNumberSetting::resolveTeamId(auth()->user());
    }

    /**
     * Superadmin lolos otorisasi apa pun timnya (sama seperti Memos::scopeViewable()).
     * User biasa cuma boleh akses template tim dia sendiri.
     */
    private function authorizeTeamAccess(MemoTemplate $memoTemplate): void
    {
        if (auth()->user()->isMemoSuperadmin()) {
            return;
        }
        abort_if($memoTemplate->team_id !== $this->resolveTeamId(), 403, 'Template ini bukan milik tim Anda');
    }

    private function extractSnapshot(Request $request): array
    {
        $dynamicColumns = $request->dynamic_columns;
        if (is_string($dynamicColumns)) {
            $dynamicColumns = json_decode($dynamicColumns, true);
        }

        $items = $request->items;
        if (is_string($items)) {
            $items = json_decode($items, true);
        }

        return [
            'perihal' => $request->perihal,
            'kepada' => $request->kepada,
            'dari' => $request->dari,
            'instruksi' => $request->instruksi,
            'bank' => $request->bank,
            'atas_nama' => $request->atas_nama,
            'no_rek' => $request->no_rek,
            'sertakan_rekening' => $request->boolean('sertakan_rekening', true),
            'paragraf_pembuka' => $request->paragraf_pembuka,
            'keterangan_label' => $request->keterangan_label,
            'dynamic_columns_definition' => is_array($dynamicColumns) ? $dynamicColumns : [],
            'items' => is_array($items) ? $items : [],
        ];
    }
}
