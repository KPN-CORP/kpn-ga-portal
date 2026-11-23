{{--
    Bagian "Batalkan Request" (pemohon) dan "Pengembalian Kartu" di halaman detail.
    Variabel dari DetailController: $data, $canReturn, $canProcessReturn, $canCancel
    Memakai modal konfirmasi bawaan halaman detail lewat atribut data-confirm-*.
--}}
@php
    $RS        = \App\Models\IDCard\RequestIdCard::class;
    $rs        = $data->return_status ?? 'none';
    $isKembali = in_array($data->kategori, $RS::KATEGORI_KEMBALI, true);
    $fmtR      = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d-m-Y H:i') : '-';
@endphp

@if(!empty($canCancel))
<div class="mt-8 pt-6 border-t border-gray-200">
    <h3 class="text-lg font-medium text-gray-800 mb-4">Batalkan Request</h3>
    <div class="bg-gray-50 rounded-lg p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-sm text-gray-600">Request ini masih menunggu diproses. Salah isi data? Batalkan lalu buat request baru.</p>
        <form action="{{ route('idcard.cancel', $data->id) }}" method="POST" id="cancelForm">
            @csrf
            <button type="button" data-confirm-form="cancelForm"
                    data-confirm-title="Batalkan request ini?"
                    data-confirm-text="Request atas nama {{ $data->nama }} akan dibatalkan dan tidak bisa dikembalikan."
                    data-confirm-label="Batalkan Request"
                    data-confirm-class="bg-red-600 hover:bg-red-700"
                    class="inline-flex items-center justify-center px-5 py-2.5 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50">Batalkan Request</button>
        </form>
    </div>
</div>
@endif

@if($isKembali && $data->status === 'approved')
<div class="mt-8 pt-6 border-t border-gray-200" id="pengembalian">
    <h3 class="text-lg font-medium text-gray-800 mb-4">Pengembalian Kartu</h3>
    <div class="bg-gray-50 rounded-lg p-6 space-y-4">

        {{-- Status & rincian --}}
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm text-gray-600">Status:</span>
            @if($rs === 'none')
                <span class="text-sm font-medium text-gray-800">{{ $data->is_active ? 'Kartu masih dipegang pemohon' : 'Kartu nonaktif' }}</span>
            @else
                @include('idcard.partials.return-badge', ['status' => $rs])
            @endif
        </div>

        @if($rs !== 'none')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3 text-sm">
            @if(!empty($data->return_requested_by_name))
                <div><p class="text-gray-500">Diajukan oleh</p><p class="font-medium">{{ $data->return_requested_by_name }}</p><p class="text-xs text-gray-500">{{ $fmtR($data->return_requested_at) }}</p></div>
            @endif
            @if(!empty($data->return_kondisi))
                <div><p class="text-gray-500">Kondisi kartu</p><p class="font-medium">{{ $RS::RETURN_KONDISI_LABELS[$data->return_kondisi] ?? $data->return_kondisi }}</p></div>
            @endif
            @if(!empty($data->return_note))
                <div class="md:col-span-2"><p class="text-gray-500">Catatan pemohon</p><div class="bg-white border border-gray-200 rounded p-3">{{ $data->return_note }}</div></div>
            @endif
            @if(!empty($data->return_processed_by_name))
                <div><p class="text-gray-500">{{ $rs === 'returned' ? 'Diterima oleh' : 'Diproses oleh' }}</p><p class="font-medium">{{ $data->return_processed_by_name }}</p><p class="text-xs text-gray-500">{{ $fmtR($data->return_processed_at) }}</p></div>
            @endif
            @if($rs === 'rejected' && !empty($data->return_reject_reason))
                <div class="md:col-span-2"><p class="text-gray-500">Alasan penolakan</p><div class="bg-red-50 border border-red-200 rounded p-3 text-red-800">{{ $data->return_reject_reason }}</div></div>
            @endif
        </div>
        @endif

        {{-- Pemohon --}}
        @if(!empty($canReturn))
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <button type="button" data-return-card data-id="{{ $data->id }}" data-nama="{{ $data->nama }}" data-nomor="{{ $data->nomor_kartu }}"
                        class="inline-flex items-center justify-center px-5 py-2.5 bg-blue-600 text-white rounded-md hover:bg-blue-700">{{ $rs === 'rejected' ? 'Ajukan Pengembalian Lagi' : 'Kembalikan Kartu' }}</button>
                <span class="text-xs text-gray-500">Selesai dengan kartu ini? Ajukan pengembalian, lalu serahkan kartu fisik ke GA.</span>
            </div>
        @elseif($rs === 'requested' && !$canProcessReturn)
            <p class="text-sm text-gray-600">Menunggu GA mengonfirmasi penerimaan kartu fisik.</p>
        @endif

        {{-- Admin --}}
        @if(!empty($canProcessReturn))
            <div class="border-t border-gray-200 pt-4 space-y-5">
                <form action="{{ route('idcard.return.approve', $data->id) }}" method="POST" id="returnApproveForm">
                    @csrf
                    <p class="text-sm font-medium text-gray-700 mb-3">{{ $rs === 'requested' ? 'Konfirmasi kartu sudah diterima' : 'Terima kartu langsung (tanpa pengajuan)' }}</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Kartu</label>
                            <input type="text" name="nomor_kartu" maxlength="50" value="{{ $data->nomor_kartu }}" class="w-full px-3 py-2 border border-gray-300 rounded-md font-mono text-sm">
                            <p class="text-xs text-gray-500 mt-1">Cocokkan dengan kartu fisik. Ubah bila berbeda.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kondisi kartu</label>
                            <select name="return_kondisi" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                                @foreach($RS::RETURN_KONDISI_LABELS as $v => $l)
                                    <option value="{{ $v }}" {{ ($data->return_kondisi ?: 'baik') === $v ? 'selected' : '' }}>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <button type="button" data-confirm-form="returnApproveForm"
                            data-confirm-title="Kartu sudah diterima?"
                            data-confirm-text="Kartu atas nama {{ $data->nama }} akan ditandai sudah dikembalikan dan dinonaktifkan."
                            data-confirm-label="Terima Kartu"
                            data-confirm-class="bg-green-600 hover:bg-green-700"
                            class="inline-flex items-center px-5 py-2.5 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50">Terima Kartu</button>
                </form>

                @if($rs === 'requested')
                <form action="{{ route('idcard.return.reject', $data->id) }}" method="POST" id="returnRejectForm">
                    @csrf
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tolak pengajuan (mis. kartu belum diterima) *</label>
                    <textarea name="return_reject_reason" rows="2" minlength="5" maxlength="500" required class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm mb-3" placeholder="Alasan penolakan (minimal 5 karakter)"></textarea>
                    <button type="button" data-confirm-form="returnRejectForm"
                            data-confirm-title="Tolak pengajuan pengembalian?"
                            data-confirm-text="Pemohon akan diberi tahu dan pengingat pengembalian berjalan kembali."
                            data-confirm-label="Tolak"
                            data-confirm-class="bg-red-600 hover:bg-red-700"
                            class="inline-flex items-center px-5 py-2.5 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50">Tolak Pengajuan</button>
                </form>
                @endif
            </div>
        @endif
    </div>
</div>
@endif
