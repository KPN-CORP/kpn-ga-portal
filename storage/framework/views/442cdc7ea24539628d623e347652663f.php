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
                    <option value="karyawan_baru">Karyawan Baru</option>
                    <option value="ganti_kartu">Ganti Kartu</option>
                    <option value="magang">Magang</option>
                </select>
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
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Kategori</th>
                    <?php if(isset($hasSpecialAccess) && $hasSpecialAccess): ?>
                        <th class="px-4 py-3 text-left hidden md:table-cell">Bisnis Unit</th>
                    <?php endif; ?>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Tanggal</th>
                    <th class="px-4 py-3 text-left">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php $__empty_1 = true; $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 hidden sm:table-cell"><?php echo e($item->nik ?? '-'); ?></td>

                    <td class="px-4 py-3">
                        <div class="font-medium"><?php echo e($item->nama); ?></div>
                        <div class="text-xs text-gray-500 sm:hidden"><?php echo e($item->nik); ?></div>
                    </td>

                    <td class="px-4 py-3 hidden lg:table-cell capitalize">
                        <?php echo e(str_replace('_',' ',$item->kategori)); ?>

                    </td>

                    <?php if(isset($hasSpecialAccess) && $hasSpecialAccess): ?>
                    <td class="px-4 py-3 hidden md:table-cell">
                        <?php echo e(optional($bisnisUnits->firstWhere('id_bisnis_unit',$item->bisnis_unit_id))->nama_bisnis_unit); ?>

                    </td>
                    <?php endif; ?>

                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                            <?php echo e($item->status=='pending'?'bg-yellow-100 text-yellow-800':
                               ($item->status=='approved'?'bg-green-100 text-green-800':
                               'bg-red-100 text-red-800')); ?>">
                            <?php echo e(ucfirst($item->status)); ?>

                        </span>
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
                    <td colspan="7" class="py-10 text-center text-gray-500">
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