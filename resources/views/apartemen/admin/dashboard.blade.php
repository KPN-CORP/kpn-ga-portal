@extends('layouts.app_apartadmin_sidebar')
@section('content')
<div class="w-full">

    <x-apartemen.page-header title="Dashboard Apartemen" subtitle="Ringkasan unit, permintaan, dan agenda penempatan" />

    {{-- RINGKASAN: "Penghuni aktif" dan "Lewat jadwal" sama dengan isi halaman Monitoring --}}
    <div class="ap-kpis c5">
        <x-apartemen.stat-card label="Total apartemen" :value="$stats['total_apartemen']" color="blue" :href="route('apartemen.admin.apartemen')"><i class="fas fa-building"></i></x-apartemen.stat-card>
        <x-apartemen.stat-card label="Permintaan pending" :value="$stats['permintaan_pending']" color="yellow" :href="route('apartemen.admin.index')"><i class="far fa-clock"></i></x-apartemen.stat-card>
        <x-apartemen.stat-card label="Penghuni aktif" :value="$stats['penghuni_aktif']" color="green" :href="route('apartemen.admin.monitoring')"><i class="fas fa-user-check"></i></x-apartemen.stat-card>
        <x-apartemen.stat-card label="Lewat jadwal" :value="$stats['lewat_jadwal']" color="red" :href="route('apartemen.admin.monitoring')"><i class="fas fa-hourglass-end"></i></x-apartemen.stat-card>
        <x-apartemen.stat-card label="Maintenance" :value="$stats['unit_maintenance']" color="gray" :href="route('apartemen.admin.apartemen')"><i class="fas fa-tools"></i></x-apartemen.stat-card>
    </div>

    {{-- PERLU TINDAKAN & PERMINTAAN PENDING --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        <div class="ap-card overflow-hidden">
            <div class="ap-listhead">
                <h3>Perlu tindakan: lewat jadwal</h3>
                <a href="{{ route('apartemen.admin.monitoring') }}" class="ap-link">Monitoring</a>
            </div>
            @if($overdueAssigns->count() > 0)
                <div class="ap-list">
                    @foreach($overdueAssigns as $assign)
                    @php $lewat = (int) abs(now()->startOfDay()->diffInDays($assign->tanggal_selesai->copy()->startOfDay(), false)); @endphp
                    <div class="ap-item">
                        <div class="min-w-0">
                            <div class="ap-name truncate">{{ $assign->unit->apartemen->nama_apartemen ?? '-' }} · Unit {{ $assign->unit->nomor_unit ?? '-' }}</div>
                            <div class="ap-sub truncate">{{ $assign->penghuniAktif->pluck('nama')->join(', ') }}</div>
                            <div class="ap-rel late">Lewat {{ $lewat }} hari · selesai {{ $assign->tanggal_selesai->format('d/m/Y') }}{{ $assign->checkin_at ? '' : ' · belum check-in' }}</div>
                        </div>
                        <a href="{{ route('apartemen.admin.monitoring') }}" class="ap-btn ap-btn-red">Tindak</a>
                    </div>
                    @endforeach
                </div>
                @if($stats['lewat_jadwal'] > $overdueAssigns->count())
                    <div class="ap-sub p-3">Menampilkan {{ $overdueAssigns->count() }} teratas. Lihat semuanya di Monitoring.</div>
                @endif
            @else
                <x-apartemen.empty-state title="Tidak ada yang lewat jadwal" text="Semua penempatan yang berakhir sudah diselesaikan." />
            @endif
        </div>

        <div class="ap-card overflow-hidden">
            <div class="ap-listhead">
                <h3>Permintaan pending</h3>
                <a href="{{ route('apartemen.admin.index') }}" class="ap-link">Lihat semua</a>
            </div>
            @if($pendingRequests->count() > 0)
                <div class="ap-list">
                    @foreach($pendingRequests as $req)
                    <div class="ap-item">
                        <div class="min-w-0">
                            <div class="ap-name truncate">{{ $req->user->name ?? 'N/A' }}</div>
                            <div class="ap-sub">{{ $req->penghuni->count() }} penghuni · {{ $req->created_at->format('d/m/Y H:i') }}</div>
                        </div>
                        <a href="{{ route('apartemen.admin.approve', $req->id) }}" class="ap-btn ap-btn-blue">Review</a>
                    </div>
                    @endforeach
                </div>
            @else
                <x-apartemen.empty-state title="Tidak ada permintaan pending" text="Semua permintaan sudah diproses." />
            @endif
        </div>
    </div>

    {{-- CHECK-OUT MENDATANG & UNIT MAINTENANCE --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        <div class="ap-card overflow-hidden">
            <div class="ap-listhead">
                <h3>Check-out hari ini &amp; 7 hari ke depan</h3>
                <a href="{{ route('apartemen.admin.monitoring') }}" class="ap-link">Monitoring</a>
            </div>
            @if($upcomingCheckouts->count() > 0)
                <div class="ap-list">
                    @foreach($upcomingCheckouts as $assign)
                    @php
                        $hari = (int) now()->startOfDay()->diffInDays($assign->tanggal_selesai->copy()->startOfDay(), false);
                        $tone = $hari <= 0 ? 'late' : ($hari <= 2 ? 'soon' : '');
                    @endphp
                    <div class="ap-item">
                        <div class="min-w-0">
                            <div class="ap-name truncate">{{ $assign->unit->apartemen->nama_apartemen ?? '-' }} · Unit {{ $assign->unit->nomor_unit ?? '-' }}</div>
                            <div class="ap-sub truncate">{{ $assign->penghuniAktif->pluck('nama')->join(', ') }}</div>
                            <div class="ap-rel {{ $tone }}">{{ $hari <= 0 ? 'Hari ini' : 'Sisa ' . $hari . ' hari' }} · {{ $assign->tanggal_selesai->format('d/m/Y') }}</div>
                        </div>
                        <a href="{{ route('apartemen.admin.monitoring') }}" class="ap-btn ap-btn-ghost">Monitoring</a>
                    </div>
                    @endforeach
                </div>
            @else
                <x-apartemen.empty-state title="Tidak ada check-out" text="Belum ada penghuni yang selesai hari ini sampai 7 hari ke depan." />
            @endif
        </div>

        <div class="ap-card overflow-hidden">
            <div class="ap-listhead">
                <h3>Unit maintenance</h3>
                <a href="{{ route('apartemen.admin.apartemen') }}" class="ap-link">Kelola unit</a>
            </div>
            @if($maintenanceUnits->count() > 0)
                <div class="ap-list">
                    @foreach($maintenanceUnits as $unit)
                    <div class="ap-item">
                        <div class="min-w-0">
                            <div class="ap-name truncate">{{ $unit->apartemen->nama_apartemen ?? '-' }} · Unit {{ $unit->nomor_unit }}</div>
                            @if($unit->catatan)<div class="ap-sub truncate">{{ $unit->catatan }}</div>@endif
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <x-apartemen.empty-state title="Tidak ada unit maintenance" text="Semua unit tersedia untuk ditempati." />
            @endif
        </div>
    </div>

    {{-- KALENDER --}}
    <div class="ap-card ap-block">
        <h3 class="text-base font-semibold text-gray-900 mb-3">Agenda &amp; penempatan</h3>
        <div id="calendar" class="w-full"></div>
    </div>
</div>

{{-- DETAIL EVENT KALENDER (menggantikan alert bawaan browser) --}}
<div id="evModal" class="ap-modal" role="dialog" aria-modal="true" aria-labelledby="evTitle">
    <div class="ap-panel sm">
        <div class="ap-panel-h">
            <span class="ap-tile t-blue"><i class="far fa-calendar-check"></i></span>
            <div class="min-w-0">
                <h3 id="evTitle">-</h3>
                <p id="evSub">-</p>
            </div>
            <button type="button" class="ap-x" onclick="document.getElementById('evModal').classList.remove('is-open')" aria-label="Tutup"><i class="fas fa-times"></i></button>
        </div>
        <div class="ap-panel-b"><p id="evBody" class="text-sm text-gray-700 leading-relaxed"></p></div>
        <div class="ap-panel-f">
            <button type="button" class="ap-btn ap-btn-lg ap-btn-ghost" onclick="document.getElementById('evModal').classList.remove('is-open')">Tutup</button>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<style>
    #calendar, #calendar .fc, #calendar .fc-view-harness { width: 100% !important; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    const small = window.innerWidth < 768;
    const modal = document.getElementById('evModal');
    modal.addEventListener('mousedown', (e) => { if (e.target === modal) modal.classList.remove('is-open'); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') modal.classList.remove('is-open'); });

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: small ? 'listMonth' : 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: small ? 'dayGridMonth,listMonth' : 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: '{{ route("apartemen.admin.calendar.events") }}',
        eventClick: function (info) {
            const props = info.event.extendedProps;
            document.getElementById('evTitle').textContent = info.event.title;
            if (props.type === 'unit') {
                document.getElementById('evSub').textContent = 'Penempatan unit';
                document.getElementById('evBody').textContent = 'Penghuni: ' + (props.penghuni || '-');
            } else {
                document.getElementById('evSub').textContent = 'Fasilitas';
                document.getElementById('evBody').textContent = 'Jumlah orang: ' + (props.jumlah_orang ?? '-');
            }
            modal.classList.add('is-open');
        },
        height: 'auto'
    });
    calendar.render();

    // Hitung ulang ukuran agar bar event penuh sampai tepi sel (sidebar, zoom, font CDN yang telat load)
    const refresh = () => calendar.updateSize();
    setTimeout(refresh, 150);
    window.addEventListener('load', refresh);
    window.addEventListener('resize', refresh);
    if (window.ResizeObserver) new ResizeObserver(refresh).observe(calendarEl);
    document.getElementById('sidebar-toggle')?.addEventListener('click', () => setTimeout(refresh, 350));
});
</script>
@endsection
