<?php $__env->startSection('content'); ?>
<div class="p-4 md:p-6">

    
    <div class="mb-6 md:mb-8">
        
        <div class="lg:hidden mb-4">
            <h1 class="text-xl font-bold text-gray-800">Permintaan Apartemen</h1>
            <p class="text-gray-600 text-xs mt-1">Kelola dan review permintaan penghuni apartemen</p>
        </div>

        
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 mb-4 md:mb-6">
            
            <div class="hidden lg:flex items-center space-x-4 flex-1">
                
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Permintaan Apartemen</h1>
                    <p class="text-gray-600 text-sm mt-1">Kelola dan review permintaan penghuni apartemen</p>
                </div>
            </div>

            
            <div class="w-full lg:w-auto lg:mx-4 lg:flex-1 lg:max-w-md order-first lg:order-none">
                <div class="relative">
                    <form method="GET" action="<?php echo e(route('apartemen.admin.index')); ?>">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" 
                               name="search"
                               value="<?php echo e(request('search')); ?>"
                               class="pl-10 pr-4 py-2 md:py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full" 
                               placeholder="Cari permintaan...">
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
                class="inline-flex items-center px-3 py-2 bg-blue-50 border border-blue-200 rounded-lg transition-colors flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="font-medium text-blue-700 text-sm truncate">Permintaan</span>
                    <?php if($pendingCount > 0): ?>
                    <span class="ml-1 md:ml-2 bg-blue-100 text-blue-800 text-xs px-1.5 md:px-2 py-0.5 rounded-full whitespace-nowrap"><?php echo e($pendingCount); ?></span>
                    <?php endif; ?>
                </a>

                
                <a href="<?php echo e(route('apartemen.admin.apartemen')); ?>"
                   class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-green-600 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span class="font-medium text-gray-700 text-sm truncate">Unit</span>
                    <?php if($unitCount > 0): ?>
                    <span class="ml-1 md:ml-2 bg-green-100 text-green-800 text-xs px-1.5 md:px-2 py-0.5 rounded-full whitespace-nowrap"><?php echo e($unitCount); ?></span>
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
                    <?php
                        $historyCount = \App\Models\Apartemen\ApartemenHistory::count();
                    ?>
                    <?php if($historyCount > 0): ?>
                    <span class="ml-1 md:ml-2 bg-blue-100 text-blue-800 text-xs px-1.5 md:px-2 py-0.5 rounded-full whitespace-nowrap"><?php echo e($historyCount); ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>

        
        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4">
            <form method="GET" action="<?php echo e(route('apartemen.admin.index')); ?>" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Status</option>
                        <option value="PENDING" <?php echo e(request('status') == 'PENDING' ? 'selected' : ''); ?>>Pending</option>
                        <option value="APPROVED" <?php echo e(request('status') == 'APPROVED' ? 'selected' : ''); ?>>Disetujui</option>
                        <option value="REJECTED" <?php echo e(request('status') == 'REJECTED' ? 'selected' : ''); ?>>Ditolak</option>
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
                    <a href="<?php echo e(route('apartemen.admin.index')); ?>" 
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
                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pemohon</th>
                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penghuni</th>
                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php $__empty_1 = true; $__currentLoopData = $requests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $req): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 md:px-6 py-4">
                            <span class="text-sm font-medium text-gray-900">#<?php echo e(str_pad($req->id, 6, '0', STR_PAD_LEFT)); ?></span>
                        </td>
                        <td class="px-4 md:px-6 py-4">
                            <div class="text-sm font-medium text-gray-900"><?php echo e($req->user->name ?? 'N/A'); ?></div>
                            <div class="text-xs text-gray-500 mt-1"><?php echo e($req->tanggal_pengajuan->format('d/m/Y')); ?></div>
                        </td>
                        <td class="px-4 md:px-6 py-4">
                            <div class="text-sm text-gray-900"><?php echo e($req->penghuni->count()); ?> orang</div>
                            <div class="text-xs text-gray-500 mt-1">
                                <?php $__currentLoopData = $req->penghuni->take(2); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $penghuni): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php echo e($penghuni->nama); ?><?php if(!$loop->last): ?>, <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php if($req->penghuni->count() > 2): ?>
                                <span class="text-gray-400">+<?php echo e($req->penghuni->count() - 2); ?> lainnya</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-4 md:px-6 py-4 text-sm text-gray-500">
                            <?php echo e($req->created_at->format('d M Y')); ?>

                        </td>
                        <td class="px-4 md:px-6 py-4">
                            <?php switch($req->status):
                                case ('PENDING'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Pending
                                    </span>
                                    <?php break; ?>
                                <?php case ('APPROVED'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Disetujui
                                    </span>
                                    <?php break; ?>
                                <?php case ('REJECTED'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Ditolak
                                    </span>
                                    <?php break; ?>
                            <?php endswitch; ?>
                        </td>
                        <td class="px-4 md:px-6 py-4 text-sm text-gray-500">
                            <?php if($req->assign && $req->assign->unit): ?>
                                <div class="font-medium text-gray-900"><?php echo e($req->assign->unit->apartemen->nama_apartemen ?? 'N/A'); ?></div>
                                <div class="text-xs text-gray-500"><?php echo e($req->assign->unit->nomor_unit ?? 'N/A'); ?></div>
                            <?php elseif($req->status == 'APPROVED' && !$req->assign): ?>
                                <span class="text-xs text-orange-600">Belum ditetapkan</span>
                            <?php else: ?>
                                <span class="text-xs text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 md:px-6 py-4">
                            <div class="flex items-center space-x-2">
                                <?php if($req->status == 'PENDING'): ?>
                                    <a href="<?php echo e(route('apartemen.admin.approve', $req->id)); ?>" 
                                       class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded-lg transition-colors">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Review
                                    </a>
                                <?php endif; ?>
                                
                                <a href="<?php echo e(route('apartemen.admin.detail', $req->id)); ?>" 
                                class="inline-flex items-center px-3 py-1.5 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-xs rounded-lg transition-colors">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Detail
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="px-4 md:px-6 py-12 text-center">
                            <div class="text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-gray-500">Belum ada permintaan</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        
        <?php if($requests->hasPages()): ?>
        <div class="px-4 md:px-6 py-4 border-t border-gray-200">
            <?php echo e($requests->links()); ?>

        </div>
        <?php endif; ?>
    </div>

</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/apartemen/admin/index.blade.php ENDPATH**/ ?>