<?php $__env->startSection('content'); ?>
<div class="p-4 md:p-6">
    <!-- Header -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 md:p-6 mb-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4">
            <div class="flex-1">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <h2 class="text-xl md:text-2xl font-bold text-gray-800"><?php echo e($tiket->judul); ?></h2>
                    <?php
                        $statusColors = [
                            'OPEN' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                            'ON_PROCESS' => 'bg-blue-100 text-blue-800 border-blue-200',
                            'WAITING' => 'bg-orange-100 text-orange-800 border-orange-200',
                            'DONE' => 'bg-green-100 text-green-800 border-green-200',
                            'CLOSED' => 'bg-gray-100 text-gray-800 border-gray-200'
                        ];
                        $priorityColors = [
                            'URGENT' => 'bg-red-100 text-red-800 border-red-200',
                            'HIGH' => 'bg-orange-100 text-orange-800 border-orange-200',
                            'MEDIUM' => 'bg-blue-100 text-blue-800 border-blue-200',
                            'LOW' => 'bg-gray-100 text-gray-800 border-gray-200'
                        ];
                    ?>
                    <span class="px-3 py-1 text-sm font-medium rounded-full border <?php echo e($statusColors[$tiket->status]); ?>">
                        <?php echo e($tiket->status); ?>

                    </span>
                    <span class="px-3 py-1 text-sm font-medium rounded-full border <?php echo e($priorityColors[$tiket->prioritas]); ?>">
                        <?php echo e($tiket->prioritas); ?>

                    </span>
                </div>
                <p class="text-gray-600"><?php echo e($tiket->nomor_tiket); ?></p>
            </div>
            
            <?php if($tiket->status !== 'CLOSED'): ?>
            <div class="flex flex-wrap gap-2">
                <?php if($tiket->status === 'OPEN'): ?>
                <span class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-800 font-medium rounded-lg">
                    <i class="fas fa-clock mr-2"></i> Menunggu Penugasan
                </span>
                <?php elseif($tiket->status === 'ON_PROCESS'): ?>
                <span class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 font-medium rounded-lg">
                    <i class="fas fa-cog mr-2"></i> Sedang Diproses
                </span>
                <?php elseif($tiket->status === 'WAITING'): ?>
                <span class="inline-flex items-center px-4 py-2 bg-orange-100 text-orange-800 font-medium rounded-lg">
                    <i class="fas fa-hourglass-half mr-2"></i> Menunggu Respons
                </span>
                <?php elseif($tiket->status === 'DONE'): ?>
                <span class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 font-medium rounded-lg">
                    <i class="fas fa-check-circle mr-2"></i> Selesai
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Dilaporkan</h3>
                <div class="flex items-center">
                    <?php
                        $pelaporName = $tiket->pelapor ? $tiket->pelapor->name : '';
                        $pelaporInitial = substr($pelaporName, 0, 1);
                    ?>
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center font-medium text-blue-700 mr-3">
                        <?php echo e($pelaporInitial); ?>

                    </div>
                    <div>
                        <p class="text-sm text-gray-500"><?php echo e($tiket->created_at->format('d/m/Y H:i')); ?></p>
                    </div>
                </div>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Kategori</h3>
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center mr-3">
                        <i class="fas fa-tag text-blue-600"></i>
                    </div>
                    <p class="font-medium text-gray-900"><?php echo e($tiket->kategori->nama ?? '-'); ?></p>
                </div>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Penanggung Jawab</h3>
                <div class="flex items-center">
                    <?php if($tiket->ditugaskanKe): ?>
                        <?php
                            if ($tiket->ditugaskanKe->user) {
                                $pjName = $tiket->ditugaskanKe->user->name;
                            } elseif ($tiket->ditugaskanKe->nama) {
                                $pjName = $tiket->ditugaskanKe->nama;
                            } else {
                                $pjName = 'Staff GA';
                            }
                            $pjInitial = substr($pjName, 0, 1);
                        ?>
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-100 to-green-50 flex items-center justify-center font-medium text-green-700 mr-3">
                            <?php echo e($pjInitial); ?>

                        </div>
                        <div>
                            <p class="font-medium text-gray-900"><?php echo e($pjName); ?></p>
                            <?php if($tiket->diproses_pada): ?>
                            <p class="text-sm text-gray-500">Sejak <?php echo e($tiket->diproses_pada->format('d/m/Y')); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 italic">Belum ditugaskan</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg border border-gray-200 p-4 md:p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Deskripsi Masalah</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-gray-700 whitespace-pre-line"><?php echo e($tiket->deskripsi); ?></p>
                </div>
            </div>
            
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800">Diskusi Tiket</h3>
                        <div class="flex items-center text-sm text-gray-500">
                            <i class="fas fa-users mr-2"></i>
                            <span><?php echo e($tiket->komentar->count()); ?> pesan</span>
                        </div>
                    </div>
                </div>
                
                <?php if($tiket->status !== 'CLOSED'): ?>
                <div class="p-4 bg-gray-50" style="height: 400px; overflow-y: auto;" id="chatContainer">
                    <?php $__empty_1 = true; $__currentLoopData = $tiket->komentar; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $komentar): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php if($komentar->pesan_sistem): ?>
                            <div class="text-center my-4">
                                <div class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                                    <i class="fas fa-robot mr-2"></i>
                                    <?php
                                        $systemUserName = $komentar->pengguna && $komentar->pengguna->user 
                                            ? $komentar->pengguna->user->name 
                                            : 'System';
                                    ?>
                                    <?php echo e($systemUserName); ?> • <?php echo e($komentar->created_at->format('H:i')); ?>

                                </div>
                                <div class="mt-2 text-sm text-gray-600 bg-white p-3 rounded-lg border border-gray-200 max-w-md mx-auto">
                                    <?php echo e($komentar->komentar); ?>

                                </div>
                            </div>
                        <?php else: ?>
                            <?php
                                $currentUserId = auth()->user()->pelanggan->id_pelanggan;
                                $penggunaName = $komentar->pengguna && $komentar->pengguna->user 
                                    ? $komentar->pengguna->user->name 
                                    : 'User';
                                $penggunaInitial = substr($penggunaName, 0, 1);
                                $isOwnMessage = $komentar->pengguna_id === $currentUserId;
                            ?>
                            <div class="flex items-start mb-4 <?php echo e($isOwnMessage ? 'justify-end' : ''); ?>">
                                <?php if(!$isOwnMessage): ?>
                                    <div class="flex-shrink-0 mr-3">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center font-medium text-blue-700">
                                            <?php echo e($penggunaInitial); ?>

                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="<?php echo e($isOwnMessage ? 'max-w-[70%]' : 'max-w-[70%]'); ?>">
                                    <div class="flex items-center mb-1 <?php echo e($isOwnMessage ? 'justify-end' : ''); ?>">
                                        <span class="text-xs text-gray-500"><?php echo e($penggunaName); ?></span>
                                        <span class="text-xs text-gray-400 mx-2">•</span>
                                        <span class="text-xs text-gray-400"><?php echo e($komentar->created_at->format('H:i')); ?></span>
                                    </div>
                                    
                                    <div class="<?php echo e($isOwnMessage ? 'bg-green-100' : 'bg-white'); ?> p-3 rounded-2xl <?php echo e($isOwnMessage ? 'rounded-tr-none' : 'rounded-tl-none'); ?> border <?php echo e($isOwnMessage ? 'border-green-200' : 'border-gray-200'); ?>">
                                        <p class="text-gray-800 whitespace-pre-line"><?php echo e($komentar->komentar); ?></p>
                                        
                                        <?php
                                            $komentarLampiran = $tiket->lampiran->where('pengguna_id', $komentar->pengguna_id)
                                                ->where('created_at', '>=', $komentar->created_at->subMinute(1))
                                                ->where('created_at', '<=', $komentar->created_at->addMinute(1))
                                                ->first();
                                        ?>
                                        <?php if($komentarLampiran): ?>
                                            <div class="mt-2 pt-2 border-t border-gray-200">
                                                <div class="flex items-center">
                                                    <i class="fas fa-paperclip text-xs text-gray-500 mr-2"></i>
                                                    <?php
                                                        $iconClass = '';
                                                        if (str_contains($komentarLampiran->tipe_file, 'image')) {
                                                            $iconClass = 'fas fa-image text-blue-600';
                                                        } elseif (str_contains($komentarLampiran->tipe_file, 'pdf')) {
                                                            $iconClass = 'fas fa-file-pdf text-red-600';
                                                        } elseif (str_contains($komentarLampiran->tipe_file, 'word')) {
                                                            $iconClass = 'fas fa-file-word text-blue-600';
                                                        } else {
                                                            $iconClass = 'fas fa-file text-gray-600';
                                                        }
                                                    ?>
                                                    
                                                    <?php if(str_contains($komentarLampiran->tipe_file, 'image')): ?>
                                                        <button onclick="previewFile('<?php echo e($komentarLampiran->id); ?>')" 
                                                                class="text-sm text-gray-700 hover:text-blue-600 flex items-center mr-2">
                                                            <i class="<?php echo e($iconClass); ?> mr-2"></i>
                                                            <?php echo e($komentarLampiran->nama_file); ?>

                                                        </button>
                                                        <a href="/help/tiket/lampiran/<?php echo e($komentarLampiran->id); ?>/download" 
                                                           class="text-xs text-gray-500 hover:text-blue-600"
                                                           title="Download">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="/help/tiket/lampiran/<?php echo e($komentarLampiran->id); ?>/download" 
                                                           class="text-sm text-gray-700 hover:text-blue-600 flex items-center">
                                                            <i class="<?php echo e($iconClass); ?> mr-2"></i>
                                                            <?php echo e($komentarLampiran->nama_file); ?>

                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if($isOwnMessage): ?>
                                        <div class="text-right mt-1">
                                            <span class="text-xs text-gray-400"><i class="fas fa-check-double"></i></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if($isOwnMessage): ?>
                                    <?php
                                        $currentUser = auth()->user();
                                        $currentUserName = $currentUser->name ?? 'You';
                                        $currentUserInitial = substr($currentUserName, 0, 1);
                                    ?>
                                    <div class="flex-shrink-0 ml-3">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-green-100 to-green-50 flex items-center justify-center font-medium text-green-700">
                                            <?php echo e($currentUserInitial); ?>

                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="flex flex-col items-center justify-center h-64 text-gray-400">
                            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                                <i class="fas fa-comments text-2xl"></i>
                            </div>
                            <p class="text-lg font-medium text-gray-500">Belum ada diskusi</p>
                            <p class="text-sm text-gray-400 mt-2">Mulai percakapan tentang tiket ini</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="border-t border-gray-200 p-4">
                    <form action="<?php echo e(route('help.tiket.add-komentar', $tiket)); ?>" method="POST" enctype="multipart/form-data" id="chatForm">
                        <?php echo csrf_field(); ?>
                        <div class="space-y-3">
                            <textarea name="komentar" 
                                      id="chatInput"
                                      rows="2"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                                      placeholder="Ketik pesan..."
                                      required></textarea>
                            
                            <div class="flex justify-between items-center">
                                <div class="flex items-center">
                                    <input type="file" 
                                           id="chat-lampiran" 
                                           name="lampiran[]" 
                                           multiple
                                           class="hidden"
                                           accept="image/*,.pdf,.doc,.docx">
                                    <button type="button" 
                                            onclick="document.getElementById('chat-lampiran').click()"
                                            class="inline-flex items-center text-gray-500 hover:text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-100">
                                        <i class="fas fa-paperclip mr-2"></i> Lampirkan File
                                    </button>
                                    <span id="file-count" class="text-xs text-gray-500 ml-2"></span>
                                </div>
                                <button type="submit"
                                        class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                                    <i class="fas fa-paper-plane mr-2"></i> Kirim
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                <div class="p-8 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                        <i class="fas fa-lock text-2xl text-gray-400"></i>
                    </div>
                    <h4 class="text-lg font-medium text-gray-700 mb-2">Chat Dinonaktifkan</h4>
                    <p class="text-gray-500">Diskusi telah ditutup karena tiket sudah selesai</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="space-y-6">
            <div class="bg-white rounded-lg border border-gray-200 p-4 md:p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Status Timeline</h3>
                <div class="space-y-4">
                    <?php $__currentLoopData = $tiket->logStatus; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $logUserName = $log->pengguna && $log->pengguna->user 
                            ? $log->pengguna->user->name 
                            : 'Unknown';
                    ?>
                    <div class="relative pl-8 pb-4 last:pb-0">
                        <?php if(!$loop->last): ?>
                            <div class="absolute left-3 top-3 bottom-0 w-0.5 bg-gray-200"></div>
                        <?php endif; ?>
                        
                        <div class="absolute left-0 top-1 w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-exchange-alt text-blue-600 text-xs"></i>
                        </div>
                        
                        <div>
                            <div class="flex justify-between items-start mb-1">
                                <p class="font-medium text-gray-900"><?php echo e($log->status_baru); ?></p>
                                <span class="text-xs text-gray-500"><?php echo e($log->created_at->format('H:i')); ?></span>
                            </div>
                            <?php if($logUserName): ?>
                            <p class="text-sm text-gray-600">Oleh: <?php echo e($logUserName); ?></p>
                            <?php endif; ?>
                            <?php if($log->catatan): ?>
                            <div class="mt-2 p-2 bg-gray-50 rounded text-sm text-gray-600">
                                <?php echo e($log->catatan); ?>

                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
            
            <div class="bg-white rounded-lg border border-gray-200 p-4 md:p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Lampiran</h3>
                <div class="space-y-3">
                    <?php $__empty_1 = true; $__currentLoopData = $tiket->lampiran; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lampiran): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $lampiranUser = \App\Models\Pelanggan::find($lampiran->pengguna_id);
                        $uploaderName = $lampiranUser && $lampiranUser->user 
                            ? $lampiranUser->user->name 
                            : 'Unknown';
                    ?>
                    <div class="group flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center min-w-0 flex-1 cursor-pointer" 
                             onclick="openFile('<?php echo e($lampiran->id); ?>', '<?php echo e($lampiran->nama_file); ?>', '<?php echo e($lampiran->tipe_file); ?>')">
                            <div class="flex-shrink-0 mr-3">
                                <?php
                                    $iconClass = '';
                                    $bgClass = '';
                                    if (str_contains($lampiran->tipe_file, 'image')) {
                                        $iconClass = 'fas fa-image text-blue-600';
                                        $bgClass = 'bg-blue-50';
                                    } elseif (str_contains($lampiran->tipe_file, 'pdf')) {
                                        $iconClass = 'fas fa-file-pdf text-red-600';
                                        $bgClass = 'bg-red-50';
                                    } elseif (str_contains($lampiran->tipe_file, 'word')) {
                                        $iconClass = 'fas fa-file-word text-blue-600';
                                        $bgClass = 'bg-blue-50';
                                    } else {
                                        $iconClass = 'fas fa-file text-gray-600';
                                        $bgClass = 'bg-gray-100';
                                    }
                                ?>
                                <div class="w-10 h-10 rounded-lg <?php echo e($bgClass); ?> flex items-center justify-center">
                                    <i class="<?php echo e($iconClass); ?>"></i>
                                </div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900 truncate"><?php echo e($lampiran->nama_file); ?></p>
                                <div class="flex items-center text-xs text-gray-500 mt-1">
                                    <span class="mr-3"><?php echo e($lampiran->created_at->format('d/m/Y')); ?></span>
                                    <span class="px-2 py-0.5 bg-gray-100 rounded text-gray-600"><?php echo e($lampiran->tipe); ?></span>
                                </div>
                                <p class="text-xs text-gray-400 mt-1">Diunggah oleh: <?php echo e($uploaderName); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 ml-3">
                            <?php if(str_contains($lampiran->tipe_file, 'image')): ?>
                                <button onclick="previewFile('<?php echo e($lampiran->id); ?>')" 
                                        class="text-blue-600 hover:text-blue-800 p-1 rounded hover:bg-blue-50"
                                        title="Preview">
                                    <i class="fas fa-eye text-sm"></i>
                                </button>
                            <?php endif; ?>
                            <button onclick="downloadFile('<?php echo e($lampiran->id); ?>', '<?php echo e($lampiran->nama_file); ?>')" 
                                    class="text-green-600 hover:text-green-800 p-1 rounded hover:bg-green-50"
                                    title="Download">
                                <i class="fas fa-download text-sm"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="text-center py-6">
                        <div class="inline-flex items-center justify-center w-12 h-12 bg-gray-100 rounded-full mb-4">
                            <i class="fas fa-paperclip text-gray-400"></i>
                        </div>
                        <p class="text-gray-500">Tidak ada lampiran</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="bg-white rounded-lg border border-gray-200 p-4 md:p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Informasi Status</h3>
                <div class="space-y-3">
                    <?php if($tiket->status === 'OPEN'): ?>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <i class="fas fa-info-circle text-blue-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-700">Tiket Anda sedang menunggu untuk ditugaskan ke petugas GA.</p>
                        </div>
                    </div>
                    <?php elseif($tiket->status === 'ON_PROCESS'): ?>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <i class="fas fa-cog text-green-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-700">Tiket Anda sedang diproses oleh petugas GA.</p>
                            <?php if($tiket->diproses_pada): ?>
                            <p class="text-xs text-gray-500 mt-1">Diproses sejak: <?php echo e($tiket->diproses_pada->format('d/m/Y H:i')); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php elseif($tiket->status === 'WAITING'): ?>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <i class="fas fa-hourglass-half text-orange-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-700">Petugas GA membutuhkan informasi tambahan dari Anda.</p>
                            <p class="text-sm font-medium text-gray-800 mt-1">Silakan berikan respons melalui kolom chat di atas.</p>
                        </div>
                    </div>
                    <?php elseif($tiket->status === 'DONE'): ?>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <i class="fas fa-check-circle text-green-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-700">Tiket Anda telah diselesaikan oleh petugas GA.</p>
                            <?php if($tiket->diselesaikan_pada): ?>
                            <p class="text-xs text-gray-500 mt-1">Diselesaikan: <?php echo e($tiket->diselesaikan_pada->format('d/m/Y H:i')); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php elseif($tiket->status === 'CLOSED'): ?>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <i class="fas fa-lock text-gray-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-700">Tiket ini telah ditutup.</p>
                            <?php if($tiket->ditutup_pada): ?>
                            <p class="text-xs text-gray-500 mt-1">Ditutup: <?php echo e($tiket->ditutup_pada->format('d/m/Y H:i')); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="previewModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-hidden">
        <div class="flex justify-between items-center p-4 border-b">
            <h3 class="text-lg font-semibold" id="previewFileName"></h3>
            <button onclick="closePreview()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-4 overflow-auto max-h-[calc(90vh-4rem)]">
            <div class="flex justify-center">
                <img id="previewImage" src="" alt="" class="max-w-full h-auto rounded-lg">
            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
// Auto-scroll chat to bottom
const chatContainer = document.getElementById('chatContainer');
if (chatContainer) {
    chatContainer.scrollTop = chatContainer.scrollHeight;
}

// Auto-resize textarea
const chatInput = document.getElementById('chatInput');
if (chatInput) {
    chatInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    chatInput.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'Enter') {
            document.getElementById('chatForm').submit();
        }
    });
}

// File upload for chat
const chatFileInput = document.getElementById('chat-lampiran');
const fileCountSpan = document.getElementById('file-count');

if (chatFileInput) {
    chatFileInput.addEventListener('change', function() {
        const files = this.files;
        const count = files.length;
        
        if (fileCountSpan) {
            fileCountSpan.textContent = count > 0 ? `${count} file dipilih` : '';
        }
    });
}

// File handling functions - GUNAKAN URL LANGSUNG
function openFile(id, fileName, fileType) {
    if (fileType.includes('image')) {
        previewFile(id);
    } else {
        downloadFile(id, fileName);
    }
}

function previewFile(id) {
    // URL langsung - buka di tab baru
    const previewUrl = "/help/tiket/lampiran/" + id + "/preview";
    console.log('Opening preview:', previewUrl);
    window.open(previewUrl, '_blank', 'noopener,noreferrer');
}

function downloadFile(id, fileName) {
    // URL langsung
    const downloadUrl = "/help/tiket/lampiran/" + id + "/download";
    console.log('Downloading:', downloadUrl);
    
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function closePreview() {
    const modal = document.getElementById('previewModal');
    const image = document.getElementById('previewImage');
    
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    image.src = '';
}

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePreview();
    }
});

// Close modal when clicking outside
const previewModal = document.getElementById('previewModal');
if (previewModal) {
    previewModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closePreview();
        }
    });
}
</script>
<?php $__env->stopPush(); ?>

<style>
    #chatContainer::-webkit-scrollbar {
        width: 6px;
    }
    
    #chatContainer::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    
    #chatContainer::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }
    
    #chatContainer::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
    
    .bg-green-100 {
        background-color: #d1fae5;
    }
    
    .border-green-200 {
        border-color: #a7f3d0;
    }
    
    .from-blue-100 { --tw-gradient-from: #dbeafe; }
    .to-blue-50 { --tw-gradient-to: #eff6ff; }
    .from-green-100 { --tw-gradient-from: #d1fae5; }
    .to-green-50 { --tw-gradient-to: #ecfdf5; }
    
    .rounded-xl {
        border-radius: 0.75rem;
    }
    
    .bg-blue-50 { background-color: #eff6ff; }
    .bg-green-50 { background-color: #ecfdf5; }
    .bg-gray-100 { background-color: #f3f4f6; }
    .bg-red-50 { background-color: #fef2f2; }
    
    .border-blue-200 { border-color: #bfdbfe; }
    .border-green-200 { border-color: #a7f3d0; }
    .border-gray-300 { border-color: #d1d5db; }
    .border-red-200 { border-color: #fecaca; }
    
    .max-h-\[90vh\] {
        max-height: 90vh;
    }
    
    .max-h-\[calc\(90vh-4rem\)\] {
        max-height: calc(90vh - 4rem);
    }
</style>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/help/tiket/show.blade.php ENDPATH**/ ?>