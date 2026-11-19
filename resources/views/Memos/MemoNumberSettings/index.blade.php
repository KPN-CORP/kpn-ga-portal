@extends('layouts.app_memos')
@section('title', 'Setting Nomor Memo')
@section('content')
<div class="w-full px-2 md:px-4">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold">Setting Nomor Memo</h2>
            <p class="text-sm text-gray-500">Atur format & urutan nomor memo untuk setiap admin. Urutan berjalan sendiri per admin, reset sesuai periode yang dipilih.</p>
        </div>
        <a href="{{ route('memo-teams.index') }}" class="text-sm text-gray-500 hover:underline">&larr; Kelola Tim</a>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-50 text-green-700 text-sm px-4 py-2 rounded-lg">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Admin</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prefix / Kode</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Format</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Digit</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Reset</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Nomor Terakhir</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                @forelse($admins as $admin)
                    @php $s = $settings->get($admin->id); @endphp
                    <tr>
                        <form method="POST" action="{{ route('memo-number-settings.store') }}">
                            @csrf
                            <input type="hidden" name="admin_id" value="{{ $admin->id }}">
                            <td class="px-4 py-3 font-medium">{{ $admin->name }}</td>
                            <td class="px-4 py-3"><input name="prefix_kode" value="{{ old('prefix_kode', $s->prefix_kode ?? 'UNK') }}" class="w-24 border rounded p-1"></td>
                            <td class="px-4 py-3"><input name="format_template" value="{{ old('format_template', $s->format_template ?? '{seq}/{prefix}/Fin/{bulan_romawi}/{tahun}') }}" class="w-64 border rounded p-1"></td>
                            <td class="px-4 py-3 text-center"><input type="number" name="digit_padding" min="1" max="10" value="{{ old('digit_padding', $s->digit_padding ?? 3) }}" class="w-14 border rounded p-1 text-center"></td>
                            <td class="px-4 py-3 text-center">
                                <select name="reset_period" class="border rounded p-1">
                                    <option value="yearly" @selected(($s->reset_period ?? 'yearly') === 'yearly')>Tahunan</option>
                                    <option value="monthly" @selected(($s->reset_period ?? '') === 'monthly')>Bulanan</option>
                                    <option value="never" @selected(($s->reset_period ?? '') === 'never')>Tidak pernah</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-500">{{ $s->last_number ?? 0 }}</td>
                            <td class="px-4 py-3 text-center">
                                <button class="text-blue-600 hover:underline text-xs">Simpan</button>
                            </td>
                        </form>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">Belum ada admin. Tambahkan admin lewat halaman Kelola Tim dulu.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-3">
        Placeholder format yang bisa dipakai: <code>{seq}</code> nomor urut, <code>{prefix}</code> kode admin, <code>{bulan_romawi}</code>, <code>{bulan}</code>, <code>{tahun}</code>.
    </p>
</div>
@endsection
