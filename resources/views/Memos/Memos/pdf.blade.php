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
        font-family: 'Verdana', Geneva, sans-serif;
        font-size: 10pt;
        color: #000;
        margin: 0;
    }
    h2.title {
        text-align: center;
        font-size: 18pt;
        margin: 0 0 18px 0;
    }
    p { margin: 4px 0; }
    hr {
        margin: 12px 0;
        border: none;
        border-top: 2px solid #333;
    }
    .meta-table {
        border-collapse: collapse;
        margin-bottom: 4px;
    }
    .meta-table td {
        border: none;
        padding: 1px 0;
        vertical-align: top;
    }
    .meta-table td.meta-label {
        width: 95px;
        font-weight: bold;
        white-space: nowrap;
    }
    .meta-table td.meta-sep {
        width: 12px;
    }
    table.items-table, table.rekening-table {
        width: 100%;
        border-collapse: collapse;
    }
    .items-table {
        font-size: 10pt;
        margin-top: 8px;
        table-layout: auto;
    }
    .items-table th, .items-table td {
        border: 1px solid #000;
        padding: 4px 6px;
        text-align: center;
        vertical-align: top;
    }
    .nowrap { white-space: nowrap; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .font-bold { font-weight: bold; }
    .rekening-table {
        margin: 10px 0;
    }
    .rekening-table td {
        border: none;
        padding: 1px 0;
        vertical-align: top;
    }
    .rekening-table td.meta-label {
        width: 95px;
        font-weight: bold;
        white-space: nowrap;
    }
    .print-footer {
        position: fixed;
        bottom: -10mm;
        left: 0;
        right: 0;
        padding-top: 8px;
        border-top: 1px solid #ccc;
        text-align: center;
        font-size: 8pt;
        color: #555;
    }
</style>
</head>
<body>

    <h2 class="title">MEMORANDUM</h2>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Kepada</td><td class="meta-sep">:</td><td>{{ $memo->kepada }}</td>
        </tr>
        <tr>
            <td class="meta-label">Dari</td><td class="meta-sep">:</td><td>{{ $memo->dari }}</td>
        </tr>
        <tr>
            <td class="meta-label">Tanggal</td><td class="meta-sep">:</td><td>{{ $memo->created_at->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Nomor Memo</td><td class="meta-sep">:</td><td>{{ $memo->memo_number ?? '(belum ada — masih draft)' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Perihal</td><td class="meta-sep">:</td><td>{{ $memo->perihal }}</td>
        </tr>
    </table>
    <hr>
    <p>
        {!! $memo->paragraf_pembuka
            ? nl2br(e($memo->paragraf_pembuka))
            : 'Mohon disiapkan dana sebesar <strong>Rp ' . rupiah($memo->total_amount) . '</strong> (' . terbilang($memo->total_amount) . ') untuk ' . e($memo->perihal) . ' dengan rincian:' !!}
    </p>

    @php
        $dynamicColumns = $memo->dynamic_columns_definition ?? [];
        $columnGroups = \App\Support\Memos\MemoItemsTableColumns::build($dynamicColumns);
        $hasGroups = \App\Support\Memos\MemoItemsTableColumns::hasGroups($columnGroups);
        $hasInlineTagihan = \App\Support\Memos\MemoItemsTableColumns::hasInlineTagihan($dynamicColumns);
        $labelColspan = \App\Support\Memos\MemoItemsTableColumns::labelColspan($dynamicColumns);
    @endphp

    <table class="items-table">
        <thead>
            <tr>
                <th class="nowrap text-center" style="width:24px;" rowspan="{{ $hasGroups ? 2 : 1 }}">No</th>
                <th class="text-center" rowspan="{{ $hasGroups ? 2 : 1 }}">{{ $memo->keterangan_label ?? 'Keterangan' }}</th>
                @foreach($columnGroups as $col)
                    @if($col['type'] === 'group')
                        <th class="text-center" colspan="2">{{ $col['label'] }}</th>
                    @else
                        @php $isMoney = \App\Support\Memos\MemoItemsTableColumns::isMoneyColumn($col['label']); @endphp
                        <th class="{{ $isMoney ? 'nowrap text-center' : 'text-center' }}" rowspan="{{ $hasGroups ? 2 : 1 }}">{{ $col['label'] }}</th>
                    @endif
                @endforeach
                @unless($hasInlineTagihan)
                    <th class="nowrap text-center" rowspan="{{ $hasGroups ? 2 : 1 }}">Tagihan</th>
                @endunless
            </tr>
            @if($hasGroups)
            <tr>
                @foreach($columnGroups as $col)
                    @if($col['type'] === 'group')
                        @foreach($col['sub'] as $subLabel)
                            <th class="nowrap text-center">{{ $subLabel }}</th>
                        @endforeach
                    @endif
                @endforeach
            </tr>
            @endif
        </thead>
        <tbody>
            @foreach($memo->items as $index => $item)
            <tr>
                <td class="text-center nowrap">{{ $index + 1 }}</td>
                <td>{{ $item->keterangan }}</td>
                @php $dyn = is_array($item->dynamic_columns) ? $item->dynamic_columns : []; @endphp
                @foreach($dynamicColumns as $i => $colName)
                    @php
                        $isMoney = \App\Support\Memos\MemoItemsTableColumns::isMoneyColumn($colName);
                        $rawVal = $dyn[$i] ?? null;
                        $displayVal = ($isMoney && $rawVal !== null && $rawVal !== '' && $rawVal !== '-')
                            ? rupiah(\App\Support\Memos\MemoItemsTableColumns::parseFormattedNumber($rawVal))
                            : ($rawVal ?? '-');
                    @endphp
                    <td class="{{ $isMoney ? 'nowrap text-right' : '' }}">{{ $displayVal }}</td>
                @endforeach
                @unless($hasInlineTagihan)
                    <td class="text-right nowrap">Rp {{ rupiah($item->tagihan) }}</td>
                @endunless
            </tr>
            @endforeach
            <tr class="font-bold">
                <td colspan="{{ $labelColspan }}" class="text-right">TOTAL</td>
                @foreach($dynamicColumns as $i => $colName)
                    @continue(!\App\Support\Memos\MemoItemsTableColumns::isMoneyColumn($colName))
                    @php
                        $isTagihanCol = strcasecmp(trim($colName), 'Tagihan') === 0;
                        $total = $isTagihanCol ? $memo->total_amount : \App\Support\Memos\MemoItemsTableColumns::sumColumn($memo->items, $i);
                    @endphp
                    <td class="text-right nowrap">Rp {{ rupiah($total) }}</td>
                @endforeach
                @unless($hasInlineTagihan)
                    <td class="text-right nowrap">Rp {{ rupiah($memo->total_amount) }}</td>
                @endunless
            </tr>
        </tbody>
    </table>

    @if($memo->instruksi)
        <p>{!! nl2br(e($memo->instruksi)) !!}</p>
    @endif

    @if($memo->sertakan_rekening)
    <table class="rekening-table">
        <tr>
            <td class="meta-label">Nama Bank</td><td style="width:12px;">:</td><td>{{ $memo->bank }}</td>
        </tr>
        <tr>
            <td class="meta-label">Nama Rekening</td><td>:</td><td>{{ $memo->atas_nama }}</td>
        </tr>
        <tr>
            <td class="meta-label">No. Rekening</td><td>:</td><td>{{ $memo->no_rek }}</td>
        </tr>
    </table>
    @endif

    <p style="margin-top:40px;">
        Hormat kami,<br><br><br><br>
        <strong>{{ $memo->penandatangan }}</strong><br>
        {{ $memo->jabatan }}
    </p>

    <div class="print-footer">Generate Memo - {{ $memo->memo_number ?? 'Draft' }} by GA Portal</div>

</body>
</html>