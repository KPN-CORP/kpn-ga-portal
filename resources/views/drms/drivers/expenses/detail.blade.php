@extends('layouts.app_car_drive_sidebar')

@section('content')
<div class="container mx-auto max-w-3xl px-4 py-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">🧾 Detail Laporan Pengeluaran</h1>
        <a href="{{ route('drms.driver.expenses.index') }}" class="text-sm text-blue-600 hover:underline">← Kembali ke Laporan</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white p-4 rounded-lg shadow-sm border mb-4">
        <p class="font-semibold text-gray-800">🚗 #{{ $driverRequest->request_no }} — {{ $driverRequest->destination }}</p>
        <p class="text-xs text-gray-500 mt-0.5">{{ \Carbon\Carbon::parse($driverRequest->usage_date)->format('d M Y') }}</p>

        @if($isEditable)
            <div class="bg-green-50 border border-green-200 text-green-700 text-xs px-3 py-2 rounded mt-3">
                ✅ Laporan ini masih bisa diedit sampai <strong>{{ $editDeadline?->format('d M Y') }}</strong>.
            </div>
        @else
            <div class="bg-gray-50 border border-gray-200 text-gray-500 text-xs px-3 py-2 rounded mt-3">
                🔒 Laporan ini sudah lewat masa edit (maks. {{ \App\Models\Drms\ExpenseReport::EDITABLE_DAYS }} hari sejak diisi) dan terkunci.
            </div>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow-sm border overflow-x-auto mb-4">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-2 text-left">Tanggal</th>
                    <th class="px-4 py-2 text-left">Kategori</th>
                    <th class="px-4 py-2 text-left">Keterangan</th>
                    <th class="px-4 py-2 text-right">Nominal</th>
                    <th class="px-4 py-2 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($items as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $item->report_date->format('d M Y') }}</td>
                        <td class="px-4 py-2">{{ $item->category_label }}</td>
                        <td class="px-4 py-2 text-gray-600">{{ $item->description ?: '-' }}</td>
                        <td class="px-4 py-2 text-right font-medium">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-2 text-center">
                            @if($item->is_editable)
                                <a href="{{ route('drms.driver.expenses.edit', $item->id) }}" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">✏️ Edit</a>
                            @else
                                <span class="text-gray-400 text-xs">🔒 Terkunci</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-blue-600 text-white p-4 rounded-lg shadow-sm mb-4 flex items-center justify-between">
        <span class="font-semibold">Total Perjalanan Ini</span>
        <span class="text-xl font-bold">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
    </div>

    <a href="{{ route('drms.driver.expenses.pdf', $driverRequest->id) }}" class="inline-block bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
        📄 Download PDF
    </a>
</div>
@endsection
