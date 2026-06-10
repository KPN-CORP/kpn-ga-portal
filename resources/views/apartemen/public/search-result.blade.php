{{-- resources/views/apartemen/public/search-result.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Hasil Pencarian</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Pannellum CSS & JS untuk viewer 360 interaktif --}}
    <link rel="stylesheet" href="https://cdn.pannellum.org/2.5/pannellum.css"/>
    <script src="https://cdn.pannellum.org/2.5/pannellum.js"></script>

    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f3f4f6;
        }

        .page-wrapper {
            padding: 40px 16px;
        }

        .result-container {
            max-width: 720px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 12px 30px rgba(0,0,0,.08);
            border: 1px solid #e5e7eb;
            padding: 24px;
        }

        .title {
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 24px;
            color: #1f2937;
        }

        .title span {
            color: #2563eb;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            margin-bottom: 16px;
            overflow: hidden;
            background: #fff;
            transition: box-shadow .2s ease;
        }

        .card:hover {
            box-shadow: 0 8px 20px rgba(0,0,0,.08);
        }

        .card-header {
            padding: 14px 16px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .name {
            font-size: 15px;
            font-weight: 600;
            color: #111827;
        }

        .sub {
            font-size: 12px;
            color: #6b7280;
            margin-top: 2px;
        }

        .badge {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 999px;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge.active {
            background: #dcfce7;
            color: #166534;
        }

        .badge.done {
            background: #e5e7eb;
            color: #4b5563;
        }

        .badge.pending {
            background: #fef9c3;
            color: #854d0e;
        }

        .location {
            padding: 0 16px 12px;
        }

        .location-box {
            background: #eff6ff;
            border-radius: 10px;
            padding: 10px 12px;
        }

        .location-box strong {
            display: block;
            font-size: 14px;
            color: #1f2937;
        }

        .location-box span {
            font-size: 12px;
            color: #374151;
        }

        .checkin-status {
            font-size: 11px;
            margin-top: 4px;
        }
        .checkin-status.sudah {
            color: #16a34a;
        }
        .checkin-status.belum {
            color: #ca8a04;
        }

        .period {
            padding: 0 16px 12px;
        }

        .period small {
            font-size: 12px;
            color: #6b7280;
        }

        .period p {
            margin: 4px 0;
            font-size: 14px;
            color: #1f2937;
        }

        .remaining {
            font-size: 12px;
            font-weight: 600;
            color: #2563eb;
        }

        .remaining.danger {
            color: #dc2626;
        }

        .extra {
            border-top: 1px solid #f3f4f6;
            padding: 10px 16px;
            font-size: 12px;
            color: #4b5563;
            line-height: 1.6;
        }

        .action {
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            padding: 12px 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .btn-checkin, .btn-checkout {
            width: 100%;
            padding: 10px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-checkin {
            color: #16a34a;
            border: 1px solid #bbf7d0;
            background: #ffffff;
        }

        .btn-checkin:hover:not(:disabled) {
            background: #f0fdf4;
        }

        .btn-checkout {
            color: #dc2626;
            border: 1px solid #fecaca;
            background: #ffffff;
        }

        .btn-checkout:hover:not(:disabled) {
            background: #fef2f2;
        }

        .btn-checkin:disabled, .btn-checkout:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .empty {
            text-align: center;
            padding: 60px 0;
            color: #6b7280;
        }

        .early-checkin-notice {
            text-align: center;
            padding: 12px;
            background: #fef9c3;
            border: 1px solid #fde047;
            border-radius: 10px;
            width: 100%;
        }

        .early-checkin-notice .icon {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            margin-bottom: 4px;
            font-weight: 600;
            color: #854d0e;
            font-size: 13px;
        }

        .early-checkin-notice .details {
            color: #92400e;
            font-size: 12px;
        }

        .early-checkin-notice .countdown {
            margin-top: 2px;
        }

        .early-checkin-notice .info {
            margin-top: 4px;
            font-size: 11px;
            color: #b45309;
            border-top: 1px dashed #fde047;
            padding-top: 4px;
        }

        .already-checkin {
            text-align: center;
            padding: 10px;
            background: #f0fdf4;
            border-radius: 10px;
            color: #166534;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            width: 100%;
        }

        .no-access-notice {
            text-align: center;
            padding: 10px;
            background: #f3f4f6;
            border-radius: 10px;
            color: #6b7280;
            font-size: 12px;
            width: 100%;
        }

        .no-access-notice span {
            display: block;
            font-weight: 600;
            color: #9ca3af;
        }

        /* Tombol 360 */
        .btn-360 {
            background: #2563eb;
            border: none;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.2s;
        }
        .btn-360:hover {
            background: #1d4ed8;
        }

        /* Modal 360 */
        #modal360 {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        #modal360 .close-btn {
            position: absolute;
            top: 20px;
            right: 30px;
            background: white;
            border: none;
            border-radius: 40px;
            padding: 8px 20px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            z-index: 10001;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        #panorama {
            width: 95%;
            height: 85vh;
            border-radius: 12px;
            box-shadow: 0 0 30px rgba(0,0,0,0.5);
        }
        @media (min-width: 480px) {
            .action {
                flex-direction: row;
            }
        }
    </style>
</head>
<body>

<div class="page-wrapper">
    <div class="result-container">

        <div class="title">
            Hasil untuk <span>"{{ $search }}"</span>
        </div>

        @if($penghuni->count() > 0)

            @foreach($penghuni as $p)
            @php
                $assign = $p->assign;
                $unit = $assign?->unit;
                $apartemen = $unit?->apartemen;
                $accessCode = session('access_code_data');
                $today = now()->startOfDay();

                $sudahCheckin = $assign && $assign->checkin_at ? true : false;
                $sudahWaktunyaCheckin = $assign && $assign->tanggal_mulai->startOfDay()->lessThanOrEqualTo($today);
                $daysUntilCheckin = $assign && $assign->tanggal_mulai > now() 
                    ? now()->startOfDay()->diffInDays($assign->tanggal_mulai->startOfDay(), false) 
                    : 0;

                $bisaCheckin = $accessCode 
                    && $p->status === 'AKTIF' 
                    && !$sudahCheckin 
                    && $sudahWaktunyaCheckin
                    && in_array($accessCode->tipe, ['CHECKIN', 'BOTH']);
                    
                $bisaCheckout = $accessCode 
                    && $p->status === 'AKTIF' 
                    && $sudahCheckin
                    && in_array($accessCode->tipe, ['CHECKOUT', 'BOTH']);

                $tanggalMulai = $assign?->tanggal_mulai?->format('d/m/Y') ?? '-';
                $tanggalSelesai = $assign?->tanggal_selesai?->format('d/m/Y') ?? '-';
                $sisaHari = $assign && $assign->tanggal_selesai
                    ? round(now()->diffInDays($assign->tanggal_selesai, false))
                    : null;
            @endphp

            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="name">{{ $p->nama }}</div>
                        <div class="sub">ID {{ $p->id_karyawan }}</div>
                    </div>
                    <span class="badge {{ $p->status === 'AKTIF' ? 'active' : 'done' }}">
                        {{ $p->status === 'AKTIF' ? 'Aktif' : 'Selesai' }}
                    </span>
                </div>

                @if($apartemen && $unit)
                <div class="location">
                    <div class="location-box">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                            <div>
                                <strong>{{ $apartemen->nama_apartemen }}</strong><br>
                                <span>Unit {{ $unit->nomor_unit }}</span>
                            </div>
                            @if($unit->gambar_360)
                                <button class="btn-360" onclick="open360Modal('{{ Storage::url($unit->gambar_360) }}')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <path d="M12 2a15 15 0 0 0 0 20 15 15 0 0 0 0-20z"/>
                                        <path d="M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20z"/>
                                        <line x1="2" y1="12" x2="22" y2="12"/>
                                        <line x1="12" y1="2" x2="12" y2="22"/>
                                    </svg>
                                    Lihat 360
                                </button>
                            @endif
                        </div>
                        @if($assign)
                            <div class="checkin-status {{ $sudahCheckin ? 'sudah' : 'belum' }}">
                                @if($sudahCheckin)
                                    ✓ Sudah check-in {{ $assign->checkin_at->format('d/m H:i') }}
                                @else
                                    ⏳ Belum check-in
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                @endif

                @if($assign)
                <div class="period">
                    <small>Periode Hunian</small>
                    <p>{{ $tanggalMulai }} → {{ $tanggalSelesai }}</p>

                    @if($p->status === 'AKTIF' && $sisaHari !== null)
                        <div class="remaining {{ $sisaHari <= 3 ? 'danger' : '' }}">
                            Sisa {{ $sisaHari }} hari
                        </div>
                    @endif
                </div>
                @endif

                @if($p->no_hp || $p->unit_kerja)
                <div class="extra">
                    @if($p->no_hp) 📞 {{ $p->no_hp }}<br>@endif
                    @if($p->unit_kerja) 🏢 {{ $p->unit_kerja }} @endif
                </div>
                @endif

                {{-- ACTION SECTION --}}
                <div class="action">
                    @if($p->status === 'AKTIF' && !$sudahCheckin && $assign && !$sudahWaktunyaCheckin)
                        <div class="early-checkin-notice">
                            <div class="icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#854d0e" stroke-width="2">
                                    <circle cx="12" cy="12" r="10" />
                                    <polyline points="12 6 12 12 16 14" />
                                </svg>
                                <span>Belum Waktunya Check-in</span>
                            </div>
                            <div class="details">
                                <div>Mulai: <strong>{{ $assign->tanggal_mulai->format('d/m/Y') }}</strong></div>
                                @if($daysUntilCheckin > 0)
                                <div class="countdown">⏳ {{ $daysUntilCheckin }} hari lagi</div>
                                @endif
                                <div class="info">
                                    Check-in hanya dapat dilakukan mulai H-0
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($p->status === 'AKTIF' && !$sudahCheckin && $assign && $sudahWaktunyaCheckin)
                        @if($bisaCheckin)
                            <form action="{{ route('apartemen.public.checkin', $p->id) }}"
                                  method="POST"
                                  style="width: 100%;"
                                  onsubmit="return confirm('Yakin ingin check-in untuk {{ addslashes($p->nama) }}?')">
                                @csrf
                                <button type="submit" class="btn-checkin">Check-in</button>
                            </form>
                        @else
                            <div class="no-access-notice">
                                <span>Check-in</span>
                                <span style="font-size: 11px;">Kode akses tidak valid</span>
                            </div>
                        @endif
                    @endif

                    @if($sudahCheckin)
                        <div class="already-checkin">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#166534" stroke-width="2">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                            <span>Sudah Check-in {{ $assign->checkin_at->format('d/m H:i') }}</span>
                        </div>
                    @endif

                    @if($bisaCheckout)
                        <form action="{{ route('apartemen.public.checkout', $p->id) }}"
                              method="POST"
                              style="width: 100%;"
                              onsubmit="return confirm('Yakin ingin check-out untuk {{ addslashes($p->nama) }}?')">
                            @csrf
                            <button type="submit" class="btn-checkout">Check-out</button>
                        </form>
                    @elseif($p->status === 'AKTIF' && $sudahCheckin && !$bisaCheckout && $accessCode)
                        <div class="no-access-notice">
                            <span>Check-out</span>
                            <span style="font-size: 11px;">Kode akses tidak valid untuk check-out</span>
                        </div>
                    @endif
                </div>
            </div>
            @endforeach

        @else
            <div class="empty">
                😕<br>
                Tidak ada hasil untuk "{{ $search }}"
            </div>
        @endif

    </div>
</div>

{{-- MODAL 360 dengan Pannellum --}}
<div id="modal360">
    <button class="close-btn" onclick="close360Modal()">✕ Tutup</button>
    <div id="panorama"></div>
</div>

<script>
    let viewer = null;

    function open360Modal(imageUrl) {
        const modal = document.getElementById('modal360');
        modal.style.display = 'flex';

        // Hancurkan viewer sebelumnya jika ada
        if (viewer) {
            viewer.destroy();
            viewer = null;
        }

        // Inisialisasi Pannellum
        viewer = pannellum.viewer('panorama', {
            type: 'equirectangular',
            panorama: imageUrl,
            autoLoad: true,
            showZoomCtrl: true,
            showFullscreenCtrl: true,
            compass: true,
            hotSpotDebug: false
        });
    }

    function close360Modal() {
        const modal = document.getElementById('modal360');
        modal.style.display = 'none';
        if (viewer) {
            viewer.destroy();
            viewer = null;
        }
        // Kosongkan container untuk menghindari残留
        document.getElementById('panorama').innerHTML = '';
        // Re-inisialisasi container (Pannellum akan membuat ulang)
        // Tidak perlu, karena destroy sudah membersihkan
    }

    // Tutup modal jika klik di luar area panorama (pada background modal)
    document.getElementById('modal360').addEventListener('click', function(e) {
        if (e.target === this) {
            close360Modal();
        }
    });
</script>

</body>
</html>