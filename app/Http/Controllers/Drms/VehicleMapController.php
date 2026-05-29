<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\Vehicle;
use Illuminate\Support\Facades\Cache;

class VehicleMapController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::all();
        $gpsData = $this->fetchGpsData($vehicles->pluck('plate_number')->toArray());
        return view('drms.vehicles.map', compact('vehicles', 'gpsData'));
    }

    public function show(Vehicle $vehicle)
    {
        $vehicles = Vehicle::all();
        $gpsData = $this->fetchGpsData([$vehicle->plate_number]);
        return view('drms.vehicles.map', [
            'vehicles'   => $vehicles,
            'gpsData'    => $gpsData,
            'focusPlate' => $vehicle->plate_number,
        ]);
    }

    /**
     * Ambil data GPS dari API menggunakan cURL (sama persis dengan test.php)
     */
    private function fetchGpsData(array $plateNumbers)
    {
        if (empty($plateNumbers)) return [];

        $cacheKey = 'gps_' . md5(implode(',', $plateNumbers));
        return Cache::remember($cacheKey, 300, function () use ($plateNumbers) {
            $url = "https://gps.gtrack.id/monitoring/search_vehicle";
            $result = [];

            foreach ($plateNumbers as $car) {
                $postData = ['keyword' => $car];

                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => http_build_query($postData),
                    CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest'],
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_TIMEOUT => 10
                ]);

                $response = curl_exec($ch);
                curl_close($ch);

                $data = json_decode($response, true);
                if (!$data) continue;

                foreach ($data as $group) {
                    if (!isset($group['vehicles'])) continue;
                    foreach ($group['vehicles'] as $v) {
                        $alamatRaw = $v[52] ?? '-';
                        $alamatParts = explode(',', $alamatRaw);
                        $alamatSimple = implode(', ', array_slice($alamatParts, 0, 3));

                        // Simpan dengan key = plat nomor
                        $result[$car] = [
                            'nopol'  => $v[17] ?? '-',
                            'lat'    => (float)($v[3] ?? 0),
                            'lng'    => (float)($v[2] ?? 0),
                            'status' => $v[14] ?? '-',
                            'alamat' => trim($alamatSimple),
                        ];
                        break 2; // keluar dari dua loop
                    }
                }
            }

            return $result;
        });
    }
}