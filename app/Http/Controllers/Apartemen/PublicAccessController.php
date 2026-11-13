<?php
// app/Http/Controllers/Apartemen/PublicAccessController.php

namespace App\Http\Controllers\Apartemen;

use App\Http\Controllers\Controller;
use App\Models\Apartemen\ApartemenAccessCode;
use App\Models\Apartemen\ApartemenPenghuni;
use App\Models\Apartemen\ApartemenHistory;
use App\Models\Apartemen\BisnisUnit;
use App\Services\Apartemen\UnitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Check-in / check-out mandiri dengan kode akses.
 *
 * Pengamanan:
 *  - Sesi dicek ulang di setiap langkah (kode masih aktif, belum kedaluwarsa, belum habis kuota)
 *    dan berakhir otomatis setelah IDLE_SECONDS tanpa aktivitas.
 *  - Aksi hanya boleh untuk penghuni yang muncul di hasil pencarian terakhir pada sesi ini.
 *  - Setiap aksi wajib konfirmasi 4 digit terakhir NIK (atau no. HP bila NIK tidak ada).
 *  - No. HP pada hasil pencarian disamarkan.
 *  - Throttle dipasang di route (lihat routes_apartemen.php).
 */
class PublicAccessController extends Controller
{
    private const IDLE_SECONDS   = 1800; // 30 menit tanpa aktivitas
    private const MAX_GAGAL_KONF = 5;    // salah konfirmasi berturut-turut sebelum sesi ditutup

    /** Halaman input kode akses */
    public function index()
    {
        return view('apartemen.public.index');
    }

    /** Verifikasi kode akses */
    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode_akses' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/'],
        ]);

        if ($validator->fails()) {
            return back()->with('error', 'Kode akses harus diisi (huruf/angka, maksimal 10 karakter).');
        }

        try {
            $kodeAkses = strtoupper(trim($request->kode_akses));

            $accessCode = ApartemenAccessCode::where('kode_akses', $kodeAkses)
                ->where('is_active', true)
                ->first();

            if (!$accessCode || !$accessCode->isValid()) {
                Log::warning('Percobaan kode akses tidak valid', [
                    'kode' => substr($kodeAkses, 0, 2) . '****',
                    'ip' => $request->ip(),
                ]);
                return back()->with('error', 'Kode akses tidak valid atau sudah kadaluarsa!');
            }

            $request->session()->regenerate();
            session([
                'access_code'      => encrypt($accessCode->kode_akses),
                'access_code_id'   => encrypt($accessCode->id),
                'access_code_data' => $accessCode,
                'access_time'      => now()->timestamp,
                'public_gagal'     => 0,
            ]);
            session()->forget('public_allowed_ids');
            session()->save();

            Log::info('Kode akses terverifikasi', ['kode_id' => $accessCode->id, 'ip' => $request->ip()]);

            return redirect()->route('apartemen.public.search');

        } catch (\Throwable $e) {
            Log::error('Error verifikasi kode akses: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem. Silakan coba lagi.');
        }
    }

    /** Halaman pencarian penghuni */
    public function search(Request $request)
    {
        if (!$this->resolveSession()) {
            return redirect()->route('apartemen.public.index')
                ->with('error', 'Sesi tidak valid atau sudah berakhir. Silakan masukkan kode akses lagi.');
        }

        return view('apartemen.public.search');
    }

    /** Proses pencarian penghuni (hanya yang AKTIF) */
    public function find(Request $request)
    {
        if (!$this->resolveSession()) {
            return redirect()->route('apartemen.public.index')
                ->with('error', 'Sesi tidak valid atau sudah berakhir.');
        }

        $validator = Validator::make($request->all(), [
            'search' => 'required|string|min:3|max:30',
        ]);

        if ($validator->fails()) {
            return back()->with('error', 'Pencarian minimal 3 karakter.');
        }

        try {
            $search = trim($request->search);

            $penghuni = ApartemenPenghuni::with(['assign.unit.apartemen'])
                ->where('status', 'AKTIF')
                ->where(function ($q) use ($search) {
                    $q->where('nama', 'LIKE', '%' . $search . '%')
                      ->orWhere('id_karyawan', 'LIKE', '%' . $search . '%');
                })
                ->select(['id', 'assign_id', 'nama', 'id_karyawan', 'no_hp', 'unit_kerja', 'gol', 'status', 'tanggal_mulai', 'tanggal_selesai'])
                ->orderBy('nama')
                ->limit(10)
                ->get();

            // Hanya penghuni pada hasil ini yang boleh diproses di sesi ini.
            session(['public_allowed_ids' => $penghuni->pluck('id')->map(fn($v) => (int) $v)->all()]);

            // Petunjuk konfirmasi (dihitung dari data asli), lalu samarkan no. HP.
            $penghuni->each(function ($p) {
                [, $hint] = $this->konfirmasiRahasia($p);
                $p->setAttribute('konfirmasi_hint', $hint);
                $p->no_hp = $this->maskHp($p->no_hp);
            });

            return view('apartemen.public.search-result', compact('penghuni', 'search'));

        } catch (\Throwable $e) {
            Log::error('Error pencarian publik: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat mencari data.');
        }
    }

    /** CHECK-IN mandiri */
    public function checkin(Request $request, $id)
    {
        $accessCode = $this->resolveSession();
        if (!$accessCode) {
            return redirect()->route('apartemen.public.index')
                ->with('error', 'Sesi tidak valid atau sudah berakhir. Silakan masukkan kode akses lagi.');
        }

        if (!in_array($accessCode->tipe, ['CHECKIN', 'BOTH'], true)) {
            return redirect()->route('apartemen.public.search')
                ->with('error', 'Kode akses ini tidak memiliki izin untuk check-in!');
        }

        DB::beginTransaction();
        try {
            $penghuni = $this->ambilPenghuniUntukAksi($request, (int) $id);
            $assign   = $penghuni->assign;

            if (!$assign) {
                throw new \DomainException('Data penempatan tidak ditemukan.');
            }
            if ($assign->status !== 'AKTIF') {
                throw new \DomainException('Penempatan ini sudah tidak aktif.');
            }
            if ($assign->checkin_at) {
                throw new \DomainException('Sudah melakukan check-in pada ' . $assign->checkin_at->format('d/m/Y H:i'));
            }
            if ($assign->tanggal_mulai > now()) {
                throw new \DomainException('Belum waktunya check-in. Tanggal mulai: ' . $assign->tanggal_mulai->format('d/m/Y'));
            }

            $assign->update(['checkin_at' => now()]);

            // Check-in berlaku untuk seluruh penghuni aktif pada penempatan ini; riwayat dicatat per orang.
            $penghuniMasuk = $assign->penghuni()->where('status', 'AKTIF')->get();
            if ($penghuniMasuk->isEmpty()) {
                $penghuniMasuk = collect([$penghuni]);
            }

            foreach ($penghuniMasuk as $p) {
                ApartemenHistory::create([
                    'id_karyawan'    => $p->id_karyawan,
                    'nama'           => $p->nama,
                    'no_hp'          => $p->no_hp ?? '-',
                    'unit_kerja'     => $p->unit_kerja ?? '-',
                    'gol'            => $p->gol ?? '-',
                    'apartemen'      => $assign->unit->apartemen->nama_apartemen ?? '-',
                    'unit'           => $assign->unit->nomor_unit ?? '-',
                    'periode'        => $assign->tanggal_mulai->format('d/m/Y') . ' - ' . $assign->tanggal_selesai->format('d/m/Y'),
                    'status_selesai' => 'CHECKIN',
                    'created_at'     => now(),
                ]);
            }

            UnitService::sinkron((int) $assign->unit_id);
            $accessCode->incrementUsed();

            DB::commit();
            session()->forget('public_allowed_ids');

            Log::info('Check-in publik berhasil', ['assign_id' => $assign->id, 'kode_id' => $accessCode->id]);

            return redirect()->route('apartemen.public.success')
                ->with('public_done', ['action' => 'checkin', 'nama' => $penghuni->nama]);

        } catch (\DomainException $e) {
            DB::rollBack();
            return $this->gagal($e->getMessage());
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Check-in publik gagal', ['penghuni_id' => $id, 'message' => $e->getMessage()]);
            return back()->with('error', 'Gagal check-in. Silakan coba lagi atau hubungi admin.');
        }
    }

    /** CHECK-OUT mandiri (wajib sudah check-in, sama seperti di halaman admin) */
    public function checkout(Request $request, $id)
    {
        $accessCode = $this->resolveSession();
        if (!$accessCode) {
            return redirect()->route('apartemen.public.index')
                ->with('error', 'Sesi tidak valid atau sudah berakhir. Silakan masukkan kode akses lagi.');
        }

        if (!in_array($accessCode->tipe, ['CHECKOUT', 'BOTH'], true)) {
            return redirect()->route('apartemen.public.search')
                ->with('error', 'Kode akses ini tidak memiliki izin untuk check-out!');
        }

        DB::beginTransaction();
        try {
            $penghuni = $this->ambilPenghuniUntukAksi($request, (int) $id, ['assign.request.user']);
            $assign   = $penghuni->assign;

            if (!$assign) {
                throw new \DomainException('Data penempatan tidak ditemukan.');
            }
            if ($assign->status !== 'AKTIF') {
                throw new \DomainException('Penempatan ini sudah tidak aktif.');
            }
            if (!$assign->checkin_at) {
                throw new \DomainException('Belum melakukan check-in, tidak bisa check-out.');
            }

            $penghuni->update(['status' => 'SELESAI']);

            $masihAktif = ApartemenPenghuni::where('assign_id', $assign->id)->where('status', 'AKTIF')->count();
            if ($masihAktif == 0) {
                $assign->forceFill(['status' => 'SELESAI', 'checkout_at' => now()])->save();
                UnitService::sinkron((int) $assign->unit_id);
            }

            $bisnisUnit = BisnisUnit::resolveNamaForUser($assign->request->user ?? null);

            ApartemenHistory::create([
                'id_karyawan'    => $penghuni->id_karyawan,
                'nama'           => $penghuni->nama,
                'no_hp'          => $penghuni->no_hp ?? '-',
                'unit_kerja'     => $penghuni->unit_kerja ?? '-',
                'gol'            => $penghuni->gol ?? '-',
                'apartemen'      => $assign->unit->apartemen->nama_apartemen ?? '-',
                'unit'           => $assign->unit->nomor_unit ?? '-',
                'periode'        => $assign->tanggal_mulai->format('d/m/Y') . ' - ' . $assign->tanggal_selesai->format('d/m/Y'),
                'status_selesai' => 'SELESAI',
                'bisnis_unit'    => $bisnisUnit ?? $penghuni->unit_kerja,
                'created_at'     => now(),
            ]);

            $accessCode->incrementUsed();

            DB::commit();
            session()->forget('public_allowed_ids');

            Log::info('Check-out publik berhasil', ['assign_id' => $assign->id, 'kode_id' => $accessCode->id]);

            return redirect()->route('apartemen.public.success')
                ->with('public_done', ['action' => 'checkout', 'nama' => $penghuni->nama]);

        } catch (\DomainException $e) {
            DB::rollBack();
            return $this->gagal($e->getMessage());
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Check-out publik gagal', ['penghuni_id' => $id, 'message' => $e->getMessage()]);
            return back()->with('error', 'Gagal check-out. Silakan coba lagi atau hubungi admin.');
        }
    }

    /** Halaman sukses (data dari flash session, bukan dari URL) */
    public function success(Request $request)
    {
        $done = session('public_done');

        if (!session('access_code') || !is_array($done)) {
            return redirect()->route('apartemen.public.search');
        }

        return view('apartemen.public.success', [
            'action' => $done['action'] ?? null,
            'nama'   => $done['nama'] ?? '',
        ]);
    }

    /** Keluar dari akses publik */
    public function logout()
    {
        $this->clearSession();
        return redirect()->route('apartemen.public.index')
            ->with('success', 'Anda telah keluar dari sistem.');
    }

    // ------------------------------------------------------------------
    // Helper
    // ------------------------------------------------------------------

    /**
     * Cek sesi + kode akses. Mengembalikan model kode akses yang SEGAR, atau null bila tidak sah.
     * Dicek ulang di setiap langkah: aktif, belum kedaluwarsa/habis kuota, dan tidak idle terlalu lama.
     */
    private function resolveSession(): ?ApartemenAccessCode
    {
        $enc    = session('access_code');
        $encId  = session('access_code_id');
        $waktu  = session('access_time');

        if (!$enc || !$encId || !$waktu) {
            return null;
        }

        if (now()->timestamp - (int) $waktu > self::IDLE_SECONDS) {
            $this->clearSession();
            return null;
        }

        try {
            $kode = decrypt($enc);
            $id   = decrypt($encId);
        } catch (\Throwable $e) {
            $this->clearSession();
            return null;
        }

        $accessCode = ApartemenAccessCode::find($id);

        if (!$accessCode
            || !hash_equals((string) $accessCode->kode_akses, (string) $kode)
            || !$accessCode->is_active
            || !$accessCode->isValid()) {
            $this->clearSession();
            return null;
        }

        // Perpanjang batas idle & simpan salinan terbaru (view membaca tipe akses dari sini).
        session(['access_time' => now()->timestamp, 'access_code_data' => $accessCode]);

        return $accessCode;
    }

    private function clearSession(): void
    {
        session()->forget(['access_code', 'access_code_id', 'access_code_data', 'access_time', 'public_allowed_ids', 'public_gagal']);
    }

    /**
     * Ambil penghuni AKTIF (terkunci) untuk diproses, setelah memastikan:
     *  1) ID ada di hasil pencarian terakhir sesi ini,
     *  2) konfirmasi 4 digit cocok.
     */
    private function ambilPenghuniUntukAksi(Request $request, int $id, array $extraRelasi = []): ApartemenPenghuni
    {
        if (!in_array($id, (array) session('public_allowed_ids', []), true)) {
            throw new \DomainException('Cari nama/NIK terlebih dahulu, lalu pilih penghuni dari hasil pencarian.');
        }

        $penghuni = ApartemenPenghuni::with(array_merge(['assign.unit.apartemen'], $extraRelasi))
            ->where('id', $id)
            ->where('status', 'AKTIF')
            ->lockForUpdate()
            ->first();

        if (!$penghuni) {
            throw new \DomainException('Penghuni tidak ditemukan atau sudah tidak aktif.');
        }

        [$rahasia, $hint] = $this->konfirmasiRahasia($penghuni);
        if ($rahasia === null) {
            throw new \DomainException('Data pembanding untuk konfirmasi tidak tersedia. Hubungi admin untuk proses manual.');
        }

        $input = preg_replace('/\D/', '', (string) $request->input('konfirmasi', ''));
        if ($input === '' || !hash_equals($rahasia, $input)) {
            throw new \DomainException('KONFIRMASI_SALAH');
        }

        return $penghuni;
    }

    /** Respons untuk kegagalan yang disengaja (pesan aman untuk ditampilkan). */
    private function gagal(string $pesan)
    {
        if ($pesan === 'KONFIRMASI_SALAH') {
            $gagal = (int) session('public_gagal', 0) + 1;

            if ($gagal >= self::MAX_GAGAL_KONF) {
                $this->clearSession();
                return redirect()->route('apartemen.public.index')
                    ->with('error', 'Terlalu banyak konfirmasi yang salah. Sesi ditutup, silakan masukkan kode akses lagi.');
            }

            session(['public_gagal' => $gagal]);
            return back()->with('error', 'Konfirmasi tidak cocok. Sisa percobaan: ' . (self::MAX_GAGAL_KONF - $gagal) . '.');
        }

        return back()->with('error', $pesan);
    }

    /** [4 digit rahasia, petunjuk] — NIK diutamakan, no. HP sebagai cadangan. */
    private function konfirmasiRahasia($penghuni): array
    {
        $nik = preg_replace('/\D/', '', (string) $penghuni->id_karyawan);
        if (strlen($nik) >= 4) {
            return [substr($nik, -4), '4 digit terakhir NIK'];
        }

        $hp = preg_replace('/\D/', '', (string) $penghuni->no_hp);
        if (strlen($hp) >= 4) {
            return [substr($hp, -4), '4 digit terakhir no. HP'];
        }

        return [null, null];
    }

    private function maskHp($raw): ?string
    {
        $d = preg_replace('/\D/', '', (string) $raw);
        if ($d === '') {
            return null;
        }
        if (strlen($d) < 8) {
            return str_repeat('*', strlen($d));
        }

        return substr($d, 0, 4) . str_repeat('*', strlen($d) - 7) . substr($d, -3);
    }
}
