<?php $__env->startSection('content'); ?>
<div class="space-y-6 text-sm text-gray-800 font-sans">

    
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Messenger</h2>
            <p class="text-xs text-gray-500">Daftar pengiriman & status</p>
            <?php if($hasAccessAll): ?>
                <span class="inline-block mt-1 px-2 py-1 bg-purple-100 text-purple-700 
                           rounded-full text-xs font-semibold">
                    <i class="fas fa-eye mr-1"></i> Akses Semua Data
                </span>
            <?php endif; ?>
        </div>

        <div class="flex gap-2 w-full sm:w-auto">
            <a href="<?php echo e(route('messenger.request')); ?>"
               class="flex-1 sm:flex-none inline-flex items-center justify-center
                      px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold
                      hover:bg-blue-700 transition">
                + New Request
            </a>

            <button id="toggleFilterBtn"
                class="flex-1 sm:flex-none px-4 py-2 bg-gray-100 text-gray-700
                       rounded-lg text-sm font-semibold hover:bg-gray-200 transition">
                Filters
            </button>
        </div>
    </div>

    
    <div id="filterSection"
         class="bg-white border rounded-xl p-4
                <?php echo e(request()->hasAny(['search','status','date','pengirim']) ? '' : 'hidden'); ?>">
        <form method="GET" action="<?php echo e(route('messenger.index')); ?>"
              class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-<?php echo e($hasAccessAll ? '5' : '4'); ?> gap-4">

            <?php if($hasAccessAll): ?>
            <div>
                <label class="text-sm font-medium text-gray-600">Pengirim</label>
                <select name="pengirim"
                        class="mt-1 w-full border rounded-lg px-3 py-2
                               text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="all">Semua Pengirim</option>
                    <?php $__currentLoopData = $pelangganList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($p->id_pelanggan); ?>" 
                                <?php echo e(request('pengirim') == $p->id_pelanggan ? 'selected' : ''); ?>>
                            <?php echo e($p->nama_pelanggan); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <?php endif; ?>

            <div>
                <label class="text-sm font-medium text-gray-600">Search</label>
                <input type="text" name="search" value="<?php echo e(request('search')); ?>"
                       class="mt-1 w-full border rounded-lg px-3 py-2
                              text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Status</label>
                <select name="status"
                        class="mt-1 w-full border rounded-lg px-3 py-2
                               text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    <?php $__currentLoopData = ['Belum Terkirim','Proses Pengiriman','Terkirim','Ditolak','Batal']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($s); ?>" <?php echo e(request('status')==$s?'selected':''); ?>>
                            <?php echo e($s); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Tanggal</label>
                <input type="date" name="date" value="<?php echo e(request('date')); ?>"
                       class="mt-1 w-full border rounded-lg px-3 py-2
                              text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex items-end gap-2">
                <button class="w-full bg-blue-600 text-white py-2 rounded-lg
                               text-sm font-semibold hover:bg-blue-700">
                    Apply
                </button>
                <a href="<?php echo e(route('messenger.index')); ?>"
                   class="w-full bg-gray-200 py-2 rounded-lg
                          text-sm text-center font-medium">
                    Reset
                </a>
            </div>
        </form>
    </div>

    
    <div class="block sm:hidden space-y-3">
        <?php $__currentLoopData = $transaksi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

        <?php
        $badge = match($item->status){
            'Belum Terkirim'=>'bg-blue-100 text-blue-700',
            'Proses Pengiriman'=>'bg-orange-100 text-orange-700',
            'Terkirim'=>'bg-green-100 text-green-700',
            'Ditolak'=>'bg-red-100 text-red-700',
            default=>'bg-gray-100 text-gray-700'
        };
        ?>

        <div class="bg-white rounded-xl border p-4 shadow-sm">

            
            <div class="flex justify-between items-center cursor-pointer toggleCardHeader">

                
                <div>
                    <div class="text-sm font-semibold text-gray-800">
                        <?php echo e($item->no_transaksi); ?>

                    </div>
                    <div class="text-xs text-gray-500">
                        <?php echo e($item->penerima ?? '-'); ?>

                    </div>
                    <?php if($hasAccessAll): ?>
                    <div class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-user mr-1"></i><?php echo e($item->nama_pengirim ?? '-'); ?>

                    </div>
                    <?php endif; ?>
                </div>

                
                <div class="flex items-center gap-2">
                    <div class="text-right">
                        <div class="text-xs text-gray-500">
                            <?php echo e(\Carbon\Carbon::parse($item->created_at)->format('d M Y')); ?>

                        </div>
                        <span class="inline-block mt-1 px-2 py-0.5 rounded-full
                                     text-xs font-semibold <?php echo e($badge); ?>">
                            <?php echo e($item->status); ?>

                        </span>
                    </div>
                    <i class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                </div>
            </div>

            
            <div class="mt-3 hidden toggleCardContent">

                <div class="bg-gray-50/70 border border-gray-200 rounded-lg p-3 space-y-3">

                    <div>
                        <div class="text-xs text-gray-500">Jenis Barang</div>
                        <div class="text-sm font-medium text-gray-800">
                            <?php echo e($item->nama_barang ?? '-'); ?>

                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-500">Alamat Asal</div>
                        <div class="text-sm font-medium text-gray-800 leading-relaxed">
                            <?php echo e($item->alamat_asal ?? '-'); ?>

                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-500">Alamat Tujuan</div>
                        <div class="text-sm font-medium text-gray-800 leading-relaxed">
                            <?php echo e($item->alamat_tujuan ?? '-'); ?>

                        </div>
                    </div>

                </div>

                <div class="pt-3">
                    <a href="<?php echo e(route('messenger.detail',$item->no_transaksi)); ?>"
                    class="inline-block text-blue-600 font-semibold text-sm">
                    Lihat Detail →
                    </a>
                </div>

            </div>


        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    
    <div class="hidden sm:block bg-white rounded-xl border overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-bold">No. Transaksi</th>
                    <?php if($hasAccessAll): ?>
                    <th class="px-4 py-3 text-left font-bold">Pengirim</th>
                    <?php endif; ?>
                    <th class="px-4 py-3 text-left font-bold">Jenis</th>
                    <th class="px-4 py-3 text-left font-bold">Status</th>
                    <th class="px-4 py-3 text-left font-bold">Penerima</th>
                    <th class="px-4 py-3 text-left font-bold">Alamat</th>
                    <th class="px-4 py-3 text-left font-bold">Tanggal</th>
                    <th class="px-4 py-3 text-left font-bold">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y">
                <?php $__currentLoopData = $transaksi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                <?php
                $badge = match($item->status){
                    'Belum Terkirim'=>'bg-blue-100 text-blue-700',
                    'Proses Pengiriman'=>'bg-orange-100 text-orange-700',
                    'Terkirim'=>'bg-green-100 text-green-700',
                    'Ditolak'=>'bg-red-100 text-red-700',
                    default=>'bg-gray-100 text-gray-700'
                };
                ?>

                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 font-medium"><?php echo e($item->no_transaksi); ?></td>
                    <?php if($hasAccessAll): ?>
                    <td class="px-4 py-3">
                        <div class="font-medium"><?php echo e($item->nama_pengirim); ?></div>
                        <div class="text-xs text-gray-500"><?php echo e($item->hp_pengirim ?? '-'); ?></div>
                    </td>
                    <?php endif; ?>
                    <td class="px-4 py-3"><?php echo e($item->nama_barang); ?></td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo e($badge); ?>">
                            <?php echo e($item->status); ?>

                        </span>
                    </td>
                    <td class="px-4 py-3"><?php echo e($item->penerima); ?></td>
                    <td class="px-4 py-3"><?php echo e($item->alamat_tujuan); ?></td>
                    <td class="px-4 py-3">
                        <?php echo e(\Carbon\Carbon::parse($item->created_at)->format('d M Y')); ?>

                    </td>
                    <td class="px-4 py-3">
                        <a href="<?php echo e(route('messenger.detail',$item->no_transaksi)); ?>"
                           class="text-blue-600 font-semibold hover:underline">
                            Detail
                        </a>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>

    
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between
                gap-3 text-sm text-gray-600">
        <div>
            Showing <?php echo e($transaksi->firstItem()); ?> – <?php echo e($transaksi->lastItem()); ?>

            of <?php echo e($transaksi->total()); ?>

        </div>
        <?php echo e($transaksi->links()); ?>

    </div>

</div>


<script>
document.getElementById('toggleFilterBtn')?.addEventListener('click', () => {
    document.getElementById('filterSection').classList.toggle('hidden')
})

document.querySelectorAll('.toggleCardHeader').forEach(header => {
    header.addEventListener('click', () => {
        const content = header.nextElementSibling
        const icon = header.querySelector('i')

        content.classList.toggle('hidden')
        icon.classList.toggle('rotate-180')
    })
})
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/messenger/messenger.blade.php ENDPATH**/ ?>