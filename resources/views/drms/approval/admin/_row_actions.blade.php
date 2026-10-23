{{-- Tombol aksi satu request (dipakai tampilan tabel desktop & kartu HP). Butuh: $req, $detailData. --}}
<div class="flex flex-wrap items-center gap-2">
        <button type="button" @click="openDetailModal({{ json_encode($detailData) }})" title="Lihat detail permintaan" class="bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-1.5 rounded text-xs font-semibold">Detail</button>

        {{-- Pending Admin: Proses / Tolak / Forward --}}
        @if($req->status === 'approved_l1')
            <a href="{{ route('drms.approval.admin.edit', $req->id) }}" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded text-xs font-semibold inline-block">Proses</a>
            <button type="button" @click="openRejectModal({{ $req->id }})" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded text-xs font-semibold">Tolak</button>
            <button type="button" @click="openForwardModal({{ $req->id }})" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded text-xs font-semibold">Forward</button>
        @endif

        {{-- Disetujui: Selesai / Ganti Driver / Pending --}}
        @if($req->status === 'approved_admin')
            @if($req->canComplete)
                <form action="{{ route('drms.approval.admin.complete', $req->id) }}" method="POST" onsubmit="return confirm('Tandai perjalanan ini selesai?')">
                    @csrf
                    <button type="submit" title="Tandai perjalanan ini selesai. Driver &amp; kendaraan kembali tersedia." class="bg-green-500 hover:bg-green-600 text-white px-2.5 py-1.5 rounded text-xs font-semibold">✅ Selesai</button>
                </form>
            @else
                <span class="text-xs text-gray-400 cursor-help px-1" title="{{ $req->completeBlockReason }}">🔒</span>
            @endif
            @if($req->driver_id && !$req->merged_into_id)
                <button type="button"
                        @click="openSwapModal({{ $req->id }}, @js($req->driver->name ?? '-'), @js($req->vehicle ? ($req->vehicle->type.' - '.$req->vehicle->plate_number) : '-'))"
                        title="Ganti driver dan/atau kendaraan. Request tetap berstatus Disetujui." class="bg-orange-500 hover:bg-orange-600 text-white px-2.5 py-1.5 rounded text-xs font-semibold">🔄 Driver</button>
            @endif
            @if($req->canReturnToPending)
                <button type="button" @click="openReturnModal({{ $req->id }}, @js($req->request_no))"
                        title="Kembalikan ke Pending Admin untuk diproses ulang. Driver &amp; kendaraan dilepas. Hanya bisa sebelum jam perjalanan berakhir dan bukan request voucher."
                        class="bg-gray-600 hover:bg-gray-700 text-white px-2.5 py-1.5 rounded text-xs font-semibold">↩ Pending</button>
            @endif
        @endif
</div>
