<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pengeluaran</title>
    <style>
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 18px; margin-bottom: 2px; }
        .sub { color: #6b7280; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
        th { background-color: #f3f4f6; }
        .text-right { text-align: right; }
        .totals-table { width: 50%; margin-top: 16px; margin-left: auto; }
        .totals-table td { border: none; padding: 3px 8px; }
        .grand-total-row td { font-weight: bold; border-top: 2px solid #1f2937; font-size: 13px; }
        .header-table { width: 100%; margin-bottom: 12px; }
        .header-table td { border: none; padding: 2px 0; }
    </style>
</head>
<body>
    <h1>Laporan Pengeluaran Driver</h1>
    <p class="sub">
        Perjalanan #{{ $driverRequest->request_no }} — {{ \Carbon\Carbon::parse($driverRequest->usage_date)->translatedFormat('d F Y') }} — {{ $driverRequest->destination }}
    </p>

    <table class="header-table">
        <tr>
            <td style="width: 120px;"><strong>Nama Driver</strong></td>
            <td>: {{ $driver->name }}</td>
        </tr>
        @if($driver->phone)
        <tr>
            <td><strong>Telepon</strong></td>
            <td>: {{ $driver->phone }}</td>
        </tr>
        @endif
        <tr>
            <td><strong>Dicetak</strong></td>
            <td>: {{ now()->translatedFormat('d F Y, H:i') }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 90px;">Tanggal</th>
                <th style="width: 90px;">Kategori</th>
                <th>Keterangan</th>
                <th style="width: 110px;" class="text-right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->report_date->format('d/m/Y') }}</td>
                    <td>{{ $item->category_label }}</td>
                    <td>{{ $item->description ?: '-' }}</td>
                    <td class="text-right">{{ number_format($item->amount, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        @foreach(\App\Models\Drms\ExpenseReport::CATEGORIES as $catKey => $catLabel)
            <tr>
                <td>{{ $catLabel }}</td>
                <td class="text-right">Rp {{ number_format($totals[$catKey] ?? 0, 0, ',', '.') }}</td>
            </tr>
        @endforeach
        <tr class="grand-total-row">
            <td>Total Perjalanan Ini</td>
            <td class="text-right">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
        </tr>
    </table>
</body>
</html>
