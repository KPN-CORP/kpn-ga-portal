<?php $__env->startSection('content'); ?>
<div class="bg-white shadow rounded-lg p-6">
    <h2 class="text-2xl font-semibold mb-4 text-left">Detail Request ID Card</h2>
    
    <?php if(session('success')): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>
    
    <?php if(session('error')): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?>
    
    <div class="flex flex-col md:flex-row gap-8">
        <!-- Bagian Foto & Bukti Bayar (Kiri) -->
        <div class="md:w-1/3 flex flex-col items-center space-y-6">
            <?php if($data->foto && !in_array($data->kategori, ['magang', 'magang_extend'])): ?>
                <div class="w-full">
                    <p class="text-sm font-medium text-gray-600 mb-3">Foto</p>
                    <div class="bg-gray-100 rounded-lg p-3 shadow-inner">
                        <div class="relative w-full" style="padding-bottom: 150%;">
                            <?php
                                $fotoUrl = route('idcard.photo', $data->foto);
                            ?>
                            <img src="<?php echo e($fotoUrl); ?>" 
                                 class="absolute inset-0 w-full h-full object-contain rounded-md shadow"
                                 alt="Foto ID Card"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x600?text=Foto+Tidak+Ditemukan';">
                        </div>
                        <div class="mt-3 text-center space-y-2">
                            <a href="<?php echo e($fotoUrl); ?>" 
                               download="foto_idcard_<?php echo e($data->nama); ?>.jpg"
                               class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm font-medium">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Download Foto
                            </a>
                            <p class="text-xs text-gray-500">
                                File: <?php echo e($data->foto); ?><br>
                                Format: 4x6 (600x400 pixels)
                            </p>
                        </div>
                    </div>
                </div>
            <?php elseif(in_array($data->kategori, ['magang', 'magang_extend'])): ?>
                <div class="w-full">
                    <p class="text-sm font-medium text-gray-600 mb-3">Kategori Magang</p>
                    <div class="bg-gray-100 rounded-lg p-3 shadow-inner">
                        <div class="flex flex-col items-center justify-center p-6">
                            <?php if($data->kategori == 'magang_extend'): ?>
                                <svg class="w-16 h-16 text-orange-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                                <p class="text-sm font-medium text-gray-700">Kategori: Magang Extend</p>
                                <p class="text-xs text-gray-500 mt-1">Perpanjangan magang</p>
                            <?php else: ?>
                                <svg class="w-16 h-16 text-green-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5" transform="translate(0 4)"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5" transform="translate(0 8)"/>
                                </svg>
                                <p class="text-sm font-medium text-gray-700">Kategori: Magang</p>
                                <p class="text-xs text-gray-500 mt-1">Tidak memerlukan foto</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if($data->bukti_bayar && $data->kategori == 'ganti_kartu'): ?>
                <div class="w-full">
                    <p class="text-sm font-medium text-gray-600 mb-3">Bukti Bayar/Rusak</p>
                    <div class="bg-gray-100 rounded-lg p-3 shadow-inner">
                        <?php
                            $buktiUrl = route('idcard.photo', $data->bukti_bayar);
                            $isPdf = pathinfo($data->bukti_bayar, PATHINFO_EXTENSION) == 'pdf';
                        ?>
                        
                        <div class="relative w-full" style="padding-bottom: 150%;">
                            <?php if($isPdf): ?>
                                <div class="absolute inset-0 flex flex-col items-center justify-center p-4 bg-white rounded-md">
                                    <svg class="w-16 h-16 text-red-500 mb-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="text-sm font-medium text-gray-700">File PDF</p>
                                </div>
                            <?php else: ?>
                                <img src="<?php echo e($buktiUrl); ?>" 
                                     class="absolute inset-0 w-full h-full object-contain rounded-md shadow"
                                     alt="Bukti Bayar"
                                     onerror="this.onerror=null; this.src='https://via.placeholder.com/400x600?text=File+Tidak+Ditemukan';">
                            <?php endif; ?>
                        </div>
                        <div class="mt-3 text-center space-y-2">
                            <a href="<?php echo e($buktiUrl); ?>" 
                               download="bukti_bayar_<?php echo e($data->nama); ?>.<?php echo e($isPdf ? 'pdf' : 'jpg'); ?>"
                               class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm font-medium">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Download <?php echo e($isPdf ? 'PDF' : 'File'); ?>

                            </a>
                            <p class="text-xs text-gray-500">
                                File: <?php echo e($data->bukti_bayar); ?>

                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Bagian Data (Kanan) -->
        <div class="md:w-2/3">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                <!-- NIK -->
                <div class="space-y-1">
                    <p class="text-sm text-gray-500">NIK</p>
                    <p class="font-medium text-gray-800"><?php echo e($data->nik ?? '-'); ?></p>
                </div>
                
                <!-- Nama -->
                <div class="space-y-1">
                    <p class="text-sm text-gray-500">Nama</p>
                    <p class="font-medium text-gray-800"><?php echo e($data->nama ?? '-'); ?></p>
                </div>
                
                <!-- Bisnis Unit -->
                <div class="space-y-1">
                    <p class="text-sm text-gray-500">Bisnis Unit</p>
                    <p class="font-medium text-gray-800"><?php echo e($data->bisnis_unit_nama ?? '-'); ?></p>
                </div>
                
                <!-- Kategori -->
                <div class="space-y-1">
                    <p class="text-sm text-gray-500">Kategori</p>
                    <?php
                        $kategoriLabels = [
                            'karyawan_baru' => 'Karyawan Baru',
                            'karyawan_mutasi' => 'Karyawan Mutasi',
                            'ganti_kartu' => 'Ganti Kartu',
                            'magang' => 'Magang',
                            'magang_extend' => 'Magang Extend'
                        ];
                    ?>
                    <p class="font-medium text-gray-800"><?php echo e($kategoriLabels[$data->kategori] ?? ucfirst($data->kategori)); ?></p>
                </div>
                
                <!-- Status -->
                <div class="space-y-1">
                    <p class="text-sm text-gray-500">Status Request</p>
                    <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full 
                        <?php echo e($data->status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                           ($data->status == 'approved' ? 'bg-green-100 text-green-800' : 
                           'bg-red-100 text-red-800')); ?>">
                        <?php echo e(ucfirst($data->status)); ?>

                    </span>
                </div>
                
                <!-- TAMPILKAN SEMUA DATA MAGANG -->
                <?php if(in_array($data->kategori, ['magang', 'magang_extend'])): ?>
                    <!-- Nomor Kartu -->
                    <div class="space-y-1">
                        <p class="text-sm text-gray-500">Nomor Kartu</p>
                        <p class="font-medium text-gray-800 font-mono">
                            <?php if(!empty($data->nomor_kartu)): ?>
                                <?php echo e($data->nomor_kartu); ?>

                            <?php else: ?>
                                <span class="text-gray-400 italic">Belum ada</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <!-- Masa Berlaku -->
                    <div class="space-y-1">
                        <p class="text-sm text-gray-500">Masa Berlaku (Mulai)</p>
                        <p class="font-medium text-gray-800">
                            <?php if(!empty($data->masa_berlaku)): ?>
                                <?php echo e(date('d-m-Y', strtotime($data->masa_berlaku))); ?>

                            <?php else: ?>
                                <span class="text-gray-400 italic">-</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <!-- Sampai Tanggal -->
                    <div class="space-y-1">
                        <p class="text-sm text-gray-500">Sampai Tanggal (Akhir)</p>
                        <p class="font-medium text-gray-800">
                            <?php if(!empty($data->sampai_tanggal)): ?>
                                <?php echo e(date('d-m-Y', strtotime($data->sampai_tanggal))); ?>

                            <?php else: ?>
                                <span class="text-gray-400 italic">-</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <!-- Durasi Magang -->
                    <div class="space-y-1">
                        <p class="text-sm text-gray-500">Durasi Magang</p>
                        <?php
                            $durationText = '-';
                            if (!empty($data->masa_berlaku) && !empty($data->sampai_tanggal)) {
                                $start = new DateTime($data->masa_berlaku);
                                $end = new DateTime($data->sampai_tanggal);
                                $interval = $start->diff($end);
                                
                                $years = $interval->y;
                                $months = $interval->m;
                                $days = $interval->d;
                                
                                $durationParts = [];
                                if ($years > 0) {
                                    $durationParts[] = $years . ' tahun';
                                }
                                if ($months > 0) {
                                    $durationParts[] = $months . ' bulan';
                                }
                                if ($days > 0) {
                                    $durationParts[] = $days . ' hari';
                                }
                                
                                $durationText = implode(' ', $durationParts);
                                
                                // Jika hasil 0, berarti 1 hari
                                if (empty($durationText)) {
                                    $durationText = '1 hari';
                                }
                            }
                        ?>
                        <p class="font-medium text-gray-800"><?php echo e($durationText); ?></p>
                    </div>
                    
                    <!-- Status Kartu -->
                    <div class="space-y-1">
                        <p class="text-sm text-gray-500">Status Kartu</p>
                        <?php
                            $cardStatus = 'Tidak Diketahui';
                            $cardStatusClass = 'bg-gray-100 text-gray-800';
                            
                            if (!empty($data->sampai_tanggal)) {
                                $today = date('Y-m-d');
                                $expiryDate = date('Y-m-d', strtotime($data->sampai_tanggal));
                                
                                if ($today > $expiryDate) {
                                    $cardStatus = 'Expired';
                                    $cardStatusClass = 'bg-red-100 text-red-800';
                                } elseif ($today >= date('Y-m-d', strtotime($data->masa_berlaku))) {
                                    // Hitung hari tersisa
                                    $daysLeft = floor((strtotime($expiryDate) - strtotime($today)) / (60 * 60 * 24));
                                    
                                    if ($daysLeft <= 7) {
                                        $cardStatus = 'Aktif (Hampir Expired - ' . $daysLeft . ' hari lagi)';
                                        $cardStatusClass = 'bg-yellow-100 text-yellow-800';
                                    } else {
                                        $cardStatus = 'Aktif';
                                        $cardStatusClass = 'bg-green-100 text-green-800';
                                    }
                                } else {
                                    $cardStatus = 'Belum Aktif';
                                    $cardStatusClass = 'bg-blue-100 text-blue-800';
                                }
                            }
                        ?>
                        <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full <?php echo e($cardStatusClass); ?>">
                            <?php echo e($cardStatus); ?>

                        </span>
                    </div>
                    
                    <!-- Periode -->
                    <!-- <?php if(!empty($data->masa_berlaku) || !empty($data->sampai_tanggal)): ?>
                    <div class="md:col-span-2 space-y-1">
                        <p class="text-sm text-gray-500">Periode Magang</p>
                        <div class="bg-gray-50 border border-gray-200 rounded p-3">
                            <div class="flex flex-col md:flex-row items-center justify-between gap-2">
                                <div class="text-center flex-1">
                                    <p class="text-xs text-gray-500">Mulai</p>
                                    <p class="font-medium text-gray-800">
                                        <?php if(!empty($data->masa_berlaku)): ?>
                                            <?php echo e(date('d-m-Y', strtotime($data->masa_berlaku))); ?>

                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="mx-2 md:mx-4">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                    </svg>
                                </div>
                                <div class="text-center flex-1">
                                    <p class="text-xs text-gray-500">Selesai</p>
                                    <p class="font-medium text-gray-800">
                                        <?php if(!empty($data->sampai_tanggal)): ?>
                                            <?php echo e(date('d-m-Y', strtotime($data->sampai_tanggal))); ?>

                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?> -->
                <?php else: ?>
                    <!-- Untuk Non-Magang: Tanggal Join -->
                    <?php if(!empty($data->tanggal_join)): ?>
                    <div class="space-y-1">
                        <p class="text-sm text-gray-500">Tanggal Join</p>
                        <p class="font-medium text-gray-800"><?php echo e(date('d-m-Y', strtotime($data->tanggal_join))); ?></p>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <!-- Tanggal Request -->
                <div class="space-y-1">
                    <p class="text-sm text-gray-500">Tanggal Request</p>
                    <p class="font-medium text-gray-800"><?php echo e(date('d-m-Y H:i', strtotime($data->created_at))); ?></p>
                </div>
                
                <!-- Diajukan Oleh -->
                <?php if(!empty($data->user_name)): ?>
                <div class="space-y-1">
                    <p class="text-sm text-gray-500">Diajukan Oleh</p>
                    <p class="font-medium text-gray-800"><?php echo e($data->user_name); ?></p>
                </div>
                <?php endif; ?>
                
                <!-- Disetujui Oleh -->
                <?php if(!empty($data->approved_by_name)): ?>
                <div class="space-y-1">
                    <p class="text-sm text-gray-500">Disetujui Oleh</p>
                    <p class="font-medium text-gray-800"><?php echo e($data->approved_by_name); ?></p>
                    <p class="text-xs text-gray-500"><?php echo e(date('d-m-Y H:i', strtotime($data->approved_at))); ?></p>
                </div>
                <?php endif; ?>
                
                <!-- Ditolak Oleh -->
                <?php if($data->status == 'rejected' && !empty($data->rejected_by_name)): ?>
                <div class="md:col-span-2 space-y-1">
                    <p class="text-sm text-gray-500">Ditolak Oleh</p>
                    <p class="font-medium text-gray-800"><?php echo e($data->rejected_by_name); ?></p>
                    <p class="text-xs text-gray-500">
                        <?php if($data->rejected_at): ?>
                            <?php echo e(date('d-m-Y H:i', strtotime($data->rejected_at))); ?>

                        <?php endif; ?>
                    </p>
                    
                    <?php if(!empty($data->rejection_reason)): ?>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500 mb-1">Alasan Penolakan:</p>
                        <div class="bg-red-50 border border-red-200 rounded p-3">
                            <p class="text-sm text-red-800"><?php echo e($data->rejection_reason); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Keterangan -->
                <?php if(!empty($data->keterangan)): ?>
                <div class="md:col-span-2 space-y-1">
                    <p class="text-sm text-gray-500">Lantai Kerja/Keterangan</p>
                    <div class="bg-gray-50 border border-gray-200 rounded p-3">
                        <p class="font-medium text-gray-800"><?php echo e($data->keterangan); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Tombol Action untuk Approval/Reject -->
    <?php if($isPending && $canProses): ?>
    <div class="mt-8 pt-6 border-t border-gray-200">
        <h3 class="text-lg font-medium text-gray-800 mb-4">Action</h3>
        <div class="bg-gray-50 rounded-lg p-6">
            <!-- Form Approve -->
            <form action="<?php echo e(route('idcard.approve', $data->id)); ?>" method="POST" class="mb-6" id="approveForm">
            <?php echo csrf_field(); ?>
            
            <?php if(in_array($data->kategori, ['magang', 'magang_extend'])): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Nomor Kartu *
                    </label>
                    <input type="text" name="nomor_kartu" 
                        id="nomor_kartu"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                        value="<?php echo e($data->nomor_kartu ?? ''); ?>"
                        placeholder="Contoh: MAG20240115001"
                        required>
                    <p class="text-xs text-gray-500 mt-1">Wajib diisi untuk kategori Magang</p>
                    
                    <!-- Tampilkan warning jika nomor kartu sudah digunakan -->
                    <?php if($data->nomor_kartu): ?>
                        <div class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-700">
                            <p>⚠️ Nomor kartu saat ini: <strong><?php echo e($data->nomor_kartu); ?></strong></p>
                            <p class="mt-1">Isi untuk mengganti nomor kartu</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Sampai Tanggal
                    </label>
                    <input type="date" name="sampai_tanggal" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                        value="<?php echo e($data->sampai_tanggal ?? ''); ?>"
                        min="<?php echo e($data->masa_berlaku ?? ''); ?>">
                    <p class="text-xs text-gray-500 mt-1">Opsional, untuk update tanggal akhir</p>
                </div>
            </div>
            <?php endif; ?>
            
            <button type="button" 
                    onclick="confirmApprove()"
                    class="inline-flex items-center px-5 py-2.5 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Approve Request
            </button>
            <p class="text-xs text-gray-500 mt-2">Status akan berubah menjadi "Approved"</p>
        </form>
            
            <!-- Form Reject -->
            <form action="<?php echo e(route('idcard.reject', $data->id)); ?>" method="POST" id="rejectForm">
                <?php echo csrf_field(); ?>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Alasan Penolakan *
                    </label>
                    <textarea name="rejection_reason" 
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md"
                              placeholder="Masukkan alasan penolakan (minimal 5 karakter)"
                              required></textarea>
                    <p class="text-xs text-gray-500 mt-1">Wajib diisi minimal 5 karakter</p>
                </div>
                
                <button type="button" 
                        onclick="confirmReject()"
                        class="inline-flex items-center px-5 py-2.5 bg-red-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none focus:border-red-900 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Reject Request
                </button>
                <p class="text-xs text-gray-500 mt-2">Status akan berubah menjadi "Rejected"</p>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Logs Activity -->
    <?php if(isset($logs) && $logs->count() > 0): ?>
    <div class="mt-8 pt-6 border-t border-gray-200">
        <h3 class="text-lg font-medium text-gray-800 mb-4">Activity Logs</h3>
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="space-y-3">
                <?php $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="flex items-start gap-3 p-3 bg-white rounded border">
                    <div class="flex-shrink-0">
                        <?php switch($log->action):
                            case ('created'): ?>
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                </div>
                                <?php break; ?>
                            <?php case ('approved'): ?>
                                <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <?php break; ?>
                            <?php case ('rejected'): ?>
                                <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </div>
                                <?php break; ?>
                            <?php default: ?>
                                <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                        <?php endswitch; ?>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-800 capitalize"><?php echo e($log->action); ?></p>
                        <p class="text-sm text-gray-600"><?php echo e($log->notes); ?></p>
                        <div class="flex items-center gap-2 mt-1">
                            <p class="text-xs text-gray-500">by <?php echo e($log->action_by_name ?? 'System'); ?></p>
                            <span class="text-xs text-gray-400">•</span>
                            <p class="text-xs text-gray-500"><?php echo e(date('d-m-Y H:i', strtotime($log->created_at))); ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Tombol Kembali -->
    <div class="mt-6 pt-4 border-t">
        <a href="<?php echo e(route('idcard')); ?>" 
           class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke List ID Card
        </a>
    </div>
</div>

<script>
function confirmApprove() {
    const kategori = '<?php echo e($data->kategori); ?>';
    
    if (kategori === 'magang' || kategori === 'magang_extend') {
        const nomorKartu = document.querySelector('input[name="nomor_kartu"]')?.value.trim();
        if (!nomorKartu) {
            alert('Nomor kartu wajib diisi untuk kategori Magang!');
            return false;
        }
    }
    
    if (confirm('Apakah Anda yakin ingin menyetujui request ini?')) {
        document.getElementById('approveForm').submit();
    }
}

function confirmReject() {
    const reason = document.querySelector('textarea[name="rejection_reason"]').value.trim();
    if (reason.length < 5) {
        alert('Alasan penolakan minimal 5 karakter!');
        return false;
    }
    
    if (confirm('Apakah Anda yakin ingin menolak request ini?')) {
        document.getElementById('rejectForm').submit();
    }
}
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/idcard/detail.blade.php ENDPATH**/ ?>