<?php $__env->startSection('content'); ?>
<div class="space-y-6 text-sm text-gray-800 font-sans">

    
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">ID Card</h2>

            <?php if(isset($hasSpecialAccess) && $hasSpecialAccess): ?>
                <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full
                             text-xs font-semibold bg-green-100 text-green-800">
                    Full Access Mode
                </span>
            <?php else: ?>
                <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full
                             text-xs font-semibold bg-blue-100 text-blue-800">
                    Personal Requests Only
                </span>
            <?php endif; ?>
        </div>

        <div class="flex gap-2 w-full sm:w-auto">
            <a href="<?php echo e(route('idcard.request')); ?>"
               class="flex-1 sm:flex-none inline-flex items-center justify-center
                      px-4 py-2 bg-blue-600 text-white rounded-lg
                      text-sm font-semibold hover:bg-blue-700 transition">
                + New Request
            </a>

            <button id="toggleFilterBtn"
                class="flex-1 sm:flex-none px-4 py-2 bg-gray-100 text-gray-700
                       rounded-lg text-sm font-semibold hover:bg-gray-200 transition">
                Filters
            </button>
        </div>
    </div>

    
    <div id="filterSection" class="bg-white border rounded-xl p-4 hidden">
        <form method="GET" action="<?php echo e(route('idcard')); ?>"
              class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            <div>
                <label class="text-sm font-medium text-gray-600">Search</label>
                <input type="text" name="search" value="<?php echo e(request('search')); ?>"
                       class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Status</label>
                <select name="status"
                        class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="all">All</option>
                    <option value="pending" <?php echo e(request('status')=='pending'?'selected':''); ?>>Pending</option>
                    <option value="approved" <?php echo e(request('status')=='approved'?'selected':''); ?>>Approved</option>
                    <option value="rejected" <?php echo e(request('status')=='rejected'?'selected':''); ?>>Rejected</option>
                </select>
            </div>

            <?php if(isset($hasSpecialAccess) && $hasSpecialAccess): ?>
            <div>
                <label class="text-sm font-medium text-gray-600">Bisnis Unit</label>
                <select name="bisnis_unit_id"
                        class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="all">All Units</option>
                    <?php $__currentLoopData = $bisnisUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($unit->id_bisnis_unit); ?>"
                            <?php echo e(request('bisnis_unit_id')==$unit->id_bisnis_unit?'selected':''); ?>>
                            <?php echo e($unit->nama_bisnis_unit); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <?php endif; ?>

            <div>
                <label class="text-sm font-medium text-gray-600">Kategori</label>
                <select name="kategori"
                        class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="all">All</option>
                    <option value="karyawan_baru" <?php echo e(request('kategori')=='karyawan_baru'?'selected':''); ?>>Karyawan Baru</option>
                    <option value="karyawan_mutasi" <?php echo e(request('kategori')=='karyawan_mutasi'?'selected':''); ?>>Karyawan Mutasi</option>
                    <option value="ganti_kartu" <?php echo e(request('kategori')=='ganti_kartu'?'selected':''); ?>>Ganti Kartu</option>
                    <option value="magang" <?php echo e(request('kategori')=='magang'?'selected':''); ?>>Magang</option>
                    <option value="magang_extend" <?php echo e(request('kategori')=='magang_extend'?'selected':''); ?>>Magang Extend</option>
                </select>
            </div>

            
            <div>
                <label class="text-sm font-medium text-gray-600">Periode</label>
                <select name="periode"
                        class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="all">Semua Periode</option>
                    <option value="masa_aktif" <?php echo e(request('periode')=='masa_aktif'?'selected':''); ?>>Masa Aktif</option>
                    <option value="masa_tidak_aktif" <?php echo e(request('periode')=='masa_tidak_aktif'?'selected':''); ?>>Masa Tidak Aktif</option>
                    <option value="masa_habis_segera" <?php echo e(request('periode')=='masa_habis_segera'?'selected':''); ?>>Habis dalam 30 Hari</option>
                </select>
            </div>

            
            <div>
                <label class="text-sm font-medium text-gray-600">Nomor Kartu</label>
                <input type="text" name="nomor_kartu" value="<?php echo e(request('nomor_kartu')); ?>"
                       placeholder="Cari nomor kartu"
                       class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="lg:col-span-4 flex flex-col sm:flex-row gap-2 justify-end">
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">
                    Apply
                </button>
                <a href="<?php echo e(route('idcard')); ?>"
                   class="px-4 py-2 bg-gray-200 rounded-lg text-sm font-semibold text-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    
    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    
                    <th class="px-4 py-3 text-left hidden sm:table-cell">NIK</th>
                    <th class="px-4 py-3 text-left">Nama</th>
                    
                    <?php if(isset($hasSpecialAccess) && $hasSpecialAccess): ?>
                        <th class="px-4 py-3 text-left hidden md:table-cell">Bisnis Unit</th>
                    <?php endif; ?>
                    
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Kategori</th>
                    <th class="px-4 py-3 text-left">Periode</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left hidden xl:table-cell">No. Kartu</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Tanggal Request</th>
                    <th class="px-4 py-3 text-left">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php $__empty_1 = true; $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-50">
                    
                    <td class="px-4 py-3 hidden sm:table-cell">
                        <?php if($item->nik): ?>
                            <span class="font-mono text-sm"><?php echo e($item->nik); ?></span>
                        <?php else: ?>
                            <span class="text-gray-400">-</span>
                        <?php endif; ?>
                    </td>

                    
                    <td class="px-4 py-3">
                        <div class="font-medium"><?php echo e($item->nama); ?></div>
                        
                        <div class="text-xs text-gray-500 sm:hidden">
                            NIK: <?php echo e($item->nik ?? '-'); ?>

                        </div>
                        
                        <?php if($item->nomor_kartu): ?>
                            <div class="text-xs text-gray-500 mt-1 sm:hidden">
                                No. Kartu: <?php echo e($item->nomor_kartu); ?>

                            </div>
                        <?php endif; ?>
                    </td>

                    
                    <?php if(isset($hasSpecialAccess) && $hasSpecialAccess): ?>
                    <td class="px-4 py-3 hidden md:table-cell">
                        <?php echo e(optional($bisnisUnits->firstWhere('id_bisnis_unit',$item->bisnis_unit_id))->nama_bisnis_unit ?? '-'); ?>

                    </td>
                    <?php endif; ?>

                    
                    <td class="px-4 py-3 hidden lg:table-cell">
                        <span class="capitalize">
                            <?php echo e($kategoriLabels[$item->kategori] ?? str_replace('_', ' ', $item->kategori)); ?>

                        </span>
                    </td>

                    
                    <td class="px-4 py-3">
                        <?php if($item->masa_berlaku && $item->sampai_tanggal): ?>
                            <?php
                                $today = now();
                                $masaBerlaku = \Carbon\Carbon::parse($item->masa_berlaku);
                                $sampaiTanggal = \Carbon\Carbon::parse($item->sampai_tanggal);
                                $isActive = $today->between($masaBerlaku, $sampaiTanggal);
                                $daysLeft = $today->startOfDay()->diffInDays($sampaiTanggal->startOfDay(), false);
                            ?>
                            
                            <div class="flex flex-col">
                                <span class="text-xs text-gray-500">
                                    <?php echo e(\Carbon\Carbon::parse($item->masa_berlaku)->format('d M Y')); ?>

                                    -
                                    <?php echo e(\Carbon\Carbon::parse($item->sampai_tanggal)->format('d M Y')); ?>

                                </span>
                                
                                <?php if($isActive): ?>
                                    <?php if($daysLeft <= 30): ?>
                                        <span class="mt-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-orange-100 text-orange-800">
                                            Habis dalam <?php echo e($daysLeft); ?> hari
                                        </span>
                                    <?php else: ?>
                                        <span class="mt-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                            Aktif
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="mt-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                        Tidak Aktif
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php elseif(in_array($item->kategori, ['karyawan_baru', 'karyawan_mutasi', 'ganti_kartu'])): ?>
                            <span class="text-xs text-gray-400">Tetap</span>
                        <?php else: ?>
                            <span class="text-xs text-gray-400">-</span>
                        <?php endif; ?>
                    </td>

                    
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                            <?php echo e($item->status=='pending'?'bg-yellow-100 text-yellow-800':
                               ($item->status=='approved'?'bg-green-100 text-green-800':
                               'bg-red-100 text-red-800')); ?>">
                            <?php echo e($statusLabels[$item->status] ?? ucfirst($item->status)); ?>

                        </span>
                    </td>

                    
                    <td class="px-4 py-3 hidden xl:table-cell">
                        <?php if($item->nomor_kartu): ?>
                            <span class="font-mono text-sm bg-gray-50 px-2 py-1 rounded border">
                                <?php echo e($item->nomor_kartu); ?>

                            </span>
                        <?php else: ?>
                            <span class="text-gray-400 text-xs">-</span>
                        <?php endif; ?>
                    </td>

                    
                    <td class="px-4 py-3 hidden lg:table-cell">
                        <?php echo e(\Carbon\Carbon::parse($item->created_at)->format('d M Y')); ?>

                    </td>

                    
                    <td class="px-4 py-3">
                        <a href="<?php echo e(route('idcard.detail',$item->id)); ?>"
                           class="text-blue-600 font-semibold hover:underline">
                            Detail
                        </a>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="
                        <?php if(isset($hasSpecialAccess) && $hasSpecialAccess): ?>
                            9
                        <?php else: ?>
                            8
                        <?php endif; ?>
                    " class="py-10 text-center text-gray-500">
                        Data tidak ditemukan
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    
    <?php if($data->hasPages()): ?>
        <div class="mt-4">
            <?php echo e($data->links()); ?>

        </div>
    <?php endif; ?>
</div>


<script>
document.getElementById('toggleFilterBtn')?.addEventListener('click', () => {
    document.getElementById('filterSection').classList.toggle('hidden')
})
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/idcard/list.blade.php ENDPATH**/ ?>