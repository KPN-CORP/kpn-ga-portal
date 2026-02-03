<?php $__env->startSection('content'); ?>
<style>
    [x-cloak]{display:none!important;}
</style>

<div class="space-y-6 text-sm text-gray-800">


<div>
    <h2 class="text-xl font-semibold">Messenger</h2>
    <p class="text-xs text-gray-500">Proses Pengiriman</p>
    
    
    <?php if(isset($has_full_access) && $has_full_access): ?>
    <div class="mt-2 inline-flex items-center gap-1 px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
        <span>Akses Penuh Messenger</span>
    </div>
    <?php endif; ?>
    
    <?php if($kurir): ?>
    <div class="mt-1 text-xs text-gray-600">
        Login sebagai: <span class="font-semibold"><?php echo e($kurir->nama_pelanggan); ?></span>
        <?php if(!$has_full_access): ?>
        <span class="text-gray-400"> • Hanya melihat transaksi yang Anda tangani</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>


<div class="hidden md:block bg-white rounded-xl shadow border overflow-x-auto">

<table class="min-w-full text-sm">
<thead class="bg-slate-50 border-b">
<tr>
    <th class="px-4 py-3 text-left">No. Transaksi</th>
    <th class="px-4 py-3 text-left">Status</th>
    <th class="px-4 py-3 text-left">Tanggal</th>
    <th class="px-4 py-3 text-left">Ditangani Oleh</th>
    <th class="px-4 py-3 text-right">Aksi</th>
</tr>
</thead>

<?php $__currentLoopData = $transaksi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<?php
    // Helper untuk mendapatkan URL file
    $getFileUrl = function($filename, $type = 'foto_barang') {
        if (!$filename) return null;
        return route('messenger.file', [
            'type' => $type,
            'filename' => $filename
        ]);
    };
    
    $fotoBarangUrl = $getFileUrl($row->foto_barang, 'foto_barang');
    $gambarAkhirUrl = $getFileUrl($row->gambar_akhir, 'gambar_akhir');
?>

<tbody x-data="{ open:false, foto:false, selesai:false, bukti:false, tolak:false }" class="border-b">

<tr>
    
    <td class="px-4 py-3 align-top">
        <p class="font-semibold text-blue-600"><?php echo e($row->no_transaksi); ?></p>
        <p class="text-xs text-slate-500">
            Penerima: <?php echo e($row->penerima); ?>

        </p>
        <?php if($row->note_penerima): ?>
        <div class="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs">
            <p class="font-semibold text-yellow-700">note_penerima:</p>
            <p class="text-yellow-600"><?php echo e($row->note_penerima); ?></p>
        </div>
        <?php endif; ?>
    </td>

    
    <td class="px-4 py-3 align-top">
        <span class="px-2 py-1 rounded-full text-xs
        <?php if($row->status=='Proses Pengiriman'): ?> bg-blue-100 text-blue-700
        <?php elseif($row->status=='Pengiriman Dibuat'): ?> bg-slate-100 text-slate-700
        <?php elseif($row->status=='Terkirim'): ?> bg-green-100 text-green-700
        <?php elseif($row->status=='Belum Terkirim'): ?> bg-yellow-100 text-yellow-700
        <?php elseif($row->status=='Ditolak'): ?> bg-red-100 text-red-700
        <?php else: ?> bg-slate-200 text-slate-700 <?php endif; ?>">
            <?php echo e($row->status); ?>

        </span>
    </td>

    
    <td class="px-4 py-3 align-top text-slate-500 text-xs">
        <?php echo e(\Carbon\Carbon::parse($row->created_at)->format('d M Y H:i')); ?>

    </td>

    
    <td class="px-4 py-3 align-top">
        <?php if($row->nama_kurir): ?>
        <p class="text-xs font-medium text-green-600"><?php echo e($row->nama_kurir); ?></p>
        <?php else: ?>
        <p class="text-xs text-gray-400">Belum ada kurir</p>
        <?php endif; ?>
        <?php if($row->kurir == $kurir_id): ?>
        <span class="inline-block px-2 py-0.5 bg-blue-50 text-blue-600 text-[10px] rounded mt-1">
            Anda
        </span>
        <?php endif; ?>
    </td>

    
    <td class="px-4 py-3 align-top text-right space-x-2 whitespace-nowrap">
        
        <?php
            $canAccess = $has_full_access || $row->kurir == 0 || $row->kurir == $kurir_id;
        ?>
        
        <?php if($canAccess): ?>
            <?php if(in_array($row->status,['Belum Terkirim','Pengiriman Dibuat'])): ?>
            <div class="inline-flex gap-2">
                <form method="POST" action="<?php echo e(route('messenger.antar', $row->no_transaksi)); ?>" class="inline">
                    <?php echo csrf_field(); ?>
                    <button type="submit" 
                            class="px-3 py-1 border border-green-600 text-green-600 rounded hover:bg-green-50 transition">
                        Antar
                    </button>
                </form>
                
                <button @click="tolak=true"
                        class="px-3 py-1 border border-red-600 text-red-600 rounded hover:bg-red-50 transition">
                    Tolak
                </button>
            </div>
            <?php endif; ?>

            <?php if($row->status=='Proses Pengiriman' && $row->kurir == $kurir_id): ?>
            <div class="inline-flex gap-2">
                <button @click="selesai=true"
                        class="px-3 py-1 border border-blue-600 text-blue-600 rounded hover:bg-blue-50 transition">
                    Selesaikan
                </button>
                
                <button @click="tolak=true"
                        class="px-3 py-1 border border-red-600 text-red-600 rounded hover:bg-red-50 transition">
                    Tolak
                </button>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <span class="text-xs text-gray-400">Tidak diizinkan</span>
        <?php endif; ?>

        <button @click="open=!open"
                class="px-3 py-1 bg-slate-100 rounded text-slate-700 hover:bg-slate-200 transition">
            Lihat Detail
        </button>
    </td>
</tr>


<tr x-show="open" x-cloak>
<td colspan="5" class="bg-slate-50 px-6 py-4">

<div class="grid grid-cols-3 gap-6 text-xs">

    <div>
        <p class="font-semibold mb-1">Pengirim</p>
        <p><?php echo e($row->nama_pengirim ?? '-'); ?></p>
        <p class="text-slate-500"><?php echo e($row->hp_pengirim ?? '-'); ?></p>
    </div>

    <div>
        <p class="font-semibold mb-1">Ditangani Oleh</p>
        <?php if($row->nama_kurir): ?>
        <p class="text-green-600"><?php echo e($row->nama_kurir); ?></p>
        <?php else: ?>
        <p class="text-gray-400">Belum ada kurir</p>
        <?php endif; ?>
        <?php if($row->kurir == $kurir_id): ?>
        <p class="text-xs text-blue-600 mt-1">✓ Anda menangani pengiriman ini</p>
        <?php endif; ?>
    </div>

    <div>
        <p class="font-semibold mb-1">Status</p>
        <span class="px-2 py-1 rounded-full text-xs
            <?php if($row->status=='Proses Pengiriman'): ?> bg-blue-100 text-blue-700
            <?php elseif($row->status=='Terkirim'): ?> bg-green-100 text-green-700
            <?php elseif($row->status=='Belum Terkirim'): ?> bg-yellow-100 text-yellow-700
            <?php elseif($row->status=='Ditolak'): ?> bg-red-100 text-red-700
            <?php else: ?> bg-slate-200 text-slate-700 <?php endif; ?>">
            <?php echo e($row->status); ?>

        </span>
    </div>

    <div>
        <p class="font-semibold mb-1">Alamat Asal</p>
        <p><?php echo e($row->alamat_asal); ?></p>
    </div>

    <div>
        <p class="font-semibold mb-1">Alamat Tujuan</p>
        <p><?php echo e($row->alamat_tujuan); ?></p>
    </div>

    <div class="col-span-3">
        <p class="font-semibold mb-2">Riwayat Pengiriman</p>
        <?php $__currentLoopData = explode('<br>', strip_tags($row->waktu,'<br>')); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if(trim($item)): ?>
            <div class="flex gap-2 mb-1">
                <span class="w-2 h-2 mt-1 bg-blue-500 rounded-full"></span>
                <span><?php echo $item; ?></span>
            </div>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <?php if($row->foto_barang): ?>
    <div>
        <p class="font-semibold mb-1">Foto Barang</p>
        <?php if($fotoBarangUrl): ?>
        <img src="<?php echo e($fotoBarangUrl); ?>"
             @click="foto=true"
             class="w-24 h-24 rounded border cursor-pointer hover:opacity-90 transition object-cover"
             onerror="this.onerror=null; this.src='https://via.placeholder.com/100x100?text=Tidak+Tersedia';">
        <?php else: ?>
        <div class="w-24 h-24 rounded border bg-gray-100 flex items-center justify-center">
            <span class="text-xs text-gray-500">File tidak tersedia</span>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if($row->gambar_akhir): ?>
    <div>
        <p class="font-semibold mb-1">Bukti Terkirim</p>
        <?php if($gambarAkhirUrl): ?>
        <img src="<?php echo e($gambarAkhirUrl); ?>"
             @click="bukti=true"
             class="w-24 h-24 rounded border cursor-pointer hover:opacity-90 transition object-cover"
             onerror="this.onerror=null; this.src='https://via.placeholder.com/100x100?text=Tidak+Tersedia';">
        <?php else: ?>
        <div class="w-24 h-24 rounded border bg-gray-100 flex items-center justify-center">
            <span class="text-xs text-gray-500">File tidak tersedia</span>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>
</td>
</tr>


<?php if($row->foto_barang && $fotoBarangUrl): ?>
<tr x-show="foto" x-cloak>
<td colspan="5">
<div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50"
     @click="foto=false">
<img src="<?php echo e($fotoBarangUrl); ?>"
     class="max-h-[90vh] max-w-[90vw] rounded-xl object-contain"
     onerror="this.onerror=null; this.src='https://via.placeholder.com/400x400?text=Tidak+Tersedia';">
</div>
</td>
</tr>
<?php endif; ?>


<?php if($row->gambar_akhir && $gambarAkhirUrl): ?>
<tr x-show="bukti" x-cloak>
<td colspan="5">
<div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50"
     @click="bukti=false">
<img src="<?php echo e($gambarAkhirUrl); ?>"
     class="max-h-[90vh] max-w-[90vw] rounded-xl object-contain"
     onerror="this.onerror=null; this.src='https://via.placeholder.com/400x400?text=Tidak+Tersedia';">
</div>
</td>
</tr>
<?php endif; ?>


<?php if($row->status=='Proses Pengiriman' && $row->kurir == $kurir_id): ?>
<tr x-show="selesai" x-cloak>
<td colspan="5">
<div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
<div class="bg-white p-6 rounded-xl w-full max-w-md" @click.outside="selesai=false">
    <h3 class="font-semibold mb-4 text-lg">Upload Bukti Terkirim</h3>
    
    <form method="POST" 
          action="<?php echo e(route('messenger.selesaikan', $row->no_transaksi)); ?>" 
          enctype="multipart/form-data" 
          class="space-y-4">
        <?php echo csrf_field(); ?>
        
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-500 transition-colors cursor-pointer"
             onclick="document.getElementById('file-desktop-<?php echo e($row->no_transaksi); ?>').click()">
            <div class="space-y-2">
                <svg class="w-10 h-10 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                <p class="text-sm text-gray-600">
                    <span class="font-semibold">Klik untuk upload</span>
                </p>
                <p class="text-xs text-gray-500">
                    Format: JPG, PNG (Maksimal: 5MB)
                </p>
            </div>
            <input type="file" 
                   name="gambar_akhir" 
                   id="file-desktop-<?php echo e($row->no_transaksi); ?>" 
                   class="hidden" 
                   accept="image/*" 
                   required
                   onchange="previewDesktopImage(event, '<?php echo e($row->no_transaksi); ?>')">
        </div>
        
        
        <div id="preview-desktop-<?php echo e($row->no_transaksi); ?>" class="hidden">
            <div class="relative">
                <img id="preview-image-desktop-<?php echo e($row->no_transaksi); ?>" 
                     class="w-full h-48 object-cover rounded-lg border">
                <button type="button" 
                        onclick="clearDesktopPreview('<?php echo e($row->no_transaksi); ?>')" 
                        class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center">
                    ✕
                </button>
            </div>
            <div id="file-info-desktop-<?php echo e($row->no_transaksi); ?>" class="mt-2 text-xs text-gray-600"></div>
        </div>
        
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                note_penerima (Opsional)
            </label>
            <textarea name="note_penerima" 
                      rows="3" 
                      class="w-full border border-gray-300 rounded-lg p-2 text-sm"
                      placeholder="Tambahkan note_penerima atau catatan tentang pengiriman..."></textarea>
        </div>
        
        
        <div class="flex justify-end gap-2 pt-4">
            <button type="button" @click="selesai=false" 
                    class="px-4 py-2 border rounded-lg hover:bg-gray-50 transition">
                Batal
            </button>
            
            <button type="submit" 
                    id="submit-btn-desktop-<?php echo e($row->no_transaksi); ?>"
                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                Simpan Bukti
            </button>
        </div>
    </form>
</div>
</div>
</td>
</tr>
<?php endif; ?>


<?php if($canAccess && in_array($row->status,['Belum Terkirim','Pengiriman Dibuat','Proses Pengiriman'])): ?>
<tr x-show="tolak" x-cloak>
<td colspan="5">
<div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
<div class="bg-white p-6 rounded-xl w-full max-w-md" @click.outside="tolak=false">
    <h3 class="font-semibold mb-4 text-lg">Tolak Pengiriman</h3>
    
    <form method="POST" 
           action="<?php echo e(route('messenger.tolak', $row->no_transaksi)); ?>" 
          class="space-y-4">
        <?php echo csrf_field(); ?>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Alasan Penolakan *
            </label>
            <textarea name="alasan_tolak" 
                      rows="4" 
                      class="w-full border border-gray-300 rounded-lg p-2 text-sm"
                      placeholder="Masukkan alasan penolakan pengiriman..."
                      required></textarea>
        </div>
        
        
        <div class="flex justify-end gap-2 pt-4">
            <button type="button" @click="tolak=false" 
                    class="px-4 py-2 border rounded-lg hover:bg-gray-50 transition">
                Batal
            </button>
            
            <button type="submit" 
                    class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                Tolak Pengiriman
            </button>
        </div>
    </form>
</div>
</div>
</td>
</tr>
<?php endif; ?>

</tbody>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

</table>
</div>


<div class="md:hidden space-y-4">

<?php $__currentLoopData = $transaksi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<?php
    $canAccess = $has_full_access || $row->kurir == 0 || $row->kurir == $kurir_id;
    
    // Helper untuk mendapatkan URL file
    $getFileUrl = function($filename, $type = 'foto_barang') {
        if (!$filename) return null;
        return route('messenger.file', [
            'type' => $type,
            'filename' => $filename
        ]);
    };
    
    $fotoBarangUrl = $getFileUrl($row->foto_barang, 'foto_barang');
    $gambarAkhirUrl = $getFileUrl($row->gambar_akhir, 'gambar_akhir');
?>

<div x-data="{ open:false, foto:false, bukti:false, selesai:false, tolak:false }"
     class="bg-white rounded-xl shadow border p-4 text-xs <?php echo e(!$canAccess ? 'opacity-60' : ''); ?>">

    
    <div class="flex justify-between">
        <div>
             <p class="font-semibold text-blue-600"><?php echo e($row->no_transaksi); ?></p>

            <p class="text-slate-500">
                Pengirim: <?php echo e($row->nama_pengirim ?? '-'); ?>

            </p>

            <p class="text-slate-500">
                Penerima: <?php echo e($row->penerima ?? '-'); ?>

            </p>
            <?php if($row->note_penerima): ?>
            <div class="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs">
                <p class="font-semibold text-yellow-700">note_penerima:</p>
                <p class="text-yellow-600"><?php echo e($row->note_penerima); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <div class="text-right">
            <span class="px-2 py-1 rounded-full text-[10px]
                <?php if($row->status=='Proses Pengiriman'): ?> bg-blue-100 text-blue-700
                <?php elseif($row->status=='Terkirim'): ?> bg-green-100 text-green-700
                <?php elseif($row->status=='Belum Terkirim'): ?> bg-yellow-100 text-yellow-700
                <?php elseif($row->status=='Ditolak'): ?> bg-red-100 text-red-700
                <?php else: ?> bg-slate-100 text-slate-700 <?php endif; ?>">
                <?php echo e($row->status); ?>

            </span>
            <p class="text-[10px] text-slate-500 mt-1">
                <?php echo e(\Carbon\Carbon::parse($row->created_at)->format('d M Y H:i')); ?>

            </p>
        </div>
    </div>

    
    <?php if($row->nama_kurir): ?>
    <div class="mt-2 flex items-center justify-between">
        <p class="text-xs text-gray-600">
            Ditangani: <span class="font-medium <?php echo e($row->kurir == $kurir_id ? 'text-green-600' : 'text-gray-500'); ?>">
                <?php echo e($row->nama_kurir); ?>

                <?php if($row->kurir == $kurir_id): ?>
                <span class="text-blue-500"> (Anda)</span>
                <?php endif; ?>
            </span>
        </p>
        <?php if(!$canAccess): ?>
        <span class="text-[10px] text-red-500 bg-red-50 px-2 py-0.5 rounded">Tidak diizinkan</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    
    <?php if($canAccess): ?>
    <div class="flex gap-2 mt-3">
        <?php if(in_array($row->status,['Belum Terkirim','Pengiriman Dibuat'])): ?>
        <div class="flex gap-2 w-full">
            <form method="POST"
                  action="<?php echo e(route('messenger.antar', $row->no_transaksi)); ?>"
                  class="flex-1">
                <?php echo csrf_field(); ?>
                <button type="submit" class="w-full border border-green-600 text-green-600 rounded py-2 hover:bg-green-50 transition">
                    Antar
                </button>
            </form>
            
            <button @click="tolak=true"
                    class="flex-1 border border-red-600 text-red-600 rounded py-2 hover:bg-red-50 transition">
                Tolak
            </button>
        </div>
        <?php endif; ?>

        <?php if($row->status=='Proses Pengiriman' && $row->kurir == $kurir_id): ?>
        <div class="flex gap-2 w-full">
            <button @click="selesai=true"
                    class="flex-1 border border-blue-600 text-blue-600 rounded py-2 hover:bg-blue-50 transition">
                Selesaikan
            </button>
            
            <button @click="tolak=true"
                    class="flex-1 border border-red-600 text-red-600 rounded py-2 hover:bg-red-50 transition">
                Tolak
            </button>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="mt-3 p-2 bg-gray-50 rounded text-center text-xs text-gray-500">
        Anda tidak memiliki akses untuk mengelola pengiriman ini
    </div>
    <?php endif; ?>

    
    <button @click="open=!open"
            class="mt-3 w-full bg-slate-100 py-2 rounded font-semibold hover:bg-slate-200 transition">
        Lihat Detail
    </button>

    
    <div x-show="open" x-cloak class="mt-3 space-y-3 bg-slate-50 p-3 rounded">

        <div>
            <p class="font-semibold">Alamat Asal</p>
            <p><?php echo e($row->alamat_asal); ?></p>
        </div>

        <div>
            <p class="font-semibold">Alamat Tujuan</p>
            <p><?php echo e($row->alamat_tujuan); ?></p>
        </div>

        <div>
            <p class="font-semibold mb-1">Riwayat Pengiriman</p>
            <?php $__currentLoopData = explode('<br>', strip_tags($row->waktu,'<br>')); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(trim($item)): ?>
                <div class="flex gap-2">
                    <span class="w-2 h-2 bg-blue-500 rounded-full mt-1"></span>
                    <span><?php echo $item; ?></span>
                </div>
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <?php if($row->foto_barang): ?>
        <div>
            <p class="font-semibold mb-1">Foto Barang</p>
            <?php if($fotoBarangUrl): ?>
            <img src="<?php echo e($fotoBarangUrl); ?>"
                 @click="foto=true"
                 class="w-24 h-24 rounded border cursor-pointer hover:opacity-90 transition object-cover"
                 onerror="this.onerror=null; this.src='https://via.placeholder.com/100x100?text=Tidak+Tersedia';">
            <?php else: ?>
            <div class="w-24 h-24 rounded border bg-gray-100 flex items-center justify-center">
                <span class="text-xs text-gray-500">File tidak tersedia</span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if($row->gambar_akhir): ?>
        <div>
            <p class="font-semibold mb-1">Bukti Terkirim</p>
            <?php if($gambarAkhirUrl): ?>
            <img src="<?php echo e($gambarAkhirUrl); ?>"
                 @click="bukti=true"
                 class="w-24 h-24 rounded border cursor-pointer hover:opacity-90 transition object-cover"
                 onerror="this.onerror=null; this.src='https://via.placeholder.com/100x100?text=Tidak+Tersedia';">
            <?php else: ?>
            <div class="w-24 h-24 rounded border bg-gray-100 flex items-center justify-center">
                <span class="text-xs text-gray-500">File tidak tersedia</span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    
    <?php if($row->foto_barang && $fotoBarangUrl): ?>
    <div x-show="foto" x-cloak
         class="fixed inset-0 bg-black/70 flex items-center justify-center z-50"
         @click="foto=false">
        <img src="<?php echo e($fotoBarangUrl); ?>"
             class="max-h-[90vh] max-w-[90vw] rounded-xl object-contain"
             onerror="this.onerror=null; this.src='https://via.placeholder.com/400x400?text=Tidak+Tersedia';">
    </div>
    <?php endif; ?>

    
    <?php if($row->gambar_akhir && $gambarAkhirUrl): ?>
    <div x-show="bukti" x-cloak
         class="fixed inset-0 bg-black/70 flex items-center justify-center z-50"
         @click="bukti=false">
        <img src="<?php echo e($gambarAkhirUrl); ?>"
             class="max-h-[90vh] max-w-[90vw] rounded-xl object-contain"
             onerror="this.onerror=null; this.src='https://via.placeholder.com/400x400?text=Tidak+Tersedia';">
    </div>
    <?php endif; ?>

    
    <?php if($row->status=='Proses Pengiriman' && $row->kurir == $kurir_id): ?>
    <div x-show="selesai" x-cloak
         class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
         
        <div class="bg-white p-4 rounded-xl w-full max-w-sm">
            <h3 class="font-semibold mb-4 text-base">Upload Bukti Terkirim</h3>
            
            <form method="POST" 
                  action="<?php echo e(route('messenger.selesaikan', $row->no_transaksi)); ?>" 
                  enctype="multipart/form-data" 
                  id="form-mobile-<?php echo e($row->no_transaksi); ?>"
                  class="space-y-4">
                <?php echo csrf_field(); ?>
                
                <input type="file" 
                       name="gambar_akhir" 
                       id="mobile-file-input-<?php echo e($row->no_transaksi); ?>" 
                       class="hidden" 
                       accept="image/*"
                       onchange="previewMobileImage(event, '<?php echo e($row->no_transaksi); ?>')">
                
                <div id="buttons-<?php echo e($row->no_transaksi); ?>" class="space-y-3">
                    <button type="button" 
                            onclick="openCamera('<?php echo e($row->no_transaksi); ?>')" 
                            class="w-full bg-blue-600 text-white py-3 rounded-lg flex items-center justify-center gap-2 hover:bg-blue-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                        </svg>
                        Ambil Foto dengan Kamera
                    </button>
                    
                    <button type="button" 
                            onclick="openGallery('<?php echo e($row->no_transaksi); ?>')" 
                            class="w-full border border-gray-300 py-3 rounded-lg flex items-center justify-center gap-2 hover:bg-gray-50 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Pilih dari Galeri
                    </button>
                </div>
                
                <div id="preview-mobile-<?php echo e($row->no_transaksi); ?>" class="hidden mt-3">
                    <div class="relative">
                        <img id="preview-image-mobile-<?php echo e($row->no_transaksi); ?>" 
                             class="w-full h-40 object-cover rounded-lg border">
                        <button type="button" 
                                onclick="clearMobilePreview('<?php echo e($row->no_transaksi); ?>')" 
                                class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center">
                            ✕
                        </button>
                    </div>
                    <div id="file-info-mobile-<?php echo e($row->no_transaksi); ?>" class="mt-2 text-xs text-gray-600"></div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        note_penerima (Opsional)
                    </label>
                    <textarea name="note_penerima" 
                              rows="3" 
                              class="w-full border border-gray-300 rounded-lg p-2 text-sm"
                              placeholder="Tambahkan note_penerima..."></textarea>
                </div>
                
                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" 
                            @click="selesai=false" 
                            class="px-4 py-2 border rounded-lg hover:bg-gray-50 transition">
                        Batal
                    </button>
                    
                    <button type="submit" 
                            id="submit-btn-mobile-<?php echo e($row->no_transaksi); ?>"
                            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition hidden">
                        Simpan Bukti
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    
    <?php if($canAccess && in_array($row->status,['Belum Terkirim','Pengiriman Dibuat','Proses Pengiriman'])): ?>
    <div x-show="tolak" x-cloak
         class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
         
        <div class="bg-white p-4 rounded-xl w-full max-w-sm">
            <h3 class="font-semibold mb-4 text-base">Tolak Pengiriman</h3>
            
            <form method="POST" 
                   action="<?php echo e(route('messenger.tolak', $row->no_transaksi)); ?>" 
                  class="space-y-4">
                <?php echo csrf_field(); ?>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Alasan Penolakan *
                    </label>
                    <textarea name="alasan_tolak" 
                              rows="4" 
                              class="w-full border border-gray-300 rounded-lg p-2 text-sm"
                              placeholder="Masukkan alasan penolakan..."
                              required></textarea>
                </div>
                
                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" 
                            @click="tolak=false" 
                            class="px-4 py-2 border rounded-lg hover:bg-gray-50 transition">
                        Batal
                    </button>
                    
                    <button type="submit" 
                            class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                        Tolak Pengiriman
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

</div>

</div>

<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<script>
// ============================================
// FUNGSI UNTUK DESKTOP
// ============================================
function previewDesktopImage(event, noTransaksi) {
    const file = event.target.files[0];
    if (file) {
        if (!validateFile(file)) {
            event.target.value = '';
            return;
        }
        
        const previewDiv = document.getElementById('preview-desktop-' + noTransaksi);
        const previewImg = document.getElementById('preview-image-desktop-' + noTransaksi);
        const fileInfo = document.getElementById('file-info-desktop-' + noTransaksi);
        
        previewImg.src = URL.createObjectURL(file);
        previewDiv.classList.remove('hidden');
        
        const fileSize = (file.size / 1024 / 1024).toFixed(2);
        fileInfo.innerHTML = `
            <p>File: ${file.name}</p>
            <p>Ukuran: ${fileSize} MB</p>
        `;
    }
}

function clearDesktopPreview(noTransaksi) {
    const previewDiv = document.getElementById('preview-desktop-' + noTransaksi);
    const fileInput = document.getElementById('file-desktop-' + noTransaksi);
    
    previewDiv.classList.add('hidden');
    fileInput.value = '';
}

// ============================================
// FUNGSI UNTUK MOBILE
// ============================================
function openCamera(noTransaksi) {
    const fileInput = document.getElementById('mobile-file-input-' + noTransaksi);
    fileInput.removeAttribute('capture');
    fileInput.setAttribute('capture', 'environment');
    fileInput.click();
}

function openGallery(noTransaksi) {
    const fileInput = document.getElementById('mobile-file-input-' + noTransaksi);
    fileInput.removeAttribute('capture');
    fileInput.click();
}

function previewMobileImage(event, noTransaksi) {
    const file = event.target.files[0];
    if (file) {
        if (!validateFile(file)) {
            event.target.value = '';
            return;
        }
        
        document.getElementById('buttons-' + noTransaksi).classList.add('hidden');
        
        const previewDiv = document.getElementById('preview-mobile-' + noTransaksi);
        const previewImg = document.getElementById('preview-image-mobile-' + noTransaksi);
        const submitBtn = document.getElementById('submit-btn-mobile-' + noTransaksi);
        const fileInfo = document.getElementById('file-info-mobile-' + noTransaksi);
        
        previewImg.src = URL.createObjectURL(file);
        previewDiv.classList.remove('hidden');
        submitBtn.classList.remove('hidden');
        
        const fileSize = (file.size / 1024 / 1024).toFixed(2);
        fileInfo.innerHTML = `
            <p>File: ${file.name}</p>
            <p>Ukuran: ${fileSize} MB</p>
        `;
    }
}

function clearMobilePreview(noTransaksi) {
    const fileInput = document.getElementById('mobile-file-input-' + noTransaksi);
    fileInput.value = '';
    
    document.getElementById('buttons-' + noTransaksi).classList.remove('hidden');
    document.getElementById('preview-mobile-' + noTransaksi).classList.add('hidden');
    document.getElementById('submit-btn-mobile-' + noTransaksi).classList.add('hidden');
}

// ============================================
// FUNGSI VALIDASI UMUM
// ============================================
function validateFile(file) {
    const maxSize = 5 * 1024 * 1024;
    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    
    if (!allowedTypes.includes(file.type)) {
        alert('Format file tidak didukung! Hanya JPG, JPEG, PNG yang diperbolehkan.');
        return false;
    }
    
    if (file.size > maxSize) {
        alert('File terlalu besar! Maksimal 5MB.');
        return false;
    }
    
    return true;
}

// ============================================
// EVENT LISTENER UNTUK VALIDASI FORM
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('form').forEach(form => {
        if (form.querySelector('input[name="gambar_akhir"]')) {
            form.addEventListener('submit', function(e) {
                const fileInput = this.querySelector('input[name="gambar_akhir"]');
                
                if (!fileInput || !fileInput.files[0]) {
                    e.preventDefault();
                    alert('Silakan pilih atau ambil foto terlebih dahulu!');
                    return false;
                }
                
                const file = fileInput.files[0];
                if (!validateFile(file)) {
                    e.preventDefault();
                    fileInput.value = '';
                    return false;
                }
                
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<span class="flex items-center gap-2"><svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyimpan...</span>';
                    submitBtn.disabled = true;
                    
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 10000);
                }
            });
        }
    });
});
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/messenger/proses.blade.php ENDPATH**/ ?>