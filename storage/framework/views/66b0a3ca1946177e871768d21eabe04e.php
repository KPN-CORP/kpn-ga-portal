<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 py-6">

    
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-gray-900">Pengajuan Apartemen</h1>
        <p class="text-sm text-gray-500">Form permintaan hunian karyawan</p>
    </div>

    <form id="apartemenForm" method="POST" action="<?php echo e(route('apartemen.user.store')); ?>" class="space-y-6">
        <?php echo csrf_field(); ?>

        
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah Penghuni</label>
            <div class="relative w-40">
                <input type="number" id="jumlah" min="1" max="15" value="1"
                       onchange="generate()"
                       class="w-full pl-10 pr-3 py-2 rounded-xl border-gray-200 focus:ring-blue-500 focus:border-blue-500">

                <svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                          d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m6-4a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
        </div>

        
        <div id="penghuni" class="space-y-5"></div>

        
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Alasan Pengajuan <span class="text-red-500">*</span>
            </label>
            <textarea name="alasan" rows="3" required minlength="20"
                      placeholder="Contoh: Penempatan kerja proyek jangka menengah"
                      class="w-full rounded-xl border-gray-200 focus:ring-blue-500 focus:border-blue-500"></textarea>
            <p class="text-xs text-gray-400 mt-1">Minimal 20 karakter</p>
        </div>

        
        <div class="flex justify-end gap-3">
            <a href="<?php echo e(route('apartemen.user.index')); ?>"
               class="px-5 py-2 rounded-xl border text-gray-600 hover:bg-gray-50">
                Batal
            </a>
            <button id="submitBtn"
                    class="px-6 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-medium shadow">
                Ajukan Permintaan
            </button>
        </div>
    </form>
</div>

<script>
const MAX = 15;

/* ========= UTIL ========= */
function onlyNumber(el){
    el.value = el.value.replace(/[^0-9]/g,'');
}

/* ========= GENERATE PENGHUNI ========= */
function generate(){
    const jml = Math.min(Math.max(+jumlah.value || 1,1),MAX);
    jumlah.value = jml;
    penghuni.innerHTML = '';

    for(let i=0;i<jml;i++){
        const wajib = i===0 ? 'required' : '';
        penghuni.innerHTML += `
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-gray-800">Penghuni ${i+1}</h3>
                ${i===0 ? '<span class="text-xs bg-blue-50 text-blue-600 px-3 py-1 rounded-full">Wajib</span>' : ''}
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                ${inputIcon('Nama Lengkap','penghuni['+i+'][nama]','user',wajib)}
                ${inputIcon('ID Karyawan','penghuni['+i+'][id_karyawan]','badge',wajib)}
                ${inputIcon('No HP','penghuni['+i+'][no_hp]','phone',wajib,'onlyNumber(this)',9,15)}
                ${selectGolongan('penghuni['+i+'][gol]')}
                ${inputIcon('Unit Kerja','penghuni['+i+'][unit_kerja]','office')}
                ${inputDate('Tanggal Mulai','penghuni['+i+'][tanggal_mulai]',wajib,'mulai')}
                ${inputDate('Tanggal Selesai','penghuni['+i+'][tanggal_selesai]',wajib,'selesai')}
            </div>
        </div>`;
    }
}

/* ========= INPUT COMPONENT ========= */
function inputIcon(label,name,icon,req='',oninput='',min='',max=''){
    return `
    <div>
        <label class="text-xs text-gray-500 mb-1 block">${label}</label>
        <div class="relative">
            <input ${req} name="${name}"
                   ${min ? `minlength="${min}" maxlength="${max}"` : ''}
                   oninput="${oninput}"
                   placeholder="${label}"
                   class="w-full pl-10 pr-3 py-2 rounded-xl border-gray-200 focus:ring-blue-500 focus:border-blue-500">
            ${iconSvg(icon)}
        </div>
    </div>`;
}

function inputDate(label,name,req,cls){
    return `
    <div>
        <label class="text-xs text-gray-500 mb-1 block">${label}</label>
        <input type="date" ${req} name="${name}"
               class="w-full px-3 py-2 rounded-xl border-gray-200 focus:ring-blue-500 focus:border-blue-500 ${cls}">
    </div>`;
}

function selectGolongan(name){
    return `
    <div>
        <label class="text-xs text-gray-500 mb-1 block">Golongan</label>
        <div class="relative">
            <select name="${name}"
                class="w-full pl-10 pr-3 py-2 rounded-xl border-gray-200 bg-white focus:ring-blue-500 focus:border-blue-500">
                <option value="">Pilih Golongan</option>
                <option>4</option>
                <option>5</option>
                <option>6</option>
                <option>7</option>
                <option>8</option>
                <option>9</option>
                <option>10</option>
            </select>
            ${iconSvg('layers')}
        </div>
    </div>`;
}

/* ========= ICONS ========= */
function iconSvg(type){
    const icons = {
        user:`<svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A9 9 0 1119.9 7.1"/></svg>`,
        phone:`<svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h2l2 5-2 1a11 11 0 005 5l1-2 5 2v2a2 2 0 01-2 2A16 16 0 013 5z"/></svg>`,
        badge:`<svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>`,
        office:`<svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M3 21h18V7H3v14z"/></svg>`,
        layers:`<svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 2l9 5-9 5-9-5 9-5zm0 10l9 5-9 5-9-5 9-5z"/></svg>`
    };
    return icons[type] ?? '';
}

/* ========= VALIDASI & SUBMIT ========= */
function validateForm(){
    const phones = document.querySelectorAll('[name*="[no_hp]"]');
    for (let p of phones){
        if (p.value && (p.value.length < 9 || p.value.length > 15)){
            alert('No HP harus 9–15 digit angka');
            p.focus(); return false;
        }
    }

    const mulai = document.querySelectorAll('.mulai');
    const selesai = document.querySelectorAll('.selesai');
    for (let i=0;i<mulai.length;i++){
        if (mulai[i].value && selesai[i].value && selesai[i].value <= mulai[i].value){
            alert(`Tanggal selesai harus setelah tanggal mulai (Penghuni ${i+1})`);
            selesai[i].focus(); return false;
        }
    }
    return true;
}

apartemenForm.addEventListener('submit', e => {
    e.preventDefault();
    if (!validateForm()) return;

    submitBtn.disabled = true;
    submitBtn.innerText = 'Mengirim...';
    apartemenForm.submit();
});

document.addEventListener('DOMContentLoaded', generate);
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/apartemen/user/create.blade.php ENDPATH**/ ?>