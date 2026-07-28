@extends('layouts.app_memos')
@section('title', 'Tim Baru')
@section('content')
<div class="w-full px-2 md:px-4 max-w-lg">
    <h2 class="text-2xl font-bold mb-4">Buat Tim Baru</h2>

    <form method="POST" action="{{ route('memo-teams.store') }}" class="bg-white rounded-xl shadow-sm p-5">
        @csrf
        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Tim</label>
        <input type="text" name="team_name" required placeholder="Contoh: Tim Finance HC"
               class="w-full border rounded-lg p-2 focus:ring-blue-500 focus:border-blue-500">
        @error('team_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror

        <div class="flex justify-end gap-2 mt-5">
            <a href="{{ route('memo-teams.index') }}" class="px-4 py-2 rounded-lg border text-gray-600">Batal</a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Simpan & Lanjut Atur Anggota</button>
        </div>
    </form>
</div>
@endsection
