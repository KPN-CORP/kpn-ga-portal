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
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Barang</th>
                <th class="text-right">Jumlah</th>
                <th>Satuan</th>
                <th class="text-right">Harga (Rp)</th>
                <th class="text-right">Nilai (Rp)</th>
                <th>Area Asal</th>
                <th>Area Tujuan</th>
                <th>Keterangan</th>
                <th>User</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transaksi as $item)
            @php
                $harga = $item->barang->harga ?? 0;
                $nilai = $item->jumlah * $harga;
                $grandTotal += $nilai;
            @endphp
            <tr>
                <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d M Y H:i') }}</td>
                <td>{{ ucfirst($item->jenis) }}</td>
                <td>{{ $item->barang->nama_barang ?? '-' }}</td>
                <td class="text-right">{{ number_format($item->jumlah) }}</td>
                <td>{{ $item->barang->satuan ?? '-' }}</td>
                <td class="text-right">{{ number_format($harga, 2) }}</td>
                <td class="text-right">{{ number_format($nilai, 2) }}</td>
                <td>
                    @if($item->areaAsal)
                        {{ $item->areaAsal->nama_area }} ({{ $item->areaAsal->bisnisUnit->nama_bisnis_unit ?? '-' }})
                    @else
                        -
                    @endif
                </td>
                <td>
                    @if($item->areaTujuan)
                        {{ $item->areaTujuan->nama_area }} ({{ $item->areaTujuan->bisnisUnit->nama_bisnis_unit ?? '-' }})
                    @else
                        -
                    @endif
                </td>
                <td>{{ $item->keterangan ?? '-' }}</td>
                <td>{{ $item->user->name ?? '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="11" style="text-align: center;">Tidak ada data</td></tr>
            @endforelse
        </tbody>
        @if($transaksi->count())
        <tfoot>
            <tr class="total-row">
                <td colspan="6" class="text-right"><strong>Total Nilai Transaksi:</strong></td>
                <td class="text-right"><strong>{{ number_format($grandTotal, 2) }}</strong></td>
                <td colspan="4"></td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>