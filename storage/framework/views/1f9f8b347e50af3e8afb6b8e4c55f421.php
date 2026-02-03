<?php $__env->startSection('content'); ?>
<div class="p-4 md:p-6">

    
    <div class="mb-6 md:mb-8">
        
        <div class="lg:hidden mb-4">
            <h1 class="text-xl font-bold text-gray-800">Review Permintaan</h1>
            <p class="text-gray-600 text-xs mt-1">Tinjau dan setujui/tolak permintaan apartemen</p>
        </div>

        
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 mb-4 md:mb-6">
            
            <div class="hidden lg:flex items-center space-x-4 flex-1">
                
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Review Permintaan</h1>
                    <p class="text-gray-600 text-sm mt-1">Tinjau dan setujui/tolak permintaan apartemen</p>
                </div>
            </div>

            
            <div class="w-full lg:w-auto lg:mx-4 lg:flex-1 lg:max-w-md order-first lg:order-none">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 md:w-5 md:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" readonly
                           class="pl-10 pr-4 py-2 md:py-3 border border-gray-300 rounded-lg text-sm bg-gray-50 w-full cursor-not-allowed" 
                           placeholder="Review permintaan...">
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
                   class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium text-gray-700 text-sm truncate">Riwayat</span>
                </a>
            </div>
        </div>

        
        <div class="lg:hidden flex justify-end mb-4">
            <a href="<?php echo e(route('apartemen.admin.index')); ?>" 
               class="text-gray-600 hover:text-gray-800 text-sm flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Daftar
            </a>
        </div>
    </div>

    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        
        <div class="lg:col-span-2 space-y-6">
            
            
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Detail Permintaan</h2>
                    
                    <div class="space-y-4">
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Pemohon</label>
                                <p class="font-medium text-gray-900"><?php echo e($request->user->name ?? 'N/A'); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Tanggal Pengajuan</label>
                                <p class="font-medium text-gray-900"><?php echo e($request->created_at->format('d F Y')); ?></p>
                            </div>
                        </div>

                        
                        <?php if($request->alasan): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Alasan Pengajuan</label>
                            <div class="mt-1 p-3 bg-gray-50 rounded-lg">
                                <p class="text-gray-700"><?php echo e($request->alasan); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Status</label>
                            <?php switch($request->status):
                                case ('PENDING'): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Menunggu Approval
                                    </span>
                                    <?php break; ?>
                                <?php case ('APPROVED'): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Disetujui
                                    </span>
                                    <?php break; ?>
                                <?php case ('REJECTED'): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Ditolak
                                    </span>
                                    <?php break; ?>
                            <?php endswitch; ?>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">Daftar Penghuni</h2>
                        <span class="text-sm font-medium text-gray-700 bg-gray-100 px-3 py-1 rounded-full">
                            <?php echo e($request->penghuni->count()); ?> orang
                        </span>
                    </div>

                    <div class="space-y-3">
                        <?php $__currentLoopData = $request->penghuni; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $penghuni): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-medium text-gray-900"><?php echo e($penghuni->nama); ?></h4>
                                    <p class="text-sm text-gray-500 mt-1"><?php echo e($penghuni->id_karyawan); ?></p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <?php if($penghuni->unit_kerja): ?>
                                    <span class="text-xs font-medium text-gray-600 bg-gray-100 px-2 py-1 rounded">
                                        <?php echo e($penghuni->unit_kerja); ?>

                                    </span>
                                    <?php endif; ?>
                                    <?php if($penghuni->gol): ?>
                                    <span class="text-xs font-medium text-gray-600 bg-gray-100 px-2 py-1 rounded">
                                        Gol. <?php echo e($penghuni->gol); ?>

                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mt-3 grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <label class="text-gray-500">Mulai</label>
                                    <p class="font-medium"><?php echo e(\Carbon\Carbon::parse($penghuni->tanggal_mulai)->format('d/m/Y')); ?></p>
                                </div>
                                <div>
                                    <label class="text-gray-500">Selesai</label>
                                    <p class="font-medium"><?php echo e(\Carbon\Carbon::parse($penghuni->tanggal_selesai)->format('d/m/Y')); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>

        </div>

        
        <div class="space-y-6">
            
            
            <?php if($request->status == 'PENDING'): ?>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="p-6">
                    <form action="<?php echo e(route('apartemen.admin.approve.process', $request->id)); ?>" method="POST" id="approvalForm">
                        <?php echo csrf_field(); ?>
                        
                        
                        <div class="mb-6">
                            <label class="flex items-center mb-3">
                                <input type="radio" name="action" value="approve" class="h-4 w-4 text-blue-600 focus:ring-blue-500" checked id="approveRadio">
                                <span class="ml-2 text-sm font-medium text-gray-900">Setujui Permintaan</span>
                            </label>
                            
                            <div id="approve-section" class="mt-4 space-y-4">
                                
                                <div class="grid grid-cols-2 gap-4 mb-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Masuk *</label>
                                        <input type="date" name="tanggal_mulai" id="tanggal_mulai"
                                            value="<?php echo e(old('tanggal_mulai', $request->penghuni->first()->tanggal_mulai ?? '')); ?>"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Keluar *</label>
                                        <input type="date" name="tanggal_selesai" id="tanggal_selesai"
                                            value="<?php echo e(old('tanggal_selesai', $request->penghuni->first()->tanggal_selesai ?? '')); ?>"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>

                                
                                <div id="penempatan-container">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-md font-semibold text-gray-800">Penempatan Penghuni</h3>
                                        <button type="button" onclick="tambahUnit()" 
                                                class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                            Tambah Unit
                                        </button>
                                    </div>

                                    
                                    <div class="unit-group mb-6 p-4 border border-gray-200 rounded-lg bg-gray-50" data-unit-index="0">
                                        <div class="flex justify-between items-center mb-3">
                                            <h4 class="font-medium text-gray-700">Unit #1</h4>
                                            <button type="button" onclick="hapusUnit(this)" class="text-red-600 hover:text-red-800 text-sm">
                                                Hapus
                                            </button>
                                        </div>
                                        
                                        
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Unit *</label>
                                            <select name="penempatan[0][unit_id]" 
                                                    class="unit-select w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    onchange="updateSummary()">
                                                <option value="">-- Pilih Unit --</option>
                                                <?php $__currentLoopData = $availableUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($unit->id); ?>" 
                                                        data-kapasitas="<?php echo e($unit->kapasitas); ?>"
                                                        data-apartemen="<?php echo e($unit->apartemen->nama_apartemen); ?>"
                                                        data-unit="<?php echo e($unit->nomor_unit); ?>">
                                                    <?php echo e($unit->apartemen->nama_apartemen); ?> - <?php echo e($unit->nomor_unit); ?> (Kap: <?php echo e($unit->kapasitas); ?>)
                                                </option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </div>

                                        
                                        <div class="mb-3">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Penghuni *</label>
                                            <div class="penghuni-checkbox-group grid grid-cols-1 md:grid-cols-2 gap-2">
                                                <?php $__currentLoopData = $request->penghuni; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $penghuni): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <label class="flex items-center penghuni-option" data-penghuni-id="<?php echo e($penghuni->id); ?>">
                                                    <input type="checkbox" 
                                                           name="penempatan[0][penghuni_ids][]" 
                                                           value="<?php echo e($penghuni->id); ?>"
                                                           class="penghuni-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500"
                                                           onchange="updateSummary()">
                                                    <span class="ml-2 text-sm text-gray-700">
                                                        <?php echo e($penghuni->nama); ?> (<?php echo e($penghuni->id_karyawan); ?>)
                                                    </span>
                                                </label>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="text-sm text-gray-500">
                                            Terpilih: <span class="jumlah-penghuni font-medium">0</span> orang
                                            | Kapasitas: <span class="kapasitas-unit font-medium">-</span>
                                        </div>
                                    </div>
                                </div>

                                
                                <div id="placement-summary" class="p-3 bg-blue-50 rounded-lg border border-blue-200">
                                    <h4 class="font-medium text-blue-800 mb-2">Ringkasan Penempatan</h4>
                                    <div class="space-y-1 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-blue-700">Total Penghuni:</span>
                                            <span class="font-medium"><?php echo e($request->penghuni->count()); ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-blue-700">Tercover:</span>
                                            <span class="font-medium text-green-600" id="penghuni-tercover">0</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-blue-700">Belum Ditempatkan:</span>
                                            <span class="font-medium text-red-600" id="penghuni-belum-tercover"><?php echo e($request->penghuni->count()); ?></span>
                                        </div>
                                        <div id="unit-summary" class="text-xs text-blue-600 mt-2">
                                            <div>Belum ada unit yang dipilih</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if($availableUnits->isEmpty()): ?>
                                <p class="text-sm text-red-600 mt-1">⚠️ Tidak ada unit yang tersedia</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        
                        <div class="mb-6">
                            <label class="flex items-center mb-3">
                                <input type="radio" name="action" value="reject" class="h-4 w-4 text-red-600 focus:ring-red-500" id="rejectRadio">
                                <span class="ml-2 text-sm font-medium text-gray-900">Tolak Permintaan</span>
                            </label>
                            
                            <div id="reject-section" class="mt-4 hidden">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Alasan Penolakan *</label>
                                    <textarea name="reject_reason" id="reject_reason" rows="3"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                                            placeholder="Masukkan alasan penolakan..."><?php echo e(old('reject_reason')); ?></textarea>
                                </div>
                            </div>
                        </div>

                        
                        <div id="validationMessage" class="hidden mb-4 p-3 bg-yellow-100 text-yellow-700 rounded-lg text-sm">
                        </div>

                        
                        <div class="flex space-x-3">
                            <button type="submit" id="submitBtn"
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                Proses Penempatan
                            </button>
                            <a href="<?php echo e(route('apartemen.admin.index')); ?>" 
                            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium text-center transition-colors">
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi</h2>
                    
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">ID Permintaan</label>
                            <p class="font-medium text-gray-900">#<?php echo e(str_pad($request->id, 6, '0', STR_PAD_LEFT)); ?></p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Total Penghuni</label>
                            <p class="font-medium text-gray-900"><?php echo e($request->penghuni->count()); ?> orang</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Kapasitas Dibutuhkan</label>
                            <p class="font-medium text-gray-900">Minimal <?php echo e($request->penghuni->count()); ?> orang</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Unit Tersedia</label>
                            <p class="font-medium text-gray-900"><?php echo e($availableUnits->count()); ?> unit</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    let unitCounter = 1;
    const totalPenghuni = <?php echo e($request->penghuni->count()); ?>;
    
    function updateSummary() {
        let totalTercover = 0;
        let unitSummaryHtml = '';
        
        // Reset semua checkbox untuk tracking
        document.querySelectorAll('.penghuni-option').forEach(option => {
            option.style.opacity = '1';
            const checkbox = option.querySelector('.penghuni-checkbox');
            checkbox.disabled = false;
        });
        
        // Hitung total tercover dan buat summary
        document.querySelectorAll('.unit-group').forEach((unitGroup, index) => {
            const unitSelect = unitGroup.querySelector('.unit-select');
            const selectedOption = unitSelect.options[unitSelect.selectedIndex];
            const unitId = unitSelect.value;
            
            if (unitId) {
                const unitName = selectedOption.getAttribute('data-apartemen') + ' - ' + 
                               selectedOption.getAttribute('data-unit');
                const kapasitas = selectedOption.getAttribute('data-kapasitas');
                
                // Update kapasitas display
                const kapasitasSpan = unitGroup.querySelector('.kapasitas-unit');
                if (kapasitasSpan) {
                    kapasitasSpan.textContent = kapasitas;
                }
                
                // Hitung penghuni di unit ini
                const checkboxes = unitGroup.querySelectorAll('.penghuni-checkbox:checked');
                const jumlahPenghuni = checkboxes.length;
                totalTercover += jumlahPenghuni;
                
                unitSummaryHtml += `<div>${unitName}: ${jumlahPenghuni} penghuni (max: ${kapasitas})</div>`;
                
                // Update info di unit group
                const jumlahSpan = unitGroup.querySelector('.jumlah-penghuni');
                if (jumlahSpan) {
                    jumlahSpan.textContent = jumlahPenghuni;
                }
                
                // Validasi kapasitas
                if (jumlahPenghuni > parseInt(kapasitas)) {
                    unitGroup.style.borderColor = '#ef4444';
                    unitGroup.style.backgroundColor = '#fef2f2';
                } else {
                    unitGroup.style.borderColor = '#e5e7eb';
                    unitGroup.style.backgroundColor = '#f9fafb';
                }
            }
        });
        
        // Update display
        document.getElementById('penghuni-tercover').textContent = totalTercover;
        document.getElementById('penghuni-belum-tercover').textContent = totalPenghuni - totalTercover;
        document.getElementById('unit-summary').innerHTML = unitSummaryHtml || '<div>Belum ada unit yang dipilih</div>';
        
        // Nonaktifkan checkbox yang sudah dipilih di unit lain
        const semuaCheckedIds = [];
        document.querySelectorAll('.penghuni-checkbox:checked').forEach(cb => {
            semuaCheckedIds.push(cb.value);
        });
        
        document.querySelectorAll('.penghuni-checkbox:not(:checked)').forEach(cb => {
            if (semuaCheckedIds.includes(cb.value)) {
                cb.disabled = true;
                cb.closest('.penghuni-option').style.opacity = '0.5';
            }
        });
    }
    
    window.tambahUnit = function() {
        const container = document.getElementById('penempatan-container');
        const newIndex = unitCounter++;
        
        const newUnitHTML = `
            <div class="unit-group mb-6 p-4 border border-gray-200 rounded-lg bg-gray-50" data-unit-index="${newIndex}">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="font-medium text-gray-700">Unit #${newIndex + 1}</h4>
                    <button type="button" onclick="hapusUnit(this)" class="text-red-600 hover:text-red-800 text-sm">
                        Hapus
                    </button>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Unit *</label>
                    <select name="penempatan[${newIndex}][unit_id]" 
                            class="unit-select w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            onchange="updateSummary()">
                        <option value="">-- Pilih Unit --</option>
                        <?php $__currentLoopData = $availableUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($unit->id); ?>" 
                                data-kapasitas="<?php echo e($unit->kapasitas); ?>"
                                data-apartemen="<?php echo e($unit->apartemen->nama_apartemen); ?>"
                                data-unit="<?php echo e($unit->nomor_unit); ?>">
                            <?php echo e($unit->apartemen->nama_apartemen); ?> - <?php echo e($unit->nomor_unit); ?> (Kap: <?php echo e($unit->kapasitas); ?>)
                        </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Penghuni *</label>
                    <div class="penghuni-checkbox-group grid grid-cols-1 md:grid-cols-2 gap-2">
                        <?php $__currentLoopData = $request->penghuni; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $penghuni): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <label class="flex items-center penghuni-option" data-penghuni-id="<?php echo e($penghuni->id); ?>">
                            <input type="checkbox" 
                                   name="penempatan[${newIndex}][penghuni_ids][]" 
                                   value="<?php echo e($penghuni->id); ?>"
                                   class="penghuni-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500"
                                   onchange="updateSummary()">
                            <span class="ml-2 text-sm text-gray-700">
                                <?php echo e($penghuni->nama); ?> (<?php echo e($penghuni->id_karyawan); ?>)
                            </span>
                        </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
                
                <div class="text-sm text-gray-500">
                    Terpilih: <span class="jumlah-penghuni font-medium">0</span> orang
                    | Kapasitas: <span class="kapasitas-unit font-medium">-</span>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', newUnitHTML);
        updateSummary();
    };
    
    window.hapusUnit = function(button) {
        const unitGroup = button.closest('.unit-group');
        if (document.querySelectorAll('.unit-group').length > 1) {
            unitGroup.remove();
            updateSummary();
            
            // Renumber units
            document.querySelectorAll('.unit-group').forEach((group, index) => {
                group.setAttribute('data-unit-index', index);
                const title = group.querySelector('h4');
                if (title) {
                    title.textContent = `Unit #${index + 1}`;
                }
                
                // Update input names
                const unitSelect = group.querySelector('.unit-select');
                if (unitSelect) {
                    unitSelect.name = `penempatan[${index}][unit_id]`;
                }
                
                const checkboxes = group.querySelectorAll('.penghuni-checkbox');
                checkboxes.forEach(cb => {
                    cb.name = `penempatan[${index}][penghuni_ids][]`;
                });
            });
            
            unitCounter = document.querySelectorAll('.unit-group').length;
        } else {
            alert('Minimal harus ada satu unit untuk penempatan!');
        }
    };
    
    // Event listeners untuk radio buttons
    const approveRadio = document.querySelector('#approveRadio');
    const rejectRadio = document.querySelector('#rejectRadio');
    const approveSection = document.getElementById('approve-section');
    const rejectSection = document.getElementById('reject-section');
    
    function toggleSections() {
        if (approveRadio.checked) {
            approveSection.classList.remove('hidden');
            rejectSection.classList.add('hidden');
        } else {
            approveSection.classList.add('hidden');
            rejectSection.classList.remove('hidden');
        }
    }
    
    approveRadio.addEventListener('change', toggleSections);
    rejectRadio.addEventListener('change', toggleSections);
    toggleSections();
    
    // Form validation
    const form = document.getElementById('approvalForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const action = document.querySelector('input[name="action"]:checked').value;
        let isValid = true;
        let message = '';
        
        if (action === 'approve') {
            // Validasi tanggal
            const tanggalMulai = document.getElementById('tanggal_mulai').value;
            const tanggalSelesai = document.getElementById('tanggal_selesai').value;
            
            if (!tanggalMulai || !tanggalSelesai) {
                isValid = false;
                message = 'Silakan isi tanggal masuk dan keluar!';
            } else if (new Date(tanggalSelesai) <= new Date(tanggalMulai)) {
                isValid = false;
                message = 'Tanggal keluar harus setelah tanggal masuk!';
            }
            
            // Validasi unit dan penghuni
            let totalPenghuniTercover = 0;
            let adaUnitTanpaPenghuni = false;
            let adaKapasitasMelebihi = false;
            let adaUnitBelumDipilih = false;
            
            document.querySelectorAll('.unit-group').forEach(unitGroup => {
                const unitSelect = unitGroup.querySelector('.unit-select');
                const unitId = unitSelect.value;
                const kapasitas = unitId ? parseInt(unitSelect.options[unitSelect.selectedIndex].getAttribute('data-kapasitas')) : 0;
                const checkboxes = unitGroup.querySelectorAll('.penghuni-checkbox:checked');
                const jumlahPenghuni = checkboxes.length;
                
                if (!unitId) {
                    adaUnitBelumDipilih = true;
                } else if (unitId && jumlahPenghuni === 0) {
                    adaUnitTanpaPenghuni = true;
                }
                
                if (unitId && jumlahPenghuni > kapasitas) {
                    adaKapasitasMelebihi = true;
                }
                
                if (unitId) {
                    totalPenghuniTercover += jumlahPenghuni;
                }
            });
            
            if (adaUnitBelumDipilih) {
                isValid = false;
                message = 'Ada unit yang belum dipilih!';
            } else if (adaUnitTanpaPenghuni) {
                isValid = false;
                message = 'Ada unit yang dipilih tanpa penghuni!';
            } else if (adaKapasitasMelebihi) {
                isValid = false;
                message = 'Ada unit yang melebihi kapasitas!';
            } else if (totalPenghuniTercover < totalPenghuni) {
                isValid = false;
                message = `Masih ada ${totalPenghuni - totalPenghuniTercover} penghuni yang belum ditempatkan!`;
            }
            
        } else if (action === 'reject') {
            const rejectReason = document.getElementById('reject_reason').value.trim();
            if (!rejectReason || rejectReason.length < 5) {
                isValid = false;
                message = 'Silakan isi alasan penolakan (minimal 5 karakter)!';
            }
        }
        
        if (!isValid) {
            const validationMessage = document.getElementById('validationMessage');
            validationMessage.textContent = message;
            validationMessage.classList.remove('hidden');
            validationMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }
        
        // Show loading
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.innerHTML = 'Memproses...';
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        
        // Submit form
        form.submit();
    });
    
    // Update summary on load
    updateSummary();
});
</script>

<style>
.unit-group {
    transition: all 0.3s ease;
}
.penghuni-option {
    transition: opacity 0.2s ease;
}
.penghuni-checkbox:disabled {
    cursor: not-allowed;
}
</style>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/apartemen/admin/approve.blade.php ENDPATH**/ ?>