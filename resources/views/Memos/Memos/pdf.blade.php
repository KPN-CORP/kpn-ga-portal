<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Generate Memo - {{ $memo->memo_number ?? 'Draft' }} by GA Portal</title>
<style>
    @page {
        margin: 20mm 18mm;
    }
    body {
        font-family: 'Times New Roman', serif;
        font-size: 13px;
        color: #000;
        margin: 0;
    }
    .header-date {
        text-align: right;
        font-size: 12px;
    }
    h2.title {
        text-align: center;
        font-size: 20px;
        margin: 20px 0;
    }
    p { margin: 4px 0; }
    hr {
        margin: 16px 0;
        border: none;
        border-top: 2px solid #333;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
    }
    th, td {
        border: 1px solid #000;
        padding: 6px;
        text-align: left;
        vertical-align: top;
    }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .font-bold { font-weight: bold; }
    .rekening {
        border-left: 4px solid #2563eb;
        padding-left: 12px;
        margin: 14px 0;
    }
    .print-footer {
        position: fixed;
        bottom: -10mm;
        left: 0;
        right: 0;
        padding-top: 8px;
        border-top: 1px solid #ccc;
        text-align: center;
        font-size: 10px;
        color: #555;
    }
</style>
</head>
<body>

    <div class="header-date">
        {{ $memo->created_at->translatedFormat('d F Y') }}<br>
        No. {{ $memo->memo_number ?? '(belum ada — masih draft)' }}
    </div>

    <h2 class="title">MEMORANDUM</h2>

    <p><strong>Kepada</strong> : {{ $memo->kepada }}</p>
    <p><strong>Dari</strong> : {{ $memo->dari }}</p>
    <p><strong>Perihal</strong> : {{ $memo->perihal }}</p>
    <hr>
    <p>
        Mohon disiapkan dana sebesar
        <strong>Rp {{ number_format($memo->total_amount, 0, ',', '.') }}</strong>
        ({{ terbilang($memo->total_amount) }} rupiah)
        untuk {{ $memo->perihal }} dengan rincian:
    </p>

    @php
        $dynamicColumns = $memo->dynamic_columns_definition ?? [];
        $colspan = 2 + count($dynamicColumns);
    @endphp

    <table>
        <thead>
            <tr>
                <th style="width:30px;">No</th>
                <th>{{ $memo->keterangan_label ?? 'Keterangan' }}</th>
                @foreach($dynamicColumns as $colName)
                    <th>{{ $colName }}</th>
                @endforeach
                <th>Tagihan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($memo->items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item->keterangan }}</td>
                @php $dyn = is_array($item->dynamic_columns) ? $item->dynamic_columns : []; @endphp
                @foreach($dyn as $val)
                    <td>{{ $val ?? '-' }}</td>
                @endforeach
                <td class="text-right">Rp {{ number_format($item->tagihan, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="font-bold">
                <td colspan="{{ $colspan }}" class="text-right">TOTAL</td>
                <td class="text-right">Rp {{ number_format($memo->total_amount, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    @if($memo->instruksi)
        <p>{!! nl2br(e($memo->instruksi)) !!}</p>
    @endif

    <div class="rekening">
        <strong>Rekening Tujuan</strong><br>
        Bank: {{ $memo->bank }}<br>
        Atas Nama: {{ $memo->atas_nama }}<br>
        No Rek: {{ $memo->no_rek }}
    </div>

    <p style="margin-top:40px;">
        Hormat kami,<br><br><br><br>
        {{ $memo->penandatangan }}<br>
        {{ $memo->jabatan }}
    </p>

    <div class="print-footer">Generate Memo - {{ $memo->memo_number ?? 'Draft' }} by GA Portal</div>

</body>
</html>