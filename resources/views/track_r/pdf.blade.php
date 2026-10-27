<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tanda Terima {{ $document->nomor_dokumen }}</title>

{{-- CSS sengaja sederhana (tabel, tanpa flex/grid) agar aman dirender DomPDF --}}
<style>
@page {
    size: A4;
    margin: 15mm 18mm;
}

body {
    font-family: "DejaVu Sans", Arial, sans-serif;
    font-size: 9pt;
    line-height: 1.45;
    color: #111;
    margin: 0;
    padding: 0;
}

/* ===== SALINAN ===== */
.receipt {
    border: 1px solid #111;
    padding: 7mm 9mm;
    page-break-inside: avoid;
}

.cut {
    border-top: 1px dashed #888;
    margin: 7mm 0;
    height: 0;
}

/* ===== HEADER ===== */
.head {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 5mm;
    border-bottom: 1px solid #111;
}
.head td {
    padding: 0 0 3mm 0;
    vertical-align: bottom;
}
.title {
    font-size: 14pt;
    font-weight: bold;
    letter-spacing: 0.5pt;
}
.brand {
    font-size: 7.5pt;
    color: #555;
}
.copy {
    text-align: right;
    font-size: 7.5pt;
    color: #555;
}

/* ===== META: label | : | nilai ===== */
.meta {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 4mm;
}
.meta td {
    padding: 1.2mm 0;
    vertical-align: top;
}
.meta .k { width: 17%; color: #444; }
.meta .c { width: 3%; }
.meta .v { width: 30%; font-weight: bold; padding-right: 4mm; }

/* ===== BLOK TEKS ===== */
.block { margin-bottom: 4mm; }
.block .label {
    font-weight: bold;
    margin-bottom: 0.8mm;
}
.block .value {
    border: 1px solid #bbb;
    padding: 2mm 3mm;
    min-height: 6mm;
}

/* ===== TANDA TANGAN ===== */
.sign {
    width: 100%;
    margin-top: 6mm;
    border-collapse: collapse;
    text-align: center;
}
.sign td {
    width: 50%;
    vertical-align: top;
}
.sign .role { margin-bottom: 16mm; }
.sign .line {
    width: 70%;
    border-top: 1px solid #111;
    margin: 0 auto;
}
.sign .name {
    margin-top: 1mm;
    font-weight: bold;
}
</style>
</head>
<body>

@foreach(['Salinan pengirim', 'Salinan penerima'] as $copyLabel)
    <div class="receipt">

        <table class="head">
            <tr>
                <td>
                    <div class="title">TANDA TERIMA</div>
                    <div class="brand">Dokumen – GA Portal</div>
                </td>
                <td class="copy">{{ $copyLabel }}</td>
            </tr>
        </table>

        <table class="meta">
            <tr>
                <td class="k">No. dokumen</td>
                <td class="c">:</td>
                <td class="v">{{ $document->nomor_dokumen }}</td>
                <td class="k">Pengirim</td>
                <td class="c">:</td>
                <td class="v">{{ $document->pengirim->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="k">Tanggal</td>
                <td class="c">:</td>
                <td class="v">{{ $document->created_at->format('d-m-Y') }}</td>
                <td class="k">Penerima</td>
                <td class="c">:</td>
                <td class="v">{{ $document->penerima->name ?? '-' }}</td>
            </tr>
        </table>

        <div class="block">
            <div class="label">Judul dokumen</div>
            <div class="value">{{ $document->judul }}</div>
        </div>

        <div class="block">
            <div class="label">Keterangan</div>
            <div class="value">{!! filled($document->keterangan) ? nl2br(e($document->keterangan)) : '-' !!}</div>
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

    @unless($loop->last)
        <div class="cut"></div>
    @endunless
@endforeach

</body>
</html>
