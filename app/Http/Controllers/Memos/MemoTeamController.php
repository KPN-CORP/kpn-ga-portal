<?php

namespace App\Http\Controllers\Memos;

use App\Http\Controllers\Controller;
use App\Models\Memos\MemoTeam;
use App\Models\Memos\MemoTeamAdmin;
use App\Models\Memos\MemoTeamMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Halaman kelola Tim e-Memo. Hanya superadmin yang bisa masuk (lihat route: middleware 'memo.superadmin').
 * Di sini superadmin:
 *  - Membuat tim baru
 *  - Menunjuk 1 atau lebih user jadi admin tim
 *  - Menambahkan anggota ke tim + menentukan admin mana yang jadi acuan nomor memo anggota itu
 */
class MemoTeamController extends Controller
{
    public function index()
    {
        $teams = MemoTeam::with(['admins', 'members'])->withCount('members')->latest()->get();
        return view('Memos.MemoTeams.index', compact('teams'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get(['id', 'name', 'username', 'email']);
        return view('Memos.MemoTeams.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'team_name' => 'required|string|max:255|unique:memo_teams,team_name',
        ]);

        $team = MemoTeam::create([
            'team_name' => $request->team_name,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('memo-teams.show', $team)->with('success', 'Tim berhasil dibuat. Silakan tambahkan admin & anggota.');
    }

    public function show(MemoTeam $memoTeam)
    {
        $memoTeam->load(['admins', 'members']);
        $users = User::orderBy('name')->get(['id', 'name', 'username', 'email']);

        return view('Memos.MemoTeams.show', [
            'team' => $memoTeam,
            'users' => $users,
        ]);
    }

    public function destroy(MemoTeam $memoTeam)
    {
        $memoTeam->delete();
        return redirect()->route('memo-teams.index')->with('success', 'Tim dihapus');
    }

    // ---------- Kelola admin dalam tim ----------

    public function addAdmin(Request $request, MemoTeam $memoTeam)
    {
        $request->validate([
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::unique('memo_team_admins', 'user_id')->where('team_id', $memoTeam->id),
            ],
            'jabatan' => 'nullable|string|max:255',
        ]);

        MemoTeamAdmin::create([
            'team_id' => $memoTeam->id,
            'user_id' => $request->user_id,
            'jabatan' => $request->jabatan,
            'assigned_by' => auth()->id(),
        ]);

        // Pastikan flag memo_admin di tb_access_menu user ini aktif supaya menu admin muncul.
        $this->ensureMemoAdminFlag($request->user_id);

        return back()->with('success', 'Admin ditambahkan ke tim');
    }

    public function removeAdmin(MemoTeam $memoTeam, User $user)
    {
        MemoTeamAdmin::where('team_id', $memoTeam->id)->where('user_id', $user->id)->delete();
        return back()->with('success', 'Admin dikeluarkan dari tim');
    }

    /**
     * Ubah jabatan admin yang sudah terdaftar di tim ini. Jabatan ini yang otomatis
     * dipakai mengisi "Penandatangan" & "Jabatan" pada memo yang dibuat tim ini.
     */
    public function updateAdminJabatan(Request $request, MemoTeam $memoTeam, User $user)
    {
        $request->validate([
            'jabatan' => 'nullable|string|max:255',
        ]);

        MemoTeamAdmin::where('team_id', $memoTeam->id)
            ->where('user_id', $user->id)
            ->update(['jabatan' => $request->jabatan]);

        return back()->with('success', 'Jabatan admin diperbarui');
    }

    // ---------- Kelola anggota dalam tim ----------

    public function addMember(Request $request, MemoTeam $memoTeam)
    {
        $request->validate([
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::unique('memo_team_members', 'user_id')->where('team_id', $memoTeam->id),
            ],
            'responsible_admin_id' => [
                'nullable',
                Rule::exists('memo_team_admins', 'user_id')->where('team_id', $memoTeam->id),
            ],
        ]);

        // Kalau tim cuma punya 1 admin, otomatis pakai admin itu tanpa perlu dipilih manual.
        $responsibleAdminId = $request->responsible_admin_id;
        if (!$responsibleAdminId) {
            $adminIds = $memoTeam->admins()->pluck('users.id');
            if ($adminIds->count() === 1) {
                $responsibleAdminId = $adminIds->first();
            }
        }

        MemoTeamMember::create([
            'team_id' => $memoTeam->id,
            'user_id' => $request->user_id,
            'responsible_admin_id' => $responsibleAdminId,
            'assigned_by' => auth()->id(),
        ]);

        return back()->with('success', 'Anggota ditambahkan ke tim');
    }

    public function removeMember(MemoTeam $memoTeam, User $user)
    {
        MemoTeamMember::where('team_id', $memoTeam->id)->where('user_id', $user->id)->delete();
        return back()->with('success', 'Anggota dikeluarkan dari tim');
    }

    public function updateMemberAdmin(Request $request, MemoTeam $memoTeam, User $user)
    {
        $request->validate([
            'responsible_admin_id' => [
                'required',
                Rule::exists('memo_team_admins', 'user_id')->where('team_id', $memoTeam->id),
            ],
        ]);

        MemoTeamMember::where('team_id', $memoTeam->id)
            ->where('user_id', $user->id)
            ->update(['responsible_admin_id' => $request->responsible_admin_id]);

        return back()->with('success', 'Admin penanggung jawab nomor memo diperbarui');
    }

    /**
     * Set flag memo_admin di tb_access_menu jadi 1 untuk user yang baru ditunjuk jadi admin tim,
     * kalau baris akses-nya sudah ada. Sesuaikan nama tabel/kolom kalau berbeda di instalasi Anda.
     */
    private function ensureMemoAdminFlag(int $userId): void
    {
        $user = User::find($userId);
        if (!$user || !$user->username) {
            return;
        }

        \Illuminate\Support\Facades\DB::table('tb_access_menu')
            ->where('username', $user->username)
            ->update(['memo_admin' => 1]);
    }
}
