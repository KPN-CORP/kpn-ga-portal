<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\SsoAuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DBLoginController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('SSO DARWINBOX HIT', [
            'ip'  => $request->ip(),
            'ua'  => $request->userAgent(),
            'url' => $request->fullUrl(),
        ]);

        // =====================================================
        // 1️⃣ Ambil payload
        // =====================================================
        $payload = $request->query('data');

        if (!$payload || !is_string($payload)) {
            abort(400, 'SSO payload missing');
        }

        // =====================================================
        // 2️⃣ URL decode (WAJIB)
        // =====================================================
        $payload = rawurldecode($payload);

        // =====================================================
        // 3️⃣ Decrypt
        // =====================================================
        $json = $this->darwinboxDecrypt(
            $payload,
            config('services.darwinbox.xor_key')
        );

        if (!$json) {
            Log::error('SSO decrypt failed');
            abort(400, 'Invalid SSO payload');
        }

        // =====================================================
        // 4️⃣ Decode JSON
        // =====================================================
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            abort(400, 'Invalid SSO payload');
        }

        Log::info('SSO PAYLOAD', $data);

        // =====================================================
        // 5️⃣ VALIDASI IDENTITAS (INI KUNCI UTAMA)
        // =====================================================
        $employeeId = $data['employee_id']
            ?? $data['employee_no']
            ?? $data['emp_code']
            ?? null;

        if (!$employeeId) {
            Log::error('SSO FAILED: employee_id missing', $data);
            abort(403, 'Employee ID not found');
        }

        $employeeId = trim((string) $employeeId);

        // =====================================================
        // 6️⃣ Validasi email (opsional tapi disarankan)
        // =====================================================
        if (empty($data['email'])) {
            abort(403, 'Email missing from SSO payload');
        }

        $email = strtolower(trim($data['email']));

        // =====================================================
        // 7️⃣ Cari user BERDASARKAN employee_id (UNIK)
        // =====================================================
        $user = User::where('employee_no', $employeeId)->first();

        // =====================================================
        // 8️⃣ Create user jika belum ada
        // =====================================================
        if (!$user) {
            $user = User::create([
                'name'              => trim(($data['firstname'] ?? '') . ' ' . ($data['lastname'] ?? '')) ?: $email,
                'email'             => $email,
                'username'          => $employeeId,
                'employee_no'       => $employeeId,
                'first_name'        => $data['firstname'] ?? null,
                'last_name'         => $data['lastname'] ?? null,
                'company_name'      => $data['company_name'] ?? null,
                'office_city'       => $data['office_location_city'] ?? null,
                'office_mobile'     => $data['office_mobile'] ?? null,
                'login_type'        => 'sso',
                'email_verified_at' => now(),
                'password'          => bcrypt(Str::random(64)),
            ]);

            Log::info('SSO USER CREATED', [
                'user_id'     => $user->id,
                'employee_id' => $employeeId,
            ]);
        }

        // =====================================================
        // 9️⃣ Update data (SYNC DARWINBOX)
        // =====================================================
        $user->update([
            'email'        => $email,
            'first_name'   => $user->first_name ?: ($data['firstname'] ?? null),
            'last_name'    => $user->last_name ?: ($data['lastname'] ?? null),
            'login_type'   => 'sso',
        ]);

        // =====================================================
        // 🔐 10️⃣ Login
        // =====================================================
        Auth::login($user, true);
        $request->session()->regenerate();

        Log::info('SSO LOGIN SUCCESS', [
            'user_id'     => $user->id,
            'employee_id' => $employeeId,
        ]);

        // =====================================================
        // 🧾 11️⃣ Audit log
        // =====================================================
        try {
            SsoAuditLog::create([
                'email'      => $user->email,
                'sso_uid'    => $employeeId,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status'     => 'success',
                'message'    => 'Darwinbox SSO login success',
            ]);
        } catch (\Throwable $e) {
            Log::warning('SSO audit log failed');
        }

        return redirect()->intended('/dashboard');
    }

    // =====================================================
    // 🔐 DARWINBOX DECRYPT
    // =====================================================
    private function darwinboxDecrypt(string $encrypted, string $xorKey): ?string
    {
        if (!$xorKey) {
            return null;
        }

        $raw = rawurldecode($encrypted);
        $raw = strtr($raw, '-_', '+/');

        $pad = strlen($raw) % 4;
        if ($pad) {
            $raw .= str_repeat('=', 4 - $pad);
        }

        $decoded = base64_decode($raw);
        if ($decoded === false) {
            return null;
        }

        $out = '';
        $keyLen = strlen($xorKey);

        for ($i = 0, $len = strlen($decoded); $i < $len; $i++) {
            $out .= chr(ord($decoded[$i]) ^ ord($xorKey[$i % $keyLen]));
        }

        if ($this->isJson($out)) {
            return $out;
        }

        $second = base64_decode($out, true);
        if ($second && $this->isJson($second)) {
            return $second;
        }

        $gunzip = @gzuncompress($out);
        if ($gunzip && $this->isJson($gunzip)) {
            return $gunzip;
        }

        return null;
    }

    private function isJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
