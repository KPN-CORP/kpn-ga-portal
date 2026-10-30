{{-- resources/views/help/timeline/index.blade.php --}}
@extends('layouts.app-help-sidebar')

@section('title', 'Timeline')

@section('content')
@php
    $namaBulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $periode   = ($bulan ? $namaBulan[$bulan] . ' ' : 'Tahun ') . $tahun;
@endphp

<div class="space-y-6 text-sm text-gray-800 font-sans">

    <div>
        <h2 class="text-xl md:text-2xl font-bold text-gray-800">🗓️ Timeline</h2>
        <p class="text-gray-500 mt-1">Timeline tiket menurut tanggal kalender · periode {{ $periode }}</p>
    </div>

    @include('help_p.partials.grafik-filter', ['aksi' => 'help.timeline', 'tampilStatus' => true])

    {{-- RINGKASAN --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500">Belum close</p>
            <p class="text-2xl font-semibold text-blue-600 mt-1">{{ $ringkasan['aktif'] }}</p>
            <p class="text-xs text-gray-400 mt-1">di periode ini</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500">Belum close &amp; lewat 7 hari</p>
            <p class="text-2xl font-semibold {{ $ringkasan['telat'] > 0 ? 'text-red-600' : 'text-gray-800' }} mt-1">{{ $ringkasan['telat'] }}</p>
            <p class="text-xs text-gray-400 mt-1">di periode ini</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500">Closed di periode ini</p>
            <p class="text-2xl font-semibold text-green-600 mt-1">{{ $ringkasan['closed'] }}</p>
            <p class="text-xs text-gray-400 mt-1">
                {{ $ringkasan['persen_ok'] !== null ? $ringkasan['persen_ok'] . '% selesai ≤ 7 hari' : 'belum ada data' }}
            </p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500">Rata-rata menunggu diambil</p>
            <p class="text-2xl font-semibold text-gray-800 mt-1">{{ $ringkasan['menunggu_detik'] !== null ? \App\Support\help_s\HelpTimeline::formatDurasi($ringkasan['menunggu_detik']) : '-' }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $ringkasan['belum_diambil'] }} tiket belum diambil</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500">Rata-rata lama done</p>
            <p class="text-2xl font-semibold text-gray-800 mt-1">{{ $ringkasan['rata_detik'] !== null ? \App\Support\help_s\HelpTimeline::formatDurasi($ringkasan['rata_detik']) : '-' }}</p>
            <p class="text-xs text-gray-400 mt-1">sejak OPEN sampai DONE (pengerjaan selesai)</p>
        </div>
    </div>

    {{-- TIMELINE: BELUM CLOSE --}}
    @if($status !== 'closed')
        @include('help_p.partials.grafik-kalender', [
            'tikets'  => $ganttAktif,
            'rentang' => $rentang,
            'judul'   => 'Belum close · ' . $periode,
            'kosong'  => 'Tidak ada tiket yang belum close pada periode ini.',
        ])
    @endif

    {{-- TIMELINE: SUDAH CLOSED (terpisah) --}}
    @if($status !== 'aktif')
        @include('help_p.partials.grafik-kalender', [
            'tikets'  => $ganttClosed,
            'rentang' => $rentang,
            'judul'   => 'Sudah closed · ' . $periode,
            'kosong'  => 'Tidak ada tiket closed pada periode ini.',
        ])
    @endif

    {{-- VENDOR --}}
    @include('help_p.partials.grafik-kalender', [
        'tikets'  => $ganttVendor,
        'rentang' => $rentang,
        'judul'   => 'Pengerjaan vendor · ' . $periode,
        'mode'    => 'vendor',
        'legenda' => false,
    ])

    @if($totalTiket > $batas)
        <p class="text-xs text-gray-500">
            Menampilkan {{ $batas }} tiket tertua dari {{ $totalTiket }} tiket pada periode ini. Persempit filter bulan / status untuk melihat sisanya.
        </p>
    @endif
</div>

@endsection