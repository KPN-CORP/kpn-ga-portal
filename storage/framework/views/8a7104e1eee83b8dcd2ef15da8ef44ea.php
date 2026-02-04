<?php $__env->startSection('content'); ?>
<div class="p-4 md:p-6">

    
    <div class="mb-6 md:mb-8">
        
        <div class="lg:hidden mb-4">
            <h1 class="text-xl font-bold text-gray-800">Riwayat Permintaan</h1>
            <p class="text-gray-600 text-xs mt-1">Lihat semua permintaan apartemen Anda</p>
        </div>

        
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 mb-4 md:mb-6">
            
            <div class="hidden lg:flex items-center space-x-4 flex-1">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Riwayat Permintaan</h1>
                    <p class="text-gray-600 text-sm mt-1">Lihat semua permintaan apartemen Anda</p>
                </div>
            </div>

            
            <div class="w-full lg:w-auto lg:mx-4 lg:flex-1 lg:max-w-md order-first lg:order-none">
                <div class="relative">
                    <form method="GET" action="<?php echo e(route('apartemen.user.requests')); ?>" id="searchForm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" 
                               name="search"
                               value="<?php echo e(request('search')); ?>"
                               class="pl-10 pr-4 py-2 md:py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full" 
                               placeholder="Cari permintaan/apartemen..."
                               id="searchInput">
                    </form>
                </div>
            </div>

            
            <div class="flex flex-wrap items-center gap-2 lg:gap-3 w-full lg:w-auto">
                
                <button onclick="window.location.href='<?php echo e(route('apartemen.user.index')); ?>'" 
                       class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium text-sm truncate">Status Aktif</span>
                    <?php if($activeCount > 0): ?>
                    <span class="ml-1 md:ml-2 bg-blue-100 text-blue-800 text-xs px-1.5 md:px-2 py-0.5 rounded-full whitespace-nowrap"><?php echo e($activeCount); ?></span>
                    <?php endif; ?>
                </button>

                
                <button onclick="window.location.href='<?php echo e(route('apartemen.user.requests')); ?>'" 
                       class="inline-flex items-center px-3 py-2 bg-blue-50 border border-blue-600 text-blue-600 rounded-lg transition-colors flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="font-medium text-sm truncate">Riwayat Permintaan</span>
                    <?php if($requests->total() > 0): ?>
                    <span class="ml-1 md:ml-2 bg-blue-100 text-blue-800 text-xs px-1.5 md:px-2 py-0.5 rounded-full whitespace-nowrap"><?php echo e($requests->total()); ?></span>
                    <?php endif; ?>
                </button>

                
                <button onclick="window.location.href='<?php echo e(route('apartemen.user.create')); ?>'" 
                       class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0">
                    <svg class="w-4 h-4 md:w-5 md:h-5 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="font-medium text-sm truncate">Pengajuan Baru</span>
                </button>
            </div>
        </div>

        
        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4">
            <form method="GET" action="<?php echo e(route('apartemen.user.requests')); ?>" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Status</option>
                        <option value="APPROVED" <?php echo e(request('status') == 'APPROVED' ? 'selected' : ''); ?>>Disetujui</option>
                        <option value="REJECTED" <?php echo e(request('status') == 'REJECTED' ? 'selected' : ''); ?>>Ditolak</option>
                        <option value="PENDING" <?php echo e(request('status') == 'PENDING' ? 'selected' : ''); ?>>Tertunda</option>
                    </select>
                </div>

                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" value="<?php echo e(request('tanggal_mulai')); ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" value="<?php echo e(request('tanggal_selesai')); ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                
                <div class="flex items-end space-x-2">
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors w-full">
                        Terapkan Filter
                    </button>
                    <a href="<?php echo e(route('apartemen.user.requests')); ?>" 
                       class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium text-center transition-colors">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        
        <div class="p-4 md:p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800">Daftar Permintaan</h2>
                <div class="text-sm text-gray-500">
                    Total: <?php echo e($requests->total()); ?> permintaan
                </div>
            </div>
        </div>

        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <!-- STATUS dipindahkan ke urutan kedua -->
                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <!-- JENIS dipindahkan ke urutan ketiga -->
                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis</th>
                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php $__empty_1 = true; $__currentLoopData = $requests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $request): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo e($request->tanggal_pengajuan->format('d/m/Y')); ?></div>
                            <div class="text-xs text-gray-500"><?php echo e($request->tanggal_pengajuan->format('H:i')); ?></div>
                        </td>
                        <!-- STATUS dipindahkan ke urutan kedua -->
                        <td class="px-4 md:px-6 py-4">
                            <span class="status-badge status-<?php echo e($request->status_color); ?>">
                                <?php echo e($request->status_text); ?>

                            </span>
                            <?php if($request->status == 'REJECTED' && $request->reject_reason): ?>
                            <div class="text-xs text-gray-500 mt-1 max-w-xs"><?php echo e(Str::limit($request->reject_reason, 30)); ?></div>
                            <?php endif; ?>
                        </td>
                        <!-- JENIS dipindahkan ke urutan ketiga -->
                        <td class="px-4 md:px-6 py-4">
                            <div class="text-sm text-gray-900"><?php echo e($request->jenis_text ?? 'Permintaan Baru'); ?></div>
                            <div class="text-xs text-gray-500"><?php echo e($request->penghuni->count()); ?> penghuni</div>
                        </td>
                        <td class="px-4 md:px-6 py-4">
                            <div class="text-sm text-gray-900">
                                <?php if($request->assign && $request->assign->unit): ?>
                                    <?php echo e($request->assign->unit->apartemen->nama_apartemen ?? 'N/A'); ?>

                                <?php elseif($request->alasan): ?>
                                    <?php echo e(Str::limit($request->alasan, 30)); ?>

                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </div>
                            <?php if($request->assign && $request->assign->unit): ?>
                            <div class="text-xs text-gray-500">Unit <?php echo e($request->assign->unit->nomor_unit); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 md:px-6 py-4">
                            <a href="<?php echo e(route('apartemen.user.show', $request->id)); ?>" 
                               class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                Detail
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="5" class="px-4 md:px-6 py-12 text-center">
                            <div class="text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-gray-500">Belum ada riwayat permintaan</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        
        <?php if($requests->hasPages()): ?>
        <div class="px-4 md:px-6 py-4 border-t border-gray-200">
            <?php echo e($requests->withQueryString()->links()); ?>

        </div>
        <?php endif; ?>
    </div>
</div>


<script>
// Debounce for search input
let searchTimeout;
const searchInput = document.getElementById('searchInput');
if (searchInput) {
    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            if (this.value.length >= 3 || this.value.length === 0) {
                document.getElementById('searchForm').submit();
            }
        }, 500);
    });
    
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('searchForm').submit();
        }
    });
}

// Set default dates on page load
document.addEventListener('DOMContentLoaded', function() {
    const tanggalMulaiInput = document.querySelector('input[name="tanggal_mulai"]');
    const tanggalSelesaiInput = document.querySelector('input[name="tanggal_selesai"]');
    
    if (tanggalMulaiInput && !tanggalMulaiInput.value) {
        const lastMonth = new Date();
        lastMonth.setMonth(lastMonth.getMonth() - 1);
        tanggalMulaiInput.value = lastMonth.toISOString().split('T')[0];
    }
    
    if (tanggalSelesaiInput && !tanggalSelesaiInput.value) {
        const today = new Date();
        tanggalSelesaiInput.value = today.toISOString().split('T')[0];
    }
});
</script>

<style>
.status-badge {
    @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap;
}
.status-approved {
    @apply bg-green-100 text-green-800;
}
.status-rejected {
    @apply bg-red-100 text-red-800;
}
.status-pending {
    @apply bg-yellow-100 text-yellow-800;
}
.status-completed {
    @apply bg-gray-100 text-gray-800;
}

@media (max-width: 640px) {
    table {
        display: block;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
}
</style>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/apartemen/user/requests.blade.php ENDPATH**/ ?>