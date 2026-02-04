<?php $__env->startSection('content'); ?>
<div class="p-4 md:p-6 space-y-4 md:space-y-6">
    
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-lg md:text-2xl font-bold text-gray-900">Proses Mailing</h1>
            <p class="text-xs md:text-sm text-gray-600">Tracking: Mailing Room → Lantai 47 → Selesai</p>
            
            
            <?php if(isset($canViewAll)): ?>
                <?php if($canViewAll): ?>
                    <div class="mt-1">
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-md">
                            <i class="fas fa-user-shield mr-1"></i> Admin: Dapat melihat semua mailing
                        </span>
                    </div>
                <?php else: ?>
                    <div class="mt-1">
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-md">
                            <i class="fas fa-user mr-1"></i> User: Hanya melihat mailing ditujukan kepada Anda
                        </span>
                        <?php if($pelangganId): ?>
                            <span class="ml-2 text-xs text-gray-500">(ID Pelanggan: <?php echo e($pelangganId); ?>)</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="flex gap-2">
            <a href="<?php echo e(route('mailing.create')); ?>"
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                + Input Mailing
            </a>
            <button id="toggleFilterBtn"
                    type="button"
                    class="bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg text-sm font-semibold border border-transparent transition-all duration-200">
                <i class="fas fa-chevron-down mr-1"></i>Filter
            </button>
        </div>
    </div>

    
    <div id="filterSection" class="bg-white border rounded-xl p-4 hidden">
        <form method="GET" action="<?php echo e(route('mailing.proses')); ?>" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                    <input type="date" name="start_date" value="<?php echo e(request('start_date') ?? $today); ?>" 
                           class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Akhir</label>
                    <input type="date" name="end_date" value="<?php echo e(request('end_date') ?? $today); ?>" 
                           class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Lantai Tujuan</label>
                    <select name="lantai" class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="">Semua Lantai</option>
                        <option value="41" <?php echo e(request('lantai') == '41' ? 'selected' : ''); ?>>Lantai 41</option>
                        <option value="42" <?php echo e(request('lantai') == '42' ? 'selected' : ''); ?>>Lantai 42</option>
                        <option value="43" <?php echo e(request('lantai') == '43' ? 'selected' : ''); ?>>Lantai 43</option>
                        <option value="45" <?php echo e(request('lantai') == '45' ? 'selected' : ''); ?>>Lantai 45</option>
                        <option value="46" <?php echo e(request('lantai') == '46' ? 'selected' : ''); ?>>Lantai 46</option>
                        <option value="47" <?php echo e(request('lantai') == '47' ? 'selected' : ''); ?>>Lantai 47</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Status Proses</label>
                    <select name="status" class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="">Semua Status</option>
                        <option value="Mailing Room" <?php echo e(request('status') == 'Mailing Room' ? 'selected' : ''); ?>>Mailing Room</option>
                        <option value="Lantai 47" <?php echo e(request('status') == 'Lantai 47' ? 'selected' : ''); ?>>Lantai 47</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Cari Semua Data</label>
                    <input type="text" name="search" value="<?php echo e(request('search')); ?>"
                        placeholder="Cari resi, nama pengirim, penerima, ekspedisi, lantai..."
                        class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700">
                    Terapkan Filter
                </button>
                <a href="<?php echo e(route('mailing.proses')); ?>"
                   class="bg-gray-200 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-300">
                    Reset
                </a>
            </div>
        </form>
    </div>

    
    <?php if(request()->hasAny(['start_date', 'end_date', 'search', 'status', 'lantai'])): ?>
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-3">
        <div class="flex items-start">
            <i class="fas fa-filter text-blue-500 mt-0.5 mr-3"></i>
            <div>
                <p class="text-sm font-medium text-blue-800 mb-2">Filter Aktif</p>
                <div class="flex flex-wrap gap-2">
                    <?php if(request('start_date')): ?>
                    <span class="px-2 py-1 bg-white text-blue-700 text-xs rounded border border-blue-200">
                        Mulai: <?php echo e(request('start_date')); ?>

                    </span>
                    <?php endif; ?>
                    <?php if(request('end_date')): ?>
                    <span class="px-2 py-1 bg-white text-blue-700 text-xs rounded border border-blue-200">
                        Akhir: <?php echo e(request('end_date')); ?>

                    </span>
                    <?php endif; ?>
                    <?php if(request('lantai')): ?>
                    <span class="px-2 py-1 bg-white text-blue-700 text-xs rounded border border-blue-200">
                        Lantai: <?php echo e(request('lantai')); ?>

                    </span>
                    <?php endif; ?>
                    <?php if(request('status')): ?>
                    <span class="px-2 py-1 bg-white text-blue-700 text-xs rounded border border-blue-200">
                        Status: <?php echo e(request('status')); ?>

                    </span>
                    <?php endif; ?>
                    <?php if(request('search')): ?>
                    <span class="px-2 py-1 bg-white text-blue-700 text-xs rounded border border-blue-200">
                        Pencarian: "<?php echo e(request('search')); ?>"
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    
    <div id="bulkActions" class="hidden bg-white border border-gray-200 rounded-xl p-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <p class="font-semibold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-check-circle text-green-500"></i>
                    <span id="selectedCount">0</span> item dipilih
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button id="selectAllBtn" class="px-4 py-2 bg-blue-50 text-blue-700 border border-blue-200 rounded-lg text-sm font-medium hover:bg-blue-100">
                    Pilih Semua
                </button>
                <button id="deselectAllBtn" class="px-4 py-2 bg-gray-50 text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-100">
                    Batalkan Semua
                </button>
                <button id="bulkMoveBtn" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 hidden">
                    <i class="fas fa-arrow-right mr-2"></i>Pindahkan ke Lantai 47
                </button>
                <button id="bulkCompleteBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 hidden">
                    <i class="fas fa-check-circle mr-2"></i>Selesaikan Distribusi
                </button>
            </div>
        </div>
    </div>

    
    <div class="space-y-3">
        <?php $__currentLoopData = $mailings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div data-mailing-id="<?php echo e($m->id_mailing); ?>" 
             data-status="<?php echo e($m->mailing_status); ?>"
             class="bg-white rounded-xl border border-gray-200 p-4 relative group mailing-item cursor-pointer hover:border-blue-300 transition-colors">
            
            <div class="absolute left-4 top-4">
                <input type="checkbox" value="<?php echo e($m->id_mailing); ?>" data-status="<?php echo e($m->mailing_status); ?>"
                       class="mailing-checkbox hidden peer group-hover:block checked:block h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            </div>

            <div class="pl-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-2">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <i class="fas fa-box text-blue-500"></i>
                            <h3 class="font-bold text-gray-900"><?php echo e($m->mailing_resi); ?></h3>
                            <?php if($m->mailing_expedisi): ?>
                            <span class="px-2 py-0.5 bg-gray-100 text-gray-700 text-xs rounded">
                                <?php echo e($m->mailing_expedisi); ?>

                            </span>
                            <?php endif; ?>
                            <?php if($m->mailing_lantai): ?>
                            <span class="px-2 py-0.5 bg-blue-50 text-blue-700 text-xs rounded font-medium">
                                Lantai <?php echo e($m->mailing_lantai); ?>

                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="text-sm text-gray-600 mb-1">
                            <?php echo e($m->mailing_pengirim); ?> → 
                            <span class="font-medium"><?php echo e($m->mailing_penerima); ?></span>
                        </div>
                        
                        
                        <?php if($m->mailing_keterangan): ?>
                        <div class="text-xs text-gray-500 mb-1">
                            <i class="fas fa-info-circle text-gray-400 mr-1"></i>
                            <?php echo e(Str::limit($m->mailing_keterangan, 100)); ?>

                            
                            
                            <?php if($pelangganId && str_contains($m->mailing_keterangan, "Pelanggan ID: {$pelangganId}")): ?>
                            <span class="ml-2 px-1 py-0.5 bg-green-100 text-green-700 text-xs rounded">
                                <i class="fas fa-check mr-0.5"></i> Milik Anda
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex items-center gap-4 text-xs text-gray-500">
                            <div>
                                <i class="fas fa-calendar mr-1"></i>
                                <?php echo e($m->mailing_tanggal_input->format('d M Y, H:i')); ?>

                            </div>
                            <?php if($m->mailing_tanggal_ob47): ?>
                            <div>
                                <i class="fas fa-arrow-right text-indigo-500 mr-1"></i>
                                Lantai 47: <?php echo e($m->mailing_tanggal_ob47->format('d M Y, H:i')); ?>

                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <?php
                            $statusColors = [
                                'Mailing Room' => 'bg-blue-100 text-blue-700 border-blue-200',
                                'Lantai 47' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                                'Selesai' => 'bg-green-100 text-green-700 border-green-200'
                            ];
                            $colorClass = $statusColors[$m->mailing_status] ?? 'bg-gray-100 text-gray-700 border-gray-300';
                        ?>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold border <?php echo e($colorClass); ?>">
                            <?php echo e($m->mailing_status); ?>

                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <?php if($mailings->count() == 0): ?>
        <div class="text-center py-10 border-2 border-dashed border-gray-300 rounded-xl">
            <i class="fas fa-inbox text-4xl text-gray-400 mb-3"></i>
            <p class="font-semibold text-gray-600">Tidak ada data mailing</p>
            <p class="text-sm text-gray-500 mt-1">
                <?php if(request()->hasAny(['start_date', 'end_date', 'search', 'status', 'lantai'])): ?>
                Coba ubah filter pencarian
                <?php else: ?>
                Semua mailing sudah diproses
                <?php endif; ?>
            </p>
        </div>
        <?php endif; ?>
    </div>

    
    <?php if($mailings->hasPages()): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="text-sm text-gray-600">
                Menampilkan <?php echo e($mailings->firstItem()); ?> - <?php echo e($mailings->lastItem()); ?> dari <?php echo e($mailings->total()); ?> data
            </div>
            <div>
                <?php echo e($mailings->links()); ?>

            </div>
        </div>
    </div>
    <?php endif; ?>
</div>


<div id="bulkCompleteModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl w-full max-w-md">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Selesaikan Distribusi</h3>
                    <p class="text-sm text-gray-600" id="completeCountText">0 item terpilih</p>
                </div>
            </div>
            
            
            <form id="bulkCompleteForm" method="POST" action="<?php echo e(route('mailing.bulk-selesai')); ?>" enctype="multipart/form-data" class="space-y-4">
                <?php echo csrf_field(); ?>
                
                
                <div id="hiddenMailingIdsContainer"></div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user-check text-gray-500 mr-2"></i>
                        Diterima oleh
                        <span class="text-red-500">*</span>
                    </label>
                    
                    <div class="space-y-3">
                        
                        <div class="relative">
                            <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden">
                                <i class="fas fa-search text-gray-400 ml-3"></i>
                                <input type="text" 
                                       id="penerimaSearch" 
                                       placeholder="Ketik nama penerima..."
                                       class="flex-1 px-3 py-2 text-sm outline-none">
                                <button type="button" 
                                        id="toggleManualBtn"
                                        class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-sm font-medium border-l">
                                    Manual
                                </button>
                            </div>
                            
                            
                            <div id="penerimaResults" class="hidden absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                <div class="p-2 border-b bg-gray-50">
                                    <p class="text-xs text-gray-600">Klik untuk memilih penerima</p>
                                </div>
                                <div id="penerimaList" class="max-h-48 overflow-y-auto">
                                    
                                </div>
                            </div>
                        </div>
                        
                        
                        <div id="manualInputContainer" class="hidden">
                            <input type="text" 
                                   id="manualPenerima" 
                                   placeholder="Masukkan nama penerima manual..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <p class="text-xs text-gray-500 mt-1">
                                Nama akan otomatis tersimpan
                            </p>
                        </div>
                        
                        
                        <input type="hidden" id="selectedPenerimaId" name="penerima_id" value="">
                        <input type="hidden" id="selectedPenerimaNama" name="mailing_penerima_distribusi" value="">
                        
                        
                        <div id="selectedPenerimaDisplay" class="hidden p-3 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-green-600 font-medium">Penerima terpilih:</p>
                                    <p class="text-sm font-semibold text-gray-800" id="displayPenerimaNama"></p>
                                    <p class="text-xs text-gray-500" id="displayPenerimaSource"></p>
                                </div>
                                <button type="button" 
                                        onclick="clearPenerima()" 
                                        class="text-red-500 hover:text-red-700">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-camera text-gray-500 mr-2"></i>
                        Foto Bukti Distribusi
                        <span class="text-red-500">*</span>
                    </label>
                    
                    
                    <div class="space-y-3">
                        
                        <input type="file" 
                               name="mailing_foto" 
                               accept="image/*" 
                               id="mailingFotoInput"
                               required 
                               class="hidden">
                        
                        
                        <div class="grid grid-cols-2 gap-3">
                            
                            <button type="button" 
                                    onclick="openCameraPhone()"
                                    class="flex flex-col items-center justify-center p-4 bg-blue-50 border-2 border-blue-200 rounded-xl hover:bg-blue-100 transition-colors">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mb-2">
                                    <i class="fas fa-camera text-blue-600 text-lg"></i>
                                </div>
                                <span class="font-semibold text-blue-700 text-sm">Kamera HP</span>
                            </button>
                            
                            
                            <button type="button" 
                                    onclick="openGallery()"
                                    class="flex flex-col items-center justify-center p-4 bg-purple-50 border-2 border-purple-200 rounded-xl hover:bg-purple-100 transition-colors">
                                <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center mb-2">
                                    <i class="fas fa-images text-purple-600 text-lg"></i>
                                </div>
                                <span class="font-semibold text-purple-700 text-sm">Galeri</span>
                            </button>
                        </div>
                        
                        
                        <div id="fotoPreview" class="hidden">
                            <div class="border-2 border-green-200 rounded-xl p-4 bg-green-50">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-check-circle text-green-500"></i>
                                        <p class="text-sm font-semibold text-green-800">Foto siap diupload</p>
                                    </div>
                                    <button type="button" 
                                            onclick="removeFoto()" 
                                            class="text-red-500 hover:text-red-700">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                
                                <div class="flex items-center gap-3">
                                    <div class="w-16 h-16 bg-white rounded-lg overflow-hidden border border-green-200">
                                        <img id="fotoPreviewImage" 
                                             src="" 
                                             alt="Preview" 
                                             class="w-full h-full object-cover">
                                    </div>
                                    
                                    <div class="flex-1">
                                        <p id="fotoFileName" class="text-sm font-medium text-gray-900 truncate"></p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span id="fotoFileSize" class="text-xs text-gray-500"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 pt-6">
                    <button type="button" id="cancelBulkComplete" class="flex-1 px-4 py-3 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit" id="submitBulkComplete" class="flex-1 px-4 py-3 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">
                        Simpan Distribusi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<div id="bulkMoveModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl w-full max-w-md">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                    <i class="fas fa-arrow-right text-indigo-600 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Pindahkan ke Lantai 47</h3>
                    <p class="text-sm text-gray-600" id="moveCountText">0 item terpilih</p>
                </div>
            </div>
            <div class="space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-blue-800">Anda akan memindahkan <span id="moveCount">0</span> mailing ke status "Lantai 47"</p>
                </div>
                
                
                <form id="bulkMoveForm" method="POST" action="<?php echo e(route('mailing.bulk-lantai47')); ?>">
                    <?php echo csrf_field(); ?>
                    
                    
                    <div id="hiddenMoveIdsContainer"></div>
                    
                    <div class="flex gap-3">
                        <button type="button" id="cancelBulkMove" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" id="confirmBulkMove" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                            Ya, Pindahkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ================= FILTER TOGGLE FIX =================
    const toggleFilterBtn = document.getElementById('toggleFilterBtn');
    const filterSection = document.getElementById('filterSection');
    
    if (toggleFilterBtn && filterSection) {
        // Toggle filter visibility
        toggleFilterBtn.addEventListener('click', function() {
            filterSection.classList.toggle('hidden');
            
            // Update icon
            const icon = this.querySelector('i');
            if (filterSection.classList.contains('hidden')) {
                icon.className = 'fas fa-chevron-down mr-1';
                this.classList.remove('bg-blue-100', 'border-blue-300');
            } else {
                icon.className = 'fas fa-chevron-up mr-1';
                this.classList.add('bg-blue-100', 'border-blue-300');
            }
        });
        
        // Auto-show filter if there are active filters
        const hasActiveFilters = window.location.search.includes('start_date=') || 
                               window.location.search.includes('end_date=') || 
                               window.location.search.includes('search=') || 
                               window.location.search.includes('status=') || 
                               window.location.search.includes('lantai=');
        
        if (hasActiveFilters) {
            filterSection.classList.remove('hidden');
            const icon = toggleFilterBtn.querySelector('i');
            icon.className = 'fas fa-chevron-up mr-1';
            toggleFilterBtn.classList.add('bg-blue-100', 'border-blue-300');
        }
    }
    
    // ================= DATE VALIDATION =================
    document.querySelectorAll('input[type="date"]').forEach(input => {
        input.addEventListener('change', function() {
            const startDate = document.querySelector('input[name="start_date"]');
            const endDate = document.querySelector('input[name="end_date"]');
            
            if (startDate.value && endDate.value && startDate.value > endDate.value) {
                alert('Tanggal mulai tidak boleh lebih besar dari tanggal akhir');
                this.value = '';
            }
        });
    });
    
    // ================= FILTER FORM SUBMIT =================
    const filterForm = document.querySelector('form[method="GET"]');
    if (filterForm) {
        // Reset button functionality
        const resetBtn = filterForm.querySelector('a[href*="mailing.proses"]');
        if (resetBtn) {
            resetBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = "<?php echo e(route('mailing.proses')); ?>";
            });
        }
    }
    
    // ================= BULK SELECTION LOGIC =================
    let selectedMailings = new Set();
    let isManualMode = false;
    let currentSelectedPenerima = {
        id: '',
        nama: '',
        source: '' // 'search' atau 'manual'
    };
    
    // Data pelanggan dari PHP (simpan sebagai array)
    const pelanggans = <?php echo json_encode($pelanggans->map(function($p) {
        return ['id' => $p->id_pelanggan, 'nama' => $p->nama_pelanggan];
    }), 512) ?>;
    
    function updateUI() {
        const count = selectedMailings.size;
        document.getElementById('selectedCount').textContent = count;
        
        if (count > 0) {
            document.getElementById('bulkActions').classList.remove('hidden');
            
            const statuses = new Set();
            document.querySelectorAll('.mailing-checkbox:checked').forEach(cb => {
                statuses.add(cb.dataset.status);
            });
            
            const allMailingRoom = Array.from(statuses).every(s => s === 'Mailing Room');
            const allLantai47 = Array.from(statuses).every(s => s === 'Lantai 47');
            
            document.getElementById('bulkMoveBtn').classList.toggle('hidden', !allMailingRoom);
            document.getElementById('bulkCompleteBtn').classList.toggle('hidden', !allLantai47);
            
            document.getElementById('completeCountText').textContent = count + ' item terpilih';
            document.getElementById('moveCountText').textContent = count + ' item terpilih';
            document.getElementById('moveCount').textContent = count;
            
        } else {
            document.getElementById('bulkActions').classList.add('hidden');
        }
    }
    
    function toggleSelection(mailingId) {
        if (selectedMailings.has(mailingId)) {
            selectedMailings.delete(mailingId);
        } else {
            selectedMailings.add(mailingId);
        }
        
        const checkbox = document.querySelector(`.mailing-checkbox[value="${mailingId}"]`);
        if (checkbox) {
            checkbox.checked = selectedMailings.has(mailingId);
        }
        
        updateUI();
    }
    
    // ================= EVENT LISTENERS =================
    
    document.querySelectorAll('.mailing-item').forEach(item => {
        item.addEventListener('click', function(e) {
            if (e.target.type === 'checkbox' || e.target.closest('.mailing-checkbox')) {
                return;
            }
            
            const mailingId = this.dataset.mailingId;
            toggleSelection(mailingId);
        });
    });
    
    document.querySelectorAll('.mailing-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function(e) {
            e.stopPropagation();
            const mailingId = this.value;
            
            if (this.checked) {
                selectedMailings.add(mailingId);
            } else {
                selectedMailings.delete(mailingId);
            }
            
            updateUI();
        });
    });
    
    document.getElementById('selectAllBtn')?.addEventListener('click', function() {
        selectedMailings.clear();
        document.querySelectorAll('.mailing-checkbox').forEach(cb => {
            cb.checked = true;
            selectedMailings.add(cb.value);
        });
        updateUI();
    });
    
    document.getElementById('deselectAllBtn')?.addEventListener('click', function() {
        selectedMailings.clear();
        document.querySelectorAll('.mailing-checkbox').forEach(cb => {
            cb.checked = false;
        });
        updateUI();
    });
    
    // ================= PENERIMA FUNCTIONS =================
    
    // Bulk Complete button click
    document.getElementById('bulkCompleteBtn')?.addEventListener('click', function() {
        if (selectedMailings.size === 0) return;
        
        // Clear previous hidden inputs
        const container = document.getElementById('hiddenMailingIdsContainer');
        container.innerHTML = '';
        
        // Add hidden inputs for each selected mailing
        selectedMailings.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'mailing_ids[]';
            input.value = id;
            container.appendChild(input);
        });
        
        // Reset form state
        resetModal();
        
        // Show modal
        document.getElementById('bulkCompleteModal').classList.remove('hidden');
    });
    
    // Reset modal state
    function resetModal() {
        // Reset form inputs
        document.getElementById('bulkCompleteForm').reset();
        
        // Reset penerima
        clearPenerima();
        resetManualMode();
        
        // Reset foto
        removeFoto();
        
        // Reset state
        currentSelectedPenerima = { id: '', nama: '', source: '' };
    }
    
    // Search penerima
    document.getElementById('penerimaSearch')?.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const resultsDiv = document.getElementById('penerimaList');
        const resultsContainer = document.getElementById('penerimaResults');
        
        if (searchTerm.length < 2) {
            resultsContainer.classList.add('hidden');
            return;
        }
        
        // Filter pelanggan
        const filtered = pelanggans.filter(pelanggan => 
            pelanggan.nama.toLowerCase().includes(searchTerm)
        );
        
        // Display results
        if (filtered.length > 0) {
            resultsDiv.innerHTML = filtered.map(pelanggan => `
                <div class="p-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100 penerima-option"
                     data-id="${pelanggan.id}"
                     data-nama="${pelanggan.nama}">
                    <div class="font-medium text-gray-800">${pelanggan.nama}</div>
                    <div class="text-xs text-gray-500">ID: ${pelanggan.id}</div>
                </div>
            `).join('');
            
            resultsContainer.classList.remove('hidden');
            
            // Add click events
            document.querySelectorAll('.penerima-option').forEach(option => {
                option.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const nama = this.getAttribute('data-nama');
                    
                    selectPenerimaFromSearch(id, nama);
                });
            });
        } else {
            resultsDiv.innerHTML = `
                <div class="p-4 text-center text-gray-500">
                    <i class="fas fa-search text-gray-300 text-lg mb-2"></i>
                    <p class="text-sm">Tidak ditemukan</p>
                    <p class="text-xs">Klik "Manual" untuk input nama</p>
                </div>
            `;
            resultsContainer.classList.remove('hidden');
        }
    });
    
    // Select penerima from search
    function selectPenerimaFromSearch(id, nama) {
        currentSelectedPenerima = {
            id: id,
            nama: nama,
            source: 'search'
        };
        
        updatePenerimaDisplay();
        document.getElementById('penerimaResults').classList.add('hidden');
        document.getElementById('penerimaSearch').value = nama;
    }
    
    // Update penerima display
    function updatePenerimaDisplay() {
        const displayDiv = document.getElementById('selectedPenerimaDisplay');
        const displayNama = document.getElementById('displayPenerimaNama');
        const displaySource = document.getElementById('displayPenerimaSource');
        
        if (currentSelectedPenerima.nama) {
            displayNama.textContent = currentSelectedPenerima.nama;
            
            if (currentSelectedPenerima.source === 'search') {
                displaySource.textContent = 'Dipilih dari daftar';
            } else {
                displaySource.textContent = 'Input manual';
            }
            
            displayDiv.classList.remove('hidden');
            
            // Update hidden inputs for form submit
            document.getElementById('selectedPenerimaId').value = currentSelectedPenerima.id;
            document.getElementById('selectedPenerimaNama').value = currentSelectedPenerima.nama;
        } else {
            displayDiv.classList.add('hidden');
            document.getElementById('selectedPenerimaId').value = '';
            document.getElementById('selectedPenerimaNama').value = '';
        }
    }
    
    // Clear penerima
    window.clearPenerima = function() {
        currentSelectedPenerima = { id: '', nama: '', source: '' };
        document.getElementById('penerimaSearch').value = '';
        document.getElementById('manualPenerima').value = '';
        updatePenerimaDisplay();
        document.getElementById('penerimaResults').classList.add('hidden');
    };
    
    // Reset manual mode
    function resetManualMode() {
        isManualMode = false;
        document.getElementById('manualInputContainer').classList.add('hidden');
        const toggleBtn = document.getElementById('toggleManualBtn');
        if (toggleBtn) {
            toggleBtn.innerHTML = 'Manual';
            toggleBtn.classList.remove('bg-yellow-100', 'text-yellow-700');
        }
        document.getElementById('penerimaSearch').disabled = false;
        document.getElementById('penerimaSearch').placeholder = 'Ketik nama penerima...';
    }
    
    // Toggle manual input
    document.getElementById('toggleManualBtn')?.addEventListener('click', function() {
        isManualMode = !isManualMode;
        const manualContainer = document.getElementById('manualInputContainer');
        const searchInput = document.getElementById('penerimaSearch');
        
        if (isManualMode) {
            // Switch to manual mode
            manualContainer.classList.remove('hidden');
            this.innerHTML = '← Kembali';
            this.classList.add('bg-yellow-100', 'text-yellow-700');
            searchInput.placeholder = 'Input manual aktif';
            searchInput.disabled = true;
            searchInput.value = '';
            
            // Clear previous selection
            clearPenerima();
            
            // Focus on manual input
            setTimeout(() => {
                document.getElementById('manualPenerima').focus();
            }, 100);
        } else {
            // Switch back to search mode
            manualContainer.classList.add('hidden');
            this.innerHTML = 'Manual';
            this.classList.remove('bg-yellow-100', 'text-yellow-700');
            searchInput.placeholder = 'Ketik nama penerima...';
            searchInput.disabled = false;
            searchInput.focus();
        }
    });
    
    // Manual input change
    document.getElementById('manualPenerima')?.addEventListener('input', function() {
        const nama = this.value.trim();
        if (nama) {
            currentSelectedPenerima = {
                id: '',
                nama: nama,
                source: 'manual'
            };
            updatePenerimaDisplay();
        }
    });
    
    // ================= KAMERA & GALERI FUNCTIONS =================
    
    window.openCameraPhone = function() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        
        // Prioritize camera on mobile
        if ('mediaDevices' in navigator) {
            input.capture = 'environment';
        }
        
        input.onchange = function(e) {
            if (e.target.files && e.target.files[0]) {
                handleSelectedFile(e.target.files[0]);
            }
        };
        
        input.click();
    };
    
    window.openGallery = function() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.multiple = false;
        
        input.onchange = function(e) {
            if (e.target.files && e.target.files[0]) {
                handleSelectedFile(e.target.files[0]);
            }
        };
        
        input.click();
    };
    
    function handleSelectedFile(file) {
        // Update hidden input
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        document.getElementById('mailingFotoInput').files = dataTransfer.files;
        
        // Show preview
        showFilePreview(file);
        
        // Trigger validation
        document.getElementById('mailingFotoInput').dispatchEvent(new Event('change'));
    }
    
    function showFilePreview(file) {
        const previewDiv = document.getElementById('fotoPreview');
        const previewImg = document.getElementById('fotoPreviewImage');
        const fileName = document.getElementById('fotoFileName');
        const fileSize = document.getElementById('fotoFileSize');
        
        // Set file info
        fileName.textContent = file.name;
        
        // Format size
        const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
        fileSize.textContent = sizeInMB + ' MB';
        
        // Preview image
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewDiv.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        } else {
            previewDiv.classList.remove('hidden');
        }
    }
    
    window.removeFoto = function() {
        document.getElementById('mailingFotoInput').value = '';
        document.getElementById('fotoPreview').classList.add('hidden');
        document.getElementById('fotoPreviewImage').src = '';
        document.getElementById('fotoFileName').textContent = '';
        document.getElementById('fotoFileSize').textContent = '';
    };
    
    // ================= FORM VALIDATION =================
    document.getElementById('bulkCompleteForm')?.addEventListener('submit', function(e) {
        const fileInput = document.getElementById('mailingFotoInput');
        const penerimaNama = currentSelectedPenerima.nama;
        
        // Validate foto
        if (!fileInput.files || fileInput.files.length === 0) {
            e.preventDefault();
            alert('⚠️ Silakan upload foto bukti distribusi');
            return;
        }
        
        // Validate penerima
        if (!penerimaNama) {
            e.preventDefault();
            alert('⚠️ Silakan pilih atau masukkan nama penerima');
            return;
        }
        
        // Ensure hidden inputs are set
        document.getElementById('selectedPenerimaNama').value = penerimaNama;
        document.getElementById('selectedPenerimaId').value = currentSelectedPenerima.id;
        
        // Show loading
        const submitBtn = document.getElementById('submitBulkComplete');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
        submitBtn.disabled = true;
    });
    
    // ================= BULK MOVE =================
    document.getElementById('bulkMoveBtn')?.addEventListener('click', function() {
        if (selectedMailings.size === 0) return;
        
        const container = document.getElementById('hiddenMoveIdsContainer');
        container.innerHTML = '';
        
        selectedMailings.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'mailing_ids[]';
            input.value = id;
            container.appendChild(input);
        });
        
        document.getElementById('bulkMoveModal').classList.remove('hidden');
    });
    
    document.getElementById('cancelBulkMove')?.addEventListener('click', function() {
        document.getElementById('bulkMoveModal').classList.add('hidden');
    });
    
    document.getElementById('bulkMoveForm')?.addEventListener('submit', function(e) {
        const submitBtn = document.getElementById('confirmBulkMove');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...';
        submitBtn.disabled = true;
    });
    
    // ================= MODAL CLOSE =================
    document.getElementById('cancelBulkComplete')?.addEventListener('click', function() {
        document.getElementById('bulkCompleteModal').classList.add('hidden');
        resetModal();
    });
    
    document.addEventListener('click', function(e) {
        if (e.target.id === 'bulkCompleteModal') {
            document.getElementById('bulkCompleteModal').classList.add('hidden');
            resetModal();
        }
        if (e.target.id === 'bulkMoveModal') {
            document.getElementById('bulkMoveModal').classList.add('hidden');
        }
        
        // Close search results when clicking outside
        const searchInput = document.getElementById('penerimaSearch');
        const resultsContainer = document.getElementById('penerimaResults');
        
        if (searchInput && resultsContainer && 
            !searchInput.contains(e.target) && 
            !resultsContainer.contains(e.target)) {
            resultsContainer.classList.add('hidden');
        }
    });
    
    // Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (selectedMailings.size > 0) {
                selectedMailings.clear();
                document.querySelectorAll('.mailing-checkbox').forEach(cb => cb.checked = false);
                updateUI();
            }
            document.getElementById('penerimaResults').classList.add('hidden');
        }
    });
});
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/mailing/proses.blade.php ENDPATH**/ ?>