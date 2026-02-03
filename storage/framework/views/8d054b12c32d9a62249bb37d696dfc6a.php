<?php $__env->startSection('content'); ?>
<div class="p-6 max-w-4xl mx-auto">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Buat Tiket Baru</h2>
        <p class="text-gray-600">Isi form di bawah ini untuk membuat tiket pengaduan baru</p>
    </div>
    
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <form action="<?php echo e(route('help.tiket.store')); ?>" method="POST" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            
            <div class="space-y-6">
                <!-- Judul -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Judul Pengaduan *
                    </label>
                    <input type="text" 
                           name="judul" 
                           value="<?php echo e(old('judul')); ?>"
                           required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Masukkan judul pengaduan">
                    <?php $__errorArgs = ['judul'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                
                <!-- Deskripsi -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Deskripsi Masalah *
                    </label>
                    <textarea name="deskripsi" 
                              rows="4"
                              required
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Jelaskan masalah yang Anda alami"><?php echo e(old('deskripsi')); ?></textarea>
                    <?php $__errorArgs = ['deskripsi'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                
                <!-- Kategori & Prioritas -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Kategori *
                        </label>
                        <select name="kategori_id" 
                                required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Pilih Kategori</option>
                            <?php $__currentLoopData = $kategori; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($kat->id); ?>" <?php echo e(old('kategori_id') == $kat->id ? 'selected' : ''); ?>>
                                <?php echo e($kat->nama); ?>

                            </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['kategori_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Prioritas *
                        </label>
                        <select name="prioritas" 
                                required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Pilih Prioritas</option>
                            <option value="LOW" <?php echo e(old('prioritas') == 'LOW' ? 'selected' : ''); ?>>LOW</option>
                            <option value="MEDIUM" <?php echo e(old('prioritas') == 'MEDIUM' ? 'selected' : ''); ?>>MEDIUM</option>
                            <option value="HIGH" <?php echo e(old('prioritas') == 'HIGH' ? 'selected' : ''); ?>>HIGH</option>
                            <option value="URGENT" <?php echo e(old('prioritas') == 'URGENT' ? 'selected' : ''); ?>>URGENT</option>
                        </select>
                        <?php $__errorArgs = ['prioritas'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
                
                <!-- Lampiran -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Lampiran (Opsional)
                    </label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
                        <input type="file" 
                               id="lampiran" 
                               name="lampiran[]" 
                               multiple
                               class="hidden"
                               accept="image/*,.pdf,.doc,.docx">
                        <div id="file-upload-area" class="cursor-pointer py-4">
                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                            <p class="text-sm text-gray-600">Klik untuk memilih file</p>
                            <p class="text-xs text-gray-500 mt-1">Maks: 5MB per file. Format: JPG, PNG, PDF, DOC</p>
                        </div>
                        <div id="file-list" class="mt-4 space-y-2"></div>
                    </div>
                    <?php $__errorArgs = ['lampiran.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                
                <!-- Actions -->
                <div class="pt-6 border-t border-gray-200">
                    <div class="flex flex-col sm:flex-row justify-end gap-3">
                        <a href="<?php echo e(route('help.tiket.index')); ?>" 
                           class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium rounded-lg transition">
                            Batal
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                            <i class="fas fa-paper-plane mr-2"></i> Kirim Tiket
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
document.getElementById('file-upload-area').addEventListener('click', () => {
    document.getElementById('lampiran').click();
});

document.getElementById('lampiran').addEventListener('change', function(e) {
    const fileList = document.getElementById('file-list');
    fileList.innerHTML = '';
    
    Array.from(this.files).forEach(file => {
        const div = document.createElement('div');
        div.className = 'flex items-center justify-between bg-gray-50 p-3 rounded-lg';
        div.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-file text-gray-400 mr-3"></i>
                <div>
                    <p class="text-sm text-gray-700">${file.name}</p>
                    <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                </div>
            </div>
            <button type="button" class="text-red-500 hover:text-red-700" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        fileList.appendChild(div);
    });
});
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/help/tiket/create.blade.php ENDPATH**/ ?>