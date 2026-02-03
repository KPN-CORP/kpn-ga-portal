<?php $__env->startSection('title', 'Akses Ditolak - GA Portal'); ?>

<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="access-denied-card">
        <div class="access-denied-header">
            <div class="access-denied-icon">
                <svg width="40" height="40" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
            </div>
            <h1 class="h3 mb-0">Akses Ditolak</h1>
        </div>
        
        <div class="access-denied-body">
            <h2 class="h4 text-center mb-4">Anda tidak memiliki izin untuk mengakses halaman ini</h2>
            
            <div class="alert alert-warning mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Hub:</strong> sudetlin.sugito@kpn-corp.com
            </div>
            
            <div class="mt-4 text-center">
                <a href="/dashboard" class="btn btn-primary btn-lg px-4">
                    <i class="fas fa-redo me-2"></i>Kembali ke Dashboard
                </a>
                <form action="<?php echo e(route('logout')); ?>" method="POST" class="d-inline ms-2">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-outline-secondary btn-lg px-4">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: calc(100vh - 70px);
    }
    
    .access-denied-card {
        width: 100%;
        max-width: 500px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .access-denied-header {
        background: linear-gradient(90deg, #e74c3c, #c0392b);
        color: white;
        padding: 30px;
        text-align: center;
    }
    
    .access-denied-body {
        padding: 40px;
    }
</style>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/no-access.blade.php ENDPATH**/ ?>