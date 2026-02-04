<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <!-- DEFAULT TITLE -->
    <title><?php echo $__env->yieldContent('title', 'GA Portal'); ?></title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo e(asset('KPN123.png')); ?>" type="image/x-icon">

    <!-- Untuk CSS global -->
    <?php echo $__env->yieldContent('head'); ?>
</head>
<body>
    <?php echo $__env->yieldContent('content'); ?>

    <!-- Scripts -->
    <?php echo $__env->yieldContent('scripts'); ?>
</body>
</html><?php /**PATH /var/www/html/ga-portal/resources/views/layouts/app.blade.php ENDPATH**/ ?>