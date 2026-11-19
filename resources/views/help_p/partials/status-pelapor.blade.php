{{-- Kartu status tiket untuk pelapor: satu kalimat "bola di siapa" + tindakan bila perlu. --}}
@php
    $HS      = \App\Support\help_s\HelpStatus::class;
    $wib     = fn ($t, $f = 'd/m H:i') => \App\Support\help_s\HelpTimeline::wib($t, $f);
    $jenis   = $tiket->status === 'WAITING' ? $HS::jenis($tiket->jenis_tunggu) : null;
    $vendor  = $tiket->vendor_aktif;
    $catatanTunggu = $tiket->status === 'WAITING'
        ? optional($tiket->logStatus->where('status_baru', 'WAITING')->sortBy('created_at')->last())->catatan
        : null;
@endphp
<style>[x-cloak]{display:none !important}</style>
<div class="bg-white rounded-lg border border-gray-200 p-4">
    <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
        <i class="fas fa-info-circle text-blue-500 mr-2"></i> Status tiket
    </h3>

    <div class="mb-3">
        @include('help_p.partials.status-badge', ['tiket' => $tiket, 'pelapor' => true, 'ukuran' => 'md'])
    </div>

    <div class="space-y-2 text-sm text-gray-700">
        @if($tiket->status === 'OPEN')
            <p>Menunggu petugas GA mengambil tiket ini.</p>

        @elseif($tiket->status === 'ON_PROCESS')
            <p>Sedang diproses oleh petugas GA.</p>
            @if($tiket->diproses_pada)
                <p class="text-xs text-gray-500">Sejak {{ $wib($tiket->diproses_pada) }}</p>
            @endif
            @if($vendor)
                <div class="bg-gray-50 rounded-lg p-2 text-xs text-gray-600">
                    <i class="fas fa-truck mr-1 text-gray-400"></i>
                    Sebagian pekerjaan dikerjakan vendor ({{ $vendor->nama_vendor }}) sejak {{ $wib($vendor->mulai_pada) }}.
                </div>
            @endif

        @elseif($tiket->status === 'WAITING')
            <p>{{ $jenis['isi'] ?? 'Tiket sedang menunggu tindak lanjut.' }}</p>
            @if($catatanTunggu)
                <div class="bg-gray-50 rounded-lg p-2 text-xs text-gray-600 whitespace-pre-line">{{ $catatanTunggu }}</div>
            @endif
            @if($tiket->menunggu_pada)
                <p class="text-xs text-gray-500">Sejak {{ $wib($tiket->menunggu_pada) }}</p>
            @endif
            @if($jenis && $jenis['tindakan'])
                <a href="#chatContainer" class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                    Balas petugas
                </a>
            @endif
            @if($jenis)
                <p class="text-xs text-gray-500">{{ $jenis['bantuan'] }}</p>
            @endif

        @elseif($tiket->status === 'DONE')
            <p>Petugas menandai tiket ini selesai. Sudah beres di tempat Anda?</p>
            @if($tiket->diselesaikan_pada)
                <p class="text-xs text-gray-500">{{ $wib($tiket->diselesaikan_pada) }}</p>
            @endif

            <div x-data="{ buka: {{ $errors->has('alasan') ? 'true' : 'false' }} }" class="space-y-2">
                <div class="flex flex-wrap gap-2">
                    <form action="{{ route('help.tiket.konfirmasi', $tiket) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                            Sudah selesai
                        </button>
                    </form>
                    <button type="button" @click="buka = !buka"
                            class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-medium rounded-lg transition">
                        Belum selesai
                    </button>
                </div>

                <form x-show="buka" x-cloak action="{{ route('help.tiket.belum-selesai', $tiket) }}" method="POST" class="space-y-2">
                    @csrf
                    <textarea name="alasan" rows="3" required minlength="5" maxlength="500"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Jelaskan apa yang masih belum beres">{{ old('alasan') }}</textarea>
                    @error('alasan')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="px-3 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-lg transition">
                        Buka kembali tiket
                    </button>
                </form>
            </div>

            <p class="text-xs text-gray-500">Jika dalam 1 hari kerja tidak ada tanggapan, tiket ditutup otomatis (Sabtu–Minggu tidak dihitung).</p>

        @elseif($tiket->status === 'CLOSED')
            <p>Tiket telah ditutup.</p>
            @if($tiket->ditutup_pada)
                <p class="text-xs text-gray-500">{{ $wib($tiket->ditutup_pada) }}</p>
            @endif

        @elseif($tiket->status === 'HELP_GA_CORP')
            <p>Tiket diteruskan ke antrian GA Corp dan akan segera diproses.</p>
        @endif
    </div>
</div>
