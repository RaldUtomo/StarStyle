<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? config('name')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
    <?php if (($page ?? '') === '/' || ($page ?? '') === '/services-catalog' || ($page ?? '') === '/customer/login'): ?>
        <link href="<?= e(asset('css/landing.css')) ?>" rel="stylesheet">
    <?php endif; ?>
</head>
<?php
$currentPage = (string) ($page ?? '');
$compactPublicPages = ['/booking', '/booking/services', '/booking/time', '/booking/summary', '/booking/confirmation', '/booking/payment', '/booking/payment/qris', '/booking/payment/proof', '/booking/payment/pending', '/booking/payment/success', '/customer/login'];
$hidePublicHeader = in_array($currentPage, array_merge($compactPublicPages, ['/customer/account']), true);
$hidePublicAuthButtons = in_array($currentPage, ['/customer/account'], true);
$isLandingPage = $currentPage === '/';
?>
<body class="public-shell<?= in_array($currentPage, $compactPublicPages, true) ? ' public-shell--booking' : '' ?><?= $isLandingPage ? ' public-shell--landing' : '' ?><?= $currentPage === '/customer/login' ? ' public-shell--login' : '' ?>">
<?php if (!$hidePublicHeader): ?>
<header class="public-header">
    <div class="container">
        <nav class="navbar navbar-expand-lg py-3">
            <?php if ($isLandingPage): ?>
                <a class="navbar-brand text-dark d-flex align-items-center" href="<?= e(url('/')) ?>">
                    <span class="brand-mark">StarStyle</span>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="publicNav">
                    <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-3">
                        <li class="nav-item">
                            <a class="nav-link nav-link-custom" href="<?= e(url('/')) ?>">Home</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link nav-link-custom dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                Salons
                            </a>
                            <ul class="dropdown-menu border-0 shadow-sm mt-2">
                                <li><a class="dropdown-item" href="#">Star Salon Jakarta</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link nav-link-custom" href="<?= e(url('/services-catalog')) ?>">Services</a>
                        </li>
                        <?php if (!empty($customerUser)): ?>
                            <li class="nav-item">
                                <a class="nav-link nav-link-custom" href="<?= e(url('/booking')) ?>">Booking</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <div class="d-flex align-items-center gap-3">
                        <?php if (!empty($customerUser)): ?>
                            <a class="nav-link-custom text-decoration-none" href="<?= e(url('/customer/account')) ?>">
                                <i class="bi bi-person-circle"></i> <?= e($customerUser['name'] ?? 'Akun Saya') ?>
                            </a>
                            <form method="post" action="<?= e(url('/customer/logout')) ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-ref-outline" style="font-size: 0.7rem; padding: 8px 18px;">Logout</button>
                            </form>
                        <?php else: ?>
                            <a class="nav-link-custom text-decoration-none" href="<?= e(url('/customer/login')) ?>">Login</a>
                            <a class="btn btn-ref-solid" href="<?= e(url('/booking')) ?>">Book</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <a class="navbar-brand fw-bold text-white d-flex align-items-center gap-2" href="<?= e(url('/')) ?>">
                    <span class="brand-mark">S</span>
                    <span>StarStyle</span>
                </a>
                <div class="ms-auto d-flex align-items-center gap-3">
                    <?php foreach (($publicNav ?? []) as $item): ?>
                        <a class="text-white text-decoration-none small" href="<?= e(url($item['path'])) ?>"><?= e($item['label']) ?></a>
                    <?php endforeach; ?>
                    <?php if (!$hidePublicAuthButtons): ?>
                        <a class="btn btn-light rounded-pill px-4" href="<?= e(url('/customer/login')) ?>">Customer Login</a>
                        <a class="btn btn-dark rounded-pill px-4" href="<?= e(url('/login')) ?>">Admin</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </nav>
    </div>
</header>
<?php endif; ?>
<main>
    <?php if (!empty($success) || !empty($error)): ?>
        <div class="container pt-4">
            <?php if (!empty($success)): ?><div class="alert alert-success border-0 rounded-4"><?= e($success) ?></div><?php endif; ?>
            <?php if (!empty($error)): ?><div class="alert alert-danger border-0 rounded-4"><?= e($error) ?></div><?php endif; ?>
        </div>
    <?php endif; ?>
    <?= $content ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>

