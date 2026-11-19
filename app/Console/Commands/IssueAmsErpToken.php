<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * php artisan ams-erp:issue-token
 * php artisan ams-erp:issue-token --revoke   (cabut token lama, terbitkan baru)
 */
class IssueAmsErpToken extends Command
{
    protected $signature = 'ams-erp:issue-token {--revoke : Cabut semua token lama sebelum menerbitkan yang baru}';

    protected $description = 'Terbitkan token Sanctum read-only untuk akun integrasi AMS ERP';

    public function handle(): int
    {
        $user = User::where('username', 'ams_erp')->first();

        if (! $user) {
            $this->error('User "ams_erp" belum ada. Jalankan `php artisan migrate` dulu.');
            return self::FAILURE;
        }

        if (! $user->isDrmsSuperAdmin()) {
            $this->warn('Peringatan: isDrmsSuperAdmin() untuk user ams_erp masih FALSE.');
            $this->warn('Cek baris di tb_access_menu (username=ams_erp, drms_superadmin=1).');
        }

        if ($this->option('revoke')) {
            $count = $user->tokens()->count();
            $user->tokens()->delete();
            $this->info("Token lama dicabut ({$count} token).");
        }

        $token = $user->createToken('ams_erp', ['read'])->plainTextToken;

        $this->newLine();
        $this->info('Token untuk tim ERP (kirim lewat README_API_ERP.md):');
        $this->line($token);
        $this->newLine();
        $this->warn('Token ini hanya tampil sekali di sini — simpan sekarang.');

        return self::SUCCESS;
    }
}
