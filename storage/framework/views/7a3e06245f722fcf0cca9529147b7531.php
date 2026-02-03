<?php $__env->startPush('styles'); ?>
<style>
    .timeline-item:before {
        content: '';
        position: absolute;
        left: 28px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e5e7eb;
    }
    .timeline-item:last-child:before {
        display: none;
    }
    .image-container {
        position: relative;
        overflow: hidden;
        border-radius: 0.5rem;
        transition: transform 0.3s ease;
    }
    .image-container:hover {
        transform: translateY(-2px);
    }
    .image-badge {
        position: absolute;
        top: 8px;
        left: 8px;
        z-index: 10;
    }
    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 4px;
    }
    
    /* Responsive adjustments */
    @media (max-width: 640px) {
        .timeline-item:before {
            left: 20px;
        }
        .image-container {
            margin: 0 -1rem;
            border-radius: 0;
        }
        .image-container img {
            border-radius: 0.5rem;
            margin: 0 auto;
            max-height: 200px;
            object-fit: cover;
        }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-4 sm:space-y-6">
    <!-- Header dengan Breadcrumb - Responsive -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
        <div>
            <div class="flex items-center gap-2 sm:gap-3">
                <div class="p-2 sm:p-3 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg sm:rounded-xl">
                    <i class="fas fa-box-open text-white text-lg sm:text-xl"></i>
                </div>
                <div>
                    <h1 class="text-lg sm:text-2xl font-bold text-gray-900">Detail Messenger</h1>
                    <p class="text-xs sm:text-sm text-gray-600 mt-0.5">
                        Tracking ID: <span class="font-mono font-semibold text-blue-600"><?php echo e($transaksi->no_transaksi); ?></span>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="flex flex-wrap gap-2 mt-2 sm:mt-0">
            <a href="<?php echo e(route('messenger.index')); ?>" 
               class="inline-flex items-center px-3 py-2 sm:px-4 sm:py-2.5 bg-white border border-gray-300 rounded-lg font-medium text-xs sm:text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                <i class="fas fa-arrow-left mr-2 text-xs sm:text-sm"></i>
                Kembali
            </a>
            <a href="<?php echo e(route('messenger.print', $transaksi->no_transaksi)); ?>" 
               target="_blank"
               class="inline-flex items-center px-3 py-2 sm:px-4 sm:py-2.5 bg-white border border-gray-300 rounded-lg font-medium text-xs sm:text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                <i class="fas fa-print mr-2 text-xs sm:text-sm"></i>
                <span class="hidden sm:inline">Print</span> PDF
            </a>
        </div>
    </div>

    <!-- Status Card - Responsive -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-4 sm:px-6 py-3 sm:py-4 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-4">
                <div>
                    <h3 class="text-base sm:text-lg font-semibold text-gray-900">Status Pengiriman</h3>
                    <p class="text-xs sm:text-sm text-gray-600 mt-0.5">
                        Terakhir: <?php echo e(\Carbon\Carbon::parse($transaksi->updated_at)->format('d/m/Y H:i')); ?>

                    </p>
                </div>
                <div class="mt-2 sm:mt-0">
                    <?php
                        $statusConfig = [
                            'Belum Terkirim' => [
                                'color' => 'bg-blue-100 text-blue-800',
                                'icon' => 'fas fa-clock',
                                'badge' => 'bg-blue-500'
                            ],
                            'Proses Pengiriman' => [
                                'color' => 'bg-orange-100 text-orange-800',
                                'icon' => 'fas fa-truck',
                                'badge' => 'bg-orange-500'
                            ],
                            'Terkirim' => [
                                'color' => 'bg-green-100 text-green-800',
                                'icon' => 'fas fa-check-circle',
                                'badge' => 'bg-green-500'
                            ],
                            'Ditolak' => [
                                'color' => 'bg-red-100 text-red-800',
                                'icon' => 'fas fa-times-circle',
                                'badge' => 'bg-red-500'
                            ],
                            'Batal' => [
                                'color' => 'bg-red-100 text-red-800',
                                'icon' => 'fas fa-ban',
                                'badge' => 'bg-red-500'
                            ]
                        ];
                        $config = $statusConfig[$transaksi->status] ?? $statusConfig['Belum Terkirim'];
                    ?>
                    <div class="inline-flex items-center px-3 py-1.5 sm:px-4 sm:py-2.5 rounded-lg <?php echo e($config['color']); ?> font-semibold text-sm sm:text-base">
                        <i class="<?php echo e($config['icon']); ?> mr-2 text-xs sm:text-sm"></i>
                        <span class="hidden sm:inline"><?php echo e($transaksi->status); ?></span>
                        <span class="sm:hidden"><?php echo e(substr($transaksi->status, 0, 12)); ?><?php echo e(strlen($transaksi->status) > 12 ? '...' : ''); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Progress Timeline - Responsive -->
        <div class="px-4 sm:px-6 py-4 sm:py-6">
            <div class="relative timeline">
                <?php
                    // Parse data waktu dari kolom waktu
                    $timelineData = [];
                    if ($transaksi->waktu) {
                        // Pisahkan berdasarkan <br> tag
                        $lines = explode('<br>', $transaksi->waktu);
                        
                        foreach ($lines as $line) {
                            $line = trim($line);
                            if (!empty($line)) {
                                // Hapus &nbsp; dari teks
                                $line = str_replace('&nbsp;', '', $line);
                                
                                // Cari pola: "Text (date time)"
                                if (preg_match('/(.*?)\((.*?)\)/', $line, $matches)) {
                                    $statusText = trim($matches[1]);
                                    $datetime = trim($matches[2]);
                                    
                                    // Tentukan ikon berdasarkan status
                                    $iconClass = 'fas fa-file-alt';
                                    $iconColor = 'bg-blue-500';
                                    
                                    if (strpos($statusText, 'Pengiriman Dibuat') !== false) {
                                        $iconClass = 'fas fa-file-alt';
                                        $iconColor = 'bg-blue-500';
                                    } elseif (strpos($statusText, 'Penjemputan Barang') !== false) {
                                        $iconClass = 'fas fa-box';
                                        $iconColor = 'bg-green-500';
                                    } elseif (strpos($statusText, 'Proses Pengiriman') !== false) {
                                        $iconClass = 'fas fa-truck';
                                        $iconColor = 'bg-orange-500';
                                    } elseif (strpos($statusText, 'Terkirim') !== false) {
                                        $iconClass = 'fas fa-check-circle';
                                        $iconColor = 'bg-purple-500';
                                    }
                                    
                                    $timelineData[] = [
                                        'status' => $statusText,
                                        'datetime' => $datetime,
                                        'icon' => $iconClass,
                                        'color' => $iconColor
                                    ];
                                }
                            }
                        }
                    }
                ?>
                
                <?php if(!empty($timelineData)): ?>
                    <?php $__currentLoopData = $timelineData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="timeline-item relative <?php echo e($loop->last ? '' : 'pb-4 sm:pb-8'); ?>">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 sm:w-12 sm:h-12 rounded-full <?php echo e($item['color']); ?> flex items-center justify-center relative z-10">
                                <i class="<?php echo e($item['icon']); ?> text-white text-xs sm:text-base"></i>
                            </div>
                            <div class="ml-3 sm:ml-4 flex-1">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-2">
                                    <h4 class="text-xs sm:text-sm font-semibold text-gray-900">
                                        <?php echo e(Str::limit($item['status'], 25)); ?>

                                    </h4>
                                    <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                        <?php echo e($item['datetime']); ?>

                                    </span>
                                </div>
                                <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
                                    <?php if(strpos($item['status'], 'Pengiriman Dibuat') !== false): ?>
                                    Pengiriman telah diregistrasi dalam sistem
                                    <?php elseif(strpos($item['status'], 'Penjemputan Barang') !== false): ?>
                                    Barang telah dijemput oleh kurir
                                    <?php elseif(strpos($item['status'], 'Proses Pengiriman') !== false): ?>
                                    Barang sedang dalam perjalanan ke tujuan
                                    <?php elseif(strpos($item['status'], 'Terkirim') !== false): ?>
                                    Barang telah diterima oleh penerima
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php else: ?>
                    <!-- Default timeline jika data tidak ada -->
                    <div class="timeline-item relative pb-4 sm:pb-8">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 sm:w-12 sm:h-12 rounded-full bg-blue-500 flex items-center justify-center relative z-10">
                                <i class="fas fa-file-alt text-white text-xs sm:text-base"></i>
                            </div>
                            <div class="ml-3 sm:ml-4 flex-1">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-2">
                                    <h4 class="text-xs sm:text-sm font-semibold text-gray-900">Request Dibuat</h4>
                                    <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                        <?php echo e(\Carbon\Carbon::parse($transaksi->created_at)->format('d/m/Y H:i')); ?>

                                    </span>
                                </div>
                                <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Pengiriman telah diregistrasi dalam sistem</p>
                            </div>
                        </div>
                    </div>
                    
                    <?php if($transaksi->status == 'Terkirim' || $transaksi->status == 'Proses Pengiriman' || $transaksi->status == 'Batal'): ?>
                    <div class="timeline-item relative <?php echo e($transaksi->status == 'Terkirim' && $transaksi->gambar_akhir ? 'pb-4 sm:pb-8' : ''); ?>">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 sm:w-12 sm:h-12 rounded-full <?php echo e($transaksi->status == 'Proses Pengiriman' ? 'bg-orange-500' : ($transaksi->status == 'Batal' ? 'bg-red-500' : 'bg-green-500')); ?> flex items-center justify-center relative z-10">
                                <?php if($transaksi->status == 'Proses Pengiriman'): ?>
                                <i class="fas fa-truck text-white text-xs sm:text-base"></i>
                                <?php elseif($transaksi->status == 'Batal'): ?>
                                <i class="fas fa-ban text-white text-xs sm:text-base"></i>
                                <?php else: ?>
                                <i class="fas fa-check-circle text-white text-xs sm:text-base"></i>
                                <?php endif; ?>
                            </div>
                            <div class="ml-3 sm:ml-4 flex-1">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-2">
                                    <h4 class="text-xs sm:text-sm font-semibold text-gray-900">
                                        <?php if($transaksi->status == 'Proses Pengiriman'): ?>
                                        Proses Pengiriman
                                        <?php elseif($transaksi->status == 'Batal'): ?>
                                        Dibatalkan
                                        <?php else: ?>
                                        Terkirim
                                        <?php endif; ?>
                                    </h4>
                                    <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                        <?php echo e(\Carbon\Carbon::parse($transaksi->updated_at)->format('d/m/Y H:i')); ?>

                                    </span>
                                </div>
                                <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
                                    <?php if($transaksi->status == 'Proses Pengiriman'): ?>
                                    Barang sedang dalam perjalanan ke tujuan
                                    <?php elseif($transaksi->status == 'Batal'): ?>
                                    Pengiriman telah dibatalkan
                                    <?php else: ?>
                                    Barang telah diterima oleh penerima
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($transaksi->status == 'Terkirim' && $transaksi->gambar_akhir): ?>
                    <div class="timeline-item relative">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 sm:w-12 sm:h-12 rounded-full bg-green-500 flex items-center justify-center relative z-10">
                                <i class="fas fa-camera text-white text-xs sm:text-base"></i>
                            </div>
                            <div class="ml-3 sm:ml-4 flex-1">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-2">
                                    <h4 class="text-xs sm:text-sm font-semibold text-gray-900">Bukti Terkirim</h4>
                                    <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                        Bukti foto telah diunggah
                                    </span>
                                </div>
                                <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Foto bukti barang telah diterima</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Content Grid - Responsive -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-4 sm:space-y-6">
            <!-- Shipment Details Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-4 sm:px-6 py-3 sm:py-4 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-900">Detail Pengiriman</h3>
                </div>
                <div class="p-4 sm:p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                        <div class="space-y-3 sm:space-y-4">
                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-500 mb-1">Jenis Barang</label>
                                <div class="flex items-center p-2 sm:p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <i class="fas fa-box text-gray-400 mr-2 sm:mr-3 text-sm sm:text-base"></i>
                                    <span class="font-medium text-gray-900 text-sm sm:text-base"><?php echo e(ucfirst($transaksi->nama_barang)); ?></span>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-500 mb-1">Deskripsi Barang</label>
                                <div class="p-2 sm:p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <p class="text-gray-900 text-sm sm:text-base break-words"><?php echo e($transaksi->deskripsi); ?></p>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-500 mb-1">Tanggal Request</label>
                                <div class="flex items-center p-2 sm:p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <i class="far fa-calendar text-gray-400 mr-2 sm:mr-3 text-sm sm:text-base"></i>
                                    <span class="font-medium text-gray-900 text-sm sm:text-base"><?php echo e(\Carbon\Carbon::parse($transaksi->created_at)->format('d/m/Y H:i')); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-3 sm:space-y-4">
                            <!-- Foto Barang Awal -->
                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-500 mb-1">Foto Barang (Awal)</label>
                                <?php if($transaksi->foto_barang): ?>
                                    <?php if(str_ends_with($transaksi->foto_barang, '.pdf') || str_ends_with($transaksi->foto_barang, '.doc') || str_ends_with($transaksi->foto_barang, '.docx')): ?>
                                    <div class="p-3 sm:p-4 bg-blue-50 rounded-lg border border-blue-200 image-container">
                                        <span class="image-badge">
                                            <span class="inline-flex items-center px-2 py-0.5 sm:px-2 sm:py-1 rounded-md text-xs font-semibold bg-blue-500 text-white">
                                                <i class="fas fa-file mr-1 text-xs"></i> Dokumen
                                            </span>
                                        </span>
                                        <div class="flex items-center">
                                            <i class="fas fa-file text-lg sm:text-2xl text-blue-500 mr-2 sm:mr-3"></i>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-medium text-gray-900 text-sm sm:text-base truncate" title="<?php echo e($transaksi->foto_barang); ?>">
                                                    <?php echo e(Str::limit($transaksi->foto_barang, 20)); ?>

                                                </p>
                                                <p class="text-xs sm:text-sm text-gray-500">Dokumen pendukung</p>
                                            </div>
                                        </div>
                                        <?php if($transaksi->foto_barang_url): ?>
                                            <a href="<?php echo e($transaksi->foto_barang_url); ?>" 
                                               target="_blank" 
                                               class="inline-flex items-center mt-2 text-xs sm:text-sm font-medium text-blue-600 hover:text-blue-700">
                                                <i class="fas fa-download mr-1 sm:mr-2 text-xs"></i> Download File
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <?php else: ?>
                                    <div class="relative group image-container">
                                        <span class="image-badge">
                                            <span class="inline-flex items-center px-2 py-0.5 sm:px-2 sm:py-1 rounded-md text-xs font-semibold bg-blue-500 text-white">
                                                <i class="fas fa-camera mr-1 text-xs"></i> Foto Awal
                                            </span>
                                        </span>
                                        <?php if($transaksi->foto_barang_url): ?>
                                            <img src="<?php echo e($transaksi->foto_barang_url); ?>" 
                                                 alt="Foto Barang Awal" 
                                                 class="w-full h-32 sm:h-48 object-cover rounded-lg border border-gray-300"
                                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300?text=Gambar+Tidak+Tersedia';">
                                            <a href="<?php echo e($transaksi->foto_barang_url); ?>" 
                                               target="_blank"
                                               class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-200 rounded-lg flex items-center justify-center opacity-0 group-hover:opacity-100">
                                                <span class="bg-white px-2 sm:px-4 py-1 sm:py-2 rounded-lg font-medium text-gray-900 shadow-lg text-xs sm:text-sm">
                                                    <i class="fas fa-expand mr-1 sm:mr-2 text-xs"></i> Preview
                                                </span>
                                            </a>
                                        <?php else: ?>
                                            <div class="w-full h-32 sm:h-48 bg-gray-100 rounded-lg border border-gray-300 flex items-center justify-center">
                                                <i class="fas fa-image text-gray-400 text-3xl"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1 sm:mt-2">Foto barang saat pengiriman dibuat</p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="p-4 sm:p-8 bg-gray-50 rounded-lg border border-dashed border-gray-300 text-center">
                                        <i class="fas fa-image text-gray-300 text-xl sm:text-3xl mb-1 sm:mb-2"></i>
                                        <p class="text-xs sm:text-sm text-gray-500">Tidak ada foto barang awal</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Foto Barang Akhir -->
                            <?php if($transaksi->gambar_akhir): ?>
                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-500 mb-1">Bukti Barang Terkirim</label>
                                <?php if(str_ends_with($transaksi->gambar_akhir, '.pdf') || str_ends_with($transaksi->gambar_akhir, '.doc') || str_ends_with($transaksi->gambar_akhir, '.docx')): ?>
                                <div class="p-3 sm:p-4 bg-green-50 rounded-lg border border-green-200 image-container">
                                    <span class="image-badge">
                                        <span class="inline-flex items-center px-2 py-0.5 sm:px-2 sm:py-1 rounded-md text-xs font-semibold bg-green-500 text-white">
                                            <i class="fas fa-file-check mr-1 text-xs"></i> Bukti
                                        </span>
                                    </span>
                                    <div class="flex items-center">
                                        <i class="fas fa-file text-lg sm:text-2xl text-green-500 mr-2 sm:mr-3"></i>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-gray-900 text-sm sm:text-base truncate" title="<?php echo e($transaksi->gambar_akhir); ?>">
                                                <?php echo e(Str::limit($transaksi->gambar_akhir, 20)); ?>

                                            </p>
                                            <p class="text-xs sm:text-sm text-gray-500">Bukti pengiriman dokumen</p>
                                        </div>
                                    </div>
                                    <?php if($transaksi->gambar_akhir_url): ?>
                                        <a href="<?php echo e($transaksi->gambar_akhir_url); ?>" 
                                           target="_blank" 
                                           class="inline-flex items-center mt-2 text-xs sm:text-sm font-medium text-green-600 hover:text-green-700">
                                            <i class="fas fa-download mr-1 sm:mr-2 text-xs"></i> Download Bukti
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <div class="relative group image-container">
                                    <span class="image-badge">
                                        <span class="inline-flex items-center px-2 py-0.5 sm:px-2 sm:py-1 rounded-md text-xs font-semibold bg-green-500 text-white">
                                            <i class="fas fa-check-circle mr-1 text-xs"></i> Bukti
                                        </span>
                                    </span>
                                    <?php if($transaksi->gambar_akhir_url): ?>
                                        <img src="<?php echo e($transaksi->gambar_akhir_url); ?>" 
                                             alt="Bukti Barang Terkirim" 
                                             class="w-full h-32 sm:h-48 object-cover rounded-lg border border-gray-300"
                                             onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300?text=Bukti+Tidak+Tersedia';">
                                        <a href="<?php echo e($transaksi->gambar_akhir_url); ?>" 
                                           target="_blank"
                                           class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-200 rounded-lg flex items-center justify-center opacity-0 group-hover:opacity-100">
                                            <span class="bg-white px-2 sm:px-4 py-1 sm:py-2 rounded-lg font-medium text-gray-900 shadow-lg text-xs sm:text-sm">
                                                <i class="fas fa-expand mr-1 sm:mr-2 text-xs"></i> Preview
                                            </span>
                                        </a>
                                    <?php else: ?>
                                        <div class="w-full h-32 sm:h-48 bg-gray-100 rounded-lg border border-gray-300 flex items-center justify-center">
                                            <i class="fas fa-camera text-gray-400 text-3xl"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between mt-1 sm:mt-2 gap-1 sm:gap-0">
                                    <p class="text-xs text-gray-500">Foto bukti barang telah diterima</p>
                                    <?php if($transaksi->gambar_akhir_url): ?>
                                        <a href="<?php echo e($transaksi->gambar_akhir_url); ?>" 
                                           target="_blank" 
                                           class="inline-flex items-center text-xs font-medium text-green-600 hover:text-green-700">
                                            <i class="fas fa-external-link-alt mr-1 text-xs"></i> Buka
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php elseif($transaksi->status == 'Terkirim'): ?>
                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-500 mb-1">Bukti Barang Terkirim</label>
                                <div class="p-4 sm:p-8 bg-yellow-50 rounded-lg border border-dashed border-yellow-300 text-center">
                                    <i class="fas fa-exclamation-triangle text-yellow-400 text-lg sm:text-3xl mb-1 sm:mb-2"></i>
                                    <p class="text-xs sm:text-sm text-yellow-700 font-medium">Menunggu bukti pengiriman</p>
                                    <p class="text-xs text-yellow-600 mt-0.5">Kurir belum mengunggah bukti</p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Address Information - Responsive -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                <!-- Origin Address -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 bg-gradient-to-r from-blue-50 to-white border-b border-gray-200">
                        <h3 class="text-sm sm:text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-map-marker-alt text-blue-500 mr-2 text-sm sm:text-base"></i>
                            <span class="hidden sm:inline">Alamat Asal</span>
                            <span class="sm:hidden">Asal</span>
                        </h3>
                    </div>
                    <div class="p-3 sm:p-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                    <i class="fas fa-location-arrow text-blue-600 text-xs sm:text-sm"></i>
                                </div>
                            </div>
                            <div class="ml-3 sm:ml-4 flex-1">
                                <p class="text-xs sm:text-sm text-gray-900 break-words"><?php echo e($transaksi->alamat_asal); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Destination Address -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 bg-gradient-to-r from-green-50 to-white border-b border-gray-200">
                        <h3 class="text-sm sm:text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-flag-checkered text-green-500 mr-2 text-sm sm:text-base"></i>
                            <span class="hidden sm:inline">Alamat Tujuan</span>
                            <span class="sm:hidden">Tujuan</span>
                        </h3>
                    </div>
                    <div class="p-3 sm:p-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-green-100 flex items-center justify-center">
                                    <i class="fas fa-map-marker text-green-600 text-xs sm:text-sm"></i>
                                </div>
                            </div>
                            <div class="ml-3 sm:ml-4 flex-1">
                                <p class="text-xs sm:text-sm text-gray-900 break-words"><?php echo e($transaksi->alamat_tujuan); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-4 sm:space-y-6">
            <?php if($pengirim): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-4 sm:px-6 py-3 sm:py-4 bg-gradient-to-r from-indigo-50 to-white border-b border-gray-200">
                    <h3 class="text-sm sm:text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-user-circle text-indigo-500 mr-2"></i>
                        Pengirim
                    </h3>
                </div>

                <div class="p-3 sm:p-6 space-y-3 sm:space-y-4">
                    <div class="flex items-center">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                            <i class="fas fa-user text-indigo-600 text-xs sm:text-sm"></i>
                        </div>
                        <div class="ml-3 min-w-0">
                            <p class="text-xs sm:text-sm text-gray-500">Nama Pengirim</p>
                            <p class="font-semibold text-gray-900 text-sm sm:text-base truncate">
                                <?php echo e($pengirim->nama_pelanggan); ?>

                            </p>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                            <i class="fas fa-phone text-indigo-600 text-xs sm:text-sm"></i>
                        </div>
                        <div class="ml-3 min-w-0">
                            <p class="text-xs sm:text-sm text-gray-500">No. HP Pengirim</p>
                            <p class="font-semibold text-gray-900 text-sm sm:text-base">
                                <?php echo e($pengirim->no_hp_pelanggan ?? '-'); ?>

                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recipient Information -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-4 sm:px-6 py-3 sm:py-4 bg-gradient-to-r from-purple-50 to-white border-b border-gray-200">
                    <h3 class="text-sm sm:text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-user text-purple-500 mr-2 text-sm sm:text-base"></i>
                        Penerima
                    </h3>
                </div>
                <div class="p-3 sm:p-6">
                    <div class="space-y-3 sm:space-y-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-purple-100 flex items-center justify-center">
                                    <i class="fas fa-user-tag text-purple-600 text-xs sm:text-sm"></i>
                                </div>
                            </div>
                            <div class="ml-3 sm:ml-4 min-w-0">
                                <p class="text-xs sm:text-sm font-medium text-gray-500">Nama Penerima</p>
                                <p class="font-semibold text-gray-900 text-sm sm:text-base truncate" title="<?php echo e($transaksi->penerima); ?>">
                                    <?php echo e($transaksi->penerima); ?>

                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-purple-100 flex items-center justify-center">
                                    <i class="fas fa-phone text-purple-600 text-xs sm:text-sm"></i>
                                </div>
                            </div>
                            <div class="ml-3 sm:ml-4 min-w-0">
                                <p class="text-xs sm:text-sm font-medium text-gray-500">Nomor Telepon</p>
                                <p class="font-semibold text-gray-900 text-sm sm:text-base truncate" title="<?php echo e($transaksi->no_hp_penerima); ?>">
                                    <?php echo e($transaksi->no_hp_penerima); ?>

                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-4 sm:px-6 py-3 sm:py-4 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                    <h3 class="text-sm sm:text-lg font-semibold text-gray-900">Info Messenger</h3>
                </div>
                <div class="p-3 sm:p-6">
                    <div class="space-y-3 sm:space-y-4">
                        <!-- Kurir Information -->
                        <?php if($transaksi->kurir > 0): ?>
                            <?php if($kurir): ?>
                            <div class="bg-yellow-50 rounded-lg border border-yellow-200 p-2 sm:p-4">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 sm:w-12 sm:h-12 rounded-full bg-yellow-100 flex items-center justify-center">
                                            <i class="fas fa-shipping-fast text-yellow-600 text-xs sm:text-sm"></i>
                                        </div>
                                    </div>
                                    <div class="ml-2 sm:ml-4 flex-1 min-w-0">
                                        <h4 class="text-xs sm:text-sm font-semibold text-gray-900 mb-1">Kurir Penanggung Jawab</h4>
                                        
                                        <div class="mt-1 sm:mt-2">
                                            <div class="pb-2 border-b border-gray-100">
                                                <p class="text-xs font-medium text-gray-500">Nama Kurir</p>
                                                <p class="font-semibold text-gray-900 text-sm sm:text-base truncate" 
                                                title="<?php echo e($kurir->nama_pelanggan); ?>">
                                                    <?php echo e(Str::limit($kurir->nama_pelanggan, 15)); ?>

                                                </p>
                                            </div>
                                            
                                            <?php if($kurir->no_hp_pelanggan): ?>
                                            <div class="pt-2">
                                                <p class="text-xs font-medium text-gray-500">No. HP</p>
                                                <p class="font-semibold text-gray-900 text-sm sm:text-base truncate" 
                                                title="<?php echo e($kurir->no_hp_pelanggan); ?>">
                                                    <?php echo e($kurir->no_hp_pelanggan); ?>

                                                </p>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="bg-gray-50 rounded-lg border border-gray-300 p-2 sm:p-4">
                                <div class="flex items-center">
                                    <i class="fas fa-user-tie text-gray-400 mr-2 sm:mr-3 text-sm sm:text-base"></i>
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-900 text-sm sm:text-base truncate">Kurir ID: <?php echo e($transaksi->kurir); ?></p>
                                        <p class="text-xs text-gray-500">Data kurir tidak ditemukan</p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                        <div class="bg-gray-50 rounded-lg border border-gray-300 p-2 sm:p-4">
                            <div class="flex items-center">
                                <i class="fas fa-user-clock text-gray-400 mr-2 sm:mr-3 text-sm sm:text-base"></i>
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 text-sm sm:text-base">Belum ada kurir</p>
                                    <p class="text-xs text-gray-500">Menunggu penugasan</p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($transaksi->penilaian > 0): ?>
                        <div class="bg-blue-50 rounded-lg border border-blue-200 p-2 sm:p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <i class="fas fa-star text-blue-600 text-xs sm:text-sm"></i>
                                    </div>
                                </div>
                                <div class="ml-2 sm:ml-4 min-w-0">
                                    <p class="text-xs sm:text-sm font-medium text-gray-500">Penilaian</p>
                                    <div class="flex items-center mt-0.5 sm:mt-1">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <?php if($i <= $transaksi->penilaian): ?>
                                                <i class="fas fa-star text-yellow-400 text-sm sm:text-lg"></i>
                                            <?php else: ?>
                                                <i class="far fa-star text-gray-300 text-sm sm:text-lg"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        <span class="ml-2 sm:ml-3 font-semibold text-gray-900 text-sm sm:text-base"><?php echo e($transaksi->penilaian); ?>/5</span>
                                    </div>
                                    <?php if($transaksi->note_penerima): ?>
                                    <div class="mt-1 sm:mt-2 p-2 bg-white rounded border border-gray-200">
                                        <p class="text-xs sm:text-sm text-gray-700 break-words">
                                            <i class="fas fa-comment text-blue-500 mr-1 text-xs"></i> 
                                            "<?php echo e(Str::limit($transaksi->note_penerima, 60)); ?>"
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($transaksi->note_penerima): ?>
                        <div class="bg-yellow-50 rounded-lg border border-yellow-200 p-2 sm:p-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                                        <i class="fas fa-sticky-note text-yellow-600 text-xs sm:text-sm"></i>
                                    </div>
                                </div>
                                <div class="ml-2 sm:ml-4 flex-1 min-w-0">
                                    <p class="text-xs sm:text-sm font-medium text-gray-500 mb-0.5">Catatan Khusus</p>
                                    <p class="text-gray-900 text-xs sm:text-sm break-words"><?php echo e($transaksi->note_penerima); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <?php if($transaksi->status == 'Belum Terkirim'): ?>
            <div class="bg-gradient-to-br from-red-50 to-white rounded-xl shadow-sm border border-red-100 overflow-hidden">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-red-200">
                    <h3 class="text-sm sm:text-lg font-semibold text-red-900 flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-500 mr-2 text-sm sm:text-base"></i>
                        Aksi Cepat
                    </h3>
                </div>
                <div class="p-3 sm:p-6">
                    <div class="space-y-2 sm:space-y-3">
                        <p class="text-xs sm:text-sm text-red-600">Anda dapat membatalkan request ini jika diperlukan.</p>
                        <form action="<?php echo e(route('messenger.cancel', $transaksi->no_transaksi)); ?>" method="POST" id="cancelForm">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="status" value="Batal">
                            <button type="button" 
                                    onclick="confirmCancel()"
                                    class="w-full inline-flex items-center justify-center px-3 sm:px-4 py-2 sm:py-3 bg-gradient-to-r from-red-600 to-red-700 text-white font-medium rounded-lg hover:from-red-700 hover:to-red-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200 text-sm sm:text-base">
                                <i class="fas fa-times-circle mr-1 sm:mr-2 text-xs sm:text-sm"></i>
                                Batalkan Request
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmCancel() {
    Swal.fire({
        title: 'Batalkan Request?',
        text: "Apakah Anda yakin ingin membatalkan request pengiriman ini?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Batalkan!',
        cancelButtonText: 'Tidak',
        reverseButtons: true,
        customClass: {
            container: 'text-sm sm:text-base',
            title: 'text-sm sm:text-lg',
            htmlContainer: 'text-xs sm:text-sm',
            confirmButton: 'text-xs sm:text-sm',
            cancelButton: 'text-xs sm:text-sm'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('cancelForm').submit();
        }
    });
}
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/messenger/detail.blade.php ENDPATH**/ ?>