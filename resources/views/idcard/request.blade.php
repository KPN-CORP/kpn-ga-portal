@extends('layouts.app-sidebar')
@section('content')
<div class="flex">
    <main class="flex-1">
        <div class="bg-white shadow rounded w-full" 
             style="margin: 0.1cm 0.1cm 0 0.1cm; padding: 0.5cm;">

            <h2 class="text-2xl font-semibold mb-4 text-left">Request ID Card</h2>
            
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('idcard.store') }}" method="POST" enctype="multipart/form-data" id="idcardForm">
                @csrf

                <div class="space-y-6">
                    <!-- Baris 1: Kategori, Nama, NIK, Bisnis Unit -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Kategori *</label>
                            <select name="kategori" id="kategori" class="w-full border border-gray-300 p-2 rounded text-sm" required>
                                <option value="">Pilih Kategori</option>
                                <option value="karyawan_baru">Karyawan Baru</option>
                                <option value="karyawan_mutasi">Karyawan Mutasi</option>
                                <option value="ganti_kartu">Ganti Kartu</option>
                                <option value="magang">Magang</option>
                                <option value="magang_extend">Magang Extend</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Nama *</label>
                            <input type="text" name="nama" id="nama" class="w-full border border-gray-300 p-2 rounded text-sm" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">NIK *</label>
                            <input type="text" name="nik" id="nik" class="w-full border border-gray-300 p-2 rounded text-sm" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Bisnis Unit *</label>
                            <select name="bisnis_unit_id" id="bisnis_unit_id" class="w-full border border-gray-300 p-2 rounded text-sm" required>
                                <option value="">Pilih Bisnis Unit</option>
                                @foreach($bisnisUnits as $unit)
                                    <option value="{{ $unit->id_bisnis_unit }}">{{ $unit->nama_bisnis_unit }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Baris 2: Tanggal Join, Foto, Fields Kategori -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        <div id="tanggalJoinContainer">
                            <label class="block text-sm font-medium mb-1">Tanggal Join *</label>
                            <input type="date" name="tanggal_join" id="tanggal_join" class="w-full border border-gray-300 p-2 rounded text-sm" required>
                        </div>

                        <div id="fotoContainer">
                            <label class="block text-sm font-medium mb-1">Foto * <span class="text-red-500">(Maks: 10MB)</span></label>
                            <input type="file" name="foto" id="foto" class="w-full border border-gray-300 p-2 rounded text-sm foto-input" accept=".jpg,.jpeg,.png" required>
                            <p class="text-xs text-gray-500 mt-1">Format: jpg, jpeg, png (maksimal: 10MB)</p>
                            <div id="fotoError" class="text-red-500 text-xs mt-1 hidden"></div>
                            <div id="fileInfo" class="text-blue-600 text-xs mt-1 hidden">
                                <span id="fileName"></span> - <span id="fileSize"></span>
                            </div>
                        </div>

                        <div id="kategoriExtra">
                            <!-- Dynamic fields akan muncul di sini -->
                        </div>
                    </div>
                    
                    <!-- Keterangan -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Lantai Kerja *</label>
                        <input type="text" name="keterangan" id="keterangan" class="w-full border border-gray-300 p-2 rounded text-sm" placeholder="(41)" required>
                    </div>
                </div>

                <!-- Submit -->
                <div class="text-center mt-8 pt-4 border-t">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded text-sm">
                        Simpan Request
                    </button>
                    <a href="{{ route('idcard') }}" class="ml-2 bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded text-sm">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const kategoriSelect = document.getElementById('kategori');
    const kategoriExtra = document.getElementById('kategoriExtra');
    const tanggalJoinContainer = document.getElementById('tanggalJoinContainer');
    const tanggalJoinInput = document.getElementById('tanggal_join');
    const fotoContainer = document.getElementById('fotoContainer');
    const fotoInput = document.getElementById('foto');
    const fotoError = document.getElementById('fotoError');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const nikInput = document.getElementById('nik');

    // Setting maksimal ukuran file (10MB dalam bytes) - DIUBAH DARI 15MB KE 10MB
    const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    const MAX_FILE_SIZE_MB = 10; // Untuk tampilan
    
    // Format bytes ke ukuran yang mudah dibaca
    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    // Fungsi untuk generate NIK magang
    function generateNikMagang() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const counter = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
        
        return `Inter${year}${month}${day}${counter}`;
    }
    
    // Fungsi untuk generate nomor kartu magang
    function generateNomorKartuMagang() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
        
        return `MAG${year}${month}${day}${random}`;
    }

    // Fungsi validasi ukuran file (10MB) - LANGSUNG BLOKIR JIKA > 10MB
    function validateFileSize(file) {
        const fileSizeMB = file.size / (1024 * 1024);
        
        // LANGSUNG BLOKIR JIKA > 10MB
        if (file.size > MAX_FILE_SIZE) {
            fotoError.textContent = `❌ File terlalu besar! Maksimal ${MAX_FILE_SIZE_MB}MB. File Anda: ${fileSizeMB.toFixed(2)}MB`;
            fotoError.classList.remove('hidden');
            fotoError.classList.add('block');
            
            // Tambahkan class error pada input
            fotoInput.classList.add('border-red-500', 'border-2');
            
            fileInfo.classList.add('hidden');
            fotoInput.value = '';
            
            // Tampilkan alert tambahan
            setTimeout(() => {
                alert(`❌ File foto terlalu besar!\n\nUkuran file: ${fileSizeMB.toFixed(2)}MB\nBatas maksimal: ${MAX_FILE_SIZE_MB}MB\n\nSilakan kompres foto Anda terlebih dahulu atau pilih file lain yang lebih kecil.`);
            }, 100);
            
            return false;
        } else {
            // File valid (≤ 10MB)
            fotoError.classList.add('hidden');
            fotoError.classList.remove('block');
            fotoInput.classList.remove('border-red-500', 'border-2');
            
            // Tampilkan info file
            fileName.textContent = file.name;
            fileSize.textContent = formatBytes(file.size);
            fileInfo.classList.remove('hidden');
            
            // Tampilkan pesan sukses untuk file besar (5-10MB)
            if (fileSizeMB > 5) {
                fotoError.textContent = `ℹ️ File cukup besar (${fileSizeMB.toFixed(2)}MB).`;
                fotoError.classList.remove('hidden');
                fotoError.classList.add('block');
                fotoError.classList.remove('text-red-500');
                fotoError.classList.add('text-blue-600');
            }
            
            return true;
        }
    }

    // Validasi file saat dipilih
    fotoInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            validateFileSize(this.files[0]);
        } else {
            fotoError.classList.add('hidden');
            fotoError.classList.remove('block');
            fileInfo.classList.add('hidden');
            fotoInput.classList.remove('border-red-500', 'border-2');
        }
    });

    // Validasi bukti bayar (untuk kategori ganti kartu) - JUGA 10MB
    function validateBuktiBayar(file) {
        const maxSize = MAX_FILE_SIZE; // 10MB
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        
        const fileSizeMB = file.size / (1024 * 1024);
        
        if (file.size > maxSize) {
            alert(`❌ File Bukti Bayar terlalu besar!\n\nUkuran file: ${fileSizeMB.toFixed(2)}MB\nBatas maksimal: ${MAX_FILE_SIZE_MB}MB\n\nSilakan kompres file Anda terlebih dahulu.`);
            return false;
        }
        
        if (!allowedTypes.includes(file.type) && !file.name.toLowerCase().endsWith('.pdf')) {
            alert('❌ Format file Bukti Bayar tidak valid.\n\nGunakan format: jpg, jpeg, png, atau pdf');
            return false;
        }
        
        return true;
    }

    // Fungsi untuk mengubah tampilan berdasarkan kategori
    function handleKategoriChange() {
        const kategori = kategoriSelect.value;
        
        // Reset semua
        kategoriExtra.innerHTML = '';
        tanggalJoinContainer.style.display = 'block';
        tanggalJoinInput.required = true;
        fotoContainer.style.display = 'block';
        fotoInput.required = true;
        nikInput.readOnly = false;
        nikInput.style.backgroundColor = '';
        nikInput.placeholder = '';
        
        // Reset border file input
        fotoInput.classList.remove('border-red-500', 'border-2');
        fotoError.classList.add('hidden');
        fileInfo.classList.add('hidden');

        // Jika kategori adalah magang atau magang extend
        if (kategori === 'magang' || kategori === 'magang_extend') {
            // Sembunyikan tanggal join dan foto
            tanggalJoinContainer.style.display = 'none';
            tanggalJoinInput.required = false;
            tanggalJoinInput.value = '';
            
            fotoContainer.style.display = 'none';
            fotoInput.required = false;
            fotoInput.value = ''; // Clear file jika ada
            
            let nomorKartu = '';
            
            // Untuk magang: generate NIK dan nomor kartu otomatis
            if (kategori === 'magang') {
                nikInput.value = generateNikMagang();
                nomorKartu = generateNomorKartuMagang();
                nikInput.readOnly = true;
                nikInput.style.backgroundColor = '#f3f4f6';
            } 
            // Untuk magang extend: NIK diinput manual
            else if (kategori === 'magang_extend') {
                nikInput.placeholder = 'Masukkan NIK Magang yang sudah ada';
                nikInput.readOnly = false;
                nikInput.style.backgroundColor = '';
                nikInput.value = '';
            }
            
            // Tambahkan field untuk magang dan magang extend
            kategoriExtra.innerHTML = `
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Masa Berlaku *</label>
                            <input type="date" name="masa_berlaku" class="w-full border border-gray-300 p-2 rounded text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Sampai Tanggal *</label>
                            <input type="date" name="sampai_tanggal" class="w-full border border-gray-300 p-2 rounded text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Nomor Kartu *</label>
                            <input type="text" name="nomor_kartu" class="w-full border border-gray-300 p-2 rounded text-sm" 
                                   value="${nomorKartu}" ${kategori === 'magang' ? 'readonly style="background-color: #f3f4f6;"' : ''} required>
                        </div>
                    </div>
                </div>
            `;
        } 
        // Jika kategori adalah ganti kartu
        else if (kategori === 'ganti_kartu') {
            // Tambahkan field bukti bayar
            kategoriExtra.innerHTML = `
                <div>
                    <label class="block text-sm font-medium mb-1">Bukti Bayar * <span class="text-red-500">(Maks: 10MB)</span></label>
                    <input type="file" name="bukti_bayar" id="bukti_bayar" class="w-full border border-gray-300 p-2 rounded text-sm bukti-bayar-input" accept=".jpg,.jpeg,.png,.pdf" required>
                    <p class="text-xs text-gray-500 mt-1">Format: jpg, jpeg, png, pdf (maksimal: 10MB)</p>
                    <div id="buktiBayarError" class="text-red-500 text-xs mt-1 hidden"></div>
                </div>
            `;

            // Validasi bukti bayar
            const buktiBayarInput = document.getElementById('bukti_bayar');
            const buktiBayarError = document.getElementById('buktiBayarError');
            
            buktiBayarInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    const file = this.files[0];
                    const fileSizeMB = file.size / (1024 * 1024);
                    
                    if (file.size > MAX_FILE_SIZE) {
                        buktiBayarError.textContent = `❌ File terlalu besar! Maksimal ${MAX_FILE_SIZE_MB}MB. File Anda: ${fileSizeMB.toFixed(2)}MB`;
                        buktiBayarError.classList.remove('hidden');
                        buktiBayarError.classList.add('block');
                        
                        this.classList.add('border-red-500', 'border-2');
                        this.value = '';
                        
                        // Alert tambahan
                        setTimeout(() => {
                            alert(`❌ File Bukti Bayar terlalu besar!\n\nUkuran file: ${fileSizeMB.toFixed(2)}MB\nBatas maksimal: ${MAX_FILE_SIZE_MB}MB`);
                        }, 100);
                    } else {
                        buktiBayarError.classList.add('hidden');
                        buktiBayarError.classList.remove('block');
                        this.classList.remove('border-red-500', 'border-2');
                    }
                } else {
                    buktiBayarError.classList.add('hidden');
                    buktiBayarError.classList.remove('block');
                    this.classList.remove('border-red-500', 'border-2');
                }
            });
        } else {
            // Untuk karyawan baru dan karyawan mutasi
            // Reset border file input
            fotoInput.classList.remove('border-red-500', 'border-2');
            fotoError.classList.add('hidden');
            fileInfo.classList.add('hidden');
        }
    }

    // Event listener untuk perubahan kategori
    kategoriSelect.addEventListener('change', handleKategoriChange);

    // Validasi form sebelum submit - TAMBAHKAN VALIDASI UKURAN FILE
    document.getElementById('idcardForm').addEventListener('submit', function(e) {
        const kategori = kategoriSelect.value;
        let isValid = true;
        let errorMessages = [];
        
        // Validasi kategori dipilih
        if (!kategori) {
            errorMessages.push('Kategori wajib dipilih');
            isValid = false;
        }
        
        // Validasi nama
        const nama = document.getElementById('nama').value.trim();
        if (!nama) {
            errorMessages.push('Nama wajib diisi');
            isValid = false;
        }
        
        // Validasi NIK
        const nik = nikInput.value.trim();
        if (!nik) {
            errorMessages.push('NIK wajib diisi');
            isValid = false;
        }
        
        // Validasi bisnis unit
        const bisnisUnit = document.getElementById('bisnis_unit_id').value;
        if (!bisnisUnit) {
            errorMessages.push('Bisnis Unit wajib dipilih');
            isValid = false;
        }
        
        // Validasi keterangan (lantai kerja)
        const keterangan = document.getElementById('keterangan').value.trim();
        if (!keterangan) {
            errorMessages.push('Lantai Kerja wajib diisi');
            isValid = false;
        }
        
        // Validasi untuk kategori yang memerlukan tanggal join dan foto
        if (kategori !== 'magang' && kategori !== 'magang_extend') {
            const tanggalJoin = tanggalJoinInput.value;
            if (!tanggalJoin) {
                errorMessages.push('Tanggal Join wajib diisi');
                isValid = false;
            }
            
            // Validasi foto dan ukuran file
            const foto = fotoInput.files[0];
            if (!foto) {
                errorMessages.push('Foto wajib diupload');
                isValid = false;
            } else {
                // Validasi ukuran file (≤ 10MB)
                const fileSizeMB = foto.size / (1024 * 1024);
                if (foto.size > MAX_FILE_SIZE) {
                    errorMessages.push(`Foto terlalu besar (${fileSizeMB.toFixed(2)}MB). Maksimal ${MAX_FILE_SIZE_MB}MB`);
                    isValid = false;
                }
            }
        }
        
        // Validasi khusus untuk magang dan magang extend
        if (kategori === 'magang' || kategori === 'magang_extend') {
            const masaBerlaku = document.querySelector('input[name="masa_berlaku"]')?.value;
            const sampaiTanggal = document.querySelector('input[name="sampai_tanggal"]')?.value;
            const nomorKartu = document.querySelector('input[name="nomor_kartu"]')?.value;
            
            if (!masaBerlaku || !sampaiTanggal || !nomorKartu) {
                errorMessages.push('Semua field untuk kategori Magang wajib diisi');
                isValid = false;
            }
            
            // Validasi tanggal: sampai tanggal harus setelah masa berlaku
            if (masaBerlaku && sampaiTanggal) {
                if (new Date(sampaiTanggal) <= new Date(masaBerlaku)) {
                    errorMessages.push('Sampai Tanggal harus setelah Masa Berlaku');
                    isValid = false;
                }
            }
        }
        
        // Validasi bukti bayar untuk ganti kartu
        if (kategori === 'ganti_kartu') {
            const buktiBayarInput = document.querySelector('input[name="bukti_bayar"]');
            if (!buktiBayarInput || !buktiBayarInput.files.length) {
                errorMessages.push('Bukti Bayar wajib diupload untuk kategori Ganti Kartu');
                isValid = false;
            } else {
                const file = buktiBayarInput.files[0];
                const fileSizeMB = file.size / (1024 * 1024);
                
                if (file.size > MAX_FILE_SIZE) {
                    errorMessages.push(`Bukti Bayar terlalu besar (${fileSizeMB.toFixed(2)}MB). Maksimal ${MAX_FILE_SIZE_MB}MB`);
                    isValid = false;
                }
            }
        }
        
        // Jika ada error, tampilkan semua pesan error
        if (errorMessages.length > 0) {
            e.preventDefault();
            alert('❌ Mohon perbaiki kesalahan berikut:\n\n' + errorMessages.join('\n'));
        }
        
        // Jika ada yang tidak valid, cegah submit
        if (!isValid) {
            e.preventDefault();
        }
    });

    // Inisialisasi awal (jika ada kategori yang sudah dipilih dari session)
    handleKategoriChange();

});
</script>
@endsection