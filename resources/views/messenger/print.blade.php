<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tanda Terima - {{ $transaksi->no_transaksi }}</title>

<style>
@page {
    size: A4;
    margin: 8mm 15mm;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 8pt;
    margin: 0;
    padding: 0;
    color: #000;
}

.container {
    display: flex;
    flex-direction: column;
}

.copy {
    flex: 1;
}

.divider {
    text-align: center;
    font-size: 7pt;
    color: #666;
    border-top: 1px dashed #999;
    border-bottom: 1px dashed #999;
    padding: 3mm 0;
    margin: 5mm 0;
}

.header {
    text-align: center;
    margin-bottom: 4mm;
}

.header .title {
    font-weight: bold;
    font-size: 9pt;
}

.header .company {
    font-size: 8pt;
}

.info table {
    width: 100%;
    margin-bottom: 3mm;
}

.info td {
    padding: 1mm 0;
}

.table-item {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 3mm;
}

.table-item th,
.table-item td {
    border: 1px solid #000;
    padding: 2mm;
}

.table-item th {
    background: #f0f0f0;
    text-align: center;
}

.alamat {
    border: 1px solid #ddd;
    background: #f9f9f9;
    padding: 2mm;
    margin-bottom: 5mm;
}
</style>
</head>

<body>
<div class="container">

<!-- ================= COPY 1 ================= -->
<div class="copy">

    <div class="header">
        <div class="title">TANDA TERIMA - 1</div>
        <div class="company">KPN CORP - GAMA TOWER, Jakarta Selatan</div>
    </div>

    <div class="info">
        <table>
            <tr>
                <td width="30%">No. Transaksi</td>
                <td>: {{ $transaksi->no_transaksi }}</td>
            </tr>
            <tr>
                <td>Tanggal</td>
                <td>: {{ \Carbon\Carbon::parse($transaksi->created_at)->format('d-m-Y') }}</td>
            </tr>
            <tr>
                <td>Penerima</td>
                <td>: {{ $transaksi->penerima }}</td>
            </tr>
        </table>
    </div>

    <table class="table-item">
        <thead>
            <tr>
                <th width="8%">NO</th>
                <th width="42%">ITEM</th>
                <th width="50%">KETERANGAN</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td align="center">1</td>
                <td>{{ $transaksi->nama_barang }}</td>
                <td>{{ $transaksi->deskripsi }}</td>
            </tr>
        </tbody>
    </table>

    <div class="alamat">
        <strong>Alamat Tujuan:</strong><br>
        {{ $transaksi->alamat_tujuan }}
    </div>

    <!-- TANDA TANGAN -->
    <table width="100%" style="margin-top:8mm;">
        <tr>
            <td width="50%" align="center"><strong>Diterima oleh,</strong></td>
            <td width="50%" align="center"><strong>Diserahkan oleh,</strong></td>
        </tr>
        <tr>
            <td height="22mm"></td>
            <td></td>
        </tr>
        <tr>
            <td align="center">
                <div style="width:45mm;border-top:1px solid #000;margin:0 auto;"></div>
                ({{ $transaksi->penerima }})
            </td>
            <td align="center">
                <div style="width:45mm;border-top:1px solid #000;margin:0 auto;"></div>
                ({{ $transaksi->pelanggan->nama_pelanggan ?? 'N/A' }})
            </td>
        </tr>
    </table>

</div>

<div class="divider">---------------- Potong di sini ----------------</div>

<!-- ================= COPY 2 ================= -->
<div class="copy">

    <div class="header">
        <div class="title">TANDA TERIMA - 2</div>
        <div class="company">KPN CORP - GAMA TOWER, Jakarta Selatan</div>
    </div>

    <div class="info">
        <table>
            <tr>
                <td width="30%">No. Transaksi</td>
                <td>: {{ $transaksi->no_transaksi }}</td>
            </tr>
            <tr>
                <td>Tanggal</td>
                <td>: {{ \Carbon\Carbon::parse($transaksi->created_at)->format('d-m-Y') }}</td>
            </tr>
            <tr>
                <td>Penerima</td>
                <td>: {{ $transaksi->penerima }}</td>
            </tr>
        </table>
    </div>

    <table class="table-item">
        <thead>
            <tr>
                <th width="8%">NO</th>
                <th width="42%">ITEM</th>
                <th width="50%">KETERANGAN</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td align="center">1</td>
                <td>{{ $transaksi->nama_barang }}</td>
                <td>{{ $transaksi->deskripsi }}</td>
            </tr>
        </tbody>
    </table>

    <div class="alamat">
        <strong>Alamat Tujuan:</strong><br>
        {{ $transaksi->alamat_tujuan }}
    </div>

    <!-- TANDA TANGAN (SAMA PERSIS) -->
    <table width="100%" style="margin-top:8mm;">
        <tr>
            <td width="50%" align="center"><strong>Diterima oleh,</strong></td>
            <td width="50%" align="center"><strong>Diserahkan oleh,</strong></td>
        </tr>
        <tr>
            <td height="22mm"></td>
            <td></td>
        </tr>
        <tr>
            <td align="center">
                <div style="width:45mm;border-top:1px solid #000;margin:0 auto;"></div>
                ({{ $transaksi->penerima }})
            </td>
            <td align="center">
                <div style="width:45mm;border-top:1px solid #000;margin:0 auto;"></div>
                ({{ $transaksi->pelanggan->nama_pelanggan ?? 'N/A' }})
            </td>
        </tr>
    </table>

</div>

</div>
</body>
</html>
