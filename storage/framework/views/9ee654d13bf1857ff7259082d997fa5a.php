<?php $__env->startSection('title', 'Login'); ?>

<?php $__env->startSection('head'); ?>
<!-- FONT AWESOME (WAJIB) -->
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<style>
/* ===============================
   GLOBAL
================================ */
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: #f8fafc;
}

/* ===============================
   WRAPPER
================================ */
.page-wrapper {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

/* ===============================
   CARD
================================ */
.login-container {
    width: 100%;
    max-width: 420px;
}

.login-card {
    background: #fff;
    border-radius: 18px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 10px 40px rgba(0,0,0,.05);
    overflow: hidden;
}

/* ===============================
   HEADER
================================ */
.login-header {
    padding: 32px;
    text-align: center;
}

.logo {
    width: 150px;
    margin-bottom: 12px;
}

.login-header h2 {
    margin: 0;
    font-size: 1.5rem;
}

.login-header small {
    color: #6b7280;
}

/* ===============================
   BODY
================================ */
.login-body {
    padding: 30px;
}

/* ===============================
   FORM
================================ */
.form-group {
    margin-bottom: 18px;
}

.form-label {
    display: block;
    margin-bottom: 6px;
    font-size: .9rem;
    font-weight: 600;
}

.input-group {
    display: flex;
    align-items: center;
    border: 1.5px solid #d1d5db;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
}

.input-group:focus-within {
    border-color: #2563eb;
}

/* ICON LEFT */
.input-icon {
    padding: 0 14px;
    color: #6b7280;
    background: #f9fafb;
    display: flex;
    align-items: center;
}

/* INPUT */
.form-input {
    flex: 1;
    border: none;
    outline: none;
    padding: 14px;
    font-size: 1rem;
    background: transparent;
}

/* TOGGLE PASSWORD */
.toggle-password {
    background: #f9fafb;
    border: none;
    padding: 0 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    color: #374151;
}

.toggle-password i {
    font-size: 1.1rem;
}

.toggle-password:hover {
    color: #2563eb;
}

.toggle-password.active {
    color: #2563eb;
}

/* ===============================
   BUTTON
================================ */
.btn {
    width: 100%;
    padding: 14px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: #b60c0c;
    color: #fff;
}

/* ===============================
   ALERT
================================ */
.alert {
    padding: 14px;
    border-radius: 12px;
    margin-bottom: 18px;
    font-size: .9rem;
}

.alert-danger {
    background: #fee2e2;
    color: #b91c1c;
}

/* ===============================
   RESPONSIVE
================================ */
@media (max-width: 480px) {

    .login-header {
        padding: 24px;
    }

    .login-body {
        padding: 24px;
    }

    .logo {
        width: 120px;
    }

    .login-header h2 {
        font-size: 1.3rem;
    }
}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="page-wrapper">
    <div class="login-container">
        <div class="login-card">

            <div class="login-header">
                <img src="<?php echo e(asset('KPN12.png')); ?>" class="logo" alt="GA Portal">
                <h2>GA Portal</h2>
                <small>General Affairs Management</small>
            </div>

            <div class="login-body">

                
                <?php if($errors->any()): ?>
                    <div class="alert alert-danger">
                        <?php echo e($errors->first()); ?>

                    </div>
                <?php endif; ?>

                
                <form method="POST" action="<?php echo e(route('login.process')); ?>">
                    <?php echo csrf_field(); ?>

                    
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-icon">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text"
                                   name="username"
                                   class="form-input"
                                   required
                                   autofocus>
                        </div>
                    </div>

                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-icon">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password"
                                   name="password"
                                   id="password"
                                   class="form-input"
                                   required>
                            <button type="button"
                                    class="toggle-password"
                                    onclick="togglePassword(this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Login
                    </button>

                </form>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
function togglePassword(btn) {
    const input = document.getElementById('password');
    const icon  = btn.querySelector('i');

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
        btn.classList.add('active');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
        btn.classList.remove('active');
    }
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/auth/login.blade.php ENDPATH**/ ?>