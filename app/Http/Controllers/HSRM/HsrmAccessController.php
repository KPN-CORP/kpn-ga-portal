<?php

namespace App\Http\Controllers\HSRM;

use App\Helpers\HsrmRole;
use App\Http\Controllers\Controller;
use App\Models\AreaKerja;
use App\Models\HSRM\HsrmUserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Halaman "Role Access" - khusus superadmin.
 * Superadmin menentukan siapa memegang role apa (pic / admin / management / superadmin),
 * di area mana, serta hak approve & notifikasi email. Bisa tambah, ubah role, dan hapus.
 */
class HsrmAccessController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!HsrmRole::isSuperadmin()) {
                abort(403, 'Only superadmin can manage role access.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = HsrmUserRole::with(['user', 'area'])->orderBy('role')->orderBy('user_id');

        if (in_array($request->query('role'), HsrmRole::ALL, true)) {
            $query->where('role', $request->query('role'));
        }
        if ($search = trim((string) $request->query('q'))) {
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $assignments = $query->paginate(25)->withQueryString();

        // Untuk dropdown user yang bisa diketik (cari nama / username)
        $users = User::orderBy('name')->get(['id', 'name', 'username'])
            ->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'username' => $u->username])
            ->values();

        $areas = AreaKerja::orderBy('nama_area')->get();
        $roles = HsrmRole::ALL;

        return view('hsrm.access.index', compact('assignments', 'users', 'areas', 'roles'));
    }

    // Tambah akses baru, atau ubah role jika user + area tersebut sudah punya akses (upsert).
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => ['required', Rule::in(HsrmRole::ALL)],
            'area_id' => 'nullable|exists:stock_ctl_area_kerja,id_area_kerja',
        ]);

        $isGlobal = in_array($data['role'], HsrmRole::GLOBAL, true);
        if (!$isGlobal && empty($data['area_id'])) {
            return back()->withErrors(['area_id' => 'Area wajib dipilih untuk role PIC dan Admin.'])->withInput();
        }
        $areaId = $isGlobal ? null : $data['area_id'];

        $existing = HsrmUserRole::where('user_id', $data['user_id'])->where('area_id', $areaId)->first();
        if ($existing && $existing->role === HsrmRole::SUPERADMIN
            && $data['role'] !== HsrmRole::SUPERADMIN && $this->isLastSuperadmin($existing)) {
            return back()->with('error', 'Tidak bisa mengubah superadmin terakhir.')->withInput();
        }

        HsrmUserRole::updateOrCreate(
            ['user_id' => $data['user_id'], 'area_id' => $areaId],
            [
                'role' => $data['role'],
                'can_approve' => $request->boolean('can_approve'),
                'email_notif' => $request->boolean('email_notif'),
            ]
        );

        $this->refreshOwnSession((int) $data['user_id']);

        return redirect()->route('hsrm.superadmin.access.index')->with('success', 'Role access saved.');
    }

    // Ubah baris yang sudah ada: role (pic / admin / management / superadmin), area, approve, email.
    public function update(Request $request, $id)
    {
        $row = HsrmUserRole::findOrFail($id);

        $data = $request->validate([
            'role' => ['required', Rule::in(HsrmRole::ALL)],
            'area_id' => 'nullable|exists:stock_ctl_area_kerja,id_area_kerja',
        ]);

        $isGlobal = in_array($data['role'], HsrmRole::GLOBAL, true);
        $areaId = $isGlobal ? null : ($data['area_id'] ?? $row->area_id);

        if (!$isGlobal && empty($areaId)) {
            return back()->with('error', 'Area wajib dipilih untuk role PIC dan Admin.');
        }

        if ($row->role === HsrmRole::SUPERADMIN && $data['role'] !== HsrmRole::SUPERADMIN
            && $this->isLastSuperadmin($row)) {
            return back()->with('error', 'Tidak bisa mengubah superadmin terakhir.');
        }

        $conflict = HsrmUserRole::where('user_id', $row->user_id)
            ->where('area_id', $areaId)
            ->where('id', '!=', $row->id)
            ->exists();
        if ($conflict) {
            return back()->with('error', 'User ini sudah punya akses ' . ($areaId ? 'di area tersebut' : 'global') . '. Ubah baris yang sudah ada.');
        }

        $row->update([
            'role' => $data['role'],
            'area_id' => $areaId,
            'can_approve' => $request->boolean('can_approve'),
            'email_notif' => $request->boolean('email_notif'),
        ]);

        $this->refreshOwnSession($row->user_id);

        return redirect()->route('hsrm.superadmin.access.index')->with('success', 'Role access updated.');
    }

    public function destroy($id)
    {
        $row = HsrmUserRole::findOrFail($id);

        if ($row->role === HsrmRole::SUPERADMIN && $this->isLastSuperadmin($row)) {
            return back()->with('error', 'Tidak bisa menghapus superadmin terakhir.');
        }

        $userId = $row->user_id;
        $row->delete();

        $this->refreshOwnSession($userId);

        return redirect()->route('hsrm.superadmin.access.index')->with('success', 'Role access removed.');
    }

    private function isLastSuperadmin(HsrmUserRole $row): bool
    {
        return !HsrmUserRole::where('role', HsrmRole::SUPERADMIN)->where('id', '!=', $row->id)->exists();
    }

    // Jika superadmin mengubah akses miliknya sendiri, perbarui role di session saat itu juga.
    private function refreshOwnSession(int $userId): void
    {
        if ($userId === (int) auth()->id()) {
            session(['hsrm_role' => HsrmRole::resolveForUser(auth()->user())]);
        }
    }
}