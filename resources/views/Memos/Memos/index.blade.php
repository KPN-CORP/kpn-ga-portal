@extends('layouts.app_memos')
@section('title', 'Daftar Memo')
@section('content')
<div class="w-full px-2 md:px-4">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Daftar Memo</h2>
        <a href="{{ route('memos.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-sm">+ Buat Memo Baru</a>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-50 text-green-700 text-sm px-4 py-2 rounded-lg">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-50 text-red-700 text-sm px-4 py-2 rounded-lg">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No Memo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Pembuat</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Perihal</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Lampiran</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status & Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($memos as $memo)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                            {{ $memo->memo_number ?? '—' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $memo->creator->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $memo->perihal }}</td>
                        <td class="px-6 py-4 text-sm font-semibold text-right">Rp {{ number_format($memo->total_amount,0,',','.') }}</td>
                        <td class="px-6 py-4 text-sm">
                            @if($memo->attachments->count())
                                <div x-data="{ open: false }" class="relative flex justify-center">
                                    <button @click="open = !open" @click.outside="open = false"
                                        class="flex items-center gap-1 text-gray-500 hover:text-blue-600">
                                        <i class="fas fa-paperclip"></i>
                                        <span class="text-xs">{{ $memo->attachments->count() }}</span>
                                    </button>
                                    <div x-show="open" x-cloak x-transition
                                        class="absolute right-0 top-6 z-20 w-56 bg-white border rounded-lg shadow-lg p-2 text-left">
                                        <p class="text-xs font-semibold text-gray-500 px-1 pb-1 border-b mb-1">Lampiran</p>
                                        <ul class="max-h-48 overflow-y-auto">
                                            @foreach($memo->attachments as $att)
                                            <li>
                                                <a href="{{ Storage::url($att->file_path) }}" target="_blank" rel="noopener"
                                                    class="flex items-center gap-2 px-1 py-1.5 rounded hover:bg-gray-50 text-xs text-gray-700">
                                                    <i class="fas {{ str_contains($att->mime_type,'pdf') ? 'fa-file-pdf text-red-500' : 'fa-file-image text-blue-500' }}"></i>
                                                    <span class="truncate flex-1">{{ $att->original_name }}</span>
                                                    <i class="fas fa-up-right-from-square text-gray-400"></i>
                                                </a>
                                            </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @else
                                <span class="text-gray-300 flex justify-center">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm whitespace-nowrap">
                            <div class="flex items-center justify-center gap-3">
                                <span class="px-2 py-1 rounded-full text-xs {{ $memo->status=='draft' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                                    {{ $memo->status=='draft' ? 'Draf' : 'Tersimpan' }}
                                </span>
                                <a href="{{ route('memos.show', $memo) }}" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-eye"></i> Lihat
                                </a>
                                @if($memo->status === 'draft' && $memo->created_by == auth()->id())
                                    <a href="{{ route('memos.edit', $memo) }}" class="text-amber-600 hover:text-amber-800">
                                        <i class="fas fa-pen"></i> Edit
                                    </a>
                                    <form action="{{ route('memos.destroy', $memo) }}" method="POST" onsubmit="return confirm('Hapus draft ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700">
                                            <i class="fas fa-trash-alt"></i> Hapus
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                            <i class="fas fa-inbox text-3xl mb-2 block"></i>
                            Belum ada memo. <a href="{{ route('memos.create') }}" class="text-blue-600">Buat memo pertama</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t">
            {{ $memos->links() }}
        </div>
    </div>
</div>
@endsection
