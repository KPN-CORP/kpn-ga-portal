<?php $__env->startSection('content'); ?>
<?php
    // DEBUG: Lihat apa yang ada di $request->assign
    // dd($request->assign);
    
    // Penanganan yang aman untuk assignments
    $assignments = collect();
    
    // Cek jika $request->assign adalah Eloquent Collection
    if ($request->assign instanceof \Illuminate\Database\Eloquent\Collection) {
        $assignments = $request->assign;
    }
    // Cek jika $request->assign adalah array
    elseif (is_array($request->assign)) {
        $assignments = collect($request->assign);
    }
    // Cek jika ada relasi dengan nama lain (misalnya 'assigns')
    elseif ($request->relationLoaded('assigns') && $request->assigns instanceof \Illuminate\Database\Eloquent\Collection) {
        $assignments = $request->assigns;
    }
    // Coba ambil dari query langsung jika perlu
    else {
        // Query langsung ke database untuk assignments berdasarkan request_id
        $assignments = \App\Models\Apartemen\ApartemenAssign::where('request_id', $request->id)
            ->with([
                'unit.apartemen', 
                'penghuni' => function($query) {
                    // Pastikan field no_hp diambil
                    $query->select([
                        'id', 
                        'assign_id', 
                        'nama', 
                        'id_karyawan', 
                        'no_hp',  // INI YANG PENTING!
                        'unit_kerja', 
                        'gol', 
                        'tanggal_mulai', 
                        'tanggal_selesai',
                        'status'
                    ]);
                }
            ])
            ->get();
    }
    
    // Pastikan selalu collection
    if (!($assignments instanceof \Illuminate\Support\Collection)) {
        $assignments = collect();
    }
    
    $totalUnits = $assignments->count();
    $hasAssignments = $totalUnits > 0;
    
    // Hitung total penghuni yang sudah ditempatkan di semua unit
    $totalPenghuniTempat = 0;
    foreach($assignments as $assign) {
        if ($assign->relationLoaded('penghuni') && $assign->penghuni instanceof \Illuminate\Database\Eloquent\Collection) {
            $totalPenghuniTempat += $assign->penghuni->count();
        }
    }
    
    // Tentukan progress berdasarkan status
    if($request->status == 'PENDING') {
        $progress = 33;
        $progressText = 'Dalam Review';
        $progressColor = 'bg-yellow-500';
    } elseif($request->status == 'APPROVED' && $hasAssignments) {
        $progress = 100;
        $progressText = 'Selesai';
        $progressColor = 'bg-green-500';
    } elseif($request->status == 'APPROVED' && !$hasAssignments) {
        $progress = 66;
        $progressText = 'Disetujui (Menunggu Penempatan)';
        $progressColor = 'bg-green-500';
    } else {
        $progress = 66;
        $progressText = 'Ditolak';
        $progressColor = 'bg-red-500';
    }
    
    // Untuk debug - cek apakah field no_hp ada di penghuni yang ditempatkan
    $debugInfo = [];
    foreach($assignments as $assignIndex => $assign) {
        foreach($assign->penghuni as $penghuniIndex => $penghuni) {
            $debugInfo[] = [
                'assignment' => $assignIndex + 1,
                'penghuni' => $penghuni->nama ?? 'N/A',
                'no_hp_exists' => isset($penghuni->no_hp),
                'no_hp_value' => $penghuni->no_hp ?? 'NULL',
                'no_hp_field_exists' => property_exists($penghuni, 'no_hp')
            ];
        }
    }
?>




<div class="bg-white shadow rounded-lg p-4 md:p-6">

    
    <div class="mb-6 md:mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 md:gap-3 mb-2">
                    <a href="<?php echo e(route('apartemen.user.requests')); ?>"
                       class="p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                        <svg class="w-4 h-4 md:w-5 md:h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div class="min-w-0">
                        <h1 class="text-lg md:text-2xl font-bold text-gray-900 tracking-tight truncate">Detail Pengajuan</h1>
                        <div class="flex flex-wrap items-center gap-1 md:gap-2 mt-1">
                            <span class="text-xs md:text-sm text-gray-500">ID:</span>
                            <span class="text-xs md:text-sm font-medium text-gray-700 bg-gray-100 px-2 py-1 rounded">#<?php echo e(str_pad($request->id, 6, '0', STR_PAD_LEFT)); ?></span>
                            <span class="text-xs text-gray-400 hidden md:inline">•</span>
                            <span class="text-xs md:text-sm text-gray-500 truncate"><?php echo e($request->created_at->diffForHumans()); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            
            <a href="<?php echo e(route('apartemen.user.requests')); ?>" 
               class="px-3 md:px-4 py-2 text-xs md:text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 flex items-center gap-2 w-full sm:w-auto justify-center">
                <svg class="w-4 h-4 md:w-5 md:h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Daftar
            </a>
        </div>
    </div>

    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
        
        
        <div class="lg:col-span-2 space-y-4 md:space-y-6">
            
            
            <div class="bg-white rounded-lg md:rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-300">
                <div class="p-4 md:p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 md:mb-6">
                        <div class="min-w-0">
                            <h2 class="text-base md:text-lg font-semibold text-gray-900 truncate">Status Permintaan</h2>
                            <p class="text-xs md:text-sm text-gray-500 mt-1">Lacak progress pengajuan Anda</p>
                        </div>
                        <?php switch($request->status):
                            case ('PENDING'): ?>
                                <div class="flex items-center gap-2 px-3 md:px-4 py-1.5 md:py-2 bg-yellow-50 border border-yellow-100 rounded-full whitespace-nowrap">
                                    <div class="w-2 h-2 bg-yellow-500 rounded-full animate-pulse"></div>
                                    <span class="text-xs md:text-sm font-medium text-yellow-700">Menunggu Approval</span>
                                </div>
                                <?php break; ?>
                            <?php case ('APPROVED'): ?>
                                <div class="flex items-center gap-2 px-3 md:px-4 py-1.5 md:py-2 bg-green-50 border border-green-100 rounded-full whitespace-nowrap">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <span class="text-xs md:text-sm font-medium text-green-700">Disetujui</span>
                                </div>
                                <?php break; ?>
                            <?php case ('REJECTED'): ?>
                                <div class="flex items-center gap-2 px-3 md:px-4 py-1.5 md:py-2 bg-red-50 border border-red-100 rounded-full whitespace-nowrap">
                                    <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                    <span class="text-xs md:text-sm font-medium text-red-700">Ditolak</span>
                                </div>
                                <?php break; ?>
                        <?php endswitch; ?>
                    </div>

                    
                    <div class="relative pt-2">
                        <div class="flex mb-2 items-center justify-between">
                            <div>
                                <span class="text-xs font-semibold inline-block text-blue-600">
                                    <?php echo e($progressText); ?>

                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-semibold inline-block text-blue-600">
                                    <?php echo e($progress); ?>%
                                </span>
                            </div>
                        </div>
                        <div class="overflow-hidden h-2 mb-4 text-xs flex rounded-full bg-gray-100">
                            <div style="width:<?php echo e($progress); ?>%" 
                                 class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center 
                                        <?php echo e($progressColor); ?>

                                        transition-all duration-500 ease-out"></div>
                        </div>
                        
                        
                        <div class="flex justify-between text-xs text-gray-500 px-1">
                            <div class="text-center min-w-0">
                                <div class="w-5 h-5 md:w-6 md:h-6 mx-auto rounded-full flex items-center justify-center mb-1 text-xs md:text-sm
                                    <?php echo e($progress >= 0 ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-400'); ?>">
                                    1
                                </div>
                                <span class="truncate text-xs">Diajukan</span>
                            </div>
                            <div class="text-center min-w-0">
                                <div class="w-5 h-5 md:w-6 md:h-6 mx-auto rounded-full flex items-center justify-center mb-1 text-xs md:text-sm
                                    <?php echo e($progress >= 33 ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-400'); ?>">
                                    2
                                </div>
                                <span class="truncate text-xs">Review</span>
                            </div>
                            <div class="text-center min-w-0">
                                <div class="w-5 h-5 md:w-6 md:h-6 mx-auto rounded-full flex items-center justify-center mb-1 text-xs md:text-sm
                                    <?php echo e($progress >= 66 ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-400'); ?>">
                                    3
                                </div>
                                <span class="truncate text-xs">
                                    <?php if($request->status == 'REJECTED'): ?>
                                        Ditolak
                                    <?php elseif($request->status == 'APPROVED' && $hasAssignments): ?>
                                        Penempatan Selesai
                                    <?php else: ?>
                                        Disetujui
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="bg-white rounded-lg md:rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-300">
                <div class="p-4 md:p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 md:mb-6">
                        <div class="min-w-0">
                            <h2 class="text-base md:text-lg font-semibold text-gray-900 truncate">Penempatan Unit</h2>
                            <p class="text-xs md:text-sm text-gray-500 mt-1">
                                <?php if($hasAssignments): ?>
                                    <?php echo e($totalPenghuniTempat); ?> penghuni ditempatkan di <?php echo e($totalUnits); ?> unit
                                <?php else: ?>
                                    Belum ada penempatan unit
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php if($hasAssignments): ?>
                        <span class="text-xs md:text-sm font-medium text-gray-700 bg-gray-100 px-2 md:px-3 py-1 rounded-full whitespace-nowrap">
                            <?php echo e($totalUnits); ?> unit
                        </span>
                        <?php endif; ?>
                    </div>

                    
                    <?php if($hasAssignments): ?>
                        <div class="space-y-4 md:space-y-6">
                            <?php $__currentLoopData = $assignments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assign): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $unit = $assign->unit ?? null;
                                    $apartemen = $unit->apartemen ?? null;
                                    $penghuniDiUnit = $assign->penghuni ?? collect();
                                ?>
                                
                                <div class="border border-gray-200 rounded-lg md:rounded-xl overflow-hidden">
                                    
                                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-3 md:p-4 border-b border-blue-100">
                                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                    </svg>
                                                    <div class="min-w-0">
                                                        <h3 class="font-semibold text-gray-900 truncate">
                                                            <?php if($apartemen && $apartemen->nama_apartemen): ?>
                                                                <?php echo e($apartemen->nama_apartemen); ?>

                                                            <?php else: ?>
                                                                Apartemen
                                                            <?php endif; ?>
                                                        </h3>
                                                        <p class="text-xs md:text-sm text-gray-600 mt-1">
                                                            Unit <?php echo e($unit->nomor_unit ?? 'N/A'); ?>

                                                            <?php if($unit && $unit->kapasitas): ?>
                                                                • Kapasitas: <?php echo e($unit->kapasitas); ?> orang
                                                            <?php endif; ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <?php if(isset($assign->status)): ?>
                                                <span class="px-2 md:px-3 py-1 text-xs font-medium rounded-full whitespace-nowrap
                                                    <?php echo e($assign->status == 'AKTIF' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'); ?>">
                                                    <?php echo e($assign->status == 'AKTIF' ? 'Aktif' : 'Selesai'); ?>

                                                </span>
                                                <?php endif; ?>
                                                <span class="text-xs md:text-sm text-gray-600 bg-white px-2 py-1 rounded-full">
                                                    <?php echo e($penghuniDiUnit->count()); ?> orang
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    
                                    <?php if(isset($assign->tanggal_mulai) && isset($assign->tanggal_selesai)): ?>
                                    <div class="bg-gray-50 p-3 md:p-4 border-b border-gray-100">
                                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2">
                                            <div class="text-xs md:text-sm">
                                                <div class="text-gray-600 mb-1">Periode Tinggal</div>
                                                <div class="flex items-center gap-1 md:gap-2">
                                                    <span class="font-medium text-gray-900"><?php echo e(\Carbon\Carbon::parse($assign->tanggal_mulai)->format('d M Y')); ?></span>
                                                    <svg class="w-3 h-3 md:w-4 md:h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                                    </svg>
                                                    <span class="font-medium text-gray-900"><?php echo e(\Carbon\Carbon::parse($assign->tanggal_selesai)->format('d M Y')); ?></span>
                                                </div>
                                            </div>
                                            <div class="text-right mt-2 md:mt-0">
                                                <div class="text-xs md:text-sm text-gray-600 mb-1">Durasi</div>
                                                <div class="text-base md:text-lg font-bold text-blue-600 whitespace-nowrap">
                                                    <?php echo e(\Carbon\Carbon::parse($assign->tanggal_mulai)->diffInDays($assign->tanggal_selesai)); ?> hari
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    
                                    <div class="p-3 md:p-4">
                                        <h4 class="text-sm md:text-base font-medium text-gray-900 mb-3 md:mb-4">Penghuni di Unit Ini:</h4>
                                        <div class="space-y-2 md:space-y-3">
                                            <?php if($penghuniDiUnit->count() > 0): ?>
                                                <?php $__currentLoopData = $penghuniDiUnit; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $penghuni): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <?php if(is_object($penghuni)): ?>
                                                    <div class="flex items-center gap-3 p-2 md:p-3 bg-white border border-gray-100 rounded-lg hover:bg-blue-50 transition-colors">
                                                        <div class="w-8 h-8 md:w-10 md:h-10 bg-gradient-to-br from-blue-100 to-blue-200 rounded-full flex items-center justify-center flex-shrink-0">
                                                            <span class="text-sm md:text-base font-bold text-blue-600">
                                                                <?php if(isset($penghuni->nama) && !empty($penghuni->nama)): ?>
                                                                    <?php echo e(strtoupper(substr($penghuni->nama, 0, 1))); ?>

                                                                <?php else: ?>
                                                                    ?
                                                                <?php endif; ?>
                                                            </span>
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-1">
                                                                <div class="min-w-0">
                                                                    <h5 class="font-medium text-gray-900 truncate"><?php echo e($penghuni->nama ?? 'N/A'); ?></h5>
                                                                    <div class="flex items-center gap-2 text-xs text-gray-500 truncate">
                                                                        <span><?php echo e($penghuni->id_karyawan ?? 'N/A'); ?></span>
                                                                        
                                                                        <?php if(isset($penghuni->no_hp) && !empty($penghuni->no_hp)): ?>
                                                                            <span class="text-gray-400">•</span>
                                                                            <span class="text-blue-600 flex items-center gap-1">
                                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                                                                </svg>
                                                                                <?php echo e($penghuni->no_hp); ?>

                                                                            </span>
                                                                        <?php else: ?>
                                                                            
                                                                            <span class="text-gray-400">•</span>
                                                                            <span class="text-red-500 text-xs flex items-center gap-1">
                                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.928-.833-2.698 0L4.392 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                                                                </svg>
                                                                                No HP tidak tersedia
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                <div class="flex flex-wrap gap-1">
                                                                    <?php if(isset($penghuni->unit_kerja) && $penghuni->unit_kerja): ?>
                                                                        <span class="text-xs font-medium text-gray-600 bg-gray-100 px-1.5 py-0.5 rounded truncate max-w-[100px]">
                                                                            <?php echo e($penghuni->unit_kerja); ?>

                                                                        </span>
                                                                    <?php endif; ?>
                                                                    <?php if(isset($penghuni->gol) && $penghuni->gol): ?>
                                                                        <span class="text-xs font-medium text-gray-600 bg-gray-100 px-1.5 py-0.5 rounded whitespace-nowrap">
                                                                            Gol. <?php echo e($penghuni->gol); ?>

                                                                        </span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php else: ?>
                                                <div class="text-center py-4 bg-gray-50 rounded-lg">
                                                    <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-10A2.5 2.5 0 1121 10.5 2.5 2.5 0 0118.5 8z"/>
                                                    </svg>
                                                    <p class="text-gray-500 text-sm">Belum ada penghuni ditempatkan di unit ini</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php else: ?>
                        
                        <div class="border border-gray-200 rounded-xl overflow-hidden">
                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-4 border-b border-blue-100">
                                <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    Penghuni yang Diajukan
                                </h3>
                            </div>
                            
                            <div class="p-4">
                                <div class="space-y-3">
                                    <?php if($request->penghuni && $request->penghuni->count() > 0): ?>
                                        <?php $__currentLoopData = $request->penghuni; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $penghuni): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="flex items-center gap-3 p-3 bg-white border border-gray-100 rounded-lg hover:bg-blue-50 transition-colors">
                                            <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-blue-200 rounded-full flex items-center justify-center flex-shrink-0">
                                                <span class="text-base font-bold text-blue-600">
                                                    <?php if(isset($penghuni->nama) && !empty($penghuni->nama)): ?>
                                                        <?php echo e(strtoupper(substr($penghuni->nama, 0, 1))); ?>

                                                    <?php else: ?>
                                                        ?
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex flex-col">
                                                    <div class="flex justify-between items-start">
                                                        <div class="min-w-0">
                                                            <h5 class="font-medium text-gray-900 truncate"><?php echo e($penghuni->nama ?? 'N/A'); ?></h5>
                                                            <div class="flex items-center gap-2 text-xs text-gray-500 mt-1">
                                                                <span><?php echo e($penghuni->id_karyawan ?? 'N/A'); ?></span>
                                                                
                                                                <?php if(isset($penghuni->no_hp) && !empty($penghuni->no_hp)): ?>
                                                                    <span class="text-gray-400">•</span>
                                                                    <span class="text-blue-600 flex items-center gap-1">
                                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                                                        </svg>
                                                                        <?php echo e($penghuni->no_hp); ?>

                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="text-gray-400">•</span>
                                                                    <span class="text-red-500 text-xs flex items-center gap-1">
                                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.928-.833-2.698 0L4.392 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                                                        </svg>
                                                                        No HP tidak tersedia
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="text-xs text-gray-500 ml-2 whitespace-nowrap">
                                                            <?php echo e(\Carbon\Carbon::parse($penghuni->tanggal_mulai)->format('d M Y')); ?> - 
                                                            <?php echo e(\Carbon\Carbon::parse($penghuni->tanggal_selesai)->format('d M Y')); ?>

                                                        </div>
                                                    </div>
                                                    <div class="flex flex-wrap gap-1 mt-2">
                                                        <?php if(isset($penghuni->unit_kerja) && $penghuni->unit_kerja): ?>
                                                            <span class="text-xs font-medium text-gray-600 bg-gray-100 px-2 py-0.5 rounded">
                                                                <?php echo e($penghuni->unit_kerja); ?>

                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if(isset($penghuni->gol) && $penghuni->gol): ?>
                                                            <span class="text-xs font-medium text-gray-600 bg-gray-100 px-2 py-0.5 rounded">
                                                                Gol. <?php echo e($penghuni->gol); ?>

                                                            </span>
                                                        <?php endif; ?>
                                                        <span class="text-xs font-medium text-blue-600 bg-blue-100 px-2 py-0.5 rounded">
                                                            <?php echo e(\Carbon\Carbon::parse($penghuni->tanggal_mulai)->diffInDays($penghuni->tanggal_selesai)); ?> hari
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php else: ?>
                                        <div class="text-center py-4 bg-gray-50 rounded-lg">
                                            <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-10A2.5 2.5 0 1121 10.5 2.5 2.5 0 0118.5 8z"/>
                                            </svg>
                                            <p class="text-gray-500 text-sm">Tidak ada data penghuni</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            
            <?php if($request->alasan): ?>
            <div class="bg-white rounded-lg md:rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-300">
                <div class="p-4 md:p-6">
                    <h2 class="text-base md:text-lg font-semibold text-gray-900 mb-3 md:mb-4">Alasan Pengajuan</h2>
                    <div class="p-3 md:p-4 bg-gradient-to-r from-gray-50 to-blue-50 rounded-lg md:rounded-xl border border-blue-100">
                        <p class="text-gray-700 leading-relaxed text-sm md:text-base"><?php echo e($request->alasan); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>

        
        <div class="space-y-4 md:space-y-6">
            
            
            <div class="bg-white rounded-lg md:rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-300">
                <div class="p-4 md:p-6">
                    <h2 class="text-base md:text-lg font-semibold text-gray-900 mb-4 md:mb-6">Informasi Pengajuan</h2>
                    
                    <div class="space-y-4 md:space-y-5">
                        <div>
                            <div class="flex items-center gap-2 text-xs md:text-sm text-gray-500 mb-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>Tanggal Pengajuan</span>
                            </div>
                            <p class="text-base md:text-lg font-semibold text-gray-900"><?php echo e($request->created_at->format('d F Y')); ?></p>
                        </div>

                        <div class="border-t border-gray-100 pt-4">
                            <div class="flex items-center gap-2 text-xs md:text-sm text-gray-500 mb-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <span>Jumlah Penghuni</span>
                            </div>
                            <div class="flex items-baseline gap-2">
                                <p class="text-2xl md:text-3xl font-bold text-blue-600"><?php echo e($request->penghuni->count() ?? 0); ?></p>
                                <p class="text-xs md:text-sm text-gray-500">orang</p>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 pt-4">
                            <div class="flex items-center gap-2 text-xs md:text-sm text-gray-500 mb-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <span>Total Unit</span>
                            </div>
                            <div class="flex items-baseline gap-2">
                                <p class="text-2xl md:text-3xl font-bold text-blue-600"><?php echo e($totalUnits); ?></p>
                                <p class="text-xs md:text-sm text-gray-500">unit</p>
                            </div>
                        </div>

                        
                        <?php if($request->approved_by || ($request->user && $request->status == 'APPROVED')): ?>
                            <div class="border-t border-gray-100 pt-4">
                                <div class="flex items-center gap-2 text-xs md:text-sm text-gray-500 mb-2">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Disetujui Oleh</span>
                                </div>
                                <?php
                                    // Coba ambil dari beberapa sumber
                                    $approvedByName = null;
                                    
                                    if ($request->approved_by) {
                                        // Jika approved_by sudah berupa string nama
                                        $approvedByName = $request->approved_by;
                                    } elseif ($request->relationLoaded('approvedUser') && $request->approvedUser) {
                                        // Jika ada relasi approvedUser
                                        $approvedByName = $request->approvedUser->name;
                                    } elseif ($request->relationLoaded('user') && $request->user) {
                                        // Jika tidak ada, ambil dari user yang membuat request
                                        $approvedByName = $request->user->name;
                                    }
                                ?>
                                
                                <?php if($approvedByName): ?>
                                    <p class="text-base md:text-lg font-semibold text-gray-900 truncate">
                                        <?php echo e($approvedByName); ?>

                                    </p>
                                    <?php if($request->approved_at): ?>
                                    <p class="text-xs md:text-sm text-gray-500 mt-1"><?php echo e($request->approved_at->format('d M Y, H:i')); ?></p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="text-sm text-gray-500">-</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            
            <?php if($request->status == 'REJECTED' && $request->reject_reason): ?>
                <div class="bg-gradient-to-br from-red-50 to-orange-50 rounded-lg md:rounded-xl border border-red-200 shadow-sm hover:shadow-md transition-shadow duration-300">
                    <div class="p-4 md:p-6">
                        <div class="flex items-center gap-2 md:gap-3 mb-3 md:mb-4">
                            <div class="p-1.5 md:p-2 bg-white rounded-lg shadow-sm">
                                <svg class="w-5 h-5 md:w-6 md:h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.928-.833-2.698 0L4.392 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h2 class="text-base md:text-lg font-semibold text-gray-900 truncate">Alasan Penolakan</h2>
                                <p class="text-xs md:text-sm text-red-600 mt-1">Pengajuan tidak dapat diproses</p>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg p-3 md:p-4 border border-red-100">
                            <p class="text-gray-700 text-sm md:text-base"><?php echo e($request->reject_reason); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            
            <div class="bg-white rounded-lg md:rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-300">
                <div class="p-4 md:p-6">
                    <h2 class="text-base md:text-lg font-semibold text-gray-900 mb-4 md:mb-6">Aktivitas Terbaru</h2>
                    
                    <div class="space-y-3 md:space-y-4">
                        <div class="flex gap-2 md:gap-3">
                            <div class="relative">
                                <div class="w-6 h-6 md:w-8 md:h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3 h-3 md:w-4 md:h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div class="absolute left-3 top-6 w-0.5 h-full bg-gray-200"></div>
                            </div>
                            <div class="pb-3 md:pb-4">
                                <p class="font-medium text-gray-900 text-sm md:text-base">Pengajuan dibuat</p>
                                <p class="text-xs md:text-sm text-gray-500 mt-1"><?php echo e($request->created_at->format('d M Y, H:i')); ?></p>
                            </div>
                        </div>

                        <?php if($request->status != 'PENDING'): ?>
                            <div class="flex gap-2 md:gap-3">
                                <div class="relative">
                                    <div class="w-6 h-6 md:w-8 md:h-8 <?php echo e($request->status == 'APPROVED' ? 'bg-green-100' : 'bg-red-100'); ?> rounded-full flex items-center justify-center flex-shrink-0">
                                        <svg class="w-3 h-3 md:w-4 md:h-4 <?php echo e($request->status == 'APPROVED' ? 'text-green-600' : 'text-red-600'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <?php if($request->status == 'APPROVED'): ?>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            <?php else: ?>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            <?php endif; ?>
                                        </svg>
                                    </div>
                                </div>
                                <div class="pb-3 md:pb-4">
                                    <p class="font-medium text-gray-900 text-sm md:text-base">
                                        <?php echo e($request->status == 'APPROVED' ? 'Disetujui' : 'Ditolak'); ?>

                                    </p>
                                    <p class="text-xs md:text-sm text-gray-500 mt-1">
                                        <?php if($request->approved_at): ?>
                                            <?php echo e($request->approved_at->format('d M Y, H:i')); ?>

                                        <?php elseif($request->updated_at): ?>
                                            <?php echo e($request->updated_at->format('d M Y, H:i')); ?>

                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if($hasAssignments && $assignments->isNotEmpty()): ?>
                            <div class="flex gap-2 md:gap-3">
                                <div class="relative">
                                    <div class="w-6 h-6 md:w-8 md:h-8 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                                        <svg class="w-3 h-3 md:w-4 md:h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 text-sm md:text-base">Penempatan Unit</p>
                                    <p class="text-xs md:text-sm text-gray-500 mt-1">
                                        <?php if($assignments->first() && $assignments->first()->created_at): ?>
                                            <?php echo e($assignments->first()->created_at->format('d M Y, H:i')); ?>

                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add smooth hover effects
    const cards = document.querySelectorAll('.transition-shadow');
    cards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.classList.add('shadow-md');
            card.classList.remove('shadow-sm');
        });
        card.addEventListener('mouseleave', () => {
            card.classList.remove('shadow-md');
            card.classList.add('shadow-sm');
        });
    });

    // Progress bar animation
    const progressBar = document.querySelector('.transition-all');
    if (progressBar) {
        setTimeout(() => {
            progressBar.style.transition = 'width 1s ease-out';
        }, 300);
    }

    // Format nomor HP saat ditampilkan
    const phoneNumbers = document.querySelectorAll('.text-blue-600');
    phoneNumbers.forEach(phone => {
        const original = phone.textContent.trim();
        // Format menjadi +62 xxx xxxx xxxx jika mengandung +62
        if (original.includes('+62') && original.length > 3) {
            const numberOnly = original.replace('+62', '').replace(/\D/g, '');
            if (numberOnly.length >= 9) {
                const formatted = numberOnly.replace(/(\d{3})(\d{4})(\d{0,4})/, '$1 $2 $3').trim();
                phone.textContent = '+62 ' + formatted;
            }
        }
    });
});
</script>

<style>
/* Custom scrollbar */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}

/* Smooth transitions */
* {
    transition: background-color 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .grid-cols-1 {
        grid-template-columns: 1fr;
    }
    
    .p-6 {
        padding: 1rem;
    }
    
    .text-2xl {
        font-size: 1.5rem;
    }
}

/* Style untuk nomor HP */
.text-blue-600 svg {
    display: inline-block;
    vertical-align: text-top;
}

/* Style untuk NIK dan No HP */
.flex.items-center.gap-2.text-xs.text-gray-500 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

/* Style untuk status no HP tidak tersedia */
.text-red-500 svg {
    display: inline-block;
    vertical-align: text-top;
}
</style>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/apartemen/user/show.blade.php ENDPATH**/ ?>