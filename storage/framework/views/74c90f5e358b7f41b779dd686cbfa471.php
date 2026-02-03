<?php $__env->startSection('content'); ?>
<div class="p-4 md:p-6">

    
    <div class="mb-6 md:mb-8">
        
        <div class="lg:hidden mb-4">
            <h1 class="text-xl font-bold text-gray-900">Status Apartemen Aktif</h1>
            <p class="text-gray-700 text-xs mt-1">Lihat status tinggal aktif Anda saat ini</p>
        </div>

        
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 mb-4 md:mb-6">
            
            <div class="hidden lg:flex items-center space-x-4 flex-1">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Status Apartemen Aktif</h1>
                    <p class="text-gray-700 text-sm mt-1">Lihat status tinggal aktif Anda saat ini</p>
                </div>
            </div>

            
            <div class="flex flex-wrap items-center gap-2 lg:gap-3 w-full lg:w-auto">
                <?php
                    $activeCount = $activeAssignments->count();
                    // $requestCount sudah dari controller
                ?>
                
                
                <button onclick="window.location.href='<?php echo e(route('apartemen.user.index')); ?>'" 
                       class="inline-flex items-center px-3 py-2 bg-blue-50 border border-blue-200 text-blue-700 rounded-lg transition-all hover:bg-blue-100 active:scale-95 flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium text-sm truncate">Status Aktif</span>
                    <?php if($activeCount > 0): ?>
                    <span class="ml-1 md:ml-2 bg-blue-600 text-white text-xs px-1.5 md:px-2 py-0.5 rounded-full whitespace-nowrap"><?php echo e($activeCount); ?></span>
                    <?php endif; ?>
                </button>

                
                <button onclick="window.location.href='<?php echo e(route('apartemen.user.requests')); ?>'" 
                       class="inline-flex items-center px-3 py-2 bg-gray-100 border border-gray-300 text-gray-700 rounded-lg transition-all hover:bg-gray-200 active:scale-95 flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-gray-600 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="font-medium text-sm truncate">Riwayat Permintaan</span>
                    <?php if($requestCount > 0): ?>
                    <span class="ml-1 md:ml-2 bg-gray-600 text-white text-xs px-1.5 md:px-2 py-0.5 rounded-full whitespace-nowrap"><?php echo e($requestCount); ?></span>
                    <?php endif; ?>
                </button>

                
                <button onclick="window.location.href='<?php echo e(route('apartemen.user.create')); ?>'" 
                       class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all active:scale-95 flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0 shadow-sm hover:shadow">
                    <svg class="w-4 h-4 md:w-5 md:h-5 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="font-medium text-sm truncate">Pengajuan Baru</span>
                </button>
            </div>
        </div>
    </div>

    
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        
        <div class="p-4 md:p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">Status Tinggal Aktif</h2>
                <div class="text-sm text-gray-600">
                    Total: <?php echo e($activeAssignments->count()); ?> status aktif
                </div>
            </div>
        </div>

        
        <div class="p-4 md:p-6">
            <?php if($activeAssignments->count() > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php $__currentLoopData = $activeAssignments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="border border-gray-200 rounded-xl p-5 hover:shadow-md transition-all duration-200 hover:border-blue-300">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex-1">
                                <div class="flex items-center mb-2">
                                    <div class="w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center mr-2">
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900"><?php echo e($assignment->unit->apartemen->nama_apartemen ?? 'N/A'); ?></h3>
                                </div>
                                <div class="space-y-2 pl-8">
                                    <div class="flex items-center text-sm text-gray-700">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        <span>Unit <?php echo e($assignment->unit->nomor_unit); ?></span>
                                    </div>
                                    <!-- <div class="flex items-center text-sm text-gray-700">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span>Periode: <?php echo e($assignment->periode); ?></span>
                                    </div> -->
                                    <div class="flex items-center text-sm text-gray-700">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <span>Penghuni: <?php echo e($assignment->penghuni->count()); ?> orang</span>
                                    </div>
                                </div>
                            </div>
                            <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full border border-green-200">
                                ● Aktif
                            </span>
                        </div>
                        
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <!-- <h4 class="text-sm font-medium text-gray-800 mb-3">Penghuni:</h4> -->
                            <div class="space-y-3">
                                <?php $__currentLoopData = $assignment->penghuni; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $penghuni): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition-all duration-200 border border-gray-200">
                                    <div class="flex items-start">
                                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center mr-3 flex-shrink-0 mt-1">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-1">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold text-gray-900 truncate"><?php echo e($penghuni->nama); ?></p>
                                                    <p class="text-xs text-gray-600 mt-1 font-mono"><?php echo e($penghuni->id_karyawan); ?></p>
                                                    
                                                    <?php if(isset($penghuni->no_hp) && !empty($penghuni->no_hp)): ?>
                                                        <div class="flex items-center mt-2 text-xs">
                                                            <div class="w-3 h-3 bg-blue-600 rounded-full flex items-center justify-center mr-1.5 flex-shrink-0">
                                                                <svg class="w-1.5 h-1.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                                </svg>
                                                            </div>
                                                            <span class="text-blue-700 font-medium"><?php echo e($penghuni->no_hp); ?></span>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="flex items-center mt-2 text-xs text-gray-400">
                                                            <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.928-.833-2.698 0L4.392 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                                            </svg>
                                                            <span>No HP tidak tersedia</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex flex-col items-end mt-1 sm:mt-0">
                                                    <?php if(isset($penghuni->unit_kerja) && $penghuni->unit_kerja): ?>
                                                        <span class="text-xs font-medium text-gray-700 bg-gray-100 border border-gray-300 px-2 py-0.5 rounded mb-1">
                                                            <?php echo e($penghuni->unit_kerja); ?>

                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if(isset($penghuni->gol) && $penghuni->gol): ?>
                                                        <span class="text-xs font-medium text-gray-700 bg-gray-100 border border-gray-300 px-2 py-0.5 rounded">
                                                            Gol. <?php echo e($penghuni->gol); ?>

                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                            

<?php if(isset($assignment->tanggal_mulai) && isset($assignment->tanggal_selesai)): ?>
<div class="mt-4 pt-4 border-t border-gray-100">
    <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
            <div class="text-gray-600 mb-1 text-xs font-medium">Mulai</div>
            <div class="font-semibold text-gray-900">
                <?php echo e(\Carbon\Carbon::parse($assignment->tanggal_mulai)->format('d M Y')); ?>

            </div>
        </div>
        <div>
            <div class="text-gray-600 mb-1 text-xs font-medium">Selesai</div>
            <div class="font-semibold text-gray-900">
                <?php echo e(\Carbon\Carbon::parse($assignment->tanggal_selesai)->format('d M Y')); ?>

            </div>
        </div>
        <div class="col-span-2 mt-3">
            <div class="flex items-center justify-between p-3 rounded-lg" id="sisa-waktu-<?php echo e($assignment->id); ?>">
                <div class="text-gray-700 text-sm font-medium">Sisa Waktu:</div>
                <div class="font-bold text-lg">
                    <?php
                        // PERHITUNGAN YANG LEBIH SEDERHANA DAN KONSISTEN
                        $tanggalSelesai = \Carbon\Carbon::parse($assignment->tanggal_selesai);
                        $sekarang = \Carbon\Carbon::now();
                        $cutOffTime = 12; // Jam 12:00 WIB
                        
                        // Buat tanggal selesai dengan cut-off jam 12:00
                        $tanggalSelesaiCutOff = $tanggalSelesai->copy()->setTime($cutOffTime, 0, 0);
                        
                        // Variabel
                        $sisaHari = 0;
                        $warna = '';
                        $ikon = '';
                        $teks = '';
                        $statusAktif = true;
                        
                        // 1. Cek apakah sudah lewat cut-off tanggal selesai
                        if ($sekarang > $tanggalSelesaiCutOff) {
                            // SUDAH EXPIRED
                            $jamLewat = $tanggalSelesaiCutOff->diffInHours($sekarang);
                            $hariLewat = $tanggalSelesaiCutOff->diffInDays($sekarang);
                            
                            if ($jamLewat < 24) {
                                $teks = "-" . $jamLewat . " jam";
                                $sisaHari = -1;
                            } else {
                                $teks = $hariLewat . " hari";
                                $sisaHari = -$hariLewat;
                            }
                            $warna = 'text-red-600';
                            $ikon = '🔴';
                            $statusAktif = false;
                        }
                        // 2. Cek jika hari ini adalah tanggal selesai
                        elseif ($sekarang->isSameDay($tanggalSelesai)) {
                            if ($sekarang->hour < $cutOffTime) {
                                // SEBELUM JAM 12:00 - masih aktif
                                $sisaJam = $cutOffTime - $sekarang->hour;
                                $teks = $sisaJam . " jam";
                                $warna = 'text-red-600';
                                $ikon = '⏰';
                                $sisaHari = 0;
                                $statusAktif = true;
                            } else {
                                // SETELAH JAM 12:00 - expired
                                $jamLewat = $sekarang->hour - $cutOffTime;
                                if ($jamLewat <= 0) $jamLewat = 1;
                                $teks = "-" . $jamLewat . " jam";
                                $warna = 'text-red-600';
                                $ikon = '🔴';
                                $sisaHari = -1;
                                $statusAktif = false;
                            }
                        }
                        // 3. Jika masih sebelum tanggal selesai
                        else {
                            // Hitung sisa hari dengan floor() untuk menghilangkan desimal
                            $sisaHari = floor($sekarang->startOfDay()->diffInDays($tanggalSelesai->startOfDay(), false));
                            
                            if ($sisaHari > 30) {
                                $warna = 'text-blue-600';
                                $ikon = '👍';
                                $teks = "$sisaHari hari";
                            } elseif ($sisaHari > 7) {
                                $warna = 'text-green-600';
                                $ikon = '⏳';
                                $teks = "$sisaHari hari";
                            } elseif ($sisaHari > 3) {
                                $warna = 'text-yellow-600';
                                $ikon = '⚠️';
                                $teks = "$sisaHari hari";
                            } elseif ($sisaHari > 1) {
                                $warna = 'text-orange-600';
                                $ikon = '🚨';
                                $teks = "$sisaHari hari";
                            } elseif ($sisaHari == 1) {
                                // Khusus 1 hari, cek apakah besok adalah tanggal selesai
                                $besok = $sekarang->copy()->addDay()->startOfDay();
                                if ($besok->isSameDay($tanggalSelesai)) {
                                    $warna = 'text-orange-600';
                                    $ikon = '🚨';
                                    $teks = "1 hari";
                                } else {
                                    $warna = 'text-orange-600';
                                    $ikon = '🚨';
                                    $teks = "$sisaHari hari";
                                }
                            } elseif ($sisaHari == 0) {
                                $warna = 'text-red-600';
                                $ikon = '⏰';
                                $teks = "0 hari";
                            } else {
                                // Untuk kasus negatif (seharusnya tidak terjadi di kondisi ini)
                                $warna = 'text-red-600';
                                $ikon = '🔴';
                                $teks = abs($sisaHari) . " hari";
                                $statusAktif = false;
                            }
                        }
                        
                        // Format teks untuk memastikan tidak ada desimal
                        $teks = preg_replace('/\.\d+/', '', $teks);
                    ?>
                    <span class="<?php echo e($warna); ?>"><?php echo e($teks); ?></span>
                    <span class="ml-2"><?php echo e($ikon); ?></span>
                </div>
            </div>
            
            <div class="mt-2">
                <?php
                    // Hitung untuk progress bar
                    $tanggalMulai = \Carbon\Carbon::parse($assignment->tanggal_mulai)->startOfDay();
                    $tanggalSelesai = \Carbon\Carbon::parse($assignment->tanggal_selesai)->startOfDay();
                    $sekarangTanggal = \Carbon\Carbon::now()->startOfDay();
                    
                    // Total hari
                    $totalHari = $tanggalMulai->diffInDays($tanggalSelesai);
                    
                    // Hari terlewat
                    $hariTerlewat = $tanggalMulai->diffInDays($sekarangTanggal);
                    if ($hariTerlewat < 0) $hariTerlewat = 0;
                    if ($hariTerlewat > $totalHari) $hariTerlewat = $totalHari;
                    
                    // Hari tersisa
                    $hariTersisa = $totalHari - $hariTerlewat;
                    
                    // Jika sudah expired, pastikan hari tersisa = 0
                    if (!$statusAktif && $hariTersisa > 0) {
                        $hariTersisa = 0;
                    }
                    
                    // Persentase
                    $persentase = $totalHari > 0 ? min(100, max(0, ($hariTerlewat / $totalHari) * 100)) : 100;
                    
                    // Warna progress bar
                    if ($sisaHari > 30) {
                        $barColor = 'bg-blue-500';
                    } elseif ($sisaHari > 7) {
                        $barColor = 'bg-green-500';
                    } elseif ($sisaHari > 3) {
                        $barColor = 'bg-yellow-500';
                    } elseif ($sisaHari > 0) {
                        $barColor = 'bg-orange-500';
                    } elseif ($sisaHari == 0) {
                        $barColor = 'bg-red-500';
                    } else {
                        $barColor = 'bg-red-600';
                    }
                ?>
                <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full <?php echo e($barColor); ?> rounded-full" style="width: <?php echo e($persentase); ?>%"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500 mt-1">
                    <span><?php echo e(floor($hariTerlewat)); ?> hari terlewat</span>
                    <span>
                        <?php if(!$statusAktif): ?>
                            <span class="text-red-600 font-medium">telah kadaluarsa</span>
                        <?php else: ?>
                            <?php echo e(floor($hariTersisa)); ?> hari tersisa
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

                        </div>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="flex flex-col items-center justify-center text-gray-400">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4 border border-gray-200">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">Tidak Ada Status Aktif</h3>
                        <p class="text-gray-500 mb-6 max-w-md mx-auto">
                            Anda belum memiliki permintaan apartemen yang aktif saat ini.
                            Ajukan permintaan untuk mendapatkan tempat tinggal.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <button onclick="window.location.href='<?php echo e(route('apartemen.user.create')); ?>'" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all active:scale-95 shadow-sm hover:shadow">
                                <div class="flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Buat Pengajuan Baru
                                </div>
                            </button>
                            <button onclick="window.location.href='<?php echo e(route('apartemen.user.requests')); ?>'" 
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-all active:scale-95 border border-gray-300">
                                <div class="flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Lihat Riwayat Pengajuan
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.status-badge {
    @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap;
}
.status-active {
    @apply bg-green-100 text-green-700 border border-green-200;
}
</style>

<script>
// Format nomor HP otomatis
document.addEventListener('DOMContentLoaded', function() {
    // Format semua nomor HP yang ada
    const phoneNumbers = document.querySelectorAll('.text-blue-700');
    phoneNumbers.forEach(phone => {
        const original = phone.textContent.trim();
        
        // Jika mengandung +62, format menjadi +62 xxx xxxx xxxx
        if (original.includes('+62')) {
            const numberOnly = original.replace('+62', '').replace(/\D/g, '');
            if (numberOnly.length >= 9) {
                let formatted = '';
                if (numberOnly.length === 9) {
                    formatted = numberOnly.replace(/(\d{3})(\d{3})(\d{3})/, '$1 $2 $3');
                } else if (numberOnly.length === 10) {
                    formatted = numberOnly.replace(/(\d{4})(\d{3})(\d{3})/, '$1 $2 $3');
                } else if (numberOnly.length === 11) {
                    formatted = numberOnly.replace(/(\d{4})(\d{4})(\d{3})/, '$1 $2 $3');
                } else if (numberOnly.length === 12) {
                    formatted = numberOnly.replace(/(\d{4})(\d{4})(\d{4})/, '$1 $2 $3');
                } else {
                    formatted = numberOnly.replace(/(\d{4})(\d{4})(\d{4,})/, '$1 $2 $3');
                }
                phone.textContent = '+62 ' + formatted;
            }
        }
    });
});
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/apartemen/user/index.blade.php ENDPATH**/ ?>