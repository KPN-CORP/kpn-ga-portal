@extends('layouts.app_memos')
@section('title', 'Daftar Memo')
@section('content')
<div class="w-full px-2 md:px-4">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Daftar Memo</h2>
        <a href="{{ route('memos.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-sm">+ Buat Memo Baru</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No Memo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Pembuat</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Perihal</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status & Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($memos as $memo)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $memo->memo_number }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $memo->creator->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $memo->perihal }}</td>
                        <td class="px-6 py-4 text-sm font-semibold text-right">Rp {{ number_format($memo->total_amount,0,',','.') }}</td>
                        <td class="px-6 py-4 text-sm whitespace-nowrap">
                            <div class="flex items-center justify-center gap-2">
                                <span class="px-2 py-1 rounded-full text-xs {{ $memo->status=='draft' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                                    {{ $memo->status=='draft' ? 'Draf' : 'Tersimpan' }}
                                </span>
                                <a href="{{ route('memos.show', $memo) }}" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-eye"></i> Lihat
                                </a>
                                @if($memo->attachments->count())
                                    <span class="text-gray-400" title="{{ $memo->attachments->count() }} lampiran">
                                        <i class="fas fa-paperclip"></i> {{ $memo->attachments->count() }}
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">
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