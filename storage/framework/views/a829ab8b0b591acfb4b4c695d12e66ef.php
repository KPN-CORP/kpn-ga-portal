<?php $__env->startSection('title', 'Setting Access'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-6 py-6">

    <h1 class="text-2xl font-bold mb-1">Setting Akses User</h1>
    <p class="text-sm text-gray-500 mb-6">
        Ketik minimal 3 huruf untuk mencari user
    </p>

    <?php if(session('success')): ?>
        <div class="mb-4 p-3 rounded bg-green-100 text-green-700">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    
    <div class="relative mb-6">
        <input
            type="text"
            id="searchUser"
            value="<?php echo e($selectedUserName); ?>"
            placeholder="Cari nama / username..."
            class="w-full border rounded px-4 py-2 focus:ring focus:ring-blue-200"
            autocomplete="off"
        >

        <div
            id="suggestions"
            class="absolute z-10 w-full bg-white border rounded shadow hidden max-h-64 overflow-y-auto"
        ></div>
    </div>

    <?php if($username): ?>
    <form method="POST">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="username" value="<?php echo e($username); ?>">

        
        <div class="bg-white rounded shadow p-4 mb-6">
            <h2 class="font-bold text-blue-600 mb-4">Dashboard Access</h2>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <?php $__currentLoopData = $dashCols; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if(in_array($c->Field, ['id_access','username_access','bu_access'])) continue; ?>

                    <label
                        class="access-item flex items-center gap-2 p-3 rounded border cursor-pointer
                        <?php echo e(!empty($dashData->{$c->Field} ?? null)
                            ? 'bg-blue-100 border-blue-400'
                            : 'bg-gray-50 border-gray-300'); ?>"
                    >
                        <input
                            type="checkbox"
                            class="hidden access-checkbox"
                            name="dash[<?php echo e($c->Field); ?>]"
                            <?php echo e(!empty($dashData->{$c->Field} ?? null) ? 'checked' : ''); ?>

                        >

                        <span class="text-sm font-medium">
                            <?php echo e(strtoupper(str_replace('_',' ',$c->Field))); ?>

                        </span>
                    </label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>

        
        <div class="bg-white rounded shadow p-4 mb-6">
            <h2 class="font-bold text-green-600 mb-4">Menu Access</h2>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <?php $__currentLoopData = $menuCols; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if(in_array($c->Field, ['id','username'])) continue; ?>

                    <label
                        class="access-item flex items-center gap-2 p-3 rounded border cursor-pointer
                        <?php echo e(!empty($menuData->{$c->Field} ?? null)
                            ? 'bg-green-100 border-green-400'
                            : 'bg-gray-50 border-gray-300'); ?>"
                    >
                        <input
                            type="checkbox"
                            class="hidden access-checkbox"
                            name="menu[<?php echo e($c->Field); ?>]"
                            <?php echo e(!empty($menuData->{$c->Field} ?? null) ? 'checked' : ''); ?>

                        >

                        <span class="text-sm font-medium">
                            <?php echo e(strtoupper(str_replace('_',' ',$c->Field))); ?>

                        </span>
                    </label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>

        <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
            💾 Simpan Akses
        </button>
    </form>
    <?php endif; ?>
</div>


<script>
/* SEARCH USER */
const users = <?php echo json_encode($users, 15, 512) ?>;
const input = document.getElementById('searchUser');
const box = document.getElementById('suggestions');

input.addEventListener('keyup', function () {
    const q = this.value.toLowerCase();
    box.innerHTML = '';

    if (q.length < 3) {
        box.classList.add('hidden');
        return;
    }

    users.filter(u =>
        (u.nama_pelanggan + ' ' + u.username_pelanggan)
            .toLowerCase().includes(q)
    ).forEach(u => {
        const div = document.createElement('div');
        div.className = 'px-4 py-2 hover:bg-gray-100 cursor-pointer';
        div.innerHTML = `<strong>${u.nama_pelanggan}</strong> (${u.username_pelanggan})`;
        div.onclick = () => {
            window.location = '?username=' + u.username_pelanggan;
        };
        box.appendChild(div);
    });

    box.classList.remove('hidden');
});

/* CHECKBOX COLOR TOGGLE */
document.querySelectorAll('.access-item').forEach(item => {
    const checkbox = item.querySelector('.access-checkbox');

    item.addEventListener('click', () => {
        checkbox.checked = !checkbox.checked;

        if (checkbox.checked) {
            item.classList.remove('bg-gray-50','border-gray-300');
            item.classList.add(
                item.closest('.text-green-600') ? 'bg-green-100' : 'bg-blue-100'
            );
        } else {
            item.classList.remove('bg-green-100','bg-blue-100');
            item.classList.add('bg-gray-50','border-gray-300');
        }
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/setting-access/index.blade.php ENDPATH**/ ?>