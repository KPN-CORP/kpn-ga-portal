<?php $__env->startSection('content'); ?>
<div class="flex">
    <main class="flex-1">
        <div class="bg-white shadow rounded w-full" 
             style="margin: 0.1cm 0.1cm 0 0.1cm; padding: 0.5cm;">

            <h2 class="text-2xl font-semibold mb-4 text-left">Request ID Card</h2>
            
            <?php if(session('success')): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo e(session('success')); ?>

                </div>
            <?php endif; ?>
            
            <?php if(session('error')): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo e(session('error')); ?>

                </div>
            <?php endif; ?>

            <form action="<?php echo e(route('idcard.store')); ?>" method="POST" enctype="multipart/form-data" id="idcardForm">
                <?php echo csrf_field(); ?>

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
                                <?php $__currentLoopData = $bisnisUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($unit->id_bisnis_unit); ?>"><?php echo e($unit->nama_bisnis_unit); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                            <label class="block text-sm font-medium mb-1">Foto *</label>
                            <input type="file" name="foto" id="foto" class="w-full border border-gray-300 p-2 rounded text-sm foto-input" accept=".jpg,.jpeg,.png" required>
                            <p class="text-xs text-gray-500 mt-1">Format: jpg, png (max: 20MB)</p>
                            <div id="fotoError" class="text-red-500 text-xs mt-1 hidden"></div>
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
                    <a href="<?php echo e(route('idcard')); ?>" class="ml-2 bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded text-sm">
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
    const nikInput = document.getElementById('nik');

    // Counter untuk nomor urut magang
    let magangCounter = 1;
    
    // Fungsi untuk generate NIK magang
    function generateNikMagang() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const counter = String(magangCounter).padStart(3, '0');
        
        magangCounter++;
        return `Inter${year}${month}${day}${counter}`;
    }
    
    // Fungsi untuk generate nomor kartu magang
    function generateNomorKartuMagang() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const random = String(Math.floor(Math.random() * 1000)).padStart(3, '0');
        
        return `MAG${year}${month}${day}${random}`;
    }

    // Fungsi validasi ukuran file (20MB)
    function validateFileSize(file) {
        const maxSize = 20 * 1024 * 1024; // 20MB dalam bytes
        if (file.size > maxSize) {
            fotoError.textContent = `File terlalu besar! Maksimal 20MB. File Anda: ${(file.size / (1024 * 1024)).toFixed(2)}MB`;
            fotoError.classList.remove('hidden');
            fotoError.classList.add('block');
            fotoInput.value = '';
            return false;
        } else {
            fotoError.classList.add('hidden');
            fotoError.classList.remove('block');
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
        }
    });

    // Validasi bukti bayar (untuk kategori ganti kartu)
    function validateBuktiBayar(file) {
        const maxSize = 20 * 1024 * 1024; // 20MB
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        
        if (file.size > maxSize) {
            alert(`File Bukti Bayar terlalu besar! Maksimal 20MB. File Anda: ${(file.size / (1024 * 1024)).toFixed(2)}MB`);
            return false;
        }
        
        if (!allowedTypes.includes(file.type) && !file.name.toLowerCase().endsWith('.pdf')) {
            alert('Format file Bukti Bayar tidak valid. Gunakan format: jpg, jpeg, png, atau pdf');
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

        // Jika kategori adalah magang atau magang extend
        if (kategori === 'magang' || kategori === 'magang_extend') {
            // Sembunyikan tanggal join dan foto
            tanggalJoinContainer.style.display = 'none';
            tanggalJoinInput.required = false;
            tanggalJoinInput.value = '';
            
            fotoContainer.style.display = 'none';
            fotoInput.required = false;
            
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
                    <label class="block text-sm font-medium mb-1">Bukti Bayar *</label>
                    <input type="file" name="bukti_bayar" id="bukti_bayar" class="w-full border border-gray-300 p-2 rounded text-sm bukti-bayar-input" accept=".jpg,.jpeg,.png,.pdf" required>
                    <p class="text-xs text-gray-500 mt-1">Format: jpg, png, pdf (max: 20MB)</p>
                    <div id="buktiBayarError" class="text-red-500 text-xs mt-1 hidden"></div>
                </div>
            `;

            // Validasi bukti bayar
            const buktiBayarInput = document.getElementById('bukti_bayar');
            const buktiBayarError = document.getElementById('buktiBayarError');
            
            buktiBayarInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    const file = this.files[0];
                    const maxSize = 20 * 1024 * 1024;
                    
                    if (file.size > maxSize) {
                        buktiBayarError.textContent = `File terlalu besar! Maksimal 20MB. File Anda: ${(file.size / (1024 * 1024)).toFixed(2)}MB`;
                        buktiBayarError.classList.remove('hidden');
                        buktiBayarError.classList.add('block');
                        this.value = '';
                    } else {
                        buktiBayarError.classList.add('hidden');
                        buktiBayarError.classList.remove('block');
                    }
                }
            });
        }
        // Untuk karyawan baru dan karyawan mutasi: tampilkan semua field standar
        // (tidak perlu field tambahan)
    }

    // Event listener untuk perubahan kategori
    kategoriSelect.addEventListener('change', handleKategoriChange);

    // Validasi form sebelum submit
    document.getElementById('idcardForm').addEventListener('submit', function(e) {
        const kategori = kategoriSelect.value;
        let isValid = true;
        
        // Validasi kategori dipilih
        if (!kategori) {
            alert('Kategori wajib dipilih');
            isValid = false;
        }
        
        // Validasi nama
        const nama = document.getElementById('nama').value.trim();
        if (!nama) {
            alert('Nama wajib diisi');
            isValid = false;
        }
        
        // Validasi NIK
        const nik = nikInput.value.trim();
        if (!nik) {
            alert('NIK wajib diisi');
            isValid = false;
        }
        
        // Validasi bisnis unit
        const bisnisUnit = document.getElementById('bisnis_unit_id').value;
        if (!bisnisUnit) {
            alert('Bisnis Unit wajib dipilih');
            isValid = false;
        }
        
        // Validasi keterangan (lantai kerja)
        const keterangan = document.getElementById('keterangan').value.trim();
        if (!keterangan) {
            alert('Lantai Kerja wajib diisi');
            isValid = false;
        }
        
        // Validasi untuk kategori yang memerlukan tanggal join dan foto
        // (karyawan baru, karyawan mutasi, ganti kartu)
        if (kategori !== 'magang' && kategori !== 'magang_extend') {
            const tanggalJoin = tanggalJoinInput.value;
            if (!tanggalJoin) {
                alert('Tanggal Join wajib diisi');
                isValid = false;
            }
            
            // Validasi foto dan ukuran file
            const foto = fotoInput.files[0];
            if (!foto) {
                alert('Foto wajib diupload');
                isValid = false;
            } else if (!validateFileSize(foto)) {
                isValid = false;
            }
        }
        
        // Validasi khusus untuk magang dan magang extend
        if (kategori === 'magang' || kategori === 'magang_extend') {
            const masaBerlaku = document.querySelector('input[name="masa_berlaku"]')?.value;
            const sampaiTanggal = document.querySelector('input[name="sampai_tanggal"]')?.value;
            const nomorKartu = document.querySelector('input[name="nomor_kartu"]')?.value;
            
            if (!masaBerlaku || !sampaiTanggal || !nomorKartu) {
                alert('Semua field untuk kategori Magang wajib diisi');
                isValid = false;
            }
            
            // Validasi khusus untuk magang extend: NIK harus diisi
            if (kategori === 'magang_extend' && !nik.trim()) {
                alert('NIK wajib diisi untuk Magang Extend');
                isValid = false;
            }
        }
        
        // Validasi bukti bayar untuk ganti kartu
        if (kategori === 'ganti_kartu') {
            const buktiBayarInput = document.querySelector('input[name="bukti_bayar"]');
            if (!buktiBayarInput || !buktiBayarInput.files.length) {
                alert('Bukti Bayar wajib diupload untuk kategori Ganti Kartu');
                isValid = false;
            } else {
                const file = buktiBayarInput.files[0];
                if (!validateBuktiBayar(file)) {
                    isValid = false;
                }
            }
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
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/idcard/request.blade.php ENDPATH**/ ?>