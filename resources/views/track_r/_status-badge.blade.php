{{--
    Badge status dokumen (dipakai di index & show).
    Parameter:
      $status -> status mentah dari database: dikirim | diterima | ditolak | diteruskan
      $label  -> teks yang ditampilkan (dari $doc->statusForUser(...)['label'])
    Warna ditentukan dari $status, BUKAN dari teks label, supaya
    "Menunggu Penerima" tidak salah terbaca sebagai "diterima".
--}}
@php
    $map = [
        'dikirim'    => ['bg-blue-50 text-blue-700 ring-blue-600/20',       'bg-blue-500'],
        'diterima'   => ['bg-emerald-50 text-emerald-700 ring-emerald-600/20', 'bg-emerald-500'],
        'ditolak'    => ['bg-rose-50 text-rose-700 ring-rose-600/20',       'bg-rose-500'],
        'diteruskan' => ['bg-violet-50 text-violet-700 ring-violet-600/20', 'bg-violet-500'],
        'selesai'    => ['bg-emerald-50 text-emerald-700 ring-emerald-600/20', 'bg-emerald-500'],
    ];
    [$tone, $dot] = $map[$status ?? ''] ?? ['bg-slate-100 text-slate-700 ring-slate-500/20', 'bg-slate-400'];
@endphp
<span class="inline-flex max-w-full items-start gap-1.5 rounded-2xl px-2.5 py-1 text-left text-xs font-medium ring-1 ring-inset {{ $tone }}">
    <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full {{ $dot }}" aria-hidden="true"></span>
    <span class="min-w-0" style="overflow-wrap:anywhere;">{{ $label }}</span>
</span>