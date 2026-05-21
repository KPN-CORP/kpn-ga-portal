<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $judul }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #f2f2f2; border: 1px solid #000; padding: 6px; text-align: left; }
        td { border: 1px solid #000; padding: 6px; }
        .header { margin-bottom: 20px; }
        .filter { margin-bottom: 10px; font-size: 11px; color: #555; }
        .text-right { text-align: right; }
        .total-row { background-color: #f2f2f2; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $judul }}</h2>
        <p>Dicetak oleh: {{ $user->name }} ({{ $user->username }})</p>
        <p>Tanggal cetak: {{ date('d M Y H:i') }}</p>
        <div class="filter">
            <strong>Filter:</strong> Area: {{ $filter['area'] }} | Barang: {{ $filter['barang'] }} | Periode: {{ $filter['periode'] }}
        </div>
    </div>

    @php
        $grandTotal = 0;
    @endphp

    <table>
        <thead>
            <tr>
                <th>No. Permintaan</th>
                <th>Tanggal</th>
                <th>Pemohon</th>
                <th>Unit</th>
                <th>Area</th>
                <th>Barang</th>
                <th class="text-right">Jumlah</th>
                <th>Satuan</th>
                <th class="text-right">Harga (Rp)</th>
                <th class="text-right">Nilai (Rp)</th>
                <th>Status</th>
                <th>Approver L1</th>
                <th>Approver Admin</th>
            </tr>
        </thead>
        <tbody>
            @forelse($permintaan as $item)
            @php
                $harga = $item->barang->harga ?? 0;
                // Hanya status 'disetujui' yang dihitung nilainya, selain itu 0
                $nilai = ($item->status == 'disetujui') ? ($item->jumlah * $harga) : 0;
                $grandTotal += $nilai;
            @endphp
            <tr>
                <td>G-SC-{{ $item->id_permintaan }}</td>
                <td>{{ \Carbon\Carbon::parse($item->tanggal_permintaan)->format('d M Y H:i') }}</td>
                <td>{{ $item->pemohon->name ?? '-' }}</td>
                <td>
                    @if($item->pemohon && $item->pemohon->profil && $item->pemohon->profil->unit)
                        {{ explode(' (', $item->pemohon->profil->unit)[0] }}
                    @else
                        -
                    @endif
                </td>
                <td>
                    @if($item->areaKerja)
                        {{ $item->areaKerja->nama_area }} ({{ $item->areaKerja->bisnisUnit->nama_bisnis_unit ?? '-' }})
                    @else
                        -
                    @endif
                </td>
                <td>{{ $item->barang->nama_barang ?? '-' }}</td>
                <td class="text-right">{{ number_format($item->jumlah) }}</td>
                <td>{{ $item->barang->satuan ?? '-' }}</td>
                <td class="text-right">{{ number_format($harga, 2) }}</td>
                <td class="text-right">{{ number_format($nilai, 2) }}</td>
                <td>
                    @if($item->status == 'pending_l1') Menunggu L1
                    @elseif($item->status == 'pending_admin') Menunggu Admin
                    @elseif($item->status == 'disetujui') Disetujui
                    @else Ditolak @endif
                </td>
                <td>{{ $item->approverL1->name ?? '-' }}</td>
                <td>{{ $item->approverAdmin->name ?? '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="13" style="text-align: center;">Tidak ada数据</td></tr>
            @endforelse
        </tbody>
        @if($permintaan->count())
        <tfoot>
            <tr class="total-row">
                <td colspan="9" class="text-right"><strong>Total Nilai (Hanya Disetujui):</strong></td>
                <td class="text-right"><strong>{{ number_format($grandTotal, 2) }}</strong></td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>