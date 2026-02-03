<?php $__env->startSection('content'); ?>
<div class="space-y-6">

    
    <div>
        <h2 class="text-lg font-semibold">➕ Input Mailing (Bulk)</h2>
        <p class="text-xs text-gray-500">
            Bisa input banyak sekaligus · Responsive HP & Laptop
        </p>
    </div>

    
    <form method="POST"
          action="<?php echo e(route('mailing.store.bulk')); ?>"
          class="block md:hidden bg-white rounded-xl shadow p-4 space-y-6">
        <?php echo csrf_field(); ?>

        <div id="mobileRows" class="space-y-4">
            <div class="border rounded-xl p-4 space-y-3 mailing-card">
                <div class="flex justify-between items-center">
                    <span class="text-xs font-semibold text-gray-500">
                        Mailing #1
                    </span>
                    <button type="button"
                            onclick="removeMobile(this)"
                            class="text-red-500 text-xs">
                        Hapus
                    </button>
                </div>

                
                <input name="mailings[0][mailing_resi]"
                       placeholder="Nomor Resi *"
                       class="w-full border rounded-lg px-3 py-2 text-sm"
                       required>

                <input name="mailings[0][mailing_pengirim]"
                       placeholder="Pengirim *"
                       class="w-full border rounded-lg px-3 py-2 text-sm"
                       required>

                <input name="mailings[0][mailing_penerima]"
                       placeholder="Penerima *"
                       class="w-full border rounded-lg px-3 py-2 text-sm"
                       required>

                
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Lantai *</label>
                    <select name="mailings[0][mailing_lantai]"
                            class="w-full border rounded-lg px-3 py-2 text-sm"
                            required>
                        <option value="">-- Pilih Lantai --</option>
                        <option value="41">Lantai 41</option>
                        <option value="42">Lantai 42</option>
                        <option value="43">Lantai 43</option>
                        <option value="45">Lantai 45</option>
                        <option value="46">Lantai 46</option>
                        <option value="47">Lantai 47</option>
                    </select>
                </div>

                
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Ekspedisi *</label>
                    <input type="text"
                           name="mailings[0][id_ekspedisi_input]"
                           list="ekspedisiList"
                           placeholder="Ketik atau pilih ekspedisi"
                           class="w-full border rounded-lg px-3 py-2 text-sm ekspedisi-input"
                           data-index="0"
                           autocomplete="off"
                           required>
                    <input type="hidden" name="mailings[0][id_ekspedisi]" 
                           class="ekspedisi-hidden" 
                           id="ekspedisi-hidden-0">
                </div>
            </div>
        </div>

        <button type="button"
                onclick="addMobile()"
                class="w-full border border-dashed rounded-lg py-3
                       text-sm font-semibold text-gray-600 hover:bg-gray-50">
            ➕ Tambah Mailing
        </button>

        <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700
                       text-white py-3 rounded-lg font-semibold">
            💾 Simpan Semua
        </button>
    </form>

    
    <form method="POST"
          action="<?php echo e(route('mailing.store.bulk')); ?>"
          class="hidden md:block bg-white rounded-xl shadow p-4 space-y-4">
        <?php echo csrf_field(); ?>

        <div class="overflow-x-auto">
            <table class="w-full text-sm border rounded-xl overflow-hidden">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Resi *</th>
                        <th class="px-3 py-2 text-left">Pengirim *</th>
                        <th class="px-3 py-2 text-left">Penerima *</th>
                        <th class="px-3 py-2 text-left">Lantai *</th>
                        <th class="px-3 py-2 text-left">Ekspedisi *</th>
                        <th class="px-3 py-2 text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody id="desktopRows" class="divide-y">
                    <tr data-index="0">
                        
                        <td class="p-2">
                            <input name="mailings[0][mailing_resi]"
                                   class="w-full border rounded px-2 py-1"
                                   placeholder="RESI"
                                   required>
                        </td>
                        
                        
                        <td class="p-2">
                            <input name="mailings[0][mailing_pengirim]"
                                   class="w-full border rounded px-2 py-1"
                                   placeholder="PENGIRIM"
                                   required>
                        </td>
                        
                        
                        <td class="p-2">
                            <input name="mailings[0][mailing_penerima]"
                                   class="w-full border rounded px-2 py-1"
                                   placeholder="PENERIMA"
                                   required>
                        </td>
                        
                        
                        <td class="p-2">
                            <select name="mailings[0][mailing_lantai]"
                                    class="w-full border rounded px-2 py-1 text-xs"
                                    required>
                                <option value="">-- Pilih --</option>
                                <option value="41">41</option>
                                <option value="42">42</option>
                                <option value="43">43</option>
                                <option value="45">45</option>
                                <option value="46">46</option>
                                <option value="47">47</option>
                            </select>
                        </td>
                        
                        
                        <td class="p-2">
                            <div class="relative">
                                <input type="text"
                                       name="mailings[0][id_ekspedisi_input]"
                                       list="ekspedisiList"
                                       placeholder="Ketik atau pilih"
                                       class="w-full border rounded px-2 py-1 pr-8 ekspedisi-input"
                                       data-index="0"
                                       autocomplete="off"
                                       required>
                                <input type="hidden" 
                                       name="mailings[0][id_ekspedisi]" 
                                       class="ekspedisi-hidden" 
                                       id="ekspedisi-hidden-0-d">
                            </div>
                        </td>
                        
                        
                        <td class="p-2 text-center">
                            <button type="button"
                                    onclick="removeDesktop(this)"
                                    class="text-red-500 text-xs hover:text-red-700">
                                Hapus
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex justify-between items-center pt-3">
            <button type="button"
                    onclick="addDesktop()"
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200
                           rounded-lg text-sm font-semibold">
                + Tambah Baris
            </button>

            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700
                           text-white px-6 py-2 rounded-lg
                           text-sm font-semibold">
                💾 Simpan Semua
            </button>
        </div>
    </form>

    
    <datalist id="ekspedisiList">
        <?php $__currentLoopData = $ekspedisi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($item->nama_ekspedisi); ?>" data-id="<?php echo e($item->id_ekspedisi); ?>">
                <?php echo e($item->nama_ekspedisi); ?>

            </option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </datalist>

</div>


<script>
let index = 1;

// Fungsi untuk update ekspedisi
// Ganti fungsi setupEkspedisiInput
function setupEkspedisiInput(inputElement) {
    const index = inputElement.getAttribute('data-index');
    
    inputElement.addEventListener('input', function() {
        const value = this.value.trim();
        const options = document.querySelectorAll('#ekspedisiList option');
        let foundId = '';
        
        // Cari ID ekspedisi yang sesuai
        options.forEach(option => {
            if (option.value === value) {
                foundId = option.getAttribute('data-id');
            }
        });
        
        // Update semua hidden input dengan index yang sama
        const hiddenInputs = document.querySelectorAll(`[name="mailings[${index}][id_ekspedisi]"]`);
        hiddenInputs.forEach(hiddenInput => {
            hiddenInput.value = foundId;
            console.log(`Updated ekspedisi ID for row ${index}: ${foundId}`);
        });
        
        // Juga update value input text untuk konsistensi
        if (foundId) {
            this.value = value; // Pastikan value tetap
        }
    });
    
    // Juga trigger ketika dropdown dipilih dari datalist
    inputElement.addEventListener('change', function() {
        setTimeout(() => {
            this.dispatchEvent(new Event('input'));
        }, 100);
    });
}

// Tambahkan di DOMContentLoaded untuk debugging
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== MAILING FORM LOADED ===');
    
    // Test: cek apakah ekspedisi list ada
    const ekspedisiOptions = document.querySelectorAll('#ekspedisiList option');
    console.log(`Ekspedisi options found: ${ekspedisiOptions.length}`);
    
    // Setup semua input ekspedisi
    document.querySelectorAll('.ekspedisi-input').forEach(input => {
        setupEkspedisiInput(input);
        
        // Debug log untuk setiap input
        input.addEventListener('input', function() {
            const hiddenInput = document.querySelector(`[name="mailings[${this.dataset.index}][id_ekspedisi]"]`);
            console.log(`Ekspedisi input changed:`, {
                text: this.value,
                hiddenId: hiddenInput ? hiddenInput.value : 'not found'
            });
        });
    });
});

// ===== MOBILE =====
function addMobile(){
    const wrap = document.getElementById('mobileRows');
    const card = document.createElement('div');
    card.className = 'border rounded-xl p-4 space-y-3 mailing-card';

    card.innerHTML = `
        <div class="flex justify-between items-center">
            <span class="text-xs font-semibold text-gray-500">
                Mailing #${index+1}
            </span>
            <button type="button"
                    onclick="removeMobile(this)"
                    class="text-red-500 text-xs">
                Hapus
            </button>
        </div>

        <input name="mailings[${index}][mailing_resi]"
               placeholder="Nomor Resi *"
               class="w-full border rounded-lg px-3 py-2 text-sm" required>

        <input name="mailings[${index}][mailing_pengirim]"
               placeholder="Pengirim *"
               class="w-full border rounded-lg px-3 py-2 text-sm" required>

        <input name="mailings[${index}][mailing_penerima]"
               placeholder="Penerima *"
               class="w-full border rounded-lg px-3 py-2 text-sm" required>

        <div>
            <label class="block text-xs text-gray-500 mb-1">Lantai *</label>
            <select name="mailings[${index}][mailing_lantai]"
                    class="w-full border rounded-lg px-3 py-2 text-sm"
                    required>
                <option value="">-- Pilih Lantai --</option>
                <option value="41">Lantai 41</option>
                <option value="42">Lantai 42</option>
                <option value="43">Lantai 43</option>
                <option value="45">Lantai 45</option>
                <option value="46">Lantai 46</option>
                <option value="47">Lantai 47</option>
            </select>
        </div>

        <div>
            <label class="block text-xs text-gray-500 mb-1">Ekspedisi *</label>
            <input type="text"
                   name="mailings[${index}][id_ekspedisi_input]"
                   list="ekspedisiList"
                   placeholder="Ketik atau pilih ekspedisi"
                   class="w-full border rounded-lg px-3 py-2 text-sm ekspedisi-input"
                   data-index="${index}"
                   autocomplete="off"
                   required>
            <input type="hidden" name="mailings[${index}][id_ekspedisi]" 
                   class="ekspedisi-hidden" 
                   id="ekspedisi-hidden-${index}">
        </div>
    `;
    
    wrap.appendChild(card);
    
    // Setup event listener untuk input ekspedisi baru
    const ekspedisiInput = card.querySelector('.ekspedisi-input');
    setupEkspedisiInput(ekspedisiInput);
    
    index++;
}

// ===== DESKTOP =====
function addDesktop(){
    const tbody = document.getElementById('desktopRows');
    const row = document.createElement('tr');
    row.setAttribute('data-index', index);

    row.innerHTML = `
        <td class="p-2">
            <input name="mailings[${index}][mailing_resi]"
                   class="w-full border rounded px-2 py-1"
                   placeholder="RESI" required>
        </td>
        <td class="p-2">
            <input name="mailings[${index}][mailing_pengirim]"
                   class="w-full border rounded px-2 py-1"
                   placeholder="PENGIRIM" required>
        </td>
        <td class="p-2">
            <input name="mailings[${index}][mailing_penerima]"
                   class="w-full border rounded px-2 py-1"
                   placeholder="PENERIMA" required>
        </td>
        <td class="p-2">
            <select name="mailings[${index}][mailing_lantai]"
                    class="w-full border rounded px-2 py-1 text-xs"
                    required>
                <option value="">-- Pilih --</option>
                <option value="41">41</option>
                <option value="42">42</option>
                <option value="43">43</option>
                <option value="45">45</option>
                <option value="46">46</option>
                <option value="47">47</option>
            </select>
        </td>
        <td class="p-2">
            <div class="relative">
                <input type="text"
                       name="mailings[${index}][id_ekspedisi_input]"
                       list="ekspedisiList"
                       placeholder="Ketik atau pilih"
                       class="w-full border rounded px-2 py-1 pr-8 ekspedisi-input"
                       data-index="${index}"
                       autocomplete="off"
                       required>
                <input type="hidden" 
                       name="mailings[${index}][id_ekspedisi]" 
                       class="ekspedisi-hidden" 
                       id="ekspedisi-hidden-${index}-d">
            </div>
        </td>
        <td class="p-2 text-center">
            <button type="button"
                    onclick="removeDesktop(this)"
                    class="text-red-500 text-xs hover:text-red-700">
                Hapus
            </button>
        </td>
    `;
    
    tbody.appendChild(row);
    
    // Setup event listener untuk input ekspedisi baru
    const ekspedisiInput = row.querySelector('.ekspedisi-input');
    setupEkspedisiInput(ekspedisiInput);
    
    index++;
}

function removeMobile(btn){
    btn.closest('.mailing-card').remove();
}

function removeDesktop(btn){
    btn.closest('tr').remove();
}

// Inisialisasi saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    // Setup semua input ekspedisi yang sudah ada
    document.querySelectorAll('.ekspedisi-input').forEach(input => {
        setupEkspedisiInput(input);
    });
    
    // Validasi form
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        // Di dalam event listener submit form (ganti yang lama)
form.addEventListener('submit', function(e) {
    console.log('=== DEBUG FORM DATA BEFORE SUBMIT ===');
    
    // 1. Hitung jumlah baris
    const mobileRows = document.querySelectorAll('.mailing-card').length;
    const desktopRows = document.querySelectorAll('#desktopRows tr').length;
    console.log(`Rows: Mobile=${mobileRows}, Desktop=${desktopRows}`);
    
    // 2. Cek semua hidden id_ekspedisi
    const ekspedisiHiddenInputs = document.querySelectorAll('input[name*="[id_ekspedisi]"]');
    console.log('=== ALL HIDDEN EKSPEDISI INPUTS ===');
    ekspedisiHiddenInputs.forEach((input, i) => {
        console.log(`${i+1}. Name: ${input.name}, Value: "${input.value}", Valid: ${!!input.value && input.value !== '0'}`);
    });
    
    // 3. Tampilkan semua data form
    console.log('=== COMPLETE FORM DATA ===');
    const formData = new FormData(this);
    let hasEmptyEkspedisiId = false;
    
    for (let pair of formData.entries()) {
        console.log(`${pair[0]} = ${pair[1]}`);
        
        // Cek jika id_ekspedisi kosong
        if (pair[0].includes('[id_ekspedisi]') && (!pair[1] || pair[1] === '0')) {
            hasEmptyEkspedisiId = true;
            console.warn(`⚠️ EMPTY EKSPEDISI ID FOUND: ${pair[0]}`);
        }
    }
    
    // 4. Jika ada id_ekspedisi yang kosong, prevent submit
    if (hasEmptyEkspedisiId) {
        e.preventDefault();
        alert('⚠️ Beberapa ekspedisi belum dipilih dengan benar. Silakan pilih dari dropdown atau ketik nama ekspedisi yang valid.');
        return false;
    }
    
    // 5. Tampilkan loading
    const submitBtn = this.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.innerHTML = '🔄 Menyimpan...';
        submitBtn.disabled = true;
    }
});
    });
    
    // Auto-focus ke input pertama
    const firstResiInput = document.querySelector('input[name*="mailing_resi"]');
    if (firstResiInput) {
        firstResiInput.focus();
    }
});
</script>

<style>
.border-red-500 {
    border-color: #ef4444 !important;
}

.border-red-500:focus {
    border-color: #dc2626 !important;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}
</style>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/mailing/create.blade.php ENDPATH**/ ?>