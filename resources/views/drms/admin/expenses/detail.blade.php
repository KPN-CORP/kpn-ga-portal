@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto max-w-3xl px-4 py-6 text-sm text-gray-800">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">🧾 Detail Laporan Pengeluaran</h1>
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('drms.admin.expenses.index') }}"
           class="text-sm text-blue-600 hover:underline">← Kembali</a>
    </div>

    <div class="bg-white p-4 rounded-lg shadow-sm border mb-4">
        <p class="font-semibold text-gray-800">🚗 #{{ $driverRequest->request_no }} — {{ $driverRequest->destination }}</p>
        <p class="text-xs text-gray-500 mt-0.5">{{ \Carbon\Carbon::parse($driverRequest->usage_date)->format('d M Y') }}</p>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1 mt-3 text-xs text-gray-600">
            <div><dt class="inline font-medium">Driver:</dt> <dd class="inline">{{ $driver->name ?? '-' }}{{ !empty($driver->phone) ? ' ('.$driver->phone.')' : '' }}</dd></div>
            <div><dt class="inline font-medium">Kendaraan:</dt> <dd class="inline">{{ $driverRequest->vehicle ? $driverRequest->vehicle->type.' - '.$driverRequest->vehicle->plate_number : '-' }}</dd></div>
            <div><dt class="inline font-medium">Pemohon:</dt> <dd class="inline">{{ optional($driverRequest->requester)->name ?? '-' }}</dd></div>
            <div><dt class="inline font-medium">BU Pemohon:</dt> <dd class="inline">{{ optional(optional(optional($driverRequest->requester)->drmsProfile)->businessUnit)->nama_bisnis_unit ?? '-' }}</dd></div>
        </dl>

        <div class="bg-gray-50 border border-gray-200 text-gray-500 text-xs px-3 py-2 rounded mt-3">
            Tampilan admin bersifat read-only. Entri hanya bisa diedit oleh driver (maks. {{ \App\Models\Drms\ExpenseReport::EDITABLE_DAYS }} hari sejak diisi) dan tidak bisa dihapus.
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border overflow-x-auto mb-4">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-2 text-left">Tanggal</th>
                    <th class="px-4 py-2 text-left">Kategori</th>
                    <th class="px-4 py-2 text-left">Keterangan</th>
                    <th class="px-4 py-2 text-right">Nominal</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($items as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $item->report_date->format('d M Y') }}</td>
                        <td class="px-4 py-2">{{ $item->category_label }}</td>
                        <td class="px-4 py-2 text-gray-600">{{ $item->description ?: '-' }}</td>
                        <td class="px-4 py-2 text-right font-medium">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        @foreach(\App\Models\Drms\ExpenseReport::CATEGORIES as $key => $label)
            <div class="bg-white p-3 rounded-lg shadow-sm border text-center">
                <p class="text-xs text-gray-500">{{ $label }}</p>
                <p class="font-bold text-gray-800">Rp {{ number_format($totals[$key] ?? 0, 0, ',', '.') }}</p>
            </div>
        @endforeach
    </div>

    <div class="bg-blue-600 text-white p-4 rounded-lg shadow-sm mb-4 flex items-center justify-between">
        <span class="font-semibold">Total Perjalanan Ini</span>
        <span class="text-xl font-bold">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
    </div>

    <a href="{{ route('drms.admin.expenses.pdf', $driverRequest->id) }}" class="inline-block bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
        📄 Download PDF
    </a>
</div>
@endsection
