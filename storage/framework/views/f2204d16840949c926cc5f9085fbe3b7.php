<?php $__env->startSection('content'); ?>
<div class="p-4 md:p-6">

    
    <div class="mb-6 md:mb-8">
        
        <div class="lg:hidden mb-4">
            <h1 class="text-xl font-bold text-gray-800">Monitoring Penghuni</h1>
            <p class="text-gray-600 text-xs mt-1">Pantau penghuni aktif di semua apartemen</p>
        </div>

        
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 mb-4 md:mb-6">
            
            <div class="hidden lg:flex items-center space-x-4 flex-1">
                
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Monitoring Penghuni</h1>
                    <p class="text-gray-600 text-sm mt-1">Pantau penghuni aktif di semua apartemen</p>
                </div>
            </div>

            
            <div class="w-full lg:w-auto lg:mx-4 lg:flex-1 lg:max-w-md order-first lg:order-none">
                <div class="relative">
                    <form action="<?php echo e(route('apartemen.admin.monitoring')); ?>" method="GET">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="<?php echo e(request('search')); ?>" 
                               class="pl-10 pr-4 py-2 md:py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full" 
                               placeholder="Cari nama penghuni...">
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
                class="inline-flex items-center px-3 py-2 bg-blue-50 border border-blue-200 rounded-lg transition-colors flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-10A2.5 2.5 0 1121 10.5 2.5 2.5 0 0118.5 8z" />
                    </svg>
                    <span class="font-medium text-blue-700 text-sm truncate">Penghuni</span>
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

        
        <div class="bg-white border border-gray-200 rounded-lg p-3 md:p-4 mb-4 md:mb-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="w-full">
                    <h3 class="text-xs md:text-sm font-medium text-gray-700 mb-2">Filter Penghuni</h3>
                    <form action="<?php echo e(route('apartemen.admin.monitoring')); ?>" method="GET" class="space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 w-full">
                            <div>
                                <select name="apartemen_id" class="border border-gray-300 rounded-lg px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-full" onchange="this.form.submit()">
                                    <option value="">Semua Apartemen</option>
                                    <?php $__currentLoopData = $apartemen; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $apt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($apt->id); ?>" <?php echo e(request('apartemen_id') == $apt->id ? 'selected' : ''); ?>>
                                        <?php echo e($apt->nama_apartemen); ?>

                                    </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            
                            <div>
                                <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-full" onchange="this.form.submit()">
                                    <option value="">Semua Status</option>
                                    <option value="aktif" <?php echo e(request('status') == 'aktif' ? 'selected' : ''); ?>>Aktif</option>
                                    <option value="akan_selesai" <?php echo e(request('status') == 'akan_selesai' ? 'selected' : ''); ?>>Akan Selesai</option>
                                    <option value="telah_selesai" <?php echo e(request('status') == 'telah_selesai' ? 'selected' : ''); ?>>Telah Selesai</option>
                                </select>
                            </div>
                            
                            <div>
                                <select name="sort" class="border border-gray-300 rounded-lg px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-full" onchange="this.form.submit()">
                                    <option value="nama_asc" <?php echo e(request('sort') == 'nama_asc' ? 'selected' : ''); ?>>Nama A-Z</option>
                                    <option value="nama_desc" <?php echo e(request('sort') == 'nama_desc' ? 'selected' : ''); ?>>Nama Z-A</option>
                                    <option value="tanggal_mulai" <?php echo e(request('sort') == 'tanggal_mulai' ? 'selected' : ''); ?>>Tanggal Mulai</option>
                                    <option value="tanggal_selesai" <?php echo e(request('sort') == 'tanggal_selesai' ? 'selected' : ''); ?>>Tanggal Selesai</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
                            <div class="flex items-center gap-2 w-full sm:w-auto">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 md:px-4 py-1.5 md:py-2 rounded-lg text-xs md:text-sm font-medium whitespace-nowrap w-full sm:w-auto">
                                    Terapkan Filter
                                </button>
                                
                                <?php if(request()->anyFilled(['apartemen_id', 'status', 'search', 'sort'])): ?>
                                <a href="<?php echo e(route('apartemen.admin.monitoring')); ?>" class="text-gray-600 hover:text-gray-800 text-xs md:text-sm font-medium whitespace-nowrap w-full sm:w-auto text-center">
                                    Reset Filter
                                </a>
                                <?php endif; ?>
                            </div>
                            
                            
                            <?php if(request()->anyFilled(['apartemen_id', 'status'])): ?>
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-1 pt-2 sm:pt-0 border-t sm:border-t-0 border-gray-100 sm:border-none w-full">
                                <span class="text-xs text-gray-500">Filter aktif:</span>
                                <div class="flex flex-wrap gap-1">
                                    <?php if(request('apartemen_id')): ?>
                                        <?php
                                            $selectedApt = $apartemen->firstWhere('id', request('apartemen_id'));
                                        ?>
                                        <?php if($selectedApt): ?>
                                        <span class="inline-flex items-center px-1.5 md:px-2 py-0.5 md:py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 whitespace-nowrap">
                                            <?php echo e($selectedApt->nama_apartemen); ?>

                                        </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if(request('status')): ?>
                                    <span class="inline-flex items-center px-1.5 md:px-2 py-0.5 md:py-1 rounded-full text-xs font-medium 
                                        <?php echo e(request('status') == 'aktif' ? 'bg-green-100 text-green-800' : 
                                           (request('status') == 'akan_selesai' ? 'bg-yellow-100 text-yellow-800' : 
                                           'bg-red-100 text-red-800')); ?> whitespace-nowrap">
                                        <?php echo e(request('status') == 'aktif' ? 'Aktif' : 
                                         (request('status') == 'akan_selesai' ? 'Akan Selesai' : 
                                         'Telah Selesai')); ?>

                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 md:gap-4 mb-4 md:mb-6">
        <?php
            $totalActive = \App\Models\Apartemen\ApartemenPenghuni::whereHas('assign', function($q) {
                $q->where('status', 'AKTIF');
            })->count();
            
            $endingSoon = \App\Models\Apartemen\ApartemenAssign::where('status', 'AKTIF')
                ->whereBetween('tanggal_selesai', [now(), now()->addDays(7)])
                ->count();
        ?>
        
        <div class="bg-white rounded-lg border border-gray-200 p-3 md:p-4">
            <div class="flex items-center">
                <div class="p-1.5 md:p-2 rounded-lg bg-blue-100 mr-2 md:mr-3">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-10A2.5 2.5 0 1121 10.5 2.5 2.5 0 0118.5 8z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs md:text-sm text-gray-500 truncate">Penghuni Aktif</p>
                    <p class="text-lg md:text-xl font-bold text-gray-900 truncate"><?php echo e($totalActive); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg border border-gray-200 p-3 md:p-4">
            <div class="flex items-center">
                <div class="p-1.5 md:p-2 rounded-lg bg-yellow-100 mr-2 md:mr-3">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs md:text-sm text-gray-500 truncate">Akan Selesai (7 hari)</p>
                    <p class="text-lg md:text-xl font-bold text-gray-900 truncate"><?php echo e($endingSoon); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg border border-gray-200 p-3 md:p-4">
            <div class="flex items-center">
                <div class="p-1.5 md:p-2 rounded-lg bg-green-100 mr-2 md:mr-3">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs md:text-sm text-gray-500 truncate">Apartemen Aktif</p>
                    <p class="text-lg md:text-xl font-bold text-gray-900 truncate"><?php echo e($apartemen->count()); ?></p>
                </div>
            </div>
        </div>
    </div>

    
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        
        <div class="px-3 md:px-4 lg:px-6 py-3 md:py-4 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <h3 class="text-base md:text-lg font-semibold text-gray-800">Daftar Penghuni Aktif</h3>
                <div class="text-xs md:text-sm text-gray-500">
                    Total: <span class="font-medium"><?php echo e($penghuni->total() ?? 0); ?></span> penghuni
                </div>
            </div>
        </div>

        
        <div class="p-3 md:p-4 lg:p-6">
            <?php if($penghuni->count() > 0): ?>
            <div class="overflow-x-auto -mx-3 md:mx-0">
                <div class="min-w-full inline-block align-middle">
                    <div class="overflow-hidden border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-2 px-2 md:px-3 lg:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Penghuni</th>
                                    <th class="py-2 px-2 md:px-3 lg:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap hidden md:table-cell">Apartemen & Unit</th>
                                    <th class="py-2 px-2 md:px-3 lg:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap hidden lg:table-cell">Periode</th>
                                    <th class="py-2 px-2 md:px-3 lg:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Status</th>
                                    <th class="py-2 px-2 md:px-3 lg:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php $__currentLoopData = $penghuni; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $assign = $p->assign;
                                    $canCheckout = $assign && $assign->status == 'AKTIF';
                                ?>
                                <tr class="hover:bg-gray-50">
                                    
                                    <td class="py-3 px-2 md:px-3 lg:px-4">
                                        <div class="min-w-0">
                                            <div class="font-medium text-gray-900 text-sm truncate"><?php echo e($p->nama); ?></div>
                                            <div class="text-xs text-gray-500 truncate max-w-[100px] md:max-w-none"><?php echo e($p->id_karyawan); ?></div>
                                            <div class="text-xs text-gray-400 truncate max-w-[80px] md:max-w-none"><?php echo e($p->unit_kerja ?? '-'); ?></div>
                                        </div>
                                    </td>

                                    
                                    <td class="py-3 px-2 md:px-3 lg:px-4 md:hidden">
                                        <?php if($assign && $assign->unit && $assign->unit->apartemen): ?>
                                        <div class="text-xs">
                                            <div class="font-medium text-gray-900 truncate max-w-[100px]"><?php echo e($assign->unit->apartemen->nama_apartemen); ?></div>
                                            <div class="text-gray-500">Unit <?php echo e($assign->unit->nomor_unit); ?></div>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-gray-400 text-xs">-</span>
                                        <?php endif; ?>
                                    </td>

                                    
                                    <td class="py-3 px-2 md:px-3 lg:px-4 hidden md:table-cell">
                                        <?php if($assign && $assign->unit && $assign->unit->apartemen): ?>
                                        <div class="font-medium text-gray-900 text-sm truncate"><?php echo e($assign->unit->apartemen->nama_apartemen); ?></div>
                                        <div class="text-xs text-gray-500 truncate">Unit <?php echo e($assign->unit->nomor_unit); ?></div>
                                        <?php else: ?>
                                        <span class="text-gray-400 text-sm">-</span>
                                        <?php endif; ?>
                                    </td>

                                    
                                    <td class="py-3 px-2 md:px-3 lg:px-4 lg:hidden">
                                        <?php if($assign): ?>
                                        <div class="text-xs">
                                            <div class="text-gray-900"><?php echo e($assign->tanggal_mulai->format('d/m')); ?></div>
                                            <div class="text-gray-500">s/d <?php echo e($assign->tanggal_selesai->format('d/m')); ?></div>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-gray-400 text-xs">-</span>
                                        <?php endif; ?>
                                    </td>

                                    
                                    <td class="py-3 px-2 md:px-3 lg:px-4 hidden lg:table-cell">
                                        <?php if($assign): ?>
                                        <div class="text-sm text-gray-900 whitespace-nowrap"><?php echo e($assign->tanggal_mulai->format('d/m/Y')); ?></div>
                                        <div class="text-xs text-gray-500 whitespace-nowrap">s/d</div>
                                        <div class="text-sm text-gray-900 whitespace-nowrap"><?php echo e($assign->tanggal_selesai->format('d/m/Y')); ?></div>
                                        <?php else: ?>
                                        <span class="text-gray-400 text-sm">-</span>
                                        <?php endif; ?>
                                    </td>

                                    
                                    <td class="py-3 px-2 md:px-3 lg:px-4">
                                        <?php if($assign): ?>
                                            <?php if($assign->tanggal_selesai->lt(now())): ?>
                                            <span class="inline-flex items-center px-1.5 md:px-2 py-0.5 md:py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 whitespace-nowrap">
                                                Telah Selesai
                                            </span>
                                            <?php elseif($assign->tanggal_selesai->diffInDays(now()) <= 3): ?>
                                            <span class="inline-flex items-center px-1.5 md:px-2 py-0.5 md:py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 whitespace-nowrap">
                                                Akan Selesai
                                            </span>
                                            <?php else: ?>
                                            <span class="inline-flex items-center px-1.5 md:px-2 py-0.5 md:py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 whitespace-nowrap">
                                                Aktif
                                            </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                        <span class="text-gray-400 text-xs">-</span>
                                        <?php endif; ?>
                                    </td>

                                    
                                    <td class="py-3 px-2 md:px-3 lg:px-4">
                                        <?php if($canCheckout): ?>
                                        <button type="button" 
                                                onclick="confirmCheckout(<?php echo e($p->id); ?>, '<?php echo e(addslashes($p->nama)); ?>'); return false;"
                                                class="text-red-600 hover:text-red-800 text-xs md:text-sm font-medium whitespace-nowrap checkout-btn"
                                                data-id="<?php echo e($p->id); ?>">
                                            Check-out
                                        </button>
                                        <?php else: ?>
                                        <span class="text-gray-400 text-xs">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            
            <div class="flex flex-col sm:flex-row items-center justify-between px-2 md:px-3 lg:px-4 py-3 border-t border-gray-200 gap-2 md:gap-3">
                <div class="text-xs md:text-sm text-gray-700 text-center sm:text-left">
                    <span class="font-medium"><?php echo e($penghuni->firstItem()); ?></span> - 
                    <span class="font-medium"><?php echo e($penghuni->lastItem()); ?></span> dari 
                    <span class="font-medium"><?php echo e($penghuni->total()); ?></span>
                </div>
                <div class="flex space-x-1 md:space-x-2">
                    <?php if($penghuni->previousPageUrl()): ?>
                        <a href="<?php echo e($penghuni->previousPageUrl()); ?>" 
                           class="px-2 md:px-3 py-1.5 md:py-2 border border-gray-300 rounded-md text-xs md:text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors whitespace-nowrap">
                            ← Prev
                        </a>
                    <?php endif; ?>

                    <?php if($penghuni->nextPageUrl()): ?>
                        <a href="<?php echo e($penghuni->nextPageUrl()); ?>" 
                           class="px-2 md:px-3 py-1.5 md:py-2 border border-gray-300 rounded-md text-xs md:text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors whitespace-nowrap">
                            Next →
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            
            <div class="text-center py-8 md:py-12">
                <div class="mx-auto w-12 h-12 md:w-16 md:h-16 lg:w-24 lg:h-24 bg-gray-100 rounded-full flex items-center justify-center mb-3 md:mb-4 lg:mb-6">
                    <svg class="w-6 h-6 md:w-8 md:h-8 lg:w-12 lg:h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-10A2.5 2.5 0 1121 10.5 2.5 2.5 0 0118.5 8z" />
                    </svg>
                </div>
                <h3 class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">Belum ada penghuni aktif</h3>
                <p class="text-gray-500 max-w-xs md:max-w-md mx-auto text-xs md:text-sm">
                    <?php if(request()->filled('search') || request()->filled('apartemen_id') || request()->filled('status')): ?>
                    Tidak ditemukan penghuni yang sesuai dengan filter.
                    <?php else: ?>
                    Tidak ada penghuni yang sedang aktif menempati unit.
                    <?php endif; ?>
                </p>
                <?php if(request()->filled('search') || request()->filled('apartemen_id') || request()->filled('status')): ?>
                <a href="<?php echo e(route('apartemen.admin.monitoring')); ?>" 
                   class="mt-3 md:mt-4 inline-flex items-center px-3 md:px-4 py-1.5 md:py-2 border border-transparent text-xs md:text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Reset Filter
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>


<form id="checkoutForm" method="POST" style="display: none;">
    <?php echo csrf_field(); ?>
</form>

<style>
/* Smooth transitions */
* {
    transition-property: background-color, border-color, color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

/* Better table styling */
table tbody tr {
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

/* Responsive adjustments */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .filter-grid {
        grid-template-columns: 1fr;
    }
}

/* Ensure buttons are touch-friendly on mobile */
@media (max-width: 640px) {
    button, a.button-like {
        min-height: 36px;
    }
    
    .touch-target {
        padding: 0.5rem;
    }
}

/* Better truncation */
.truncate-2-lines {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Confirmation dialog styling */
.checkout-btn {
    transition: color 0.2s ease;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    font-family: inherit;
    font-size: inherit;
}

.checkout-btn:hover {
    color: #dc2626; /* red-800 */
}

.checkout-btn:disabled {
    color: #9ca3af; /* gray-400 */
    cursor: not-allowed;
}

/* Loading state */
.checkout-btn.loading {
    position: relative;
    color: transparent !important;
}

.checkout-btn.loading::after {
    content: '';
    position: absolute;
    left: 50%;
    top: 50%;
    width: 12px;
    height: 12px;
    margin-left: -6px;
    margin-top: -6px;
    border: 2px solid #dc2626;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<script>
let checkoutInProgress = false;

function confirmCheckout(id, name) {
    console.log('=== 🚀 CHECKOUT START ===');
    console.log('Penghuni ID:', id, 'Name:', name);
    
    if (checkoutInProgress) {
        console.log('⚠️ Checkout sedang berjalan, mohon tunggu...');
        alert('Checkout sedang diproses, mohon tunggu...');
        return false;
    }
    
    if (!confirm(`Yakin melakukan check-out untuk ${name}?`)) {
        console.log('❌ User membatalkan checkout');
        return false;
    }
    
    console.log('✅ User mengkonfirmasi checkout');
    checkoutInProgress = true;
    
    const button = document.querySelector(`.checkout-btn[data-id="${id}"]`);
    if (button) {
        button.disabled = true;
        button.classList.add('loading');
        button.textContent = 'Memproses...';
    }
    
    // Buat form dinamis (tanpa <?php echo method_field('PUT'); ?> karena route hanya menerima POST)
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/apartemen/admin/penghuni/${id}/checkout`;
    form.style.display = 'none';
    
    // CSRF token
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = document.querySelector('meta[name="csrf-token"]')?.content || '<?php echo e(csrf_token()); ?>';
    form.appendChild(csrfToken);
    
    // Tambahkan ke body
    document.body.appendChild(form);
    
    console.log('📤 Submitting form to:', form.action);
    
    try {
        form.submit();
        console.log('✅ Form submitted successfully');
    } catch (error) {
        console.error('❌ Form submission error:', error);
        alert('Terjadi kesalahan: ' + error.message);
        resetCheckout(button);
    }
    
    return false;
}

function resetCheckout(button) {
    if (button) {
        button.disabled = false;
        button.classList.remove('loading');
        button.textContent = 'Check-out';
    }
    checkoutInProgress = false;
}

// Debug info
document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 DOM loaded, checkout system ready');
    console.log('Found checkout buttons:', document.querySelectorAll('.checkout-btn').length);
});

// Auto-reset setelah 10 detik
setTimeout(() => {
    if (checkoutInProgress) {
        console.log('🕐 Auto-resetting checkout progress');
        checkoutInProgress = false;
        
        document.querySelectorAll('.checkout-btn.loading').forEach(button => {
            button.disabled = false;
            button.classList.remove('loading');
            button.textContent = 'Check-out';
        });
    }
}, 10000);
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/apartemen/admin/monitoring.blade.php ENDPATH**/ ?>