<?php $__env->startSection('content'); ?>
<div class="p-4 md:p-6">

    
    <?php if(session('success')): ?>
    <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg">
        <?php echo e(session('success')); ?>

    </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
    <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">
        <?php echo e(session('error')); ?>

    </div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
    <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">
        <ul class="list-disc pl-4">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
    <?php endif; ?>

    
    <div class="mb-6 md:mb-8">
        
        <div class="lg:hidden mb-4">
            <h1 class="text-xl font-bold text-gray-800">Detail Apartemen</h1>
            <p class="text-gray-600 text-xs mt-1">Informasi detail apartemen dan unit</p>
        </div>

        
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 mb-4 md:mb-6">
            
            <div class="hidden lg:flex items-center space-x-4 flex-1">
                
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Detail Apartemen</h1>
                    <p class="text-gray-600 text-sm mt-1">Informasi detail apartemen dan unit</p>
                </div>
            </div>

            
            <div class="w-full lg:w-auto lg:mx-4 lg:flex-1 lg:max-w-md order-first lg:order-none">
                <div class="relative">
                    <form action="<?php echo e(route('apartemen.admin.apartemen.detail', $apartemen->id)); ?>" method="GET">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="<?php echo e(request('search')); ?>" 
                               class="pl-10 pr-4 py-2 md:py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full" 
                               placeholder="Cari unit...">
                    </form>
                </div>
            </div>

            
            <div class="flex flex-wrap items-center gap-2 lg:gap-3 w-full lg:w-auto">
                <?php
                    $pendingCount = \App\Models\Apartemen\ApartemenRequest::where('status', 'PENDING')->count();
                    $unitCount = \App\Models\Apartemen\ApartemenUnit::count();
                    $penghuniCount = \App\Models\Apartemen\ApartemenPenghuni::whereHas('assign', function($q) {
                        $q->where('status', 'AKTIF');
                    })->count();
                ?>
                
                
                <a href="<?php echo e(route('apartemen.admin.index')); ?>" 
                   class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="font-medium text-gray-700 text-sm truncate">Permintaan</span>
                    <?php if($pendingCount > 0): ?>
                    <span class="ml-1 md:ml-2 bg-blue-100 text-blue-800 text-xs px-1.5 md:px-2 py-0.5 rounded-full whitespace-nowrap"><?php echo e($pendingCount); ?></span>
                    <?php endif; ?>
                </a>

                
                <a href="<?php echo e(route('apartemen.admin.apartemen')); ?>"
                   class="inline-flex items-center px-3 py-2 bg-blue-50 border border-blue-200 rounded-lg transition-colors flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span class="font-medium text-blue-700 text-sm truncate">Unit</span>
                    <?php if($unitCount > 0): ?>
                    <span class="ml-1 md:ml-2 bg-blue-100 text-blue-800 text-xs px-1.5 md:px-2 py-0.5 rounded-full whitespace-nowrap"><?php echo e($unitCount); ?></span>
                    <?php endif; ?>
                </a>

                
                <a href="<?php echo e(route('apartemen.admin.monitoring')); ?>"
                   class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-10A2.5 2.5 0 1121 10.5 2.5 2.5 0 0118.5 8z" />
                    </svg>
                    <span class="font-medium text-gray-700 text-sm truncate">Penghuni</span>
                    <?php if($penghuniCount > 0): ?>
                    <span class="ml-1 md:ml-2 bg-blue-100 text-blue-800 text-xs px-1.5 md:px-2 py-0.5 rounded-full whitespace-nowrap"><?php echo e($penghuniCount); ?></span>
                    <?php endif; ?>
                </a>

                
                <a href="<?php echo e(route('apartemen.admin.history')); ?>"
                   class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium text-gray-700 text-sm truncate">Riwayat</span>
                </a>
            </div>
        </div>

        
        <div class="flex items-center text-sm text-gray-600 mb-4">
            <a href="<?php echo e(route('apartemen.admin.apartemen')); ?>" class="hover:text-blue-600">Apartemen</a>
            <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="font-medium text-gray-800"><?php echo e($apartemen->nama_apartemen); ?></span>
        </div>
    </div>

    
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-6">
        <div class="p-6">
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-gray-800"><?php echo e($apartemen->nama_apartemen); ?></h2>
                    <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="text-sm text-gray-500">Alamat</label>
                            <p class="font-medium"><?php echo e($apartemen->alamat ?? '-'); ?></p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-500">Penanggung Jawab</label>
                            <p class="font-medium"><?php echo e($apartemen->penanggung_jawab ?? '-'); ?></p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-500">Telepon</label>
                            <p class="font-medium"><?php echo e($apartemen->telepon ?? '-'); ?></p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-500">Email</label>
                            <p class="font-medium"><?php echo e($apartemen->email ?? '-'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4 min-w-[200px]">
                    <div class="text-center mb-2">
                        <label class="text-sm text-gray-500">Status Unit</label>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div class="text-center">
                            <div class="text-lg font-bold text-gray-900"><?php echo e($apartemen->units_count ?? 0); ?></div>
                            <div class="text-xs text-gray-500">Total</div>
                        </div>
                        <div class="text-center">
                            <div class="text-lg font-bold text-green-600"><?php echo e($apartemen->units_ready ?? 0); ?></div>
                            <div class="text-xs text-gray-500">Tersedia</div>
                        </div>
                        <div class="text-center">
                            <div class="text-lg font-bold text-blue-600"><?php echo e($apartemen->units_terisi ?? 0); ?></div>
                            <div class="text-xs text-gray-500">Terisi</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h3 class="text-lg font-semibold text-gray-800">Daftar Unit</h3>
                <div class="flex items-center gap-3">
                    <div class="text-sm text-gray-500">
                        Total: <span class="font-medium"><?php echo e($units->total()); ?></span> unit
                    </div>
                    <button onclick="toggleAddUnitForm()"
                       class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                        + Tambah Unit
                    </button>
                </div>
            </div>
        </div>

        
        <div id="addUnitForm" class="hidden p-6 border-b border-gray-200 bg-gray-50">
            <h4 class="font-medium text-gray-800 mb-3">Tambah Unit Baru</h4>
            <form action="<?php echo e(route('apartemen.admin.unit.store')); ?>" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="apartemen_id" value="<?php echo e($apartemen->id); ?>">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Unit *</label>
                    <input type="text" name="nomor_unit" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Contoh: A101">
                    <?php $__errorArgs = ['nomor_unit'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kapasitas *</label>
                    <input type="number" name="kapasitas" required min="1" max="10"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Jumlah orang" value="2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                    <select name="status" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="READY">Tersedia</option>
                        <option value="MAINTENANCE">Maintenance</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" 
                            class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Simpan
                    </button>
                    <button type="button" onclick="toggleAddUnitForm()"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Batal
                    </button>
                </div>
            </form>
        </div>

        
        <div class="p-6">
            <?php if($units->count() > 0): ?>
            <div class="overflow-x-auto -mx-3 md:mx-0">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nomor Unit</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kapasitas</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penghuni Aktif</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php $__currentLoopData = $units; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900">Unit <?php echo e($unit->nomor_unit); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo e($unit->kapasitas); ?> orang</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php switch($unit->status):
                                    case ('READY'): ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Tersedia
                                        </span>
                                        <?php break; ?>
                                    <?php case ('TERISI'): ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Terisi
                                        </span>
                                        <?php break; ?>
                                    <?php case ('MAINTENANCE'): ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Maintenance
                                        </span>
                                        <?php break; ?>
                                    <?php default: ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <?php echo e($unit->status ?? 'Unknown'); ?>

                                        </span>
                                <?php endswitch; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo e($unit->active_assignments ?? 0); ?> orang</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <?php if($unit->status == 'READY' || $unit->status == 'MAINTENANCE'): ?>
                                    <button onclick="toggleMaintenance(<?php echo e($unit->id); ?>, '<?php echo e($unit->status); ?>')"
                                            class="text-yellow-600 hover:text-yellow-800 transition-colors">
                                        <?php if($unit->status == 'READY'): ?>
                                        Set Maintenance
                                        <?php elseif($unit->status == 'MAINTENANCE'): ?>
                                        Set Tersedia
                                        <?php else: ?>
                                        Ubah Status
                                        <?php endif; ?>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if($unit->status == 'READY' && ($unit->active_assignments ?? 0) == 0): ?>
                                    <button onclick="deleteUnit(<?php echo e($unit->id); ?>, '<?php echo e($unit->nomor_unit); ?>')"
                                            class="text-red-600 hover:text-red-800 transition-colors">
                                        Hapus
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>

            
            <div class="mt-6">
                <?php echo e($units->links()); ?>

            </div>
            <?php else: ?>
            
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">Belum ada unit</h3>
                <p class="mt-1 text-gray-500 max-w-md mx-auto">
                    Tambahkan unit baru untuk apartemen ini.
                </p>
                <button onclick="toggleAddUnitForm()"
                        class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    + Tambah Unit Pertama
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>


<div id="maintenanceModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden transition-opacity">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white transform transition-all">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4" id="modalTitle">Update Status Unit</h3>
            <form id="maintenanceForm" method="POST" class="space-y-4">
                <?php echo csrf_field(); ?>
                <input type="hidden" id="unitId" name="unit_id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                    <select name="status" id="unitStatus" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                        <option value="READY">Tersedia</option>
                        <option value="MAINTENANCE">Maintenance</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (Opsional)</label>
                    <textarea name="catatan" rows="3" 
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                              placeholder="Catatan maintenance..."></textarea>
                </div>
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeModal()"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-medium transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="submitMaintenanceBtn"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
// Toggle Add Unit Form
function toggleAddUnitForm() {
    const form = document.getElementById('addUnitForm');
    form.classList.toggle('hidden');
}

// Toggle Maintenance Modal
function toggleMaintenance(unitId, currentStatus) {
    document.getElementById('unitId').value = unitId;
    document.getElementById('maintenanceForm').action = "<?php echo e(route('apartemen.admin.setMaintenance')); ?>";
    
    // Set current status in dropdown
    const statusSelect = document.getElementById('unitStatus');
    if (currentStatus === 'READY') {
        statusSelect.value = 'MAINTENANCE';
        document.getElementById('modalTitle').textContent = 'Set Unit ke Maintenance';
    } else if (currentStatus === 'MAINTENANCE') {
        statusSelect.value = 'READY';
        document.getElementById('modalTitle').textContent = 'Set Unit ke Tersedia';
    }
    
    // Show modal with animation
    const modal = document.getElementById('maintenanceModal');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.add('opacity-100');
    }, 10);
}

// Delete Unit
function deleteUnit(unitId, unitName) {
    if (confirm(`Apakah Anda yakin ingin menghapus Unit ${unitName}?`)) {
        // Show loading
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = 'Menghapus...';
        button.disabled = true;
        
        fetch("<?php echo e(route('apartemen.admin.unit.delete')); ?>", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                unit_id: unitId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload page
                location.reload();
            } else {
                alert(data.message || 'Terjadi kesalahan');
                button.innerHTML = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            alert('Terjadi kesalahan: ' + error.message);
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }
}

// Close Modal
function closeModal() {
    const modal = document.getElementById('maintenanceModal');
    modal.classList.remove('opacity-100');
    setTimeout(() => {
        modal.classList.add('hidden');
        document.getElementById('maintenanceForm').reset();
        resetMaintenanceButton();
    }, 200);
}

function resetMaintenanceButton() {
    const submitBtn = document.getElementById('submitMaintenanceBtn');
    if (submitBtn) {
        submitBtn.innerHTML = 'Simpan';
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}

// Close modal when clicking outside
document.getElementById('maintenanceModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('maintenanceModal').classList.contains('hidden')) {
        closeModal();
    }
});

// Handle maintenance form submission
document.getElementById('maintenanceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // Show loading state
    submitBtn.innerHTML = 'Menyimpan...';
    submitBtn.disabled = true;
    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
    
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal and reload
            closeModal();
            location.reload();
        } else {
            alert(data.message || 'Terjadi kesalahan');
            resetMaintenanceButton();
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan: ' + error.message);
        resetMaintenanceButton();
    });
});

// Form validation for add unit form
document.addEventListener('DOMContentLoaded', function() {
    const addUnitForm = document.querySelector('#addUnitForm form');
    if (addUnitForm) {
        addUnitForm.addEventListener('submit', function(e) {
            const nomorUnit = this.querySelector('input[name="nomor_unit"]').value.trim();
            const kapasitas = this.querySelector('input[name="kapasitas"]').value;
            
            if (!nomorUnit) {
                e.preventDefault();
                alert('Nomor unit harus diisi');
                this.querySelector('input[name="nomor_unit"]').focus();
                return false;
            }
            
            if (!kapasitas || kapasitas < 1) {
                e.preventDefault();
                alert('Kapasitas harus minimal 1 orang');
                this.querySelector('input[name="kapasitas"]').focus();
                return false;
            }
            
            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = 'Menyimpan...';
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
            
            return true;
        });
    }
});
</script>

<style>
/* Smooth transitions */
* {
    transition-property: background-color, border-color, color, fill, stroke, opacity, transform;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
}

/* Modal animation */
#maintenanceModal {
    opacity: 0;
    transition: opacity 0.2s ease;
}

#maintenanceModal.opacity-100 {
    opacity: 1;
}

#maintenanceModal .transform {
    transform: scale(0.95);
    transition: transform 0.2s ease;
}

#maintenanceModal.opacity-100 .transform {
    transform: scale(1);
}

/* Hover effects */
tr:hover {
    transition: background-color 0.15s ease;
}

/* Custom scrollbar */
.overflow-x-auto::-webkit-scrollbar {
    height: 4px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 2px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 2px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}
</style>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/apartemen/admin/apartemen-detail.blade.php ENDPATH**/ ?>