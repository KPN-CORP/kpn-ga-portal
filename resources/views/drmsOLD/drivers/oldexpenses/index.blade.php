@extends('layouts.app_car_drive_sidebar')

@section('content')
<div class="container mx-auto max-w-4xl px-4 py-6">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
        <h1 class="text-2xl font-bold">🧾 Laporan Pengeluaran</h1>
        <div class="flex gap-2">
            <a href="{{ route('drms.driver.expenses.pdf', ['month' => $month, 'request_id' => $requestId]) }}" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                📄 Download PDF
            </a>
            <a href="{{ route('drms.driver.expenses.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                + Input Laporan
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    {{-- FILTER BULAN & PERJALANAN --}}
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-4">
        <form method="GET" action="{{ route('drms.driver.expenses.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📅 Bulan</label>
                <select name="month" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="all" {{ $month === 'all' ? 'selected' : '' }}>Semua Bulan</option>
                    @foreach(collect(range(0, 11))->map(fn($i) => now()->subMonths($i)->format('Y-m')) as $opt)
                        <option value="{{ $opt }}" {{ $month === $opt ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::createFromFormat('Y-m', $opt)->translatedFormat('F Y') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">🚗 Perjalanan</label>
                <select name="request_id" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 max-w-xs">
                    <option value="">Semua Perjalanan</option>
                    @foreach($tripOptions as $trip)
                        <option value="{{ $trip->id }}" {{ (string) $requestId === (string) $trip->id ? 'selected' : '' }}>
                            #{{ $trip->request_no }} — {{ \Carbon\Carbon::parse($trip->usage_date)->format('d M Y') }} — {{ $trip->destination }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                🔍 Tampilkan
            </button>
        </form>
    </div>

    {{-- RINGKASAN TOTAL PER KATEGORI --}}
    @php
        $cardMeta = [
            'toll'       => ['label' => 'Toll', 'icon' => '🛣️'],
            'parkir'     => ['label' => 'Parkir', 'icon' => '🅿️'],
            'bbm'        => ['label' => 'BBM', 'icon' => '⛽'],
            'cuci_mobil' => ['label' => 'Cuci Mobil', 'icon' => '🚿'],
        ];
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        @foreach($cardMeta as $catKey => $meta)
            <div class="bg-white p-4 rounded-lg shadow-sm border text-center">
                <p class="text-2xl">{{ $meta['icon'] }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $meta['label'] }}</p>
                <p class="font-bold text-gray-800">Rp {{ number_format($totals[$catKey] ?? 0, 0, ',', '.') }}</p>
            </div>
        @endforeach
    </div>
    <div class="bg-blue-600 text-white p-4 rounded-lg shadow-sm mb-6 flex items-center justify-between">
        <span class="font-semibold">Total Keseluruhan</span>
        <span class="text-xl font-bold">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
    </div>

    {{-- DAFTAR ENTRI --}}
    <div class="bg-white rounded-lg shadow-sm border overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-2 text-left">Tanggal</th>
                    <th class="px-4 py-2 text-left">Perjalanan</th>
                    <th class="px-4 py-2 text-left">Kategori</th>
                    <th class="px-4 py-2 text-left">Keterangan</th>
                    <th class="px-4 py-2 text-right">Nominal</th>
                    <th class="px-4 py-2 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($items as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $item->report_date->format('d M Y') }}</td>
                        <td class="px-4 py-2 text-gray-600 text-xs">
                            @if($item->request)
                                #{{ $item->request->request_no }} — {{ $item->request->destination }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-2">{{ $cardMeta[$item->category]['icon'] ?? '' }} {{ $item->category_label }}</td>
                        <td class="px-4 py-2 text-gray-600">{{ $item->description ?: '-' }}</td>
                        <td class="px-4 py-2 text-right font-medium">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-2 text-center">
                            @if($item->is_editable)
                                <a href="{{ route('drms.driver.expenses.edit', $item->id) }}" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">✏️ Edit</a>
                            @else
                                <span class="text-gray-400 text-xs" title="Lewat masa edit (maks. 10 hari sejak diisi)">🔒 Terkunci</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-500">Belum ada laporan pengeluaran pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
