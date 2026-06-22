@extends('layouts.app_memos')
@section('title', 'Detail Memo - ' . $memo->memo_number)
@section('breadcrumb')
    <span class="text-gray-600">Memo / </span><span class="text-gray-800 font-medium">{{ $memo->memo_number }}</span>
@endsection
@section('content')
<div x-data="{ showDeleteModal: false }" class="w-full px-2 md:px-4">
    <div class="flex justify-between items-center mb-4">
        <a href="{{ route('memos.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-700">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
        @if($memo->status === 'draft' && $memo->created_by == auth()->id())
            <button @click="showDeleteModal = true" class="bg-red-50 hover:bg-red-100 text-red-600 px-4 py-2 rounded-lg">
                <i class="fas fa-trash-alt mr-2"></i> Hapus Draft
            </button>
        @endif
    </div>

    <!-- Area Memo yang akan dicetak -->
    <div id="printMemoArea" class="bg-white rounded-xl shadow-sm p-6 font-serif">
        <div class="text-right text-sm">{{ $memo->created_at->translatedFormat('d F Y') }}<br>No. {{ $memo->memo_number }}</div>
        <h2 class="text-center text-2xl font-bold my-4">MEMORANDUM</h2>
        <p><strong>Kepada</strong> : {{ $memo->kepada }}</p>
        <p><strong>Dari</strong> : {{ $memo->dari }}</p>
        <p><strong>Perihal</strong> : {{ $memo->perihal }}</p>
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
                    <th>Nama</th>
                    <th>PT/Unit</th>
                    @foreach($dynamicColumns as $colName)
                        <th>{{ $colName }}</th>
                    @endforeach
                    <th>Tagihan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($memo->items as $item)
                <tr>
                    <td>{{ $item->nama }}</td>
                    <td>{{ $item->pt_unit ?? '-' }}</td>
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

    <!-- Tombol cetak -->
    <div class="mt-4 flex justify-end">
        <button id="printMemoBtn" class="bg-gray-800 text-white px-4 py-2 rounded-lg shadow-sm">🖨️ Cetak Memo</button>
    </div>

    <!-- Lampiran dan checklist -->
    @if($memo->attachments->count())
    <div class="mt-6 border-t pt-4">
        <h3 class="font-bold flex items-center gap-2"><i class="fas fa-paperclip"></i> Lampiran ({{ $memo->attachments->count() }})</h3>
        <ul class="space-y-2 mt-2">
            @foreach($memo->attachments as $att)
            <li class="flex justify-between items-center p-2 bg-gray-50 rounded">
                <a href="{{ Storage::url($att->file_path) }}" target="_blank" class="text-blue-600">{{ $att->original_name }}</a>
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
</div>

<script>
    document.getElementById('printMemoBtn')?.addEventListener('click', () => {
        const printContent = document.getElementById('printMemoArea').cloneNode(true);
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
            <head>
                <title>Cetak Memo - {{ $memo->memo_number }}</title>
                <style>
                    /* Reset margin dan hilangkan header/footer browser */
                    @page {
                        margin: 0;
                    }
                    body {
                        margin: 1.6cm;
                        font-family: 'Times New Roman', serif;
                        background: white;
                    }
                    .container {
                        max-width: 800px;
                        margin: 0 auto;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                    }
                    th, td {
                        border: 1px solid #000;
                        padding: 6px;
                        text-align: left;
                        vertical-align: top;
                    }
                    .text-right {
                        text-align: right;
                    }
                    .font-bold {
                        font-weight: bold;
                    }
                    .border-l-4 {
                        border-left: 4px solid #2563eb;
                        padding-left: 12px;
                    }
                    /* Sembunyikan elemen yang tidak perlu saat cetak */
                    .no-print {
                        display: none !important;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    ${printContent.innerHTML}
                </div>
            </body>
            </html>
        `);
        printWindow.document.close();
        // Tunggu sebentar agar konten termuat sempurna
        setTimeout(() => {
            printWindow.print();
        }, 500);
    });
</script>
@endsection