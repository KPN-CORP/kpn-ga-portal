@extends('layouts.app_memos')
@section('title', 'Detail Memo - ' . ($memo->memo_number ?? 'Draft'))
@section('breadcrumb')
    <span class="text-gray-600">Memo / </span><span class="text-gray-800 font-medium">{{ $memo->memo_number ?? 'Draft' }}</span>
@endsection
@section('content')
<div x-data="{ showDeleteModal: false }" class="w-full px-2 md:px-4">
    <div class="flex justify-between items-center mb-4">
        <a href="{{ route('memos.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-700">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
        @if($memo->status === 'draft' && $memo->created_by == auth()->id())
            <div class="flex gap-2">
                <a href="{{ route('memos.edit', $memo) }}" class="bg-amber-50 hover:bg-amber-100 text-amber-700 px-4 py-2 rounded-lg">
                    <i class="fas fa-pen mr-2"></i> Edit Draft
                </a>
                <button @click="showDeleteModal = true" class="bg-red-50 hover:bg-red-100 text-red-600 px-4 py-2 rounded-lg">
                    <i class="fas fa-trash-alt mr-2"></i> Hapus Draft
                </button>
            </div>
        @endif
    </div>

    @if($memo->status === 'draft')
        <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm px-4 py-2 rounded-lg">
            <i class="fas fa-circle-info mr-1"></i> Memo ini masih draft dan belum punya nomor memo. Nomor otomatis diberikan saat memo disubmit.
        </div>
    @endif

    <!-- Kanvas A4 -->
    <div class="a4-canvas">
        <div id="printMemoArea" class="a4-page font-serif">
            <div class="text-right text-sm">{{ $memo->created_at->translatedFormat('d F Y') }}<br>No. {{ $memo->memo_number ?? '(belum ada — masih draft)' }}</div>
            <h2 class="text-center text-2xl font-bold my-4">MEMORANDUM</h2>
            <p><strong>Kepada</strong> : {{ $memo->kepada }}</p>
            <p><strong>Dari</strong> : {{ $memo->dari }}</p>
            <p><strong>Perihal</strong> : {{ $memo->perihal }}</p>
            <hr style="margin: 16px 0; border: none; border-top: 2px solid #333;">
            <p>
                Mohon disiapkan dana sebesar
                <strong>Rp {{ number_format($memo->total_amount,0,',','.') }}</strong>
                ({{ terbilang($memo->total_amount) }} rupiah)
                untuk {{ $memo->perihal }} dengan rincian:
            </p>

            @php
                $dynamicColumns = $memo->dynamic_columns_definition ?? [];
                $colspan = 2 + count($dynamicColumns);
            @endphp

            <table class="w-full border mt-2">
                <thead>
                    <tr>
                        <th class="w-10">No</th>
                        <th>{{ $memo->keterangan_label ?? 'Keterangan' }}</th>
                        @foreach($dynamicColumns as $colName)
                            <th>{{ $colName }}</th>
                        @endforeach
                        <th>Tagihan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($memo->items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item->keterangan }}</td>
                        @php $dyn = is_array($item->dynamic_columns) ? $item->dynamic_columns : []; @endphp
                        @foreach($dyn as $val)
                            <td>{{ $val ?? '-' }}</td>
                        @endforeach
                        <td class="text-right">Rp {{ number_format($item->tagihan,0,',','.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="font-bold">
                        <td colspan="{{ $colspan }}" class="text-right">TOTAL</td>
                        <td class="text-right">Rp {{ number_format($memo->total_amount,0,',','.') }}</td>
                    </tr>
                </tbody>
            </table>

            @if($memo->instruksi)
                <p class="mt-3">{!! nl2br(e($memo->instruksi)) !!}</p>
            @endif

            <div class="border-l-4 border-blue-600 pl-3 my-3">
                <strong>Rekening Tujuan</strong><br>
                Bank: {{ $memo->bank }}<br>
                Atas Nama: {{ $memo->atas_nama }}<br>
                No Rek: {{ $memo->no_rek }}
            </div>
            <p class="mt-6">Hormat kami,<br><br><br><br>{{ $memo->penandatangan }}<br>{{ $memo->jabatan }}</p>
        </div>
    </div>

    <!-- Tombol cetak -->
    <div class="mt-4 flex justify-end no-print">
        <a href="{{ route('memos.pdf', $memo) }}" class="bg-gray-800 text-white px-4 py-2 rounded-lg shadow-sm inline-block">⬇️ Download PDF</a>
    </div>

    <!-- Lampiran dan checklist -->
    @if($memo->attachments->count())
    <div class="mt-6 border-t pt-4">
        <h3 class="font-bold flex items-center gap-2"><i class="fas fa-paperclip"></i> Lampiran ({{ $memo->attachments->count() }})</h3>
        <ul class="space-y-2 mt-2">
            @foreach($memo->attachments as $att)
            <li class="flex justify-between items-center p-2 bg-gray-50 rounded">
                <a href="{{ Storage::url($att->file_path) }}" target="_blank" rel="noopener" class="text-blue-600 flex items-center gap-2">
                    <i class="fas {{ str_contains($att->mime_type,'pdf') ? 'fa-file-pdf text-red-500' : 'fa-file-image text-blue-500' }}"></i>
                    {{ $att->original_name }}
                    <i class="fas fa-up-right-from-square text-xs text-gray-400"></i>
                </a>
                <form action="{{ route('memos.checklist', $att) }}" method="POST" class="inline">
                    @csrf @method('PATCH')
                    <label class="flex items-center gap-1"><input type="checkbox" name="is_checked" onchange="this.form.submit()" {{ $att->is_checked ? 'checked' : '' }}> Ceklis sudah disimpan</label>
                </form>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Modal Hapus -->
    <div x-show="showDeleteModal" class="fixed inset-0 flex items-center justify-center z-50 bg-black/50 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-xl max-w-md w-full p-6">
            <h3 class="text-lg font-bold text-red-600">Hapus Draft</h3>
            <p class="mt-2">Yakin ingin menghapus memo draft ini? Tindakan tidak dapat dibatalkan.</p>
            <div class="flex justify-end gap-3 mt-4">
                <button @click="showDeleteModal = false" class="px-4 py-2 bg-gray-200 rounded">Batal</button>
                <form action="{{ route('memos.destroy', $memo) }}" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded">Hapus</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tanda Informasi Mengambang -->
    <div class="fixed bottom-6 right-6 z-50 no-print" x-data="{ infoOpen: false }">
        <button @click="infoOpen = !infoOpen"
                class="w-12 h-12 rounded-full bg-blue-600 text-white shadow-lg flex items-center justify-center hover:bg-blue-700 transition text-lg font-bold"
                title="Info & Cara Pakai">
            <span x-show="!infoOpen">ℹ️</span>
            <span x-show="infoOpen">✖</span>
        </button>

        <div x-show="infoOpen"
             x-transition
             @click.away="infoOpen = false"
             class="absolute bottom-16 right-0 w-80 bg-white rounded-xl shadow-2xl border p-4 text-sm text-gray-700"
             style="display: none;">
            <h3 class="font-bold text-gray-800 mb-2">ℹ️ Info &amp; Cara Pakai</h3>
            <p class="mb-2">
                <strong>{{ $memo->keterangan_label ?? 'Keterangan' }}</strong>: kolom ini berisi
                nama/deskripsi tiap item rincian pada memo ini.
            </p>
            <p class="mb-2">
                <strong>Download PDF</strong>: klik tombol "⬇️ Download PDF" untuk mengunduh memo
                ini dalam bentuk file PDF.
            </p>
            @if($memo->status === 'draft' && $memo->created_by == auth()->id())
            <p class="mb-2">
                Memo ini masih <strong>draft</strong> — gunakan "Edit Draft" untuk mengubah isinya,
                atau "Hapus Draft" untuk membatalkan.
            </p>
            @endif
            @if($memo->attachments->count())
            <p>
                Centang kolom "Ceklis sudah disimpan" pada tiap lampiran setelah dokumen fisiknya
                benar-benar sudah diarsipkan.
            </p>
            @endif
        </div>
    </div>
</div>

<style>
    /* Tampilan halaman memo dibuat menyerupai kertas A4 di layar */
    .a4-canvas {
        background: #e5e7eb;
        padding: 24px;
        border-radius: 12px;
        display: flex;
        justify-content: center;
        overflow-x: auto;
    }
    .a4-page {
        background: #fff;
        width: 210mm;
        min-height: 297mm;
        max-width: 100%;
        padding: 20mm 18mm;
        box-shadow: 0 4px 18px rgba(0,0,0,0.15);
    }
    .a4-page table { width: 100%; border-collapse: collapse; }
    .a4-page th, .a4-page td { border: 1px solid #000; padding: 6px; text-align: left; vertical-align: top; }
    .a4-page .text-right { text-align: right; }
    .a4-page .text-center { text-align: center; }
    .a4-page .font-bold { font-weight: bold; }
    .a4-page .border-l-4 { border-left: 4px solid #2563eb; padding-left: 12px; }
    @media print {
        .a4-canvas { background: none; padding: 0; }
        .a4-page { box-shadow: none; width: auto; min-height: 0; }
    }
</style>
@endsection