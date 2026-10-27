@extends('layouts.app-sidebar')

@section('title', 'Laporan Mingguan' . (isset($user) && $user ? ' - ' . $user->name : ''))

@section('content')
@php
    $ageClass = fn ($age) => $age > 21
        ? 'bg-red-100 text-red-700'
        : ($age > 7 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600');

    // Siapkan data "belum selesai" sekali, dipakai oleh kartu (HP/tablet) dan tabel (desktop)
    $pendingRows = $pendingUnits->map(function ($u) {
        $m = $u->taskMonitor;
        return (object) [
            'created'  => $u->created_at,
            'age'      => (int) abs($u->created_at->copy()->startOfDay()->diffInDays(now()->startOfDay())),
            'project'  => $m->title ?? '-',
            'desc'     => $u->description,
            // start_date = end_date artinya tanggal placeholder, bukan deadline sebenarnya
            'deadline' => ($m && $m->end_date && $m->start_date && $m->end_date->gt($m->start_date))
                            ? $m->end_date->translatedFormat('d M Y') : null,
        ];
    });
@endphp

<div class="w-full px-3 sm:px-6 lg:px-8 py-4 sm:py-6">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4 print:mb-3">
        <div class="min-w-0">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Laporan Progres Mingguan</h1>
            <p class="text-xs sm:text-sm text-gray-500">
                {{ $user->name ?? '-' }} &middot;
                {{ $weekly[count($weekly)-1]['start']->translatedFormat('d M Y') }}
                &ndash; {{ $weekly[0]['end']->translatedFormat('d M Y') }}
                &middot; minggu = Senin&ndash;Minggu
            </p>
        </div>
        <div class="flex items-center gap-2 print:hidden">
            <form method="GET" class="flex-1 sm:flex-none">
                @if($userId !== Auth::id())
                    <input type="hidden" name="user_id" value="{{ $userId }}">
                @endif
                <select name="weeks" onchange="this.form.submit()"
                        class="w-full sm:w-auto border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white">
                    @foreach(collect([2, 3, 4, 5, 8, 12])->push($weeks)->unique()->sort()->values() as $w)
                        <option value="{{ $w }}" @selected($weeks == $w)>{{ $w }} minggu terakhir</option>
                    @endforeach
                </select>
            </form>
            <button onclick="window.print()"
                    class="shrink-0 px-4 py-2.5 text-sm rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
                Cetak / PDF
            </button>
        </div>
    </div>

    {{-- Lompat cepat (geser di HP) --}}
    <nav class="flex gap-2 overflow-x-auto pb-2 mb-4 -mx-3 px-3 sm:mx-0 sm:px-0 print:hidden">
        @foreach($weekly as $i => $w)
            <a href="#w{{ $i }}"
               class="shrink-0 px-3 py-1.5 rounded-full text-xs border border-green-200 bg-green-50 text-green-800">
                {{ $w['label'] }} <b>{{ $w['done']->count() }}</b>
            </a>
        @endforeach
        <a href="#belum"
           class="shrink-0 px-3 py-1.5 rounded-full text-xs border border-amber-200 bg-amber-50 text-amber-800">
            Belum selesai <b>{{ $pendingRows->count() }}</b>
        </a>
    </nav>

    {{-- ===================== SUDAH SELESAI ===================== --}}
    <h2 class="text-base sm:text-lg font-semibold text-gray-800 mb-3">Yang sudah selesai</h2>

    @foreach($weekly as $i => $w)
        <section id="w{{ $i }}" class="scroll-mt-4 bg-white rounded-xl shadow-sm mb-4 overflow-hidden break-inside-avoid">
            <div class="px-4 py-3 bg-green-50 flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                <div class="min-w-0">
                    <span class="font-semibold text-green-800">{{ $w['label'] }}</span>
                    <span class="block sm:inline text-xs text-gray-500 sm:ml-2">
                        {{ $w['start']->translatedFormat('d M') }} &ndash; {{ $w['end']->translatedFormat('d M Y') }}
                    </span>
                </div>
                <span class="text-sm font-semibold text-green-800">
                    <span class="text-xs font-normal text-gray-500 mr-1">{{ $w['created'] }} dibuat &middot;</span>{{ $w['done']->count() }} selesai
                </span>
            </div>

            @if($w['done']->isEmpty())
                <p class="px-4 py-3 text-sm text-gray-400 italic">Tidak ada tugas yang diselesaikan.</p>
            @else
                {{-- HP: kartu --}}
                <ul class="divide-y md:hidden print:hidden">
                    @foreach($w['done'] as $u)
                        <li class="px-4 py-3">
                            <p class="text-xs font-medium text-green-700">{{ $u->completed_at->translatedFormat('D, d M Y') }}</p>
                            <p class="mt-1 text-sm text-gray-800 break-words">{!! nl2br(e($u->description)) !!}</p>
                            <p class="mt-1 text-xs text-gray-500 break-words">{{ $u->taskMonitor->title ?? '-' }}</p>
                        </li>
                    @endforeach
                </ul>

                {{-- Tablet & desktop: tabel --}}
                <div class="hidden md:block print:block overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-gray-500 text-xs">
                            <tr>
                                <th class="px-4 py-2 text-left w-40">Selesai</th>
                                <th class="px-4 py-2 text-left w-1/4">Project</th>
                                <th class="px-4 py-2 text-left">Pekerjaan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($w['done'] as $u)
                                <tr>
                                    <td class="px-4 py-2 whitespace-nowrap align-top">{{ $u->completed_at->translatedFormat('D, d M Y') }}</td>
                                    <td class="px-4 py-2 text-gray-600 align-top">{{ $u->taskMonitor->title ?? '-' }}</td>
                                    <td class="px-4 py-2 break-words">{!! nl2br(e($u->description)) !!}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endforeach

    {{-- ===================== BELUM SELESAI ===================== --}}
    <h2 id="belum" class="scroll-mt-4 text-base sm:text-lg font-semibold text-gray-800 mt-8 mb-3">
        Yang belum selesai <span class="text-gray-400 font-normal">({{ $pendingRows->count() }})</span>
    </h2>

    @if($pendingRows->isEmpty())
        <div class="bg-white rounded-xl shadow-sm">
            <p class="px-4 py-3 text-sm text-gray-400 italic">Tidak ada pekerjaan yang tertunda.</p>
        </div>
    @else
        {{-- HP (1 kolom) & tablet (2 kolom): kartu --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:hidden print:hidden">
            @foreach($pendingRows as $r)
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs text-gray-500">Dibuat {{ $r->created->translatedFormat('d M Y') }}</span>
                        <span class="shrink-0 px-2 py-0.5 rounded-full text-xs {{ $ageClass($r->age) }}">{{ $r->age }} hari</span>
                    </div>
                    <p class="mt-2 text-sm text-gray-800 break-words">{!! nl2br(e($r->desc)) !!}</p>
                    <p class="mt-2 text-xs text-gray-500 break-words">{{ $r->project }}</p>
                    @if($r->deadline)
                        <p class="text-xs text-gray-400">Deadline project: {{ $r->deadline }}</p>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Desktop & cetak: tabel --}}
        <div class="hidden lg:block print:block bg-white rounded-xl shadow-sm overflow-hidden break-inside-avoid">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-amber-50 text-amber-800 text-xs">
                        <tr>
                            <th class="px-4 py-2 text-left w-32">Dibuat</th>
                            <th class="px-4 py-2 text-left w-24">Umur</th>
                            <th class="px-4 py-2 text-left w-1/4">Project</th>
                            <th class="px-4 py-2 text-left">Pekerjaan</th>
                            <th class="px-4 py-2 text-left w-36">Deadline project</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($pendingRows as $r)
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap align-top">{{ $r->created->translatedFormat('d M Y') }}</td>
                                <td class="px-4 py-2 whitespace-nowrap align-top">
                                    <span class="px-2 py-0.5 rounded-full text-xs {{ $ageClass($r->age) }}">{{ $r->age }} hari</span>
                                </td>
                                <td class="px-4 py-2 text-gray-600 align-top">{{ $r->project }}</td>
                                <td class="px-4 py-2 break-words">{!! nl2br(e($r->desc)) !!}</td>
                                <td class="px-4 py-2 text-gray-500 align-top">{{ $r->deadline ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection