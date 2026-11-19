<?php

namespace App\Http\Controllers\Memos;

use App\Http\Controllers\Controller;
use App\Models\Memos\MemoNumberSetting;
use App\Models\Memos\MemoTeamAdmin;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Halaman superadmin untuk mengatur format & urutan nomor memo per admin.
 * Contoh: "admin Budi & tim yang dia bawahi pakai format 001/HC-CRP/Fin/VII/2026".
 */
class MemoNumberSettingController extends Controller
{
    public function index()
    {
        // Semua user yang pernah/sedang jadi admin tim, dari sinilah superadmin memilih siapa yang mau di-setting.
        $admins = User::whereIn('id', MemoTeamAdmin::select('user_id')->distinct())->orderBy('name')->get();
        $settings = MemoNumberSetting::with('admin')->get()->keyBy('admin_id');

        return view('Memos.MemoNumberSettings.index', compact('admins', 'settings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'admin_id' => 'nullable|exists:users,id',
            'prefix_kode' => 'required|string|max:50',
            'format_template' => 'required|string|max:255',
            'digit_padding' => 'required|integer|min:1|max:10',
            'reset_period' => 'required|in:yearly,monthly,never',
        ]);

        MemoNumberSetting::updateOrCreate(
            ['admin_id' => $request->admin_id],
            [
                'prefix_kode' => $request->prefix_kode,
                'format_template' => $request->format_template,
                'digit_padding' => $request->digit_padding,
                'reset_period' => $request->reset_period,
                'created_by' => auth()->id(),
            ]
        );

        return back()->with('success', 'Setting nomor memo disimpan');
    }

    /**
     * Override manual counter (misal mau mulai dari angka tertentu, bukan 0/1).
     */
    public function updateCounter(Request $request, MemoNumberSetting $memoNumberSetting)
    {
        $request->validate(['last_number' => 'required|integer|min:0']);
        $memoNumberSetting->update(['last_number' => $request->last_number]);
        return back()->with('success', 'Counter nomor memo diperbarui');
    }
}
