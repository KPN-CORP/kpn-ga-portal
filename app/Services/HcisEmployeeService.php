<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HcisEmployeeService
{
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('hcis.api_url'), '/');
        $this->token = config('hcis.api_token');
    }

    public function fetchEmployees()
    {
        $url = $this->baseUrl . '/integration/employees';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json',
            ])->get($url);

            if ($response->successful()) {
                $data = $response->json();

                // Struktur 1: { "data": [...] }
                if (isset($data['data']) && is_array($data['data'])) {
                    return $data['data'];
                }
                // Struktur 2: [ ... ] (array langsung)
                if (is_array($data)) {
                    return $data;
                }

                Log::warning('HCIS response unknown structure', ['response' => $data]);
                return null;
            }

            // Tangani error
            Log::error('HCIS API error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->status() === 429) {
                throw new \Exception('Rate limit exceeded from HCIS API');
            }
            if ($response->status() === 401) {
                throw new \Exception('Unauthorized – invalid HCIS token');
            }
            return null;

        } catch (\Exception $e) {
            Log::error('Exception when calling HCIS API: ' . $e->getMessage());
            throw $e;
        }
    }
}