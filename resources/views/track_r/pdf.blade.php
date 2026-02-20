<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tanda Terima</title>

<style>
@page {
    size: A4;
    margin: 20mm;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 10pt;
    margin: 0;
    padding: 0;
    color: #000;
}

/* ===== RECEIPT ===== */
.receipt {
    border: 1px solid #000;
    padding: 8mm 10mm;
    margin-bottom: 12mm;
}

/* ===== HEADER ===== */
.header {
    text-align: center;
    margin-bottom: 6mm;
}

.title {
    font-size: 14pt;
    font-weight: bold;
}

.brand {
    font-size: 8pt;
}

/* ===== META (ATAS) ===== */
.meta {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 5mm;
}

.meta td {
    padding: 2mm 0;
    vertical-align: top;
}

.meta .left {
    width: 50%;
}

.meta .right {
    width: 50%;
}

/* ===== FIELD PANJANG ===== */
.block {
    margin-bottom: 4mm;
}

.block .label {
    font-weight: bold;
}

/* ===== SIGNATURE ===== */
.sign {
    width: 100%;
    margin-top: 8mm;
    text-align: center;
}

.sign td {
    width: 50%;
    vertical-align: top;
}

/* JARAK ANTAR TEKS DAN GARIS */
.sign .role {
    margin-bottom: 10mm;   /* ⬅️ ini bikin turun jauh */
}

/* GARIS TTD */
.line {
    width: 70%;
    border-top: 1px solid #000;
    margin: 0 auto;
}

/* JARAK GARIS KE NAMA */
.name {
    margin-top: 1mm;      /* ⬅️ jarak tambahan ke nama */
}
</style>
</head>

<body>

<!-- ================= COPY 1 ================= -->
<div class="receipt">

    <div class="header">
        <div class="title">TANDA TERIMA</div>
        <div class="brand">DOKUMEN · by GA Portal</div>
    </div>

    <table class="meta">
        <tr>
            <td class="left"><strong>No Dokumen</strong> : {{ $document->nomor_dokumen }}</td>
            <td class="right"><strong>Pengirim</strong> : {{ $document->pengirim->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="left"><strong>Tanggal</strong> : {{ $document->created_at->format('d-m-Y') }}</td>
            <td class="right"><strong>Penerima</strong> : {{ $document->penerima->name ?? '-' }}</td>
        </tr>
    </table>

    <div class="block">
        <div class="label">Judul Dokumen :</div>
        {{ $document->judul }}
    </div>

    <div class="block">
        <div class="label">Keterangan :</div>
        {{ $document->keterangan ?? '-' }}
    </div>

    <table class="sign">
        <tr>
            <td>
                <div class="role">Pengirim</div>
                <div class="line"></div>
                <div class="name">{{ $document->pengirim->name ?? '-' }}</div>
            </td>
            <td>
                <div class="role">Penerima</div>
                <div class="line"></div>
                <div class="name">{{ $document->penerima->name ?? '-' }}</div>
            </td>
        </tr>
    </table>

</div>

<!-- ================= COPY 2 ================= -->
<div class="receipt">

    <div class="header">
        <div class="title">TANDA TERIMA</div>
        <div class="brand">DOKUMEN · by GA Portal</div>
    </div>

    <table class="meta">
        <tr>
            <td class="left"><strong>No Dokumen</strong> : {{ $document->nomor_dokumen }}</td>
            <td class="right"><strong>Pengirim</strong> : {{ $document->pengirim->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="left"><strong>Tanggal</strong> : {{ $document->created_at->format('d-m-Y') }}</td>
            <td class="right"><strong>Penerima</strong> : {{ $document->penerima->name ?? '-' }}</td>
        </tr>
    </table>

    <div class="block">
        <div class="label">Judul Dokumen :</div>
        {{ $document->judul }}
    </div>

    <div class="block">
        <div class="label">Keterangan :</div>
        {{ $document->keterangan ?? '-' }}
    </div>

    <table class="sign">
        <tr>
            <td>
                <div class="role">Pengirim</div>
                <div class="line"></div>
                <div class="name">{{ $document->pengirim->name ?? '-' }}</div>
            </td>
            <td>
                <div class="role">Penerima</div>
                <div class="line"></div>
                <div class="name">{{ $document->penerima->name ?? '-' }}</div>
            </td>
        </tr>
    </table>

</div>

</body>
</html>