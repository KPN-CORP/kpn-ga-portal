<?php $__env->startSection('content'); ?>
<div class="p-4 md:p-6">

    
    <div class="mb-6 md:mb-8">
        
        <div class="lg:hidden mb-4">
            <h1 class="text-xl font-bold text-gray-800">Riwayat Permintaan</h1>
            <p class="text-gray-600 text-xs mt-1">Lihat riwayat semua permintaan yang telah diproses</p>
        </div>

        
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 mb-4 md:mb-6">
            
            <div class="hidden lg:flex items-center space-x-4 flex-1">
                
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Riwayat Permintaan</h1>
                    <p class="text-gray-600 text-sm mt-1">Lihat riwayat semua permintaan yang telah diproses</p>
                </div>
            </div>

            
            <div class="w-full lg:w-auto lg:mx-4 lg:flex-1 lg:max-w-md order-first lg:order-none">
                <div class="relative">
                    <form action="<?php echo e(route('apartemen.admin.history')); ?>" method="GET">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="<?php echo e(request('search')); ?>" 
                               class="pl-10 pr-4 py-2 md:py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full" 
                               placeholder="Cari nama/no HP penghuni...">
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
                class="inline-flex items-center px-3 py-2 bg-blue-50 border border-blue-200 rounded-lg transition-colors flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium text-blue-700 text-sm truncate">Riwayat</span>
                    <?php if($histories->total() > 0): ?>
                    <span class="ml-1 md:ml-2 bg-blue-100 text-blue-800 text-xs px-1.5 md:px-2 py-0.5 rounded-full whitespace-nowrap"><?php echo e($histories->total()); ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>

        
        <div class="bg-white border border-gray-200 rounded-lg p-3 md:p-4 mb-4 md:mb-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="w-full">
                    <form action="<?php echo e(route('apartemen.admin.history')); ?>" method="GET" class="space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 w-full">
                            <div class="md:col-span-2">
                                <div class="flex items-center gap-2">
                                    <input type="date" name="tanggal_mulai" value="<?php echo e(request('tanggal_mulai')); ?>" 
                                           class="border border-gray-300 rounded-lg px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-full">
                                    <input type="date" name="tanggal_selesai" value="<?php echo e(request('tanggal_selesai')); ?>"
                                           class="border border-gray-300 rounded-lg px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-full">
                                </div>
                            </div>
                            
                            <div>
                                <select name="status_selesai" class="border border-gray-300 rounded-lg px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-full" onchange="this.form.submit()">
                                    <option value="">Semua Status</option>
                                    <option value="SELESAI" <?php echo e(request('status_selesai') == 'SELESAI' ? 'selected' : ''); ?>>Selesai</option>
                                    <option value="DIPINDAH" <?php echo e(request('status_selesai') == 'DIPINDAH' ? 'selected' : ''); ?>>Dipindah</option>
                                    <option value="DIBATALKAN" <?php echo e(request('status_selesai') == 'DIBATALKAN' ? 'selected' : ''); ?>>Dibatalkan</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
                            <div class="flex items-center gap-2 w-full sm:w-auto">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 md:px-4 py-1.5 md:py-2 rounded-lg text-xs md:text-sm font-medium whitespace-nowrap w-full sm:w-auto">
                                    Terapkan Filter
                                </button>
                                
                                <?php if(request()->anyFilled(['tanggal_mulai', 'tanggal_selesai', 'status_selesai', 'search'])): ?>
                                <a href="<?php echo e(route('apartemen.admin.history')); ?>" class="text-gray-600 hover:text-gray-800 text-xs md:text-sm font-medium whitespace-nowrap w-full sm:w-auto text-center">
                                    Reset Filter
                                </a>
                                <?php endif; ?>
                            </div>
                            
                            
                            <?php if(request()->anyFilled(['tanggal_mulai', 'status_selesai'])): ?>
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-1 pt-2 sm:pt-0 border-t sm:border-t-0 border-gray-100 sm:border-none w-full">
                                <span class="text-xs text-gray-500">Filter aktif:</span>
                                <div class="flex flex-wrap gap-1">
                                    <?php if(request('status_selesai')): ?>
                                    <span class="inline-flex items-center px-1.5 md:px-2 py-0.5 md:py-1 rounded-full text-xs font-medium 
                                        <?php echo e(request('status_selesai') == 'SELESAI' ? 'bg-green-100 text-green-800' : 
                                           (request('status_selesai') == 'DIPINDAH' ? 'bg-blue-100 text-blue-800' : 
                                           'bg-red-100 text-red-800')); ?> whitespace-nowrap">
                                        <?php echo e(request('status_selesai') == 'SELESAI' ? 'Selesai' : 
                                         (request('status_selesai') == 'DIPINDAH' ? 'Dipindah' : 
                                         'Dibatalkan')); ?>

                                    </span>
                                    <?php endif; ?>
                                    <?php if(request('tanggal_mulai') && request('tanggal_selesai')): ?>
                                    <span class="inline-flex items-center px-1.5 md:px-2 py-0.5 md:py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 whitespace-nowrap">
                                        <?php echo e(\Carbon\Carbon::parse(request('tanggal_mulai'))->format('d/m')); ?> - <?php echo e(\Carbon\Carbon::parse(request('tanggal_selesai'))->format('d/m/Y')); ?>

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

    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-4 md:mb-6">
        <?php
            $totalHistory = \App\Models\Apartemen\ApartemenHistory::count();
            $completed = \App\Models\Apartemen\ApartemenHistory::where('status_selesai', 'SELESAI')->count();
            $transferred = \App\Models\Apartemen\ApartemenHistory::where('status_selesai', 'DIPINDAH')->count();
            $cancelled = \App\Models\Apartemen\ApartemenHistory::where('status_selesai', 'DIBATALKAN')->count();
        ?>
        
        <div class="bg-white rounded-lg border border-gray-200 p-3 md:p-4">
            <div class="flex items-center">
                <div class="p-1.5 md:p-2 rounded-lg bg-gray-100 mr-2 md:mr-3">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs md:text-sm text-gray-500 truncate">Total Riwayat</p>
                    <p class="text-lg md:text-xl font-bold text-gray-900 truncate"><?php echo e($totalHistory); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg border border-gray-200 p-3 md:p-4">
            <div class="flex items-center">
                <div class="p-1.5 md:p-2 rounded-lg bg-green-100 mr-2 md:mr-3">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs md:text-sm text-gray-500 truncate">Selesai</p>
                    <p class="text-lg md:text-xl font-bold text-gray-900 truncate"><?php echo e($completed); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg border border-gray-200 p-3 md:p-4">
            <div class="flex items-center">
                <div class="p-1.5 md:p-2 rounded-lg bg-blue-100 mr-2 md:mr-3">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs md:text-sm text-gray-500 truncate">Dipindah</p>
                    <p class="text-lg md:text-xl font-bold text-gray-900 truncate"><?php echo e($transferred); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg border border-gray-200 p-3 md:p-4">
            <div class="flex items-center">
                <div class="p-1.5 md:p-2 rounded-lg bg-red-100 mr-2 md:mr-3">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs md:text-sm text-gray-500 truncate">Dibatalkan</p>
                    <p class="text-lg md:text-xl font-bold text-gray-900 truncate"><?php echo e($cancelled); ?></p>
                </div>
            </div>
        </div>
    </div>

    
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        
        <div class="px-3 md:px-4 lg:px-6 py-3 md:py-4 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <h3 class="text-base md:text-lg font-semibold text-gray-800">Riwayat Permintaan</h3>
                <div class="text-xs md:text-sm text-gray-500">
                    Total: <span class="font-medium"><?php echo e($histories->total() ?? 0); ?></span> riwayat
                </div>
            </div>
        </div>

        
        <div class="p-3 md:p-4 lg:p-6">
            <?php if($histories->count() > 0): ?>
            <div class="overflow-x-auto -mx-3 md:mx-0">
                <div class="min-w-full inline-block align-middle">
                    <div class="overflow-hidden border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-2 px-2 md:px-3 lg:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Penghuni</th>
                                    <th class="py-2 px-2 md:px-3 lg:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap hidden md:table-cell">Apartemen & Unit</th>
                                    <th class="py-2 px-2 md:px-3 lg:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap hidden lg:table-cell">Kontak</th>
                                    <th class="py-2 px-2 md:px-3 lg:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap hidden lg:table-cell">Periode</th>
                                    <th class="py-2 px-2 md:px-3 lg:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Status</th>
                                    <th class="py-2 px-2 md:px-3 lg:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap hidden lg:table-cell">Tanggal</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php $__currentLoopData = $histories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $history): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    // PERBAIKAN: Ambil data dari relasi penghuni jika ada
                                    // Cari penghuni berdasarkan id_karyawan atau nama
                                    $penghuniData = null;
                                    $no_hp = null;
                                    $gol = null;
                                    
                                    // Coba ambil dari relasi jika ada
                                    if (method_exists($history, 'penghuni') && $history->penghuni) {
                                        $penghuniData = $history->penghuni;
                                    } 
                                    // Atau cari langsung dari model Penghuni
                                    elseif (class_exists('App\\Models\\Apartemen\\ApartemenPenghuni')) {
                                        $penghuniData = \App\Models\Apartemen\ApartemenPenghuni::where('id_karyawan', $history->id_karyawan)
                                            ->orWhere('nama', $history->nama)
                                            ->first();
                                    }
                                    
                                    // Ambil no_hp dan gol jika ditemukan
                                    if ($penghuniData) {
                                        $no_hp = $penghuniData->no_hp ?? null;
                                        $gol = $penghuniData->gol ?? null;
                                    }
                                    
                                    // Format no_hp jika ada
                                    $formattedNoHp = null;
                                    if ($no_hp) {
                                        $formattedNoHp = preg_replace('/[^\d]/', '', $no_hp);
                                        if (strlen($formattedNoHp) >= 9) {
                                            if (substr($formattedNoHp, 0, 2) == '62') {
                                                // Format +62 xxx xxxx xxxx
                                                $formattedNoHp = '+62 ' . substr($formattedNoHp, 2);
                                            } elseif (substr($formattedNoHp, 0, 1) == '0') {
                                                // Format 08xx xxxx xxxx
                                                $formattedNoHp = substr($formattedNoHp, 0, 4) . ' ' . substr($formattedNoHp, 4);
                                            }
                                        }
                                    }
                                ?>
                                <tr class="hover:bg-gray-50">
                                    
                                    <td class="py-3 px-2 md:px-3 lg:px-4">
                                        <div class="min-w-0">
                                            <div class="font-medium text-gray-900 text-sm truncate"><?php echo e($history->nama); ?></div>
                                            <div class="text-xs text-gray-500 truncate max-w-[100px] md:max-w-none"><?php echo e($history->id_karyawan); ?></div>
                                            <div class="text-xs text-gray-400 truncate max-w-[80px] md:max-w-none"><?php echo e($history->unit_kerja ?? '-'); ?></div>
                                            
                                            <div class="md:hidden mt-1">
                                                <?php
                                                    // Ambil langsung dari model
                                                    $no_hp = $history->no_hp ?? null;
                                                    $gol = $history->gol ?? null;
                                                    
                                                    // Format no_hp
                                                    $formattedNoHp = null;
                                                    if ($no_hp && !empty(trim($no_hp))) {
                                                        $cleanNo = preg_replace('/[^\d]/', '', $no_hp);
                                                        if (strlen($cleanNo) >= 10) {
                                                            if (substr($cleanNo, 0, 2) == '62') {
                                                                $formattedNoHp = '+62 ' . substr($cleanNo, 2, 3) . '-' . substr($cleanNo, 5, 4) . '-' . substr($cleanNo, 9);
                                                            } elseif (substr($cleanNo, 0, 1) == '0') {
                                                                $formattedNoHp = substr($cleanNo, 0, 4) . '-' . substr($cleanNo, 4, 4) . '-' . substr($cleanNo, 8);
                                                            } else {
                                                                $formattedNoHp = $no_hp;
                                                            }
                                                        } else {
                                                            $formattedNoHp = $no_hp;
                                                        }
                                                    }
                                                ?>
                                                
                                                <?php if($formattedNoHp): ?>
                                                <div class="text-xs text-blue-600 flex items-center gap-1">
                                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                                    </svg>
                                                    <span class="truncate"><?php echo e($formattedNoHp); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <?php if($gol): ?>
                                                <div class="text-xs text-gray-600 mt-0.5">Gol. <?php echo e($gol); ?></div>
                                                <?php endif; ?>
                                            </div>
                                    </td>

                                    
                                    <td class="py-3 px-2 md:px-3 lg:px-4 md:hidden">
                                        <div class="text-xs">
                                            <div class="font-medium text-gray-900 truncate max-w-[100px]"><?php echo e($history->apartemen); ?></div>
                                            <div class="text-gray-500">Unit <?php echo e($history->unit); ?></div>
                                            
                                            <div class="mt-1 text-gray-600 truncate max-w-[80px]"><?php echo e($history->periode); ?></div>
                                        </div>
                                    </td>

                                    
                                    <td class="py-3 px-2 md:px-3 lg:px-4 hidden md:table-cell">
                                        <div class="font-medium text-gray-900 text-sm truncate"><?php echo e($history->apartemen); ?></div>
                                        <div class="text-xs text-gray-500 truncate">Unit <?php echo e($history->unit); ?></div>
                                    </td>

                                    
                                    <td class="py-3 px-2 md:px-3 lg:px-4 hidden lg:table-cell">
                                        <div class="min-w-0">
                                            <?php
                                                // Ambil langsung dari model karena tabel sudah punya kolom no_hp
                                                $no_hp = $history->no_hp ?? null;
                                                $gol = $history->gol ?? null;
                                                
                                                // Format no_hp jika ada
                                                $formattedNoHp = null;
                                                if ($no_hp && !empty(trim($no_hp))) {
                                                    // Hapus semua karakter non-digit
                                                    $cleanNo = preg_replace('/[^\d]/', '', $no_hp);
                                                    
                                                    // Format berdasarkan panjang
                                                    if (strlen($cleanNo) >= 10) {
                                                        if (substr($cleanNo, 0, 2) == '62') {
                                                            // Format: +62 812-3456-7890
                                                            $formattedNoHp = '+62 ' . substr($cleanNo, 2, 3) . '-' . substr($cleanNo, 5, 4) . '-' . substr($cleanNo, 9);
                                                        } elseif (substr($cleanNo, 0, 1) == '0') {
                                                            // Format: 0812-3456-7890
                                                            $formattedNoHp = substr($cleanNo, 0, 4) . '-' . substr($cleanNo, 4, 4) . '-' . substr($cleanNo, 8);
                                                        } elseif (strlen($cleanNo) == 10) {
                                                            // Format: 0812345678 -> 0812-3456-78
                                                            $formattedNoHp = substr($cleanNo, 0, 4) . '-' . substr($cleanNo, 4, 4) . '-' . substr($cleanNo, 8);
                                                        } else {
                                                            $formattedNoHp = $no_hp; // Tampilkan as-is jika tidak bisa diformat
                                                        }
                                                    } else {
                                                        $formattedNoHp = $no_hp; // Tampilkan as-is jika terlalu pendek
                                                    }
                                                }
                                            ?>
                                            
                                            <?php if($formattedNoHp): ?>
                                            <div class="text-xs text-blue-600 flex items-center gap-1 mb-1">
                                                <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                                </svg>
                                                <span class="truncate" title="<?php echo e($no_hp); ?>"><?php echo e($formattedNoHp); ?></span>
                                            </div>
                                            <?php else: ?>
                                            <div class="text-xs text-gray-400">-</div>
                                            <?php endif; ?>
                                            
                                            <?php if($gol): ?>
                                            <div class="text-xs text-gray-600">Gol. <?php echo e($gol); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    
                                    <td class="py-3 px-2 md:px-3 lg:px-4 hidden lg:table-cell">
                                        <div class="text-sm text-gray-900 truncate"><?php echo e($history->periode); ?></div>
                                    </td>

                                    
                                    <td class="py-3 px-2 md:px-3 lg:px-4">
                                        <?php switch($history->status_selesai):
                                            case ('SELESAI'): ?>
                                                <span class="inline-flex items-center px-1.5 md:px-2 py-0.5 md:py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 whitespace-nowrap">
                                                    Selesai
                                                </span>
                                                <?php break; ?>
                                            <?php case ('DIPINDAH'): ?>
                                                <span class="inline-flex items-center px-1.5 md:px-2 py-0.5 md:py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 whitespace-nowrap">
                                                    Dipindah
                                                </span>
                                                <?php break; ?>
                                            <?php case ('DIBATALKAN'): ?>
                                                <span class="inline-flex items-center px-1.5 md:px-2 py-0.5 md:py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 whitespace-nowrap">
                                                    Dibatalkan
                                                </span>
                                                <?php break; ?>
                                        <?php endswitch; ?>
                                    </td>

                                    
                                    <td class="py-3 px-2 md:px-3 lg:px-4 md:hidden">
                                        <div class="text-xs">
                                            <div class="text-gray-900"><?php echo e($history->created_at->format('d/m')); ?></div>
                                            <div class="text-gray-500"><?php echo e($history->created_at->format('H:i')); ?></div>
                                        </div>
                                    </td>

                                    
                                    <td class="py-3 px-2 md:px-3 lg:px-4 hidden md:table-cell lg:table-cell">
                                        <div class="text-sm text-gray-900 whitespace-nowrap"><?php echo e($history->created_at->format('d/m/Y')); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo e($history->created_at->format('H:i')); ?></div>
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
                    <span class="font-medium"><?php echo e($histories->firstItem()); ?></span> - 
                    <span class="font-medium"><?php echo e($histories->lastItem()); ?></span> dari 
                    <span class="font-medium"><?php echo e($histories->total()); ?></span>
                </div>
                <div class="flex space-x-1 md:space-x-2">
                    <?php if($histories->previousPageUrl()): ?>
                        <a href="<?php echo e($histories->previousPageUrl()); ?>" 
                           class="px-2 md:px-3 py-1.5 md:py-2 border border-gray-300 rounded-md text-xs md:text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors whitespace-nowrap">
                            ← Prev
                        </a>
                    <?php endif; ?>

                    <?php if($histories->nextPageUrl()): ?>
                        <a href="<?php echo e($histories->nextPageUrl()); ?>" 
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">Belum ada riwayat</h3>
                <p class="text-gray-500 max-w-xs md:max-w-md mx-auto text-xs md:text-sm">
                    <?php if(request()->filled('search') || request()->filled('tanggal_mulai') || request()->filled('status_selesai')): ?>
                    Tidak ditemukan riwayat yang sesuai dengan filter.
                    <?php else: ?>
                    Riwayat akan muncul setelah permintaan selesai diproses.
                    <?php endif; ?>
                </p>
                <?php if(request()->filled('search') || request()->filled('tanggal_mulai') || request()->filled('status_selesai')): ?>
                <a href="<?php echo e(route('apartemen.admin.history')); ?>" 
                   class="mt-3 md:mt-4 inline-flex items-center px-3 md:px-4 py-1.5 md:py-2 border border-transparent text-xs md:text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Reset Filter
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

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
    
    /* Improve mobile table display */
    .overflow-x-auto {
        margin-left: -0.75rem;
        margin-right: -0.75rem;
        padding-left: 0.75rem;
        padding-right: 0.75rem;
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
    
    /* Better truncation for mobile */
    .truncate-mobile {
        max-width: 100px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}

/* Better truncation */
.truncate-2-lines {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Style for phone icon */
.text-blue-600 svg {
    display: inline-block;
    vertical-align: text-top;
}

/* Responsive column adjustments */
@media (max-width: 1024px) {
    .hidden-lg {
        display: none !important;
    }
}

@media (max-width: 768px) {
    .hidden-md {
        display: none !important;
    }
}

@media (max-width: 640px) {
    .hidden-sm {
        display: none !important;
    }
}
</style>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/apartemen/admin/history.blade.php ENDPATH**/ ?>