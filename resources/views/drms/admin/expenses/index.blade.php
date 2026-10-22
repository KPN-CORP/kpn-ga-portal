@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto max-w-5xl px-4 py-6 text-sm text-gray-800">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
        <div>
            <h1 class="text-2xl font-bold">🧾 Laporan Pengeluaran Driver</h1>
            <p class="text-xs text-gray-500 mt-1">
                {{ $isSuper ? 'Semua Business Unit' : 'Business Unit / area Anda' }} &middot; hanya lihat (read-only)
            </p>
        </div>
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

    {{-- FILTER --}}
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-4">
        <form method="GET" action="{{ route('drms.admin.expenses.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📅 Bulan</label>
                <select name="month" class="w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="all" {{ $month === 'all' ? 'selected' : '' }}>Semua Bulan</option>
                    @foreach(collect(range(0, 11))->map(fn($i) => now()->subMonths($i)->format('Y-m')) as $opt)
                        <option value="{{ $opt }}" {{ $month === $opt ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::createFromFormat('Y-m', $opt)->translatedFormat('F Y') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Driver</label>
                <select name="driver_id" class="w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="">Semua Driver</option>
                    @foreach($driverOptions as $d)
                        <option value="{{ $d->id }}" {{ (string) request('driver_id') === (string) $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Kategori</label>
                <select name="category" class="w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="">Semua Kategori</option>
                    @foreach(\App\Models\Drms\ExpenseReport::CATEGORIES as $key => $label)
                        <option value="{{ $key }}" {{ $category === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if($isSuper)
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Business Unit</label>
                <select name="bu" class="w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="">Semua BU</option>
                    @foreach($businessUnits as $bu)
                        <option value="{{ $bu->id_bisnis_unit }}" {{ (string) request('bu') === (string) $bu->id_bisnis_unit ? 'selected' : '' }}>{{ $bu->nama_bisnis_unit }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="No. request / tujuan"
                       class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">🔍 Tampilkan</button>
                <a href="{{ route('drms.admin.expenses.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition">Reset</a>
            </div>
        </form>
    </div>

    {{-- RINGKASAN TOTAL PER KATEGORI (mengikuti filter yang aktif) --}}
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

    {{-- DAFTAR PER PERJALANAN --}}
    <div class="space-y-3">
        @forelse($trips as $trip)
            @php
                $req = $trip->request;
                $buName = optional($req->currentBusinessUnit)->nama_bisnis_unit
                    ?? optional(optional(optional($req->requester)->drmsProfile)->businessUnit)->nama_bisnis_unit
                    ?? '-';
            @endphp
            <div class="bg-white rounded-lg shadow-sm border p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-gray-800">
                            🚗 #{{ $req->request_no }} — {{ $req->destination }}
                        </p>
                        <p class="text-xs text-gray-600 mt-0.5">
                            👤 {{ optional($trip->driver)->name ?? '-' }}
                            &middot; Pemohon: {{ optional($req->requester)->name ?? '-' }}
                            &middot; {{ $buName }}
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ \Carbon\Carbon::parse($req->usage_date)->format('d M Y') }}
                            &middot; {{ $trip->items->count() }} entri
                            &middot; diisi {{ $trip->submitted_at?->format('d M Y') }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-blue-600">Rp {{ number_format($trip->total, 0, ',', '.') }}</p>
                        @if($trip->is_editable)
                            <span class="text-xs text-green-600">✏️ Driver masih bisa edit</span>
                        @else
                            <span class="text-xs text-gray-400">🔒 Terkunci</span>
                        @endif
                    </div>
                </div>
                <div class="flex gap-3 mt-3 pt-3 border-t">
                    <a href="{{ route('drms.admin.expenses.detail', $req->id) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">🔍 Detail</a>
                    <a href="{{ route('drms.admin.expenses.pdf', $req->id) }}" class="text-xs font-semibold text-red-600 hover:text-red-800">📄 Download PDF</a>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-lg shadow-sm border px-4 py-10 text-center text-gray-500">
                Belum ada laporan pengeluaran pada filter ini.
            </div>
        @endforelse
    </div>

    @if($trips->hasPages())
        <div class="mt-4">{{ $trips->links() }}</div>
    @endif
</div>
@endsection
