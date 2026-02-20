<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Tiket {{ $tiket->nomor_tiket }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2563eb;
        }
        .header h1 {
            color: #2563eb;
            font-size: 22px;
            margin: 0 0 5px;
        }
        .header .nomor-tiket {
            font-size: 14px;
            color: #666;
        }
        .header .tanggal-cetak {
            font-size: 10px;
            color: #999;
            margin-top: 5px;
        }
        
        /* Badge */
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            margin: 0 2px;
        }
        .badge-open { background: #fef3c7; color: #92400e; }
        .badge-process { background: #dbeafe; color: #1e40af; }
        .badge-waiting { background: #ffedd5; color: #9a3412; }
        .badge-done { background: #dcfce7; color: #166534; }
        .badge-closed { background: #f3f4f6; color: #374151; }
        .badge-urgent { background: #fee2e2; color: #991b1b; }
        .badge-high { background: #ffedd5; color: #9a3412; }
        .badge-medium { background: #dbeafe; color: #1e40af; }
        .badge-low { background: #f3f4f6; color: #374151; }
        
        /* Tipe badge */
        .tipe-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .tipe-initial {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .tipe-follow-up {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        .tipe-completion {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        /* Info box */
        .info-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            padding: 12px;
            margin-bottom: 15px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #2563eb;
            margin: 0 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        td {
            padding: 5px;
            vertical-align: top;
        }
        .label {
            width: 120px;
            font-weight: bold;
            color: #4b5563;
        }
        
        /* Description */
        .description {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 15px;
            white-space: pre-line;
        }
        
        /* Timeline */
        .timeline-item {
            padding-left: 15px;
            padding-bottom: 10px;
            border-left: 2px solid #2563eb;
            margin-left: 5px;
        }
        .timeline-status {
            font-weight: bold;
            color: #2563eb;
            font-size: 11px;
        }
        .timeline-date {
            font-size: 9px;
            color: #6b7280;
        }
        
        /* Footer */
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            color: #999;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <div class="header">
        <h1>DETAIL TIKET GA HELPDESK</h1>
        <div class="nomor-tiket">{{ $tiket->nomor_tiket }}</div>
        <div class="tanggal-cetak">Dicetak: {{ $generated_at }} | Oleh: {{ $generated_by }}</div>
    </div>

    <!-- INFORMASI TIKET & PERSONIL (Digabung) -->
    <div class="info-box">
        <div class="section-title">INFORMASI TIKET & PERSONIL</div>
        <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
            <tr>
                <td style="width: 25%; padding: 6px; background-color: #f0f4ff; font-weight: bold; border: 1px solid #d1d5db;">Nomor Tiket</td>
                <td style="width: 25%; padding: 6px; border: 1px solid #d1d5db;">{{ $tiket->nomor_tiket }}</td>
                <td style="width: 25%; padding: 6px; background-color: #f0f4ff; font-weight: bold; border: 1px solid #d1d5db;">Status</td>
                <td style="width: 25%; padding: 6px; border: 1px solid #d1d5db;">
                    @php
                        $statusClass = match($tiket->status) {
                            'OPEN' => 'badge-open',
                            'ON_PROCESS' => 'badge-process',
                            'WAITING' => 'badge-waiting',
                            'DONE' => 'badge-done',
                            'CLOSED' => 'badge-closed',
                            default => ''
                        };
                        $priorityClass = match($tiket->prioritas) {
                            'URGENT' => 'badge-urgent',
                            'HIGH' => 'badge-high',
                            'MEDIUM' => 'badge-medium',
                            'LOW' => 'badge-low',
                            default => ''
                        };
                    @endphp
                    <span class="badge {{ $statusClass }}">{{ $statusLabels[$tiket->status] ?? $tiket->status }}</span>
                    <span class="badge {{ $priorityClass }}">{{ $tiket->prioritas }}</span>
                </td>
            </tr>
            <tr>
                <td style="padding: 6px; background-color: #f0f4ff; font-weight: bold; border: 1px solid #d1d5db;">Judul</td>
                <td style="padding: 6px; border: 1px solid #d1d5db;" colspan="3"><strong>{{ $tiket->judul }}</strong></td>
            </tr>
            <tr>
                <td style="padding: 6px; background-color: #f0f4ff; font-weight: bold; border: 1px solid #d1d5db;">Kategori</td>
                <td style="padding: 6px; border: 1px solid #d1d5db;">{{ $tiket->kategori->nama ?? '-' }}</td>
                <td style="padding: 6px; background-color: #f0f4ff; font-weight: bold; border: 1px solid #d1d5db;">Bisnis Unit</td>
                <td style="padding: 6px; border: 1px solid #d1d5db;">{{ $tiket->bisnisUnit->nama_bisnis_unit ?? '-' }}</td>
            </tr>
            <tr>
                <td style="padding: 6px; background-color: #f0f4ff; font-weight: bold; border: 1px solid #d1d5db;">Dilaporkan Oleh</td>
                <td style="padding: 6px; border: 1px solid #d1d5db;">
                    {{ $tiket->pelapor->user->name ?? $tiket->pelapor->nama ?? '-' }}
                    @if($tiket->pelapor && $tiket->pelapor->user)
                        <div style="font-size: 9px; color: #6b7280;">{{ $tiket->pelapor->user->email }}</div>
                    @endif
                </td>
                <td style="padding: 6px; background-color: #f0f4ff; font-weight: bold; border: 1px solid #d1d5db;">Penanggung Jawab</td>
                <td style="padding: 6px; border: 1px solid #d1d5db;">
                    {{ $tiket->ditugaskanKe->user->name ?? $tiket->ditugaskanKe->nama ?? 'Belum ditugaskan' }}
                    @if($tiket->ditugaskanKe && $tiket->ditugaskanKe->user)
                        <div style="font-size: 9px; color: #6b7280;">{{ $tiket->ditugaskanKe->user->email }}</div>
                    @endif
                </td>
            </tr>
            <tr>
                <td style="padding: 6px; background-color: #f0f4ff; font-weight: bold; border: 1px solid #d1d5db;">Tanggal Dibuat</td>
                <td style="padding: 6px; border: 1px solid #d1d5db;">{{ $tiket->created_at->format('d/m/Y H:i') }}</td>
                <td style="padding: 6px; background-color: #f0f4ff; font-weight: bold; border: 1px solid #d1d5db;">Terakhir Update</td>
                <td style="padding: 6px; border: 1px solid #d1d5db;">{{ $tiket->updated_at->format('d/m/Y H:i') }}</td>
            </tr>
            @if($tiket->diproses_pada)
            <tr>
                <td style="padding: 6px; background-color: #f0f4ff; font-weight: bold; border: 1px solid #d1d5db;">Mulai Diproses</td>
                <td style="padding: 6px; border: 1px solid #d1d5db;" colspan="3">{{ $tiket->diproses_pada->format('d/m/Y H:i') }}</td>
            </tr>
            @endif
            @if($tiket->diselesaikan_pada || $tiket->ditutup_pada)
            <tr>
                <td style="padding: 6px; background-color: #f0f4ff; font-weight: bold; border: 1px solid #d1d5db;">Diselesaikan/Ditutup</td>
                <td style="padding: 6px; border: 1px solid #d1d5db;" colspan="3">
                    @if($tiket->diselesaikan_pada) Selesai: {{ $tiket->diselesaikan_pada->format('d/m/Y H:i') }} @endif
                    @if($tiket->ditutup_pada) | Ditutup: {{ $tiket->ditutup_pada->format('d/m/Y H:i') }} @endif
                </td>
            </tr>
            @endif
            @if($tiket->catatan_penyelesaian)
            <tr>
                <td style="padding: 6px; background-color: #f0f4ff; font-weight: bold; border: 1px solid #d1d5db;">Catatan Penyelesaian</td>
                <td style="padding: 6px; border: 1px solid #d1d5db;" colspan="3">{{ $tiket->catatan_penyelesaian }}</td>
            </tr>
            @endif
        </table>
    </div>

    <!-- DESKRIPSI MASALAH -->
    <div class="info-box">
        <div class="section-title">DESKRIPSI MASALAH</div>
        <div class="description">{{ $tiket->deskripsi ?: 'Tidak ada deskripsi' }}</div>
    </div>

    <!-- LAMPIRAN & FOTO -->
    @if($tiket->lampiran && $tiket->lampiran->count() > 0)
    <div class="info-box">
        <div class="section-title">LAMPIRAN ({{ $tiket->lampiran->count() }})</div>
        
        <!-- TABEL LAMPIRAN DENGAN TIPE DARI DATABASE -->
        <table style="width: 100%; border-collapse: collapse; font-size: 10px;" cellpadding="5">
            <thead>
                <tr style="background: #2563eb; color: white;">
                    <th style="padding: 8px; text-align: center;">No</th>
                    <th style="padding: 8px; text-align: center;">Preview</th>
                    <th style="padding: 8px; text-align: left;">Nama File</th>
                    <th style="padding: 8px; text-align: center;">Tipe</th>
                    <th style="padding: 8px; text-align: center;">Ukuran</th>
                    <th style="padding: 8px; text-align: left;">Pengunggah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tiket->lampiran as $index => $file)
                    @php
                        $fileId = $file->id;
                        $isImage = str_contains($file->tipe_file, 'image') || 
                            in_array(strtolower(pathinfo($file->nama_file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
                        $hasImage = $isImage && isset($lampiranBase64[$fileId]) && !empty($lampiranBase64[$fileId]);
                        
                        // Ambil tipe dari database
                        $tipe = $file->tipe ?? 'OTHER';
                        
                        // Tentukan kelas badge berdasarkan tipe
                        $tipeClass = match($tipe) {
                            'INITIAL' => 'tipe-initial',
                            'FOLLOW_UP' => 'tipe-follow-up',
                            'COMPLETION' => 'tipe-completion',
                            default => ''
                        };
                        
                        // Label untuk ditampilkan
                        $tipeLabel = match($tipe) {
                            'INITIAL' => 'Awal',
                            'FOLLOW_UP' => 'Diskusinya',
                            'COMPLETION' => 'Selesai',
                            default => $tipe
                        };
                        
                        // Baris ganjil/genap untuk zebra striping
                        $rowClass = $index % 2 == 0 ? 'background: #f9fafb;' : 'background: white;';
                    @endphp
                    <tr style="border-bottom: 1px solid #e5e7eb; {{ $rowClass }}">
                        <td style="padding: 8px; text-align: center; vertical-align: middle;">{{ $index + 1 }}</td>
                        
                        <!-- Kolom Preview -->
                        <td style="padding: 8px; text-align: center; vertical-align: middle; width: 100px;">
                            @if($isImage)
                                @if($hasImage)
                                    <img src="{{ $lampiranBase64[$fileId] }}" alt="{{ $file->nama_file }}" 
                                        style="max-width: 80px; max-height: 60px; object-fit: contain; border-radius: 4px; border: 1px solid #d1d5db; background: #f9fafb;">
                                @else
                                    <div style="width: 80px; height: 60px; display: flex; align-items: center; justify-content: center; background: #f3f4f6; border-radius: 4px; color: #9ca3af; font-size: 20px; margin: 0 auto;">
                                        📷
                                    </div>
                                    <div style="font-size: 7px; color: #ef4444; margin-top: 2px;">Gagal load</div>
                                @endif
                            @else
                                <!-- Untuk file non-gambar, tampilkan ikon file -->
                                <div style="width: 80px; height: 60px; display: flex; align-items: center; justify-content: center; background: #f3f4f6; border-radius: 4px; color: #6b7280; font-size: 24px; margin: 0 auto;">
                                    📄
                                </div>
                            @endif
                        </td>
                        
                        <td style="padding: 8px; text-align: left; vertical-align: middle;">{{ $file->nama_file }}</td>
                        
                        <!-- Kolom Tipe - Sekarang menampilkan tipe dari database dengan badge -->
                        <td style="padding: 8px; text-align: center; vertical-align: middle;">
                            <span class="tipe-badge {{ $tipeClass }}">{{ $tipeLabel }}</span>
                        </td>
                        
                        <td style="padding: 8px; text-align: center; vertical-align: middle;">{{ round($file->ukuran_file / 1024, 1) }} KB</td>
                        <td style="padding: 8px; text-align: left; vertical-align: middle;">{{ $file->pengguna->user->name ?? $file->pengguna->nama ?? 'System' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- RIWAYAT STATUS -->
    @if($tiket->logStatus && $tiket->logStatus->count() > 0)
    <div class="info-box">
        <div class="section-title">RIWAYAT STATUS</div>
        @foreach($tiket->logStatus as $log)
        <div class="timeline-item">
            <div class="timeline-status">{{ $statusLabels[$log->status_baru] ?? $log->status_baru }}</div>
            <div class="timeline-date">
                {{ $log->created_at->format('d/m/Y H:i') }} - 
                {{ $log->pengguna->user->name ?? $log->pengguna->nama ?? 'System' }}
            </div>
            @if($log->catatan)
            <div style="font-size: 10px; margin-top: 3px; color: #4b5563;">{{ $log->catatan }}</div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <!-- FOOTER -->
    <div class="footer">
        <div>Dokumen ini digenerate secara otomatis dari Sistem GA Portal</div>
    </div>
</body>
</html>