<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Mutasi Supplies</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
        th, td { border: 1px solid #000; padding: 4px; text-align: left; vertical-align: top; word-break: break-word; white-space: normal; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .header { margin-bottom: 20px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { background-color: #f2f2f2; font-weight: bold; }
        /* Penyesuaian lebar kolom (silakan disesuaikan) */
        th:nth-child(1) { width: 7%; }
        th:nth-child(2) { width: 9%; }
        th:nth-child(3) { width: 5%; }
        th:nth-child(4) { width: 12%; }
        th:nth-child(5) { width: 7%; }
        th:nth-child(6) { width: 7%; }
        th:nth-child(7) { width: 7%; }
        th:nth-child(8) { width: 9%; }
        th:nth-child(9) { width: 10%; }
        th:nth-child(10) { width: 8%; }
        th:nth-child(11) { width: 10%; }
        th:nth-child(12) { width: 8%; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Mutasi Supplies</h2>
        <p>Dicetak oleh: {{ $user->name }} ({{ $user->username }})</p>
        <p>Tanggal cetak: {{ date('d M Y H:i') }}</p>
        <p>Filter: Unit: {{ $filter['bisnis_unit'] }} | Barang: {{ $filter['barang'] }} | Periode: {{ $filter['periode'] }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No-Request</th><th>Tanggal</th><th>Jenis</th><th>Barang</th><th>Jumlah</th>
                <th>Harga (Rp)</th><th>Total (Rp)</th><th>Bisnis Unit</th>
                <th>Keperluan</th><th>Request</th><th>Noted Approve</th><th>Approve</th>
            </tr>
        </thead>
        <tbody>
            @php $totalNilaiKeluar = 0; @endphp
            @forelse($transaksi as $t)
            @php
                $harga = $t->barang->harga ?? 0;
                $total = ($t->jenis == 'keluar') ? $t->jumlah * $harga : 0;
                if ($t->jenis == 'keluar') $totalNilaiKeluar += $total;
            @endphp
            <tr>
                <td style="word-break:break-word;">{{ $t->no_ref ?? '-' }}</td>
                <td>{{ $t->tanggal->format('d/m/Y H:i') }}</td>
                <td><span style="background:#{{ $t->jenis=='masuk' ? 'd4edda' : 'f8d7da' }}; padding:2px 5px; border-radius:3px;">{{ ucfirst($t->jenis) }}</span></td>
                <td>{{ $t->barang->nama_barang }}</td>
                <td class="text-right">{{ number_format($t->jumlah, 0, ',', '.') }} {{ $t->barang->satuan }}</td>
                <td class="text-right">{{ number_format($harga, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($total, 0, ',', '.') }}</td>
                <td>{{ $t->bisnisUnit->nama_bisnis_unit ?? '-' }}</td>
                <td>{{ $t->permintaan->keterangan ?? '-' }}</td>
                <td>{{ $t->permintaan->pemohon->name ?? '-' }}</td>
                <td style="word-break:break-word;">{{ $t->keterangan ?? '-' }}</td>
                <td>{{ $t->permintaan->approver->name ?? '-' }}</td>
            </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
        @if($transaksi->count())
        <tfoot>
            <tr class="total-row">
                <td colspan="6" class="text-right"><strong>TOTAL NILAI KELUAR:</strong></td>
                <td class="text-right"><strong>{{ number_format($totalNilaiKeluar, 0, ',', '.') }}</strong></td>
                <td colspan="5"></td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>