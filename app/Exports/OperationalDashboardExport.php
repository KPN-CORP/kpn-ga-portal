<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OperationalDashboardExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $stats;
    protected $chartData;
    protected $efficiencyData;
    protected $transportDistribution;
    protected $repairData;
    protected $vehicleStats;
    protected $totals;
    protected $month;
    protected $year;
    protected $businessUnitName;

    public function __construct($stats, $chartData, $efficiencyData, $transportDistribution, $repairData, $vehicleStats, $totals, $month, $year, $businessUnitName = 'Semua')
    {
        $this->stats = $stats;
        $this->chartData = $chartData;
        $this->efficiencyData = $efficiencyData;
        $this->transportDistribution = $transportDistribution;
        $this->repairData = $repairData;
        $this->vehicleStats = $vehicleStats;
        $this->totals = $totals;
        $this->month = $month;
        $this->year = $year;
        $this->businessUnitName = $businessUnitName;
    }

    public function array(): array
    {
        $data = [];

        // Header utama
        $data[] = ['LAPORAN OPERASIONAL DRMS'];
        $data[] = ['Periode', date('F Y', mktime(0, 0, 0, $this->month, 1, $this->year))];
        $data[] = ['Business Unit', $this->businessUnitName];
        $data[] = ['Tanggal Export', date('d-m-Y H:i:s')];
        $data[] = [];

        // RINGKASAN STATISTIK
        $data[] = ['RINGKASAN STATISTIK'];
        $data[] = ['Total Biaya Operasi', 'Rp ' . number_format($this->stats['total_operational_cost'] ?? 0, 0, ',', '.')];
        $data[] = ['Total BBM/Charge', 'Rp ' . number_format($this->stats['total_fuel_cost'] ?? 0, 0, ',', '.')];
        $data[] = ['Total Service', 'Rp ' . number_format($this->stats['total_service_cost'] ?? 0, 0, ',', '.')];
        $data[] = ['Total Perbaikan', 'Rp ' . number_format($this->stats['total_repair_cost'] ?? 0, 0, ',', '.')];
        $data[] = ['Total Jarak Tempuh', number_format($this->stats['total_distance'] ?? 0, 0, ',', '.') . ' km'];
        $data[] = ['Total Perjalanan (Terverifikasi)', $this->stats['total_trips'] ?? 0];
        $data[] = ['Menunggu Verifikasi', $this->stats['pending_verification'] ?? 0];
        if (isset($this->stats['avg_efficiency'])) {
            $data[] = ['Rata-rata Efisiensi', number_format($this->stats['avg_efficiency'], 2) . ' L/100km'];
        }
        $data[] = [];

        // BIAYA PER BULAN (12 bulan)
        $data[] = ['BIAYA PER BULAN (12 Bulan Terakhir)'];
        $data[] = ['Bulan', 'BBM/Charge (Rp)', 'Service (Rp)', 'Perbaikan (Rp)', 'Total (Rp)'];
        foreach ($this->chartData as $item) {
            $data[] = [
                $item['month'],
                number_format($item['fuel'] ?? 0, 0, ',', '.'),
                number_format($item['service'] ?? 0, 0, ',', '.'),
                number_format($item['repair'] ?? 0, 0, ',', '.'),
                number_format(($item['fuel'] ?? 0) + ($item['service'] ?? 0) + ($item['repair'] ?? 0), 0, ',', '.')
            ];
        }
        $data[] = [];

        // RINCIAN PER KENDARAAN (bulan terpilih)
        $data[] = ['RINCIAN PER KENDARAAN - ' . date('F Y', mktime(0, 0, 0, $this->month, 1, $this->year))];
        $data[] = ['Kendaraan', 'BBM/Charge (Rp)', 'Service (Rp)', 'Perbaikan (Rp)', 'Total Biaya (Rp)', 'Jarak (km)', 'Liter/kWh'];
        foreach ($this->vehicleStats as $v) {
            $data[] = [
                $v['plate_number'],
                number_format($v['fuel_cost'] ?? 0, 0, ',', '.'),
                number_format($v['service_cost'] ?? 0, 0, ',', '.'),
                number_format($v['repair_cost'] ?? 0, 0, ',', '.'),
                number_format($v['total_cost'] ?? 0, 0, ',', '.'),
                number_format($v['distance'] ?? 0, 0, ',', '.'),
                number_format($v['fuel_liters'] ?? 0, 2, ',', '.')
            ];
        }
        // Total baris
        $data[] = [
            'TOTAL',
            number_format($this->totals['total_fuel_cost'] ?? 0, 0, ',', '.'),
            number_format($this->totals['total_service_cost'] ?? 0, 0, ',', '.'),
            number_format($this->totals['total_repair_cost'] ?? 0, 0, ',', '.'),
            number_format($this->totals['total_operational_cost'] ?? 0, 0, ',', '.'),
            number_format($this->totals['total_distance'] ?? 0, 0, ',', '.'),
            number_format($this->totals['total_fuel_liters'] ?? 0, 2, ',', '.')
        ];
        $data[] = [];

        // EFISIENSI KENDARAAN
        $data[] = ['EFISIENSI KENDARAAN (Top 10)'];
        $data[] = ['Plat Nomor', 'Tipe', 'Rata-rata Efisiensi (L/100km)', 'Total Perjalanan'];
        foreach ($this->efficiencyData as $item) {
            $data[] = [
                $item['vehicle'],
                $item['type'] ?? '-',
                number_format($item['avg_efficiency'], 2),
                $item['total_trips'] ?? 0
            ];
        }
        $data[] = [];

        // DISTRIBUSI TRANSPORTASI
        $data[] = ['DISTRIBUSI TRANSPORTASI'];
        $data[] = ['Jenis Transportasi', 'Jumlah'];
        foreach ($this->transportDistribution as $item) {
            $data[] = [
                $item->transport_type ? ucfirst(str_replace('_', ' ', $item->transport_type)) : 'Tidak Diketahui',
                $item->total
            ];
        }
        $data[] = [];

        // DATA PERBAIKAN PER BULAN (12 bulan)
        if (!empty($this->repairData)) {
            $data[] = ['BIAYA PERBAIKAN PER BULAN (12 Bulan Terakhir)'];
            $data[] = ['Bulan', 'Total Biaya Perbaikan (Rp)'];
            foreach ($this->repairData as $monthKey => $cost) {
                $data[] = [$monthKey, number_format($cost, 0, ',', '.')];
            }
            $data[] = [];
        }

        return $data;
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        // Bold untuk judul
        $sheet->getStyle('A1:A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A6:A6')->getFont()->setBold(true);
        $sheet->getStyle('A14:A14')->getFont()->setBold(true);
        $sheet->getStyle('A20:A20')->getFont()->setBold(true);
        // etc.
        return [];
    }
}