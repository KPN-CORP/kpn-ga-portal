<?php $__env->startSection('content'); ?>
<div class="space-y-6 text-sm text-gray-800 font-sans">
    
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Proses Tiket</h2>
            <?php if($isGAAdmin): ?>
                <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full
                             text-xs font-semibold bg-green-100 text-green-800">
                    GA Admin Mode
                </span>
            <?php endif; ?>
        </div>

        <div class="flex gap-2 w-full sm:w-auto">
            <a href="<?php echo e(route('help.tiket.index')); ?>"
               class="flex-1 sm:flex-none inline-flex items-center justify-center
                      px-4 py-2 bg-blue-100 text-blue-700 rounded-lg
                      text-sm font-semibold hover:bg-blue-200 transition">
                <i class="fas fa-arrow-left mr-2"></i> Tiket Saya
            </a>

            <button id="toggleFilterBtn"
                class="flex-1 sm:flex-none px-4 py-2 bg-gray-100 text-gray-700
                       rounded-lg text-sm font-semibold hover:bg-gray-200 transition">
                Filters
            </button>
        </div>
    </div>

    
    <div id="filterSection" class="bg-white border rounded-xl p-4 hidden">
        <form method="GET" action="<?php echo e(route('help.proses.index')); ?>"
              class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

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
                    <option value="">All</option>
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
                    <option value="">All</option>
                    <option value="LOW" <?php echo e(request('prioritas')=='LOW'?'selected':''); ?>>LOW</option>
                    <option value="MEDIUM" <?php echo e(request('prioritas')=='MEDIUM'?'selected':''); ?>>MEDIUM</option>
                    <option value="HIGH" <?php echo e(request('prioritas')=='HIGH'?'selected':''); ?>>HIGH</option>
                    <option value="URGENT" <?php echo e(request('prioritas')=='URGENT'?'selected':''); ?>>URGENT</option>
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Kategori</label>
                <select name="kategori_id"
                        class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">All Categories</option>
                    <?php $__currentLoopData = $kategori; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($kat->id); ?>" <?php echo e(request('kategori_id')==$kat->id?'selected':''); ?>>
                            <?php echo e($kat->nama); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div class="lg:col-span-4 flex flex-col sm:flex-row gap-2 justify-end">
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700">
                    Apply
                </button>
                <a href="<?php echo e(route('help.proses.index')); ?>"
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-300 text-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    
    <?php if(session('success')): ?>
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center">
        <i class="fas fa-check-circle text-green-500 mr-3"></i>
        <span class="text-sm text-green-700"><?php echo e(session('success')); ?></span>
        <button class="ml-auto text-green-500 hover:text-green-700" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
    <div class="bg-red-50 border border-red-200 rounded-lg p-4 flex items-center">
        <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
        <span class="text-sm text-red-700"><?php echo e(session('error')); ?></span>
        <button class="ml-auto text-red-500 hover:text-red-700" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <?php endif; ?>

    
    <?php if($tiket->isEmpty()): ?>
    <div class="bg-white border rounded-xl p-8 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-50 rounded-full mb-4">
            <i class="fas fa-check-circle text-2xl text-blue-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-700 mb-2">Tidak ada tiket yang perlu diproses</h3>
        <p class="text-gray-500">Semua tiket sudah ditangani atau belum ada tiket yang dibuat.</p>
    </div>
    <?php else: ?>
    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">No. Tiket</th>
                    <th class="px-4 py-3 text-left">Judul</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Pelapor</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Kategori</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Prioritas</th>
                    <th class="px-4 py-3 text-left hidden sm:table-cell">Tanggal</th>
                    <th class="px-4 py-3 text-left">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php $__currentLoopData = $tiket; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="hover:bg-gray-50">
                    
                    <td class="px-4 py-3 font-medium">
                        <div class="text-blue-600"><?php echo e($item->nomor_tiket); ?></div>
                        <div class="text-xs text-gray-500 sm:hidden">
                            <?php echo e($item->created_at->format('d/m/Y')); ?>

                        </div>
                    </td>

                    
                    <td class="px-4 py-3">
                        <div class="font-medium"><?php echo e(Str::limit($item->judul, 40)); ?></div>
                        <?php if($item->deskripsi): ?>
                        <div class="text-xs text-gray-500 sm:hidden">
                            <?php echo e(Str::limit(strip_tags($item->deskripsi), 40)); ?>

                        </div>
                        <?php endif; ?>
                    </td>

                    
                    <td class="px-4 py-3 hidden md:table-cell">
                        <div class="flex items-center">
                            <?php
                                // CARI PELANGGAN
                                $pelanggan = \App\Models\Pelanggan::with('user')->find($item->pelapor_id);
                                
                                if ($pelanggan) {
                                    // AMBIL NAMA DARI USER JIKA ADA, JIKA TIDAK DARI PELANGGAN
                                    if ($pelanggan->user && $pelanggan->user->name) {
                                        $pelaporName = $pelanggan->user->name;
                                    } else {
                                        $pelaporName = $pelanggan->nama ?? "Pelanggan";
                                    }
                                } else {
                                    $pelaporName = "ID: {$item->pelapor_id}";
                                }
                                
                                $pelaporInitial = substr($pelaporName, 0, 1);
                            ?>
                            
                            <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center text-xs font-medium text-blue-700 mr-2">
                                <?php echo e($pelaporInitial); ?>

                            </div>
                            <span title="<?php echo e($pelaporName); ?>">
                                <?php echo e(Str::limit($pelaporName, 15)); ?>

                            </span>
                        </div>
                    </td>

                    
                    <td class="px-4 py-3 hidden lg:table-cell">
                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">
                            <?php echo e($item->kategori->nama ?? '-'); ?>

                        </span>
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
                        <div class="text-xs text-gray-600">
                            <?php echo e($item->created_at->format('d M Y')); ?>

                        </div>
                        <div class="text-xs text-gray-500">
                            <?php echo e($item->created_at->format('H:i')); ?>

                        </div>
                    </td>

                    
                    <td class="px-4 py-3">
                        <div class="flex flex-col sm:flex-row gap-2">
                            <a href="<?php echo e(route('help.proses.show', $item)); ?>"
                               class="text-blue-600 font-semibold hover:underline text-sm">
                                Detail
                            </a>
                            
                            <?php if($item->status === 'OPEN'): ?>
                            <form action="<?php echo e(route('help.proses.take', $item)); ?>" method="POST" class="inline">
                                <?php echo csrf_field(); ?>
                                <button type="submit" 
                                        onclick="return confirm('Ambil tiket ini untuk diproses?')"
                                        class="text-green-600 font-semibold hover:underline text-sm">
                                    Ambil
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>

    
    <?php if($tiket->hasPages()): ?>
        <div class="mt-4">
            <?php echo e($tiket->links()); ?>

        </div>
    <?php endif; ?>
    <?php endif; ?>
</div>


<script>
document.getElementById('toggleFilterBtn')?.addEventListener('click', () => {
    document.getElementById('filterSection').classList.toggle('hidden')
})

// Auto hide alerts after 5 seconds
setTimeout(() => {
    document.querySelectorAll('.bg-green-50, .bg-red-50').forEach(alert => {
        alert.style.transition = 'opacity 0.3s ease-out';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 300);
    });
}, 5000);
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/help/proses/index.blade.php ENDPATH**/ ?>