<?php $__env->startSection('content'); ?>
<div class="space-y-6 text-sm text-gray-800 font-sans">

    
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Track R – Dokumen</h2>
            <p class="text-xs text-gray-500">Daftar pengiriman dokumen & status</p>
        </div>

        <div class="flex gap-2 w-full sm:w-auto">
            <a href="/track-r/create"
               class="flex-1 sm:flex-none inline-flex items-center justify-center
                      px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold
                      hover:bg-blue-700 transition">
                + Kirim Dokumen
            </a>
        </div>
    </div>

    
    <div class="hidden sm:block bg-white rounded-xl border overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-bold">Nomor Dokumen</th>
                    <th class="px-4 py-3 text-left font-bold">Judul</th>
                    <th class="px-4 py-3 text-left font-bold">Penerima</th>
                    <th class="px-4 py-3 text-left font-bold">Status</th>
                    <th class="px-4 py-3 text-left font-bold">Aksi</th>
                </tr>
            </thead>

            <tbody class="divide-y">
                <?php $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                $badge = match(strtolower($doc->status)){
                    'draft' => 'bg-gray-100 text-gray-700',
                    'dikirim' => 'bg-blue-100 text-blue-700',
                    'diterima' => 'bg-green-100 text-green-700',
                    'ditolak' => 'bg-red-100 text-red-700',
                    'diproses' => 'bg-orange-100 text-orange-700',
                    default => 'bg-gray-100 text-gray-700'
                };
                ?>

                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 font-medium"><?php echo e($doc->nomor_dokumen); ?></td>
                    <td class="px-4 py-3"><?php echo e($doc->judul); ?></td>
                    <td class="px-4 py-3"><?php echo e($doc->penerima->name ?? '-'); ?></td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo e($badge); ?>">
                            <?php echo e(strtoupper($doc->status)); ?>

                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <a href="/track-r/<?php echo e($doc->id); ?>"
                           class="text-blue-600 font-semibold hover:underline">
                            Detail
                        </a>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>

    
    <div class="block sm:hidden space-y-3">
        <?php $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
        $badge = match(strtolower($doc->status)){
            'draft' => 'bg-gray-100 text-gray-700',
            'dikirim' => 'bg-blue-100 text-blue-700',
            'diterima' => 'bg-green-100 text-green-700',
            'ditolak' => 'bg-red-100 text-red-700',
            'diproses' => 'bg-orange-100 text-orange-700',
            default => 'bg-gray-100 text-gray-700'
        };
        ?>

        <div class="bg-white rounded-xl border p-4 shadow-sm">

            
            <div class="flex justify-between items-center cursor-pointer toggleCardHeader">

                
                <div>
                    <div class="text-sm font-semibold text-gray-800">
                        <?php echo e($doc->nomor_dokumen); ?>

                    </div>
                    <div class="text-xs text-gray-500">
                        <?php echo e($doc->judul); ?>

                    </div>
                </div>

                
                <div class="flex items-center gap-2">
                    <div class="text-right">
                        <div class="text-xs text-gray-500">
                            <?php echo e($doc->penerima->name ?? '-'); ?>

                        </div>
                        <span class="inline-block mt-1 px-2 py-0.5 rounded-full
                                     text-xs font-semibold <?php echo e($badge); ?>">
                            <?php echo e(strtoupper($doc->status)); ?>

                        </span>
                    </div>
                    <i class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                </div>
            </div>

            
            <div class="mt-3 hidden toggleCardContent">
                <div class="bg-gray-50/70 border border-gray-200 rounded-lg p-3 space-y-3">

                    <div>
                        <div class="text-xs text-gray-500">Judul Dokumen</div>
                        <div class="text-sm font-medium text-gray-800">
                            <?php echo e($doc->judul); ?>

                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-500">Penerima</div>
                        <div class="text-sm font-medium text-gray-800">
                            <?php echo e($doc->penerima->name ?? '-'); ?>

                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-500">Status</div>
                        <div class="text-sm font-medium text-gray-800">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo e($badge); ?>">
                                <?php echo e(strtoupper($doc->status)); ?>

                            </span>
                        </div>
                    </div>

                </div>

                <div class="pt-3">
                    <a href="/track-r/<?php echo e($doc->id); ?>"
                       class="inline-block text-blue-600 font-semibold text-sm">
                        Lihat Detail →
                    </a>
                </div>

            </div>

        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    
    <?php if(method_exists($documents, 'currentPage')): ?>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between
                gap-3 text-sm text-gray-600">
        <div>
            Showing <?php echo e($documents->firstItem()); ?> – <?php echo e($documents->lastItem()); ?>

            of <?php echo e($documents->total()); ?>

        </div>
        <?php echo e($documents->links()); ?>

    </div>
    <?php else: ?>
    
    <div class="text-center text-sm text-gray-500 py-4">
        Total <?php echo e($documents->count()); ?> dokumen
    </div>
    <?php endif; ?>

</div>


<script>
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
<?php echo $__env->make('layouts.app-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/track_r/index.blade.php ENDPATH**/ ?>