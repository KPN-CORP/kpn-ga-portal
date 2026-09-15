<?php

namespace App\Support;

use App\Models\AccessMenu;
use App\Models\Drms\DriverRequest;
use App\Models\Feedbacks\Feedback;
use App\Models\Feedbacks\FeedbackReply;
use App\Models\HelpTiket;
use App\Models\HSRM\HsrmCertificate;
use App\Models\HSRM\HsrmEquipment;
use App\Models\IDCard\RequestIdCard;
use App\Models\Mailing;
use App\Models\StockCtl\Permintaan;
use App\Models\StockCtl\UserProfil;
use App\Models\Task_M\TaskUnit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Menghitung badge angka merah yang tampil di atas ikon tools dashboard
 * (mirip badge notifikasi ikon aplikasi Android).
 *
 * Prinsip: satu method per modul, tiap method meniru PERSIS query "pending
 * untuk role ini" yang sudah dipakai di controller approval/proses modul
 * tersebut (bukan query baru yang saya karang) — supaya angkanya selalu
 * sesuai alur bisnis & role user yang login, bukan sekadar jumlah global.
 *
 * Setiap method dibungkus try/catch: kalau satu modul errornya (tabel/kolom
 * beda di server), modul lain tetap tampil normal, cuma badge modul itu
 * yang jadi 0 dan errornya dicatat ke log.
 */
class DashboardBadgeService
{
    public static function computeAll(User $user): array
    {
        return [
            'stock'         => self::safe(fn () => self::stockL1($user)),
            'stock_admin'   => self::safe(fn () => self::stockAdmin($user)),
            'car'           => self::safe(fn () => self::drmsL1($user)),
            'car_admin'     => self::safe(fn () => self::drmsAdmin($user)),
            'idcard'        => self::safe(fn () => self::idcard($user)),
            'trackreceipt'  => self::safe(fn () => self::trackReceipt($user)),
            'messenger'     => self::safe(fn () => self::messengerUser($user)),
            'messenger_admin' => self::safe(fn () => self::messengerKurir($user)),
            'mailing_admin' => self::safe(fn () => self::mailingAdmin($user)),
            'supplies_admin' => self::safe(fn () => self::suppliesAdmin($user)),
            'apart_admin'   => self::safe(fn () => self::apartAdmin($user)),
            'helpdesk'      => self::safe(fn () => self::helpdeskUser($user)),
            'helpdesk_admin' => self::safe(fn () => self::helpdeskAdmin($user)),
            'feedback'      => self::safe(fn () => self::feedbackUser($user)),
            'feedback_admin' => self::safe(fn () => self::feedbackAdmin($user)),
            'hsr'           => self::safe(fn () => self::hsrm($user)),
            'hsr_admin'     => self::safe(fn () => self::hsrm($user)),
            'taskmonitor'   => self::safe(fn () => self::taskMonitor($user)),
        ];
    }

    protected static function safe(\Closure $fn): int
    {
        try {
            return (int) $fn();
        } catch (\Throwable $e) {
            Log::warning('DashboardBadgeService gagal hitung badge: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Stock Control — approver L1 (atasan langsung pemohon).
     * Meniru ApprovalL1Controller::authorizeL1() / index().
     */
    protected static function stockL1(User $user): int
    {
        return Permintaan::where('status', Permintaan::STATUS_PENDING_L1)
            ->whereExists(function ($q) use ($user) {
                $q->select(DB::raw(1))
                  ->from('stock_ctl_user_profil')
                  ->whereColumn('stock_ctl_user_profil.id_user', 'stock_ctl_permintaan.id_user_pemohon')
                  ->where('stock_ctl_user_profil.id_approver', $user->id);
            })->count();
    }

    /**
     * Stock Control — admin/superadmin (menunggu approval admin).
     * Meniru ApprovalAdminController::index() tapi tanpa bergantung session,
     * langsung dari tb_access_menu + stock_ctl_user_profil.
     */
    protected static function stockAdmin(User $user): int
    {
        $access = AccessMenu::where('username', $user->username)->first();
        if (!$access) {
            return 0;
        }

        $isSuper = (bool) ($access->stock_ctl_superadmin ?? false);
        $isAdmin = (bool) ($access->stock_ctl_admin ?? false);

        if (!$isSuper && !$isAdmin) {
            return 0;
        }

        $query = Permintaan::where('status', Permintaan::STATUS_PENDING_ADMIN);

        if (!$isSuper) {
            $profil = UserProfil::where('id_user', $user->id)->first();
            $bisnisUnitId = $profil->id_bisnis_unit ?? null;
            $query->whereExists(function ($q) use ($bisnisUnitId) {
                $q->select(DB::raw(1))
                  ->from('stock_ctl_user_profil')
                  ->whereColumn('stock_ctl_user_profil.id_user', 'stock_ctl_permintaan.id_user_pemohon')
                  ->where('stock_ctl_user_profil.id_bisnis_unit', $bisnisUnitId);
            });
        }

        return $query->count();
    }

    /**
     * DRMS — approver L1 (atasan langsung pemohon driver).
     * Meniru AppL1Controller::index().
     */
    protected static function drmsL1(User $user): int
    {
        return DriverRequest::where('approver_l1_id', $user->id)
            ->where('status', 'pending_l1')
            ->count();
    }

    /**
     * DRMS — admin BU (menunggu approval admin), pakai scope model yang
     * sudah ada supaya persis sama dengan AppAdminController::index().
     */
    protected static function drmsAdmin(User $user): int
    {
        if ($user->isDrmsSuperAdmin() || $user->hasDrmsAllBuAccess()) {
            return DriverRequest::where('status', 'approved_l1')->count();
        }

        $profile = $user->drmsProfile;
        if (!$profile) {
            return 0;
        }

        return DriverRequest::pendingForAdmin($profile->business_unit_id, $profile->area)->count();
    }

    /**
     * ID Card — yang berwenang approve (superadmin proses_idcard, atau
     * admin BU tertentu). Meniru IDCardAccessTrait.
     */
    protected static function idcard(User $user): int
    {
        $isSuperAdmin = $user->username === 'admin'
            || DB::table('tb_access_menu')
                ->where('username', $user->username)
                ->where('proses_idcard', 1)
                ->exists();

        if ($isSuperAdmin) {
            return RequestIdCard::where('status', 'pending')->count();
        }

        $adminBU = DB::table('request_idcard_accesbu')
            ->where('user_id', $user->id)
            ->pluck('bisnis_unit_id')
            ->toArray();

        if (empty($adminBU)) {
            return 0;
        }

        return RequestIdCard::where('status', 'pending')
            ->whereIn('bisnis_unit_id', $adminBU)
            ->count();
    }

    /**
     * E-Tracking Receipt — dokumen yang saat ini ada "di tangan" user ini
     * dan perlu diterima/tolak/teruskan (kolom penerima_id = current holder).
     */
    protected static function trackReceipt(User $user): int
    {
        return DB::table('track_r_documents')
            ->where('penerima_id', $user->id)
            ->where('status', 'dikirim')
            ->count();
    }

    /**
     * E-Messenger — pengiriman milik user ini yang DITOLAK, perlu dikirim
     * ulang (tombol "Kirim Ulang" di MessengerController).
     */
    protected static function messengerUser(User $user): int
    {
        $pelanggan = DB::table('tb_pelanggan')->where('id_login', $user->id)->first();
        if (!$pelanggan) {
            return 0;
        }

        return DB::table('tb_transaksi')
            ->where('pengirim', $pelanggan->id_pelanggan)
            ->where('status', 'Ditolak')
            ->count();
    }

    /**
     * GA E-Messenger (kurir/Antaran) — kiriman yang masih harus diproses
     * kurir ini. Meniru AntaranKurirController::proses().
     */
    protected static function messengerKurir(User $user): int
    {
        $hasAccessAll = DB::table('tb_access_menu')
            ->where('username', $user->username)
            ->where('akses_messenger_all', 1)
            ->exists();

        $query = DB::table('tb_transaksi')
            ->whereNotIn('status', ['Terkirim', 'Ditolak', 'Batal']);

        if (!$hasAccessAll) {
            $pelanggan = DB::table('tb_pelanggan')->where('id_login', $user->id)->first();
            if (!$pelanggan) {
                return 0;
            }
            $query->where(function ($q) use ($pelanggan) {
                $q->where('kurir', $pelanggan->id_pelanggan)->orWhere('kurir', 0);
            });
        }

        return $query->count();
    }

    /**
     * GA E-Mailing Room — surat yang perlu diproses petugas mailing room.
     * Hanya dihitung kalau user memang punya izin proses (mailing_proses),
     * sesuai MailingController::applyAccessFilter().
     */
    protected static function mailingAdmin(User $user): int
    {
        $access = DB::table('tb_access_menu')->where('username', $user->username)->first();
        if (!$access || ($access->mailing_proses ?? 0) != 1) {
            return 0;
        }

        return Mailing::whereIn('mailing_status', ['Mailing Room', 'Lantai 47'])->count();
    }

    /**
     * E-Supplies — permintaan menunggu approval admin supplies.
     * Hanya dihitung untuk user dengan flag supplies_admin (CheckSuppliesAccess).
     */
    protected static function suppliesAdmin(User $user): int
    {
        $access = DB::table('tb_access_menu')->where('username', $user->username)->first();
        if (!$access || !($access->supplies_admin ?? false)) {
            return 0;
        }

        return DB::table('supplies_permintaan')->where('status', 'pending')->count();
    }

    /**
     * E-Services Apartment — permintaan unit menunggu approval admin.
     */
    protected static function apartAdmin(User $user): int
    {
        return DB::table('tb_apartemen_request')->where('status', 'PENDING')->count();
    }

    /**
     * GA Helpdesk — tiket milik user ini berstatus WAITING (menunggu
     * respons pelapor). Meniru alur di HelpTiketController.
     */
    protected static function helpdeskUser(User $user): int
    {
        if (!$user->pelanggan) {
            return 0;
        }

        return HelpTiket::where('pelapor_id', $user->pelanggan->id_pelanggan)
            ->where('status', 'WAITING')
            ->count();
    }

    /**
     * GA Helpdesk (petugas) — tiket OPEN yang bisa diambil, di business
     * unit yang bisa diakses user; kalau tidak, tiket yang sudah
     * ditugaskan ke dia. Meniru HelpProsesController::index().
     */
    protected static function helpdeskAdmin(User $user): int
    {
        $accessibleUnits = $user->getAccessibleBusinessUnits();

        if (!empty($accessibleUnits)) {
            return HelpTiket::whereIn('bisnis_unit_id', $accessibleUnits)
                ->where('status', 'OPEN')
                ->count();
        }

        if ($user->pelanggan) {
            return HelpTiket::where('ditugaskan_ke', $user->pelanggan->id_pelanggan)
                ->where('status', 'OPEN')
                ->count();
        }

        return 0;
    }

    /**
     * Feedback — balasan admin yang belum dibaca user ini.
     */
    protected static function feedbackUser(User $user): int
    {
        return FeedbackReply::whereHas('feedback', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->where('user_id', '!=', $user->id)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Feedback (admin) — thread feedback yang masih berstatus open.
     * Hanya dihitung untuk user yang memang terdaftar sebagai FeedbackAdmin.
     */
    protected static function feedbackAdmin(User $user): int
    {
        if (!$user->isFeedbackAdmin()) {
            return 0;
        }

        return Feedback::where('status', 'open')->count();
    }

    /**
     * HSR Management — sertifikat + alat yang menunggu verifikasi,
     * dibatasi ke area yang bisa di-approve user (kecuali admin HSRM).
     * Dipakai untuk tile user maupun admin — angkanya sama, cuma tampil
     * di tile mana pun yang memang bisa diakses user tsb.
     */
    protected static function hsrm(User $user): int
    {
        $isAdmin = $user->isHsrmAdmin();

        $certQuery = HsrmCertificate::where('status_verif', 'pending');
        $eqQuery = HsrmEquipment::where('status_verif', 'pending');

        if (!$isAdmin) {
            $areaIds = DB::table('hsrm_user_roles')
                ->where('user_id', $user->id)
                ->where('can_approve', true)
                ->pluck('area_id')
                ->toArray();

            if (empty($areaIds)) {
                return 0;
            }

            $certQuery->whereIn('area_id', $areaIds);
            $eqQuery->whereIn('area_id', $areaIds);
        }

        return $certQuery->count() + $eqQuery->count();
    }

    /**
     * Task — unit task milik user sendiri yang masih 'pending' (pengingat
     * pribadi, bukan approval — tapi tetap "perlu perhatian" user itu).
     */
    protected static function taskMonitor(User $user): int
    {
        return TaskUnit::whereHas('taskMonitor', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->where('status', 'pending')
            ->count();
    }
}
