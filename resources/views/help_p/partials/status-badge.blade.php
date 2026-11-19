{{-- Badge status tiket (satu istilah di semua halaman).
     Param: $tiket (wajib), $pelapor (bool, default false), $kode (bool: gaya kode untuk petugas, default false),
            $ukuran ('sm'|'md', default 'sm') --}}
@php
    $pelapor = $pelapor ?? false;
    $ukuran  = $ukuran ?? 'sm';
    $kode    = $kode ?? false;
    $jenis   = $tiket->status === 'WAITING' ? $tiket->jenis_tunggu : null;
@endphp
<span class="inline-flex items-center rounded-full border font-semibold {{ $ukuran === 'md' ? 'px-3 py-1 text-sm' : 'px-2 py-1 text-xs' }} {{ \App\Support\help_s\HelpStatus::warna($tiket->status, $jenis) }}">
    {{ $kode
        ? \App\Support\help_s\HelpStatus::labelKode($tiket->status, $jenis)
        : \App\Support\help_s\HelpStatus::label($tiket->status, $jenis, $pelapor) }}
</span>
