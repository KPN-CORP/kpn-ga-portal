{{-- Form Waiting petugas: pilih jenis tunggu + catatan. Pelapor melihat jenis dan catatan ini. --}}
@php
    $jenisAwal  = old('jenis_tunggu', $tiket->vendor_aktif ? 'VENDOR' : '');
    $jenisSingkat = ['USER' => 'Pelapor', 'PENGADAAN' => 'Pengadaan', 'VENDOR' => 'Vendor'];
@endphp
<style>[x-cloak]{display:none !important}</style>
<div class="bg-white rounded-lg border border-gray-200 p-4" x-data="{ jenis: '{{ $jenisAwal }}' }">
    <h3 class="text-lg font-semibold text-gray-800 mb-1 flex items-center">
        <i class="fas fa-hourglass-half text-orange-500 mr-2"></i> Tunggu
    </h3>
    <p class="text-xs text-gray-500 mb-3">Pilih siapa yang ditunggu. Pelapor melihat pilihan dan catatan ini.</p>

    <form action="{{ route('help.proses.waiting', $tiket) }}" method="POST" class="space-y-3">
        @csrf
        <div>
            <div class="grid grid-cols-3 gap-2">
                @foreach($jenisSingkat as $kode => $teks)
                <label class="cursor-pointer">
                    <input type="radio" name="jenis_tunggu" value="{{ $kode }}" x-model="jenis" class="sr-only" required>
                    <span class="block text-center text-sm px-2 py-2 rounded-lg border transition"
                          :class="jenis === '{{ $kode }}' ? 'border-blue-500 bg-blue-50 text-blue-700 font-medium' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                        {{ $teks }}
                    </span>
                </label>
                @endforeach
            </div>
            @error('jenis_tunggu')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500">
                Pelapor akan melihat:
                <span class="font-medium text-gray-500" x-show="jenis === ''" x-cloak>pilih jenis tunggu dulu</span>
                <span class="font-medium text-gray-700" x-show="jenis === 'USER'" x-cloak>Menunggu Anda</span>
                <span class="font-medium text-gray-700" x-show="jenis === 'PENGADAAN'" x-cloak>Menunggu pengadaan</span>
                <span class="font-medium text-gray-700" x-show="jenis === 'VENDOR'" x-cloak>Dikerjakan vendor</span>
            </p>
        </div>

        <div>
            <textarea name="catatan"
                      rows="2"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm resize-none"
                      placeholder="Contoh: PO diajukan, menunggu approval finance"
                      required>{{ old('catatan') }}</textarea>
            @error('catatan')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-lg transition-colors">
            <i class="fas fa-hourglass-half mr-2"></i> Simpan status tunggu
        </button>
    </form>
</div>
