@extends('layouts.app_stock_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans" x-data="adminDetail()">
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Approval Admin</h2>
            @if($pendingCount > 0)
                <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                    {{ $pendingCount }} menunggu admin
                </span>
            @endif
        </div>
        <button id="toggleFilterBtn" type="button"
                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-200 transition">
            Filters
        </button>
    </div>

    @if(session('success'))
        <div class="px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">{{ $errors->first() }}</div>
    @endif

    {{-- TAB STATUS (sama dengan stock-ctl/permintaan) --}}
    <div class="flex flex-wrap gap-2">
        @foreach($statuses as $key => $label)
            <a href="{{ route('stock-ctl.approval.admin.index', array_merge(request()->except(['status', 'page']), ['status' => $key])) }}"
               class="px-4 py-2 rounded-lg text-sm font-semibold transition inline-flex items-center gap-2
                      {{ $status === $key ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                {{ $label }}
                <span class="px-1.5 py-0.5 rounded-full text-xs font-semibold
                             {{ $status === $key ? 'bg-white/20 text-white' : 'bg-white text-gray-600' }}">
                    {{ number_format($counts[$key] ?? 0) }}
                </span>
            </a>
        @endforeach
    </div>

    @if(!empty($defaultBulan))
        <div class="text-xs text-gray-500">
            Riwayat ditampilkan untuk bulan berjalan ({{ $labelBulan }}). Permintaan aktif (Pending L1 &amp; Pending Admin) tampil semua.
            Gunakan filter Dari/Sampai untuk melihat periode lain.
        </div>
    @endif

    {{-- FILTER --}}
    @php
        $adaFilter = request()->hasAny(['search', 'pemohon', 'id_area_kerja', 'id_bisnis_unit', 'dari', 'sampai']);
        $kolom = 'lg:grid-cols-3';
    @endphp
    <div id="filterSection" class="bg-white border rounded-xl p-3 sm:p-4 {{ $adaFilter ? '' : 'hidden' }}">
        <form method="GET" action="{{ route('stock-ctl.approval.admin.index') }}"
              class="grid grid-cols-1 sm:grid-cols-2 {{ $kolom }} gap-3 sm:gap-4">
            {{-- tab status yang sedang aktif ikut terkirim --}}
            <input type="hidden" name="status" value="{{ $status }}">

            <div>
                <label class="text-xs sm:text-sm font-medium text-gray-600">Cari Barang</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama / kode barang"
                       class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-xs sm:text-sm font-medium text-gray-600">Nama Pemohon</label>
                <input type="text" name="pemohon" value="{{ request('pemohon') }}" placeholder="Cari nama pemohon"
                       class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            {{-- Unit bisnis: hanya superadmin --}}
            @if($bisnisUnits->isNotEmpty())
            <div>
                <label class="text-xs sm:text-sm font-medium text-gray-600">Unit Bisnis</label>
                <select name="id_bisnis_unit"
                        class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Unit</option>
                    @foreach($bisnisUnits as $bu)
                        <option value="{{ $bu->id_bisnis_unit }}" {{ request('id_bisnis_unit') == $bu->id_bisnis_unit ? 'selected' : '' }}>
                            {{ $bu->nama_bisnis_unit }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div>
                <label class="text-xs sm:text-sm font-medium text-gray-600">Area Kerja</label>
                <select name="id_area_kerja"
                        class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Area</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id_area_kerja }}" {{ request('id_area_kerja') == $area->id_area_kerja ? 'selected' : '' }}>
                            {{ $area->nama_area }}@if($bisnisUnits->isNotEmpty() && $area->bisnisUnit) ({{ $area->bisnisUnit->nama_bisnis_unit }})@endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs sm:text-sm font-medium text-gray-600">Dari Tanggal</label>
                <input type="date" name="dari" value="{{ request('dari') }}"
                       class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-xs sm:text-sm font-medium text-gray-600">Sampai Tanggal</label>
                <input type="date" name="sampai" value="{{ request('sampai') }}"
                       class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="sm:col-span-2 lg:col-span-3 flex flex-col sm:flex-row gap-2 justify-end">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700">Apply</button>
                <a href="{{ route('stock-ctl.approval.admin.index', ['status' => $status]) }}"
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-300 text-center">Reset</a>
            </div>
        </form>
    </div>

    {{-- TABEL --}}
    @php
        $fmt = fn($d) => $d ? \Carbon\Carbon::parse($d)->timezone('Asia/Jakarta')->format('d M Y H:i') : null;
        $bolehAksi = $status === 'pending_admin';
        $judulProses = [
            'pending_l1'    => 'Menunggu (L1)',
            'pending_admin' => 'Approval L1',
            'disetujui'     => 'Riwayat Approval',
            'ditolak'       => 'Penolakan',
            'dibatalkan'    => 'Pembatalan',
        ][$status] ?? 'Proses';
    @endphp
    <div class="hidden md:block bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm min-w-[900px]">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">No. Permintaan</th>
                    <th class="px-4 py-3 text-left">Pemohon</th>
                    <th class="px-4 py-3 text-left">Area</th>
                    <th class="px-4 py-3 text-left">Barang</th>
                    <th class="px-4 py-3 text-left">Jumlah</th>
                    <th class="px-4 py-3 text-left">Keterangan</th>
                    <th class="px-4 py-3 text-left">{{ $judulProses }}</th>
                    <th class="px-4 py-3 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($permintaan as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-sm">{{ $item->no_tampil }}</td>
                    <td class="px-4 py-3">
                        <div class="flex flex-col">
                            <span class="font-medium text-gray-800">{{ $item->pemohon->name ?? '-' }}</span>
                            <span class="text-xs text-gray-500">{{ $fmt($item->tanggal_permintaan) }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        {{ $item->areaKerja->nama_area ?? '-' }}
                        @if($item->areaKerja && $item->areaKerja->bisnisUnit)
                            ({{ $item->areaKerja->bisnisUnit->nama_bisnis_unit }})
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($item->substitusi)
                            <div class="text-xs text-gray-500 line-through">{{ $item->barang->nama_barang ?? '-' }}</div>
                            <div class="font-medium text-amber-700">↳ {{ $item->substitusi->nama_pengganti }}</div>
                        @else
                            {{ $item->barang->nama_barang ?? '-' }}
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        {{ number_format($item->jumlah) }} {{ $item->barang->satuan ?? '' }}
                        @if($item->substitusi)
                            <div class="text-xs text-amber-700">Diberikan: {{ (float) $item->substitusi->jumlah_pengganti }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $item->keterangan ?? '-' }}</td>

                    {{-- Kolom proses: isinya menyesuaikan tab --}}
                    <td class="px-4 py-3">
                        @switch($status)
                            @case('pending_l1')
                                <div class="flex flex-col">
                                    <span class="font-medium text-gray-800">{{ $item->pemohon->profil->approver->name ?? '-' }}</span>
                                    <span class="text-xs text-gray-500">belum diproses atasan</span>
                                </div>
                                @break

                            @case('pending_admin')
                                @if($item->approverL1)
                                    <div class="flex flex-col">
                                        <span class="font-medium text-gray-800">{{ $item->approverL1->name }}</span>
                                        @if($item->approved_l1_at)
                                            <span class="text-xs text-gray-500">{{ $fmt($item->approved_l1_at) }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                                @break

                            @case('disetujui')
                                <div class="flex flex-col gap-1">
                                    <div>
                                        <span class="text-xs text-gray-500">L1:</span>
                                        <span class="font-medium text-gray-800">{{ $item->approverL1->name ?? '-' }}</span>
                                        @if($item->approved_l1_at)<span class="text-xs text-gray-500">({{ $fmt($item->approved_l1_at) }})</span>@endif
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-500">Admin:</span>
                                        <span class="font-medium text-gray-800">{{ $item->approverAdmin->name ?? '-' }}</span>
                                        @if($item->approved_admin_at)<span class="text-xs text-gray-500">({{ $fmt($item->approved_admin_at) }})</span>@endif
                                    </div>
                                    @if($item->catatan_admin)
                                        <span class="text-xs text-gray-600">Catatan: {{ $item->catatan_admin }}</span>
                                    @endif
                                </div>
                                @break

                            @case('ditolak')
                                <div class="flex flex-col">
                                    <span class="font-medium text-gray-800">{{ $item->rejectedBy->name ?? '-' }}</span>
                                    @if($item->rejected_at)<span class="text-xs text-gray-500">{{ $fmt($item->rejected_at) }}</span>@endif
                                    <span class="text-xs text-red-600">{{ $item->alasan_tolak ?? '-' }}</span>
                                </div>
                                @break

                            @case('dibatalkan')
                                <div class="flex flex-col">
                                    @if($item->rejected_at)<span class="font-medium text-gray-800">{{ $fmt($item->rejected_at) }}</span>@endif
                                    <span class="text-xs text-gray-500">{{ $item->alasan_tolak ?? 'Dibatalkan oleh pemohon' }}</span>
                                </div>
                                @break
                        @endswitch
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="openDetail({{ $item->toJson() }})"
                                    class="px-3 py-1 bg-blue-600 text-white rounded-lg text-xs font-semibold hover:bg-blue-700">
                                Detail
                            </button>
                            @if($bolehAksi)
                            <button onclick="showApproveModal({{ $item->id_permintaan }}, {{ $item->jumlah }}, '{{ addslashes($item->barang->satuan ?? '') }}', {{ Js::from($item->barang->nama_barang ?? '-') }})"
                                    class="px-3 py-1 bg-green-600 text-white rounded-lg text-xs font-semibold hover:bg-green-700">
                                Setujui
                            </button>
                            <button onclick="showRejectModal({{ $item->id_permintaan }})"
                                    class="px-3 py-1 bg-red-600 text-white rounded-lg text-xs font-semibold hover:bg-red-700">
                                Tolak
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-10 text-center text-gray-500">
                        @if($adaFilter)
                            Data tidak ditemukan
                        @else
                            Tidak ada permintaan {{ strtolower($statuses[$status]) }}
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- KARTU (HP): tabel disembunyikan di layar kecil --}}
    <div class="md:hidden space-y-3">
        @forelse($permintaan as $item)
        <div class="bg-white border rounded-xl p-4 space-y-2">
            <div class="flex items-start justify-between gap-2">
                <span class="font-mono text-xs text-gray-600">{{ $item->no_tampil }}</span>
                <span class="text-xs text-gray-500 text-right">{{ $fmt($item->tanggal_permintaan) }}</span>
            </div>
            <div class="font-medium text-gray-800">{{ $item->pemohon->name ?? '-' }}</div>
            <div class="text-xs text-gray-500">
                {{ $item->areaKerja->nama_area ?? '-' }}
                @if($item->areaKerja && $item->areaKerja->bisnisUnit) ({{ $item->areaKerja->bisnisUnit->nama_bisnis_unit }}) @endif
            </div>
            <div>
                @if($item->substitusi)
                    <div class="text-xs text-gray-500 line-through">{{ $item->barang->nama_barang ?? '-' }}</div>
                    <div class="font-medium text-amber-700">↳ {{ $item->substitusi->nama_pengganti }}</div>
                    <div class="text-xs text-amber-700">Diberikan: {{ (float) $item->substitusi->jumlah_pengganti }}</div>
                @else
                    <div class="font-medium">{{ $item->barang->nama_barang ?? '-' }}</div>
                @endif
                <div class="text-xs text-gray-600">Diminta: {{ number_format($item->jumlah) }} {{ $item->barang->satuan ?? '' }}</div>
            </div>
            @if($item->keterangan)
                <div class="text-xs text-gray-600">{{ $item->keterangan }}</div>
            @endif
            <div class="flex flex-wrap gap-2 pt-1">
                <button type="button" @click="openDetail({{ $item->toJson() }})"
                        class="px-3 py-2 bg-blue-600 text-white rounded-lg text-xs font-semibold">Detail</button>
                @if($bolehAksi)
                <button onclick="showApproveModal({{ $item->id_permintaan }}, {{ $item->jumlah }}, '{{ addslashes($item->barang->satuan ?? '') }}', {{ Js::from($item->barang->nama_barang ?? '-') }})"
                        class="px-3 py-2 bg-green-600 text-white rounded-lg text-xs font-semibold">Setujui</button>
                <button onclick="showRejectModal({{ $item->id_permintaan }})"
                        class="px-3 py-2 bg-red-600 text-white rounded-lg text-xs font-semibold">Tolak</button>
                @endif
            </div>
        </div>
        @empty
        <div class="py-10 text-center text-gray-500 bg-white border rounded-xl">
            @if($adaFilter) Data tidak ditemukan @else Tidak ada permintaan {{ strtolower($statuses[$status]) }} @endif
        </div>
        @endforelse
    </div>

    @if($permintaan->hasPages())
        <div class="mt-4">{{ $permintaan->links() }}</div>
    @endif

    {{-- MODAL DETAIL --}}
    <div x-show="detailOpen" style="display:none" x-cloak
         class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 p-4"
         @keydown.escape.window="detailOpen = false" @click.self="detailOpen = false">
        <div class="bg-white rounded-lg p-4 sm:p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <h3 class="text-base sm:text-lg font-semibold mb-4">Detail Permintaan</h3>
            <table class="w-full text-sm">
                <tr><td class="py-2 text-gray-600 w-1/3">No. Permintaan</td><td class="py-2 font-medium font-mono" x-text="d.no_tampil"></td></tr>
                <tr><td class="py-2 text-gray-600">Tanggal</td><td class="py-2 font-medium" x-text="fmt(d.tanggal_permintaan)"></td></tr>
                <tr><td class="py-2 text-gray-600">Pemohon</td><td class="py-2 font-medium" x-text="d.pemohon?.name ?? '-'"></td></tr>
                <tr><td class="py-2 text-gray-600">Area Kerja</td><td class="py-2 font-medium" x-text="(d.area_kerja?.nama_area ?? '-') + (d.area_kerja?.bisnis_unit ? ' (' + d.area_kerja.bisnis_unit.nama_bisnis_unit + ')' : '')"></td></tr>
                <tr>
                    <td class="py-2 text-gray-600" x-text="d.status == 'pending_l1' ? 'Menunggu Approval Atasan' : 'Atasan (L1)'"></td>
                    <td class="py-2 font-medium" x-text="d.pemohon?.profil?.approver?.name ?? '-'"></td>
                </tr>
                <tr><td class="py-2 text-gray-600">Barang</td><td class="py-2 font-medium" x-text="(d.barang?.nama_barang ?? '-') + ' (' + (d.barang?.kode_barang ?? '-') + ')'"></td></tr>
                <tr><td class="py-2 text-gray-600">Jumlah</td><td class="py-2 font-medium" x-text="(d.jumlah ?? '') + ' ' + (d.barang?.satuan ?? '')"></td></tr>
                <tr><td class="py-2 text-gray-600">Alasan</td><td class="py-2 font-medium" x-text="d.keterangan ?? '-'"></td></tr>
                <tr>
                    <td class="py-2 text-gray-600">Status</td>
                    <td class="py-2">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold"
                              :class="{
                                'bg-yellow-100 text-yellow-800': d.status == 'pending_l1',
                                'bg-orange-100 text-orange-800': d.status == 'pending_admin',
                                'bg-green-100 text-green-800': d.status == 'disetujui',
                                'bg-red-100 text-red-800': d.status == 'ditolak',
                                'bg-gray-200 text-gray-700': d.status == 'dibatalkan'
                              }" x-text="statusLabel[d.status] ?? d.status"></span>
                    </td>
                </tr>
                <template x-if="d.approved_l1_by">
                    <tr><td class="py-2 text-gray-600">Approval L1</td>
                        <td class="py-2 font-medium"><span x-text="d.approver_l1?.name ?? ('User ' + d.approved_l1_by)"></span>
                            <span class="text-gray-500 text-xs" x-show="d.approved_l1_at" x-text="'(' + fmt(d.approved_l1_at) + ')'"></span></td></tr>
                </template>
                <template x-if="d.approved_admin_by">
                    <tr><td class="py-2 text-gray-600">Approval Admin</td>
                        <td class="py-2 font-medium"><span x-text="d.approver_admin?.name ?? ('User ' + d.approved_admin_by)"></span>
                            <span class="text-gray-500 text-xs" x-show="d.approved_admin_at" x-text="'(' + fmt(d.approved_admin_at) + ')'"></span></td></tr>
                </template>
                <template x-if="d.catatan_admin">
                    <tr><td class="py-2 text-gray-600">Catatan Admin</td><td class="py-2 font-medium" x-text="d.catatan_admin"></td></tr>
                </template>
                <template x-if="d.substitusi">
                    <tr><td colspan="2" class="pt-3">
                        <div class="border border-amber-200 bg-amber-50 rounded-lg p-3 text-xs space-y-1">
                            <div class="font-semibold text-amber-800">Riwayat Substitusi Barang</div>
                            <div><span class="text-gray-600">Barang diminta:</span> <span class="font-medium" x-text="d.substitusi.nama_asli + ' (' + parseFloat(d.substitusi.jumlah_diminta) + ')'"></span></div>
                            <div><span class="text-gray-600">Barang pengganti:</span> <span class="font-medium" x-text="d.substitusi.nama_pengganti + ' (' + parseFloat(d.substitusi.jumlah_pengganti) + ')'"></span></div>
                            <div><span class="text-gray-600">Alasan:</span> <span class="font-medium" x-text="d.substitusi.alasan"></span></div>
                            <div><span class="text-gray-600">Diganti oleh:</span> <span class="font-medium" x-text="(d.substitusi.admin?.name ?? '-') + ' (' + fmt(d.substitusi.created_at) + ')'"></span></div>
                        </div>
                    </td></tr>
                </template>
                <template x-if="d.status == 'ditolak' || d.status == 'dibatalkan'">
                    <tr><td class="py-2 text-gray-600" x-text="d.status == 'ditolak' ? 'Alasan Penolakan' : 'Pembatalan'"></td>
                        <td class="py-2 font-medium text-red-600"><span x-text="d.alasan_tolak ?? '-'"></span>
                            <span class="text-gray-500 text-xs" x-show="d.rejected_at" x-text="'(' + fmt(d.rejected_at) + ')'"></span></td></tr>
                </template>
            </table>
            <div class="mt-6 flex justify-end">
                <button type="button" @click="detailOpen = false" class="px-4 py-2 bg-gray-200 rounded-lg text-sm">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Approve (AJAX version) --}}
<div id="approveModal" class="fixed inset-0 bg-black bg-opacity-30 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg p-4 sm:p-6 w-full max-w-md max-h-[92vh] overflow-y-auto">
        <h3 class="text-lg font-semibold mb-4">Setujui Permintaan</h3>
        <p class="text-sm text-gray-600 mb-3">Anda dapat mengubah jumlah yang disetujui.</p>
        
        <div id="approveError" class="hidden mb-3 p-2 bg-red-100 text-red-800 rounded text-sm"></div>
        
        <form id="approveForm">
            @csrf
            <div id="approveStokInfo" class="mb-3 p-3 bg-gray-50 border rounded-lg text-sm">
                <div class="flex justify-between"><span class="text-gray-600">Stok tersedia</span><span id="approveStokTersedia" class="font-semibold">memuat...</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Sisa stok setelah disetujui</span><span id="approveStokSisa" class="font-semibold">-</span></div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Jumlah Disetujui</label>
                <input type="number" step="0.01" name="jumlah_setuju" id="approveJumlah" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                <span id="approveSatuan" class="text-xs text-gray-500"></span>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Catatan (opsional)</label>
                <textarea name="catatan" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="Tambahkan catatan untuk pemohon..."></textarea>
            </div>
            <div class="mt-6 flex flex-wrap justify-end gap-2">
                <button type="button" onclick="closeModal('approveModal')" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</button>
                <button type="button" id="approveGantiBtn" onclick="bukaGantiDariApprove()" class="hidden px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600">Ganti Barang</button>
                <button type="submit" id="approveSubmitBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg">Setujui</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Ganti Barang (Substitusi) --}}
<div id="substitusiModal" class="fixed inset-0 bg-black bg-opacity-30 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg p-4 sm:p-5 w-full max-w-xl max-h-[92vh] overflow-y-auto">
        <h3 class="text-lg font-semibold mb-1">Ganti Barang</h3>
        <p class="text-sm text-gray-600 mb-3">Pilih barang pengganti dari katalog. Permintaan akan langsung disetujui dengan barang pengganti, tanpa persetujuan tambahan dari pemohon.</p>

        <div id="subError" class="hidden mb-3 p-2 bg-red-100 text-red-800 rounded text-sm"></div>

        <div class="mb-3 p-3 bg-gray-50 border rounded-lg text-sm">
            <div class="text-xs text-gray-500">Barang diminta</div>
            <div class="font-medium" id="subAsliNama">-</div>
            <div class="text-xs text-gray-600" id="subAsliInfo"></div>
        </div>

        <form id="substitusiForm">
            @csrf
            <input type="hidden" name="id_barang_pengganti" id="subIdPengganti">

            <label class="block text-sm font-medium text-gray-600 mb-1">Cari barang pengganti (nama / merek / kode)</label>
            <input type="text" id="subCari" autocomplete="off" placeholder="Ketik nama atau merek barang..."
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <div id="subHasil" class="mt-2 border rounded-lg max-h-48 overflow-y-auto divide-y text-sm"></div>

            <div id="subRingkasan" class="hidden mt-3 border border-amber-200 bg-amber-50 rounded-lg p-3 text-sm">
                <div class="font-semibold text-amber-800 mb-2">Ringkasan Penggantian</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <div class="text-xs text-gray-500">Barang asli (diminta)</div>
                        <div class="font-medium" id="subRingAsli"></div>
                        <div class="text-xs text-gray-600" id="subRingAsliJml"></div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Barang pengganti</div>
                        <div class="font-medium text-amber-800" id="subRingPengganti"></div>
                        <div class="text-xs text-gray-600" id="subRingStok"></div>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <label class="block text-sm font-medium text-gray-600 mb-1">Jumlah pengganti</label>
                <input type="number" step="0.01" min="0.01" name="jumlah_pengganti" id="subJumlah"
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                <span class="text-xs text-gray-500" id="subJumlahHint"></span>
            </div>
            <div class="mt-3">
                <label class="block text-sm font-medium text-gray-600 mb-1">Alasan penggantian</label>
                <textarea name="alasan" id="subAlasan" rows="2" maxlength="500" required
                          class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                          placeholder="Contoh: stok barang yang diminta habis"></textarea>
            </div>

            <div class="mt-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                <button type="button" onclick="closeModal('substitusiModal')" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</button>
                <button type="submit" id="subSubmitBtn" class="px-4 py-2 bg-amber-500 text-white rounded-lg disabled:opacity-50" disabled>Ganti &amp; Setujui</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Reject --}}
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-30 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg p-4 sm:p-6 w-full max-w-md max-h-[92vh] overflow-y-auto">
        <h3 class="text-lg font-semibold mb-4">Tolak Permintaan</h3>
        <form id="rejectForm" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Alasan Penolakan</label>
                <textarea name="alasan" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal('rejectModal')" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg">Tolak</button>
            </div>
        </form>
    </div>
</div>

<script>
function adminDetail() {
    return {
        detailOpen: false,
        d: {},
        statusLabel: {pending_l1:'Pending L1', pending_admin:'Pending Admin', disetujui:'Disetujui', ditolak:'Ditolak', dibatalkan:'Dibatalkan'},
        openDetail(item) { this.d = item; this.detailOpen = true; },
        fmt(v) {
            return v ? new Date(v).toLocaleString('id-ID', {day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'}) : '-';
        }
    };
}

let approveCtx = null;
let approveStok = null;

function hitungSisaStok() {
    const sisaEl = document.getElementById('approveStokSisa');
    if (approveStok === null) { sisaEl.textContent = '-'; return; }
    const jml = parseFloat(document.getElementById('approveJumlah').value) || 0;
    const sisa = approveStok - jml;
    const satuan = approveCtx ? approveCtx.satuan : '';
    sisaEl.textContent = sisa + ' ' + satuan;
    sisaEl.className = 'font-semibold ' + (sisa < 0 ? 'text-red-600' : 'text-green-700');
}
document.getElementById('approveJumlah')?.addEventListener('input', hitungSisaStok);

function bukaGantiDariApprove() {
    if (!approveCtx) return;
    closeModal('approveModal');
    showSubstitusiModal(approveCtx);
}

function showApproveModal(id, originalJumlah, satuan, nama) {
    approveCtx = { id: id, nama: nama || '-', jumlah: parseFloat(originalJumlah), satuan: satuan };
    document.getElementById('approveGantiBtn').classList.add('hidden');
    approveStok = null;
    document.getElementById('approveStokTersedia').textContent = 'memuat...';
    document.getElementById('approveStokSisa').textContent = '-';
    const form = document.getElementById('approveForm');
    form.action = "{{ url('stock-ctl/approval/admin') }}/" + id + "/approve";
    form.setAttribute('data-id', id);
    
    const jumlahInput = document.getElementById('approveJumlah');
    jumlahInput.value = originalJumlah;
    document.getElementById('approveSatuan').innerText = satuan ? 'Satuan: ' + satuan : '';
    
    const errorDiv = document.getElementById('approveError');
    errorDiv.classList.add('hidden');
    errorDiv.innerText = '';
    
    fetch("{{ url('stock-ctl/approval/admin/cek-stok') }}/" + id)
        .then(res => res.json())
        .then(data => {
            approveStok = parseFloat(data.tersedia);
            document.getElementById('approveStokTersedia').textContent = approveStok + ' ' + satuan;
            hitungSisaStok();
            if (parseFloat(data.tersedia) <= 0) {
                // Stok habis: satu-satunya jalan adalah mengganti barang
                errorDiv.innerText = `⚠️ Stok barang ini habis (0 ${satuan}). Gunakan tombol Ganti Barang untuk memilih barang pengganti.`;
                errorDiv.classList.remove('hidden');
                document.getElementById('approveGantiBtn').classList.remove('hidden');
            } else if (data.tersedia < originalJumlah) {
                errorDiv.innerText = `⚠️ Peringatan: Stok tersedia hanya ${data.tersedia} ${satuan}, tidak cukup untuk permintaan ${originalJumlah} ${satuan}. Anda dapat mengurangi jumlah.`;
                errorDiv.classList.remove('hidden');
            }
        })
        .catch(err => console.error('Gagal cek stok:', err));
    
    document.getElementById('approveModal').classList.remove('hidden');
    document.getElementById('approveModal').classList.add('flex');
}

function showRejectModal(id) {
    const form = document.getElementById('rejectForm');
    form.action = "{{ url('stock-ctl/approval/admin') }}/" + id + "/reject";
    document.getElementById('rejectModal').classList.remove('hidden');
    document.getElementById('rejectModal').classList.add('flex');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
    document.getElementById(modalId).classList.remove('flex');
}

document.getElementById('toggleFilterBtn')?.addEventListener('click', () => {
    document.getElementById('filterSection').classList.toggle('hidden');
});

window.onclick = function(event) {
    const approveModal = document.getElementById('approveModal');
    const rejectModal = document.getElementById('rejectModal');
    if (event.target === approveModal) closeModal('approveModal');
    if (event.target === rejectModal) closeModal('rejectModal');
    if (event.target === document.getElementById('substitusiModal')) closeModal('substitusiModal');
}


// ===== Ganti Barang (Substitusi) =====
const subState = { id: null, asli: null, pilih: null, timer: null };

function showSubstitusiModal(p) {
    subState.id = p.id; subState.asli = p; subState.pilih = null;
    document.getElementById('subAsliNama').textContent = p.nama;
    document.getElementById('subAsliInfo').textContent = 'Diminta: ' + p.jumlah + ' ' + p.satuan;
    document.getElementById('subCari').value = '';
    document.getElementById('subIdPengganti').value = '';
    document.getElementById('subJumlah').value = '';
    document.getElementById('subJumlahHint').textContent = '';
    document.getElementById('subAlasan').value = '';
    document.getElementById('subRingkasan').classList.add('hidden');
    document.getElementById('subSubmitBtn').disabled = true;
    const err = document.getElementById('subError'); err.classList.add('hidden'); err.textContent = '';
    document.getElementById('substitusiModal').classList.remove('hidden');
    document.getElementById('substitusiModal').classList.add('flex');
    subCari();
}

function subCari() {
    const q = document.getElementById('subCari').value.trim();
    const box = document.getElementById('subHasil');
    box.textContent = 'Memuat...';
    fetch("{{ url('stock-ctl/approval/admin/cari-pengganti') }}/" + subState.id + '?q=' + encodeURIComponent(q), {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        box.textContent = '';
        subState.asli.stok = data.asli.stok;
        if (!data.items.length) { box.textContent = 'Barang tidak ditemukan.'; box.className = 'mt-2 border rounded-lg p-3 text-sm text-gray-500'; return; }
        box.className = 'mt-2 border rounded-lg max-h-48 overflow-y-auto divide-y text-sm';
        data.items.forEach(b => {
            const stok = parseFloat(b.stok);
            const row = document.createElement('button');
            row.type = 'button';
            row.className = 'w-full text-left px-3 py-2 flex flex-col sm:flex-row sm:justify-between gap-1 sm:gap-2 ' + (stok > 0 ? 'hover:bg-amber-50' : 'opacity-50 cursor-not-allowed');
            const kiri = document.createElement('span');
            kiri.textContent = b.nama_barang + (b.kode_barang ? ' (' + b.kode_barang + ')' : '');
            const kanan = document.createElement('span');
            kanan.className = 'text-xs whitespace-nowrap ' + (stok > 0 ? 'text-green-700' : 'text-red-600');
            kanan.textContent = 'Stok: ' + stok + ' ' + (b.satuan || '');
            row.appendChild(kiri); row.appendChild(kanan);
            if (stok > 0) row.addEventListener('click', () => subPilih(b, stok));
            box.appendChild(row);
        });
    })
    .catch(() => { box.textContent = 'Gagal memuat barang.'; });
}

function subPilih(b, stok) {
    subState.pilih = { id: b.id_barang, nama: b.nama_barang, satuan: b.satuan, stok: stok };
    document.getElementById('subIdPengganti').value = b.id_barang;
    document.getElementById('subRingAsli').textContent = subState.asli.nama;
    document.getElementById('subRingAsliJml').textContent = 'Diminta: ' + subState.asli.jumlah + ' ' + subState.asli.satuan;
    document.getElementById('subRingPengganti').textContent = b.nama_barang;
    document.getElementById('subRingStok').textContent = 'Stok tersedia: ' + stok + ' ' + (b.satuan || '');
    document.getElementById('subRingkasan').classList.remove('hidden');
    const jml = Math.min(subState.asli.jumlah, stok);
    document.getElementById('subJumlah').value = jml;
    document.getElementById('subJumlah').max = Math.min(subState.asli.jumlah, stok);
    document.getElementById('subJumlahHint').textContent = 'Maksimal ' + Math.min(subState.asli.jumlah, stok) + ' ' + (b.satuan || '') + ' (sesuai jumlah diminta & stok tersedia)';
    document.getElementById('subSubmitBtn').disabled = false;
}

document.getElementById('subCari')?.addEventListener('input', function () {
    clearTimeout(subState.timer);
    subState.timer = setTimeout(subCari, 300);
});

document.getElementById('substitusiForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const btn = document.getElementById('subSubmitBtn');
    const err = document.getElementById('subError');
    if (!subState.pilih) { err.textContent = 'Pilih barang pengganti dari daftar.'; err.classList.remove('hidden'); return; }
    btn.disabled = true; btn.textContent = 'Memproses...'; err.classList.add('hidden');

    fetch("{{ url('stock-ctl/approval/admin') }}/" + subState.id + '/substitusi', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: new FormData(this)
    })
    .then(async r => ({ ok: r.ok, data: await r.json().catch(() => ({})) }))
    .then(({ ok, data }) => {
        if (ok && data.success) {
            window.location.href = "{{ route('stock-ctl.approval.admin.index', ['status' => 'pending_admin']) }}";
        } else {
            const pesan = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Terjadi kesalahan.');
            err.textContent = pesan; err.classList.remove('hidden');
            btn.disabled = false; btn.textContent = 'Ganti & Setujui';
        }
    })
    .catch(() => {
        err.textContent = 'Error koneksi. Periksa jaringan Anda.'; err.classList.remove('hidden');
        btn.disabled = false; btn.textContent = 'Ganti & Setujui';
    });
});

document.getElementById('approveForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const url = form.action;
    const formData = new FormData(form);
    const submitBtn = document.getElementById('approveSubmitBtn');
    const errorDiv = document.getElementById('approveError');
    
    submitBtn.disabled = true;
    submitBtn.innerText = 'Memproses...';
    errorDiv.classList.add('hidden');
    
    fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = "{{ route('stock-ctl.approval.admin.index', ['status' => 'pending_admin']) }}";
        } else {
            errorDiv.innerText = data.message || 'Terjadi kesalahan. Silakan coba lagi.';
            errorDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.innerText = 'Setujui';
        }
    })
    .catch(error => {
        errorDiv.innerText = 'Error koneksi. Periksa jaringan Anda.';
        errorDiv.classList.remove('hidden');
        submitBtn.disabled = false;
        submitBtn.innerText = 'Setujui';
    });
});
</script>
@endsection