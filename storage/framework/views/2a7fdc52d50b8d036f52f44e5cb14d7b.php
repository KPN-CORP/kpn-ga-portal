<?php $__env->startSection('content'); ?>
<div class="space-y-6 text-sm text-gray-800 font-sans">
    
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Help Desk Tickets</h2>
            <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full
                         text-xs font-semibold bg-blue-100 text-blue-800">
                Personal Requests Only
            </span>
        </div>

        <div class="flex gap-2 w-full sm:w-auto">
            <a href="<?php echo e(route('help.tiket.create')); ?>"
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
        <form method="GET" action="<?php echo e(route('help.tiket.index')); ?>"
              class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

            <div>
                <label class="text-sm font-medium text-gray-600">Search</label>
                <input type="text" name="search" value="<?php echo e(request('search')); ?>"
                       placeholder="Judul atau nomor tiket"
                       class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Status</label>
                <select name="status"
                        class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="OPEN" <?php echo e(request('status')=='OPEN'?'selected':''); ?>>OPEN</option>
                    <option value="ON_PROCESS" <?php echo e(request('status')=='ON_PROCESS'?'selected':''); ?>>ON PROCESS</option>
                    <option value="WAITING" <?php echo e(request('status')=='WAITING'?'selected':''); ?>>WAITING</option>
                    <option value="DONE" <?php echo e(request('status')=='DONE'?'selected':''); ?>>DONE</option>
                    <option value="CLOSED" <?php echo e(request('status')=='CLOSED'?'selected':''); ?>>CLOSED</option>
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Prioritas</label>
                <select name="prioritas"
                        class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">All Priority</option>
                    <option value="LOW" <?php echo e(request('prioritas')=='LOW'?'selected':''); ?>>LOW</option>
                    <option value="MEDIUM" <?php echo e(request('prioritas')=='MEDIUM'?'selected':''); ?>>MEDIUM</option>
                    <option value="HIGH" <?php echo e(request('prioritas')=='HIGH'?'selected':''); ?>>HIGH</option>
                    <option value="URGENT" <?php echo e(request('prioritas')=='URGENT'?'selected':''); ?>>URGENT</option>
                </select>
            </div>

            <div class="lg:col-span-3 flex flex-col sm:flex-row gap-2 justify-end">
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700">
                    Apply
                </button>
                <a href="<?php echo e(route('help.tiket.index')); ?>"
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-300 text-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    
    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">No. Tiket</th>
                    <th class="px-4 py-3 text-left">Judul</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Kategori</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Prioritas</th>
                    <th class="px-4 py-3 text-left hidden sm:table-cell">Tanggal</th>
                    <th class="px-4 py-3 text-left">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php $__empty_1 = true; $__currentLoopData = $tiket; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-50">
                    
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900"><?php echo e($item->nomor_tiket); ?></div>
                        <div class="text-xs text-gray-500 sm:hidden">
                            <?php echo e($item->created_at->format('d/m/Y')); ?>

                        </div>
                    </td>

                    
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900"><?php echo e(Str::limit($item->judul, 40)); ?></div>
                        <?php if($item->deskripsi): ?>
                        <div class="text-xs text-gray-500 sm:hidden mt-1">
                            <?php echo e(Str::limit(strip_tags($item->deskripsi), 30)); ?>

                        </div>
                        <?php endif; ?>
                    </td>

                    
                    <td class="px-4 py-3 hidden md:table-cell capitalize">
                        <?php echo e($item->kategori->nama ?? '-'); ?>

                    </td>

                    
                    <td class="px-4 py-3">
                        <?php
                            $statusColors = [
                                'OPEN' => 'bg-yellow-100 text-yellow-800',
                                'ON_PROCESS' => 'bg-blue-100 text-blue-800',
                                'WAITING' => 'bg-orange-100 text-orange-800',
                                'DONE' => 'bg-green-100 text-green-800',
                                'CLOSED' => 'bg-gray-100 text-gray-800'
                            ];
                        ?>
                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo e($statusColors[$item->status]); ?>">
                            <?php echo e($item->status); ?>

                        </span>
                    </td>

                    
                    <td class="px-4 py-3 hidden lg:table-cell">
                        <?php
                            $priorityColors = [
                                'URGENT' => 'bg-red-100 text-red-800',
                                'HIGH' => 'bg-orange-100 text-orange-800',
                                'MEDIUM' => 'bg-blue-100 text-blue-800',
                                'LOW' => 'bg-gray-100 text-gray-800'
                            ];
                        ?>
                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo e($priorityColors[$item->prioritas]); ?>">
                            <?php echo e($item->prioritas); ?>

                        </span>
                    </td>

                    
                    <td class="px-4 py-3 hidden sm:table-cell">
                        <?php echo e(\Carbon\Carbon::parse($item->created_at)->format('d M Y')); ?>

                    </td>

                    
                    <td class="px-4 py-3">
                        <a href="<?php echo e(route('help.tiket.show', $item)); ?>"
                           class="text-blue-600 font-semibold hover:underline">
                            Detail
                        </a>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="7" class="py-10 text-center text-gray-500">
                        <?php if(request()->hasAny(['search', 'status', 'prioritas'])): ?>
                        Data tidak ditemukan
                        <?php else: ?>
                        Belum ada tiket
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    
    <?php if($tiket->hasPages()): ?>
        <div class="mt-4">
            <?php echo e($tiket->links()); ?>

        </div>
    <?php endif; ?>
</div>


<script>
document.getElementById('toggleFilterBtn')?.addEventListener('click', () => {
    document.getElementById('filterSection').classList.toggle('hidden')
})
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/help/tiket/index.blade.php ENDPATH**/ ?>