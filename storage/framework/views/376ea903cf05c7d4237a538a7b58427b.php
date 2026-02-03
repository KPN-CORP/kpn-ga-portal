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
            <h1 class="text-xl font-bold text-gray-800">Manajemen Apartemen</h1>
            <p class="text-gray-600 text-xs mt-1">Kelola data apartemen dan unit tersedia</p>
        </div>

        
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 mb-4 md:mb-6">
            
            <div class="hidden lg:flex items-center space-x-4 flex-1">
                
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Manajemen Apartemen</h1>
                    <p class="text-gray-600 text-sm mt-1">Kelola data apartemen dan unit tersedia</p>
                </div>
            </div>

            
            <div class="w-full lg:w-auto lg:mx-4 lg:flex-1 lg:max-w-md order-first lg:order-none">
                <div class="relative">
                    <form action="<?php echo e(route('apartemen.admin.apartemen')); ?>" method="GET">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="<?php echo e(request('search')); ?>" 
                               class="pl-10 pr-4 py-2 md:py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full" 
                               placeholder="Cari nama apartemen...">
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
                    <?php
                        $historyCount = \App\Models\Apartemen\ApartemenHistory::count();
                    ?>
                    <?php if($historyCount > 0): ?>
                    <span class="ml-1 md:ml-2 bg-blue-100 text-blue-800 text-xs px-1.5 md:px-2 py-0.5 rounded-full whitespace-nowrap"><?php echo e($historyCount); ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>        
    </div>

    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-4 md:mb-6">
        <?php
            $totalUnits = \App\Models\Apartemen\ApartemenUnit::count();
            $availableUnits = \App\Models\Apartemen\ApartemenUnit::where('status', 'READY')->count();
            $occupiedUnits = \App\Models\Apartemen\ApartemenUnit::where('status', 'TERISI')->count();
            $maintenanceUnits = \App\Models\Apartemen\ApartemenUnit::where('status', 'MAINTENANCE')->count();
        ?>
        
        <div class="bg-white rounded-lg border border-gray-200 p-3 md:p-4">
            <div class="flex items-center">
                <div class="p-1.5 md:p-2 rounded-lg bg-blue-100 mr-2 md:mr-3">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs md:text-sm text-gray-500 truncate">Total Apartemen</p>
                    <p class="text-lg md:text-xl font-bold text-gray-900 truncate"><?php echo e($apartemen->count()); ?></p>
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
                    <p class="text-xs md:text-sm text-gray-500 truncate">Unit Tersedia</p>
                    <p class="text-lg md:text-xl font-bold text-gray-900 truncate"><?php echo e($availableUnits); ?></p>
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
                    <p class="text-xs md:text-sm text-gray-500 truncate">Unit Terisi</p>
                    <p class="text-lg md:text-xl font-bold text-gray-900 truncate"><?php echo e($occupiedUnits); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg border border-gray-200 p-3 md:p-4">
            <div class="flex items-center">
                <div class="p-1.5 md:p-2 rounded-lg bg-red-100 mr-2 md:mr-3">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.928-.833-2.698 0L4.392 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs md:text-sm text-gray-500 truncate">Maintenance</p>
                    <p class="text-lg md:text-xl font-bold text-gray-900 truncate"><?php echo e($maintenanceUnits); ?></p>
                </div>
            </div>
        </div>
    </div>

    
    <div class="hidden md:flex justify-end mb-4">
        <button onclick="showAddModal()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium flex items-center whitespace-nowrap transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Tambah Apartemen
        </button>
    </div>

    
    <div class="md:hidden fixed bottom-6 right-6 z-40">
        <button onclick="showAddModal()"
                class="bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-full shadow-lg flex items-center justify-center transition-colors floating-button">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
        </button>
    </div>

    
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        
        <div class="px-3 md:px-4 lg:px-6 py-3 md:py-4 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <h3 class="text-base md:text-lg font-semibold text-gray-800">Daftar Apartemen</h3>
                <div class="text-xs md:text-sm text-gray-500">
                    Total: <span class="font-medium"><?php echo e($apartemen->total() ?? 0); ?></span> apartemen
                </div>
            </div>
        </div>

        
        <div class="p-3 md:p-4 lg:p-6">
            
            <?php if($apartemen->count() > 0): ?>
            <div class="overflow-x-auto -mx-3 md:mx-0">
                <div class="min-w-full inline-block align-middle">
                    <div class="overflow-hidden border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-2 px-2 md:px-3 lg:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Apartemen</th>
                                    <th class="py-2 px-2 md:px-3 lg:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap hidden lg:table-cell">Penanggung Jawab</th>
                                    <th class="py-2 px-2 md:px-3 lg:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Unit</th>
                                    <th class="py-2 px-2 md:px-3 lg:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap hidden md:table-cell">Status</th>
                                    <th class="py-2 px-2 md:px-3 lg:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php $__currentLoopData = $apartemen; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    
                                    <td class="py-3 px-2 md:px-3 lg:px-4">
                                        <div class="min-w-0">
                                            <div class="font-medium text-gray-900 text-sm truncate"><?php echo e($item->nama_apartemen); ?></div>
                                            <div class="text-xs text-gray-500 truncate max-w-[150px] md:max-w-none"><?php echo e(Str::limit($item->alamat, 40)); ?></div>
                                        </div>
                                    </td>

                                    
                                    <td class="py-3 px-2 md:px-3 lg:px-4 lg:hidden">
                                        <div class="text-xs text-gray-500">PJ:</div>
                                        <div class="text-sm text-gray-900 truncate max-w-[100px]"><?php echo e($item->penanggung_jawab); ?></div>
                                    </td>

                                    
                                    <td class="py-3 px-2 md:px-3 lg:px-4 hidden lg:table-cell">
                                        <div class="text-sm text-gray-900 truncate"><?php echo e($item->penanggung_jawab); ?></div>
                                        <div class="text-xs text-gray-500 truncate"><?php echo e($item->kontak_darurat); ?></div>
                                    </td>

                                    
                                    <td class="py-3 px-2 md:px-3 lg:px-4">
                                        <div class="flex items-center">
                                            <div class="text-base md:text-lg font-bold text-blue-600 mr-2 md:mr-3"><?php echo e($item->units_count); ?></div>
                                            <!-- <div class="hidden md:block">
                                                <div class="text-xs text-gray-500">Tersedia: <span class="font-medium text-green-600"><?php echo e($item->units_ready); ?></span></div>
                                                <div class="text-xs text-gray-500">Terisi: <span class="font-medium text-yellow-600"><?php echo e($item->units_terisi); ?></span></div>
                                            </div> -->
                                        </div>
                                    </td>

                                    
                                    <td class="py-3 px-2 md:px-3 lg:px-4 md:hidden">
                                        <div class="text-xs">
                                            <div class="text-green-600 font-medium"><?php echo e($item->units_ready); ?> Tersedia</div>
                                            <div class="text-yellow-600 font-medium"><?php echo e($item->units_terisi); ?> Terisi</div>
                                        </div>
                                    </td>

                                    
                                    <td class="py-3 px-2 md:px-3 lg:px-4 hidden md:table-cell">
                                        <div class="flex items-center space-x-3">
                                            <div class="text-center">
                                                <div class="text-xs text-gray-500">Tersedia</div>
                                                <div class="text-sm font-medium text-green-600"><?php echo e($item->units_ready); ?></div>
                                            </div>
                                            <div class="text-center">
                                                <div class="text-xs text-gray-500">Terisi</div>
                                                <div class="text-sm font-medium text-yellow-600"><?php echo e($item->units_terisi); ?></div>
                                            </div>
                                        </div>
                                    </td>

                                    
                                    <td class="py-3 px-2 md:px-3 lg:px-4">
                                        <div class="flex items-center space-x-2">
                                            <a href="<?php echo e(route('apartemen.admin.apartemen.detail', $item->id)); ?>" 
                                               class="text-blue-600 hover:text-blue-800 text-xs md:text-sm font-medium whitespace-nowrap transition-colors">
                                                Lihat Unit
                                            </a>
                                        </div>
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
                    <span class="font-medium"><?php echo e($apartemen->firstItem()); ?></span> - 
                    <span class="font-medium"><?php echo e($apartemen->lastItem()); ?></span> dari 
                    <span class="font-medium"><?php echo e($apartemen->total()); ?></span>
                </div>
                <div class="flex space-x-1 md:space-x-2">
                    <?php if($apartemen->previousPageUrl()): ?>
                        <a href="<?php echo e($apartemen->previousPageUrl()); ?>" 
                           class="px-2 md:px-3 py-1.5 md:py-2 border border-gray-300 rounded-md text-xs md:text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors whitespace-nowrap">
                            ← Prev
                        </a>
                    <?php endif; ?>

                    <?php if($apartemen->nextPageUrl()): ?>
                        <a href="<?php echo e($apartemen->nextPageUrl()); ?>" 
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <h3 class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">Belum ada data apartemen</h3>
                <p class="text-gray-500 max-w-xs md:max-w-md mx-auto text-xs md:text-sm">
                    <?php if(request()->filled('search') || request()->filled('status_unit') || request()->filled('tipe_unit')): ?>
                    Tidak ditemukan apartemen yang sesuai dengan filter.
                    <?php else: ?>
                    Tambahkan apartemen untuk memulai.
                    <?php endif; ?>
                </p>
                <div class="mt-3 md:mt-4 flex flex-col sm:flex-row gap-2 justify-center">
                    <?php if(request()->filled('search') || request()->filled('status_unit') || request()->filled('tipe_unit')): ?>
                    <a href="<?php echo e(route('apartemen.admin.apartemen')); ?>" 
                       class="inline-flex items-center px-3 md:px-4 py-1.5 md:py-2 border border-transparent text-xs md:text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        Reset Filter
                    </a>
                    <?php endif; ?>
                    <button onclick="showAddModal()"
                            class="inline-flex items-center px-3 md:px-4 py-1.5 md:py-2 border border-blue-600 text-xs md:text-sm font-medium rounded-md shadow-sm text-blue-600 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <svg class="w-3 h-3 md:w-4 md:h-4 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Apartemen
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>


<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 transition-opacity">
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md transform transition-all">
            <div class="p-4 md:p-6">
                <div class="flex justify-between items-center mb-3 md:mb-4">
                    <h3 class="text-base md:text-lg font-semibold text-gray-800">Tambah Apartemen Baru</h3>
                    <button onclick="hideAddModal()" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <form id="addForm" method="POST" action="<?php echo e(route('apartemen.admin.apartemen.store')); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="space-y-3 md:space-y-4">
                        <div>
                            <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1">
                                Nama Apartemen <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="nama_apartemen" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                   placeholder="Nama apartemen"
                                   value="<?php echo e(old('nama_apartemen')); ?>">
                            <?php $__errorArgs = ['nama_apartemen'];
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
                            <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1">
                                Alamat <span class="text-red-500">*</span>
                            </label>
                            <textarea name="alamat" rows="3" required
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                      placeholder="Alamat lengkap apartemen"><?php echo e(old('alamat')); ?></textarea>
                            <?php $__errorArgs = ['alamat'];
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
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1">
                                    Penanggung Jawab
                                </label>
                                <input type="text" name="penanggung_jawab"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                       placeholder="Nama penanggung jawab"
                                       value="<?php echo e(old('penanggung_jawab')); ?>">
                            </div>
                            <div>
                                <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1">
                                    Kontak Darurat
                                </label>
                                <input type="text" name="kontak_darurat"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                       placeholder="Telepon/HP darurat"
                                       value="<?php echo e(old('kontak_darurat')); ?>">
                            </div>
                        </div>

                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1">
                                    Telepon
                                </label>
                                <input type="text" name="telepon"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                       placeholder="Telepon apartemen"
                                       value="<?php echo e(old('telepon')); ?>">
                            </div>
                            <div>
                                <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1">
                                    Email
                                </label>
                                <input type="email" name="email"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                       placeholder="Email apartemen"
                                       value="<?php echo e(old('email')); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-2 md:space-x-3 mt-4 md:mt-6">
                        <button type="button" onclick="hideAddModal()" 
                                class="px-3 md:px-4 py-1.5 md:py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-xs md:text-sm font-medium transition-colors">
                            Batal
                        </button>
                        <button type="submit" id="submitBtn"
                                class="px-3 md:px-4 py-1.5 md:py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-xs md:text-sm font-medium transition-colors">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Modal functions
function showAddModal() {
    const modal = document.getElementById('addModal');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.add('opacity-100');
        const firstInput = document.querySelector('#addForm input');
        if (firstInput) firstInput.focus();
    }, 10);
}

function hideAddModal() {
    const modal = document.getElementById('addModal');
    modal.classList.remove('opacity-100');
    setTimeout(() => {
        modal.classList.add('hidden');
        document.getElementById('addForm').reset();
        resetSubmitButton();
    }, 200);
}

function resetSubmitButton() {
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        submitBtn.innerHTML = 'Simpan';
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}

// Close modal on outside click
document.getElementById('addModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideAddModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('addModal').classList.contains('hidden')) {
        hideAddModal();
    }
});

// Form submission dengan validation
document.getElementById('addForm').addEventListener('submit', function(e) {
    const form = this;
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // Validasi client-side
    const namaApartemen = form.querySelector('input[name="nama_apartemen"]').value.trim();
    const alamat = form.querySelector('textarea[name="alamat"]').value.trim();
    
    if (!namaApartemen) {
        e.preventDefault();
        alert('Nama apartemen harus diisi');
        form.querySelector('input[name="nama_apartemen"]').focus();
        return false;
    }
    
    if (!alamat) {
        e.preventDefault();
        alert('Alamat harus diisi');
        form.querySelector('textarea[name="alamat"]').focus();
        return false;
    }
    
    // Show loading state
    submitBtn.innerHTML = 'Menyimpan...';
    submitBtn.disabled = true;
    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
    
    // Form akan disubmit secara normal
    return true;
});

// Add floating animation to mobile button
document.addEventListener('DOMContentLoaded', function() {
    const floatingBtn = document.querySelector('.md\\:hidden button');
    if (floatingBtn) {
        // Reset animation class
        floatingBtn.classList.remove('floating-button');
        
        // Trigger reflow
        void floatingBtn.offsetWidth;
        
        // Add animation class
        floatingBtn.classList.add('floating-button');
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
#addModal {
    opacity: 0;
    transition: opacity 0.2s ease;
}

#addModal.opacity-100 {
    opacity: 1;
}

#addModal .transform {
    transform: scale(0.95);
    transition: transform 0.2s ease;
}

#addModal.opacity-100 .transform {
    transform: scale(1);
}

/* Hover effects */
.hover-lift {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.hover-lift:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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

/* Floating button animation */
@keyframes float {
    0% { 
        transform: translateY(0px); 
        box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
    }
    50% { 
        transform: translateY(-5px); 
        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4);
    }
    100% { 
        transform: translateY(0px); 
        box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
    }
}

.floating-button {
    animation: float 3s ease-in-out infinite;
}
</style>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/apartemen/admin/apartemen.blade.php ENDPATH**/ ?>