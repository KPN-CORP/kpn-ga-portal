<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OperationalDashboardExport implements FromArray, WithHeadings, WithStyles
{
    protected $stats;
    protected $chartData;
    protected $efficiencyData;
    protected $transportDistribution;

    public function __construct($stats, $chartData, $efficiencyData, $transportDistribution)
    {
        $this->stats = $stats;
        $this->chartData = $chartData;
        $this->efficiencyData = $efficiencyData;
        $this->transportDistribution = $transportDistribution;
    }

    public function array(): array
    {
        $data = [];

        // Header ringkasan statistik
        $data[] = ['LAPORAN OPERASIONAL DRMS'];
        $data[] = ['Tanggal Export', date('d-m-Y H:i:s')];
        $data[] = [];
        $data[] = ['RINGKASAN STATISTIK'];
        $data[] = ['Total Biaya Operasi', 'Rp ' . number_format($this->stats['total_operational_cost'], 0, ',', '.')];
        $data[] = ['Total BBM/Charge', 'Rp ' . number_format($this->stats['total_fuel_cost'], 0, ',', '.')];
        $data[] = ['Total Service', 'Rp ' . number_format($this->stats['total_service_cost'], 0, ',', '.')];
        $data[] = ['Total Jarak Tempuh', number_format($this->stats['total_distance'], 0) . ' km'];
        $data[] = ['Menunggu Verifikasi', $this->stats['pending_verification'] . ' log'];
        $data[] = [];

        // Biaya per bulan
        $data[] = ['BIAYA PER BULAN'];
        $data[] = ['Bulan', 'BBM/Charge (Rp)', 'Service (Rp)', 'Total (Rp)'];
        foreach ($this->chartData as $item) {
            $data[] = [
                $item['month'],
                number_format($item['fuel'], 0, ',', '.'),
                number_format($item['service'], 0, ',', '.'),
                number_format($item['total'], 0, ',', '.')
            ];
        }
        $data[] = [];

        // Efisiensi kendaraan
        $data[] = ['EFISIENSI KENDARAAN'];
        $data[] = ['Plat Nomor', 'Tipe', 'Rata-rata Efisiensi', 'Total Trip'];
        foreach ($this->efficiencyData as $item) {
            $data[] = [
                $item['vehicle'],
                $item['type'],
                $item['avg_efficiency'] . ' (km/liter atau km/kWh)',
                $item['total_trips']
            ];
        }
        $data[] = [];

        // Distribusi transportasi
        $data[] = ['DISTRIBUSI TRANSPORTASI'];
        $data[] = ['Jenis Transportasi', 'Total'];
        foreach ($this->transportDistribution as $item) {
            $data[] = [
                $item->transport_type ?? 'Tidak Diketahui',
                $item->total
            ];
        }

        return $data;
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        // Style untuk judul
        $sheet->getStyle('A1:A1')->getFont()->setBold(true)->setSize(14);
        // Bisa tambahkan styling lainnya
        return [];
    }
}