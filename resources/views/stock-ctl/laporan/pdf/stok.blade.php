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
        .header h2 { margin: 0; }
        .filter { margin-bottom: 10px; font-size: 11px; color: #555; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $judul }}</h2>
        <p>Dicetak oleh: {{ $user->name }} ({{ $user->username }})</p>
        <p>Tanggal cetak: {{ date('d M Y H:i') }}</p>
        <div class="filter">
            <strong>Filter:</strong> Area: {{ $filter['area'] }} | Barang: {{ $filter['barang'] }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Area</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Satuan</th>
                <th class="text-right">Stok</th>
                <th class="text-right">Stok Minimum</th>
                <th>Status</th>
                <th>Update Terakhir</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stok as $item)
            <tr>
                <td>{{ $item->areaKerja->nama_area ?? '-' }} ({{ $item->areaKerja->bisnisUnit->nama_bisnis_unit ?? '-' }})</td>
                <td>{{ $item->barang->kode_barang ?? '-' }}</td>
                <td>{{ $item->barang->nama_barang ?? '-' }}</td>
                <td>{{ $item->barang->satuan ?? '-' }}</td>
                <td class="text-right">{{ number_format($item->jumlah) }}</td>
                <td class="text-right">{{ number_format($item->stok_minimum) }}</td>
                <td>{{ $item->jumlah <= $item->stok_minimum ? 'Menipis' : 'Aman' }}</td>
                <td>{{ $item->last_update ? \Carbon\Carbon::parse($item->last_update)->format('d M Y H:i') : '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align: center;">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>