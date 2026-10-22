@extends('layouts.fingrab')

@section('title', 'Upload BCR')

@section('content')
<div class="space-y-6 text-sm text-gray-800">
    <div>
        <h2 class="text-xl font-semibold text-gray-800">Upload BCR Grab</h2>
        <p class="text-xs text-gray-500">Unggah file zip dari Grab (atau file xlsx BCR langsung). Hanya file <code>bcr_*.xlsx</code> di dalam zip yang dibaca, PDF invoice diabaikan.</p>
    </div>

    @if(session('fingrab_summary'))
        @php($s = session('fingrab_summary'))
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-green-800">
            <p class="font-semibold">Upload selesai: {{ number_format($s['total']) }} transaksi ({{ $s['period_from'] }} s.d. {{ $s['period_to'] }})</p>
            <ul class="mt-2 text-sm list-disc pl-5">
                <li>File xlsx dibaca: {{ $s['files'] }}</li>
                <li>Baris kembar di file (Booking ID dan Trip code sama, hanya satu dipakai): {{ $s['duplicates'] ?? 0 }}</li>
                <li>Data baru: {{ $s['new'] ?? 0 }}, diperbarui (isinya berubah): {{ $s['updated'] ?? 0 }}, tidak berubah: {{ $s['unchanged'] ?? 0 }}</li>
                <li>Cocok HCIS lewat ID: {{ $s['matched_id'] }}, lewat email: {{ $s['matched_email'] }}, belum cocok: {{ $s['unmatched'] }}</li>
                <li>PT belum terpetakan ke Bisnis unit: {{ $s['pt_unmapped'] }}@if(!empty($s['pt_unmapped_names'])) ({{ implode(', ', $s['pt_unmapped_names']) }})@endif</li>
                <li>Baris dilewati (tanpa Booking ID atau waktu): {{ $s['skipped'] }}</li>
            </ul>
            <a href="{{ route('fingrab.kecocokan') }}" class="inline-block mt-3 text-sm font-medium text-green-900 underline">Lihat kecocokan data</a>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-700">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('fingrab.upload.store') }}" enctype="multipart/form-data"
          class="bg-white border rounded-xl p-5 space-y-4" x-data="{ busy: false }" @submit="busy = true">
        @csrf
        <div>
            <label for="files" class="block text-sm font-medium text-gray-700 mb-1">File zip atau xlsx (boleh lebih dari satu)</label>
            <input id="files" type="file" name="files[]" accept=".zip,.xlsx,.xls" multiple required
                   class="block w-full text-sm border rounded-lg p-2 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-blue-700">
            <p class="mt-1 text-xs text-gray-500">Upload ulang aman: data dicocokkan per Booking ID. Data baru ditambah, data yang isinya berubah diperbarui (hanya kolom yang berubah), data yang sama tidak disentuh, dan data yang tidak ada di file tidak dihapus.</p>
        </div>
        <button type="submit" :disabled="busy" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium disabled:opacity-60">
            <span x-show="!busy">Upload dan proses</span><span x-show="busy" x-cloak>Memproses...</span>
        </button>
    </form>

    <div class="bg-white border rounded-xl p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Riwayat upload</h3>
        @if($batches->isEmpty())
            <p class="text-gray-500">Belum ada upload.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-xs text-gray-500 border-b">
                        <tr><th class="py-2 pr-4">Waktu</th><th class="pr-4">Periode</th><th class="pr-4 text-right">Transaksi</th><th class="pr-4 text-right">Cocok HCIS</th><th class="pr-4 text-right">Belum cocok</th><th class="pr-4 text-right">PT belum terpetakan</th><th>File</th></tr>
                    </thead>
                    <tbody>
                    @foreach($batches as $b)
                        <tr class="border-b last:border-0">
                            <td class="py-2 pr-4 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($b->created_at)->format('d-m-Y H:i') }}</td>
                            <td class="pr-4 whitespace-nowrap">{{ $b->period_from }} s.d. {{ $b->period_to }}</td>
                            <td class="pr-4 text-right">{{ number_format($b->rows_total) }}</td>
                            <td class="pr-4 text-right">{{ number_format($b->rows_matched) }}</td>
                            <td class="pr-4 text-right">{{ number_format($b->rows_unmatched) }}</td>
                            <td class="pr-4 text-right">{{ number_format($b->rows_pt_unmapped) }}</td>
                            <td class="text-xs text-gray-500 max-w-xs truncate" title="{{ $b->files }}">{{ $b->files }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection