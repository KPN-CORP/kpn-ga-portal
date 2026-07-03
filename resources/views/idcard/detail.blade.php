@extends('layouts.app-sidebar-card')
@section('content')
<div class="bg-white shadow rounded-lg p-6">
    <h2 class="text-2xl font-semibold mb-4 text-left">Detail Request ID Card</h2>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    <div class="flex flex-col md:flex-row gap-8">
        <div class="md:w-1/3 flex flex-col items-center space-y-6">
            @if ($data->foto && !in_array($data->kategori, ['magang', 'magang_extend']))
                <div class="w-full">
                    <p class="text-sm font-medium text-gray-600 mb-3">Foto</p>
                    <div class="bg-gray-100 rounded-lg p-3 shadow-inner">
                        <div class="relative w-full" style="padding-bottom: 150%;">
                            <img src="{{ route('idcard.photo', $data->foto) }}" class="absolute inset-0 w-full h-full object-contain rounded-md shadow" alt="Foto ID Card" onerror="this.onerror=null; this.src='https://via.placeholder.com/400x600?text=Foto+Tidak+Ditemukan';">
                        </div>
                        <div class="mt-3 text-center">
                            <a href="{{ route('idcard.photo', $data->foto) }}" download class="text-blue-600 hover:text-blue-800 text-sm font-medium">Download Foto</a>
                        </div>
                    </div>
                </div>
            @endif

            @if ($data->bukti_bayar && $data->kategori == 'ganti_kartu')
                <div class="w-full">
                    <p class="text-sm font-medium text-gray-600 mb-3">Bukti Bayar</p>
                    <div class="bg-gray-100 rounded-lg p-3 shadow-inner">
                        <div class="relative w-full" style="padding-bottom: 150%;">
                            @php $isPdf = pathinfo($data->bukti_bayar, PATHINFO_EXTENSION) == 'pdf'; @endphp
                            @if($isPdf)
                                <div class="absolute inset-0 flex flex-col items-center justify-center bg-white rounded-md">
                                    <svg class="w-16 h-16 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>
                                    <p class="text-sm font-medium text-gray-700">File PDF</p>
                                </div>
                            @else
                                <img src="{{ route('idcard.photo', $data->bukti_bayar) }}" class="absolute inset-0 w-full h-full object-contain rounded-md shadow" alt="Bukti Bayar" onerror="this.onerror=null; this.src='https://via.placeholder.com/400x600?text=File+Tidak+Ditemukan';">
                            @endif
                        </div>
                        <div class="mt-3 text-center">
                            <a href="{{ route('idcard.photo', $data->bukti_bayar) }}" download class="text-blue-600 hover:text-blue-800 text-sm font-medium">Download File</a>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="md:w-2/3">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                <div><p class="text-sm text-gray-500">NIK</p><p class="font-medium">{{ $data->nik ?? '-' }}</p></div>
                <div><p class="text-sm text-gray-500">Nama</p><p class="font-medium">{{ $data->nama }}</p></div>
                <div><p class="text-sm text-gray-500">Bisnis Unit</p><p class="font-medium">{{ $data->bisnis_unit_nama }}</p></div>
                <div><p class="text-sm text-gray-500">Kategori</p><p class="font-medium">{{ $data->kategori_label ?? $data->kategori }}</p></div>
                <div><p class="text-sm text-gray-500">Status</p>
                    <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full {{ $data->status=='pending'?'bg-yellow-100 text-yellow-800':($data->status=='approved'?'bg-green-100 text-green-800':'bg-red-100 text-red-800') }}">
                        {{ ucfirst($data->status) }}
                    </span>
                </div>
                @if(in_array($data->kategori, ['magang','magang_extend']))
                    <div><p class="text-sm text-gray-500">Nomor Kartu</p><p class="font-medium font-mono">{{ $data->nomor_kartu ?? '-' }}</p></div>
                    <div><p class="text-sm text-gray-500">Masa Berlaku</p><p class="font-medium">{{ $data->masa_berlaku ? date('d-m-Y', strtotime($data->masa_berlaku)) : '-' }}</p></div>
                    <div><p class="text-sm text-gray-500">Sampai Tanggal</p><p class="font-medium">{{ $data->sampai_tanggal ? date('d-m-Y', strtotime($data->sampai_tanggal)) : '-' }}</p></div>
                @elseif(!empty($data->tanggal_join))
                    <div><p class="text-sm text-gray-500">Tanggal Join</p><p class="font-medium">{{ date('d-m-Y', strtotime($data->tanggal_join)) }}</p></div>
                @endif
                <div><p class="text-sm text-gray-500">Tanggal Request</p><p class="font-medium">{{ date('d-m-Y H:i', strtotime($data->created_at)) }}</p></div>
                @if(!empty($data->user_name))
                    <div><p class="text-sm text-gray-500">Diajukan Oleh</p><p class="font-medium">{{ $data->user_name }}</p></div>
                @endif
                @if(!empty($data->approved_by_name))
                    <div><p class="text-sm text-gray-500">Disetujui Oleh</p><p class="font-medium">{{ $data->approved_by_name }}</p><p class="text-xs text-gray-500">{{ date('d-m-Y H:i', strtotime($data->approved_at)) }}</p></div>
                @endif
                @if($data->status == 'rejected' && !empty($data->rejected_by_name))
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-500">Ditolak Oleh</p>
                        <p class="font-medium">{{ $data->rejected_by_name }}</p>
                        <p class="text-xs text-gray-500">{{ $data->rejected_at ? date('d-m-Y H:i', strtotime($data->rejected_at)) : '' }}</p>
                        @if(!empty($data->rejection_reason))
                            <div class="mt-2 bg-red-50 border border-red-200 rounded p-3"><p class="text-sm text-red-800">{{ $data->rejection_reason }}</p></div>
                        @endif
                    </div>
                @endif
                @if(!empty($data->keterangan))
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-500">Keterangan / Lantai</p>
                        <div class="bg-gray-50 border border-gray-200 rounded p-3"><p class="font-medium">{{ $data->keterangan }}</p></div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($isPending && $canProses)
    <div class="mt-8 pt-6 border-t border-gray-200">
        <h3 class="text-lg font-medium text-gray-800 mb-4">Action</h3>
        <div class="bg-gray-50 rounded-lg p-6">
            <div class="mb-4">
                <a href="{{ route('idcard.edit', $data->id) }}" class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition">Edit Data</a>
            </div>
            <form action="{{ route('idcard.approve', $data->id) }}" method="POST" class="mb-6" id="approveForm">
                @csrf
                @if(in_array($data->kategori, ['magang','magang_extend']))
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nomor Kartu *</label>
                        <input type="text" name="nomor_kartu" class="w-full px-3 py-2 border border-gray-300 rounded-md" value="{{ $data->nomor_kartu ?? '' }}" placeholder="Contoh: MAG20240115001" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sampai Tanggal (Opsional)</label>
                        <input type="date" name="sampai_tanggal" class="w-full px-3 py-2 border border-gray-300 rounded-md" value="{{ $data->sampai_tanggal ?? '' }}" min="{{ $data->masa_berlaku ?? '' }}">
                    </div>
                </div>
                @endif
                <button type="button" onclick="if(confirm('Setujui request ini?')) document.getElementById('approveForm').submit();" class="inline-flex items-center px-5 py-2.5 bg-green-600 text-white rounded-md hover:bg-green-700">Approve</button>
            </form>

            <form action="{{ route('idcard.reject', $data->id) }}" method="POST" id="rejectForm">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Alasan Penolakan *</label>
                    <textarea name="rejection_reason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Masukkan alasan penolakan (minimal 5 karakter)" required></textarea>
                </div>
                <button type="button" onclick="if(confirm('Tolak request ini?')) document.getElementById('rejectForm').submit();" class="inline-flex items-center px-5 py-2.5 bg-red-600 text-white rounded-md hover:bg-red-700">Reject</button>
            </form>
        </div>
    </div>
    @endif

    <div class="mt-8 pt-6 border-t border-gray-200">
        <h3 class="text-lg font-medium text-gray-800 mb-4">Activity Logs</h3>
        <div class="bg-gray-50 rounded-lg p-4">
            @forelse($logs as $log)
            <div class="flex items-start gap-3 p-3 bg-white rounded border mb-2">
                <div class="flex-shrink-0">
                    @switch($log->action)
                        @case('created') <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center"><svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg></div> @break
                        @case('approved') <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center"><svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></div> @break
                        @case('rejected') <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center"><svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></div> @break
                        @default <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center"><svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                    @endswitch
                </div>
                <div>
                    <p class="font-medium text-gray-800 capitalize">{{ $log->action }}</p>
                    <p class="text-sm text-gray-600">{{ $log->notes }}</p>
                    <p class="text-xs text-gray-500">by {{ $log->action_by_name ?? 'System' }} • {{ date('d-m-Y H:i', strtotime($log->created_at)) }}</p>
                </div>
            </div>
            @empty
            <p class="text-gray-500 text-center py-4">Tidak ada aktivitas</p>
            @endforelse
        </div>
    </div>

    <div class="mt-6 pt-4 border-t">
        <a href="{{ route('idcard.index') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium">← Kembali ke List</a>
    </div>
</div>
@endsection