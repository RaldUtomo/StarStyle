<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title ?? config('name')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body class="app-shell">
<div class="dashboard-shell">
    <aside class="sidebar-panel">
        <div class="sidebar-panel__header">
            <div class="brand-profile dropdown">
                <button class="brand-card" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <div class="avatar-badge"><i class="bi bi-person"></i></div>
                    <div class="brand-card__text">
                        <div class="brand-card__name js-brand-card-name"><?= e($accountState['name'] ?? $currentUser['name'] ?? 'Guest') ?></div>
                        <div class="brand-card__sub">Star Salon</div>
                    </div>
                    <i class="bi bi-caret-down-fill brand-card__chevron"></i>
                </button>
                <div class="dropdown-menu brand-profile-menu">
                    <a class="brand-profile-menu__item" href="<?= e(url('/dashboard')) ?>">Beranda</a>
                    <a class="brand-profile-menu__item" href="<?= e(url('/account')) ?>">Akun Saya</a>
                    <div class="brand-profile-menu__section">
                        <span>Pindah Lokasi</span>
                        <strong>Star Salon</strong>
                    </div>
                    <div class="brand-profile-menu__section">
                        <span>Ubah bahasa ke:</span>
                        <strong>ENGLISH</strong>
                    </div>
                    <form method="post" action="<?= e(url('/logout')) ?>">
                        <?= csrf_field() ?>
                        <button class="brand-profile-menu__logout" type="submit">Log Out</button>
                    </form>
                </div>
            </div>
        </div>

        <nav class="side-navigation">
            <?php foreach (($sidebarModules ?? []) as $module): ?>
                <a class="side-navigation__link <?= active_path($module['path'], $page ?? '') ? 'is-active' : '' ?>" href="<?= e(url($module['path'])) ?>">
                    <i class="bi bi-<?= e($module['icon']) ?>"></i>
                    <span><?= e($module['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

    </aside>

    <main class="main-panel">
        <header class="topbar">
            <div class="topbar__left">
                <div class="topbar__title"><?= e($pageTitle ?? $title ?? 'Dashboard') ?></div>
            </div>
            <div class="topbar__actions">
                <form class="search-pill js-topbar-search" action="<?= e(url('/services')) ?>" method="get" role="search" data-topbar-search="<?= e(json_encode($topbarSearch ?? ['customers' => [], 'agenda' => []], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
                    <i class="bi bi-search"></i>
                    <input name="q" type="search" placeholder="Cari..." autocomplete="off" aria-label="Cari layanan">
                </form>
                <div class="topbar-action-wrap js-topbar-fast-wrap">
                    <button class="topbar-icon js-topbar-fast-toggle" type="button" aria-label="Fast checkout" aria-expanded="false" aria-controls="topbarFastCheckoutMenu">
                        <i class="bi bi-lightning-charge"></i>
                    </button>
                    <div class="topbar-fast-menu js-topbar-fast-menu"
                         id="topbarFastCheckoutMenu"
                         data-fast-checkout="<?= e(json_encode($fastCheckout ?? ['date' => date('Y-m-d'), 'dateLabel' => date('j M Y'), 'items' => []], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
                         hidden>
                        <div class="topbar-fast-menu__head">
                            <h2>Fast Checkout</h2>
                            <button class="js-topbar-fast-refresh" type="button" aria-label="Refresh"><i class="bi bi-arrow-clockwise"></i></button>
                        </div>
                        <button class="topbar-fast-date js-topbar-fast-date-toggle" type="button" aria-expanded="false">
                            <i class="bi bi-calendar3"></i>
                            <span class="js-topbar-fast-date"><?= e((string) ($fastCheckout['dateLabel'] ?? date('j M Y'))) ?></span>
                        </button>
                        <div class="topbar-fast-calendar js-topbar-fast-calendar" hidden></div>
                        <div class="topbar-fast-statuses js-topbar-fast-statuses" aria-label="Filter status booking">
                            <button class="is-active" type="button" data-fast-status="new">NEW</button>
                            <button type="button" data-fast-status="confirmed">Confirmed</button>
                            <button type="button" data-fast-status="arrived">Arrived</button>
                            <button type="button" data-fast-status="started">Started</button>
                            <button type="button" data-fast-status="completed">Completed</button>
                            <button type="button" data-fast-status="canceled">Canceled</button>
                            <button type="button" data-fast-status="no_show">No Show</button>
                        </div>
                        <div class="topbar-fast-list js-topbar-fast-list"></div>
                        <div class="topbar-fast-empty js-topbar-fast-empty" hidden>Tidak ada agenda untuk status ini.</div>
                        <a class="topbar-fast-more" href="<?= e(url('/calendar?date=' . ($fastCheckout['date'] ?? date('Y-m-d')))) ?>">Lainnya</a>
                    </div>
                </div>
                <div class="topbar-action-wrap js-topbar-plus-wrap">
                    <button class="topbar-icon js-topbar-plus-toggle" type="button" aria-label="Tambah" aria-expanded="false" aria-controls="topbarQuickCreateMenu"><i class="bi bi-plus-lg"></i></button>
                    <div class="topbar-create-menu js-topbar-plus-menu" id="topbarQuickCreateMenu" hidden>
                        <button class="topbar-create-menu__item js-topbar-create-action" type="button" data-topbar-action="sales">
                            <i class="bi bi-receipt"></i>
                            <span>Penjualan</span>
                            <strong>+</strong>
                        </button>
                        <button class="topbar-create-menu__item js-topbar-create-action" type="button" data-topbar-action="agenda">
                            <i class="bi bi-calendar3"></i>
                            <span>Agenda</span>
                            <strong>+</strong>
                        </button>
                        <button class="topbar-create-menu__item js-topbar-create-action" type="button" data-topbar-action="block">
                            <i class="bi bi-clock"></i>
                            <span>Blokir Waktu</span>
                            <strong>+</strong>
                        </button>
                        <button class="topbar-create-menu__item js-topbar-create-action" type="button" data-topbar-action="customer">
                            <i class="bi bi-emoji-smile"></i>
                            <span>Pelanggan</span>
                            <strong>+</strong>
                        </button>
                    </div>
                </div>
                <div class="topbar-action-wrap js-topbar-notification-wrap">
                    <button class="topbar-icon js-topbar-notification-toggle" type="button" aria-label="Notifikasi" aria-expanded="false" aria-controls="topbarNotificationMenu">
                        <i class="bi bi-bell"></i>
                        <?php if (!empty($notifications)): ?>
                            <span class="topbar-icon__dot"></span>
                        <?php endif; ?>
                    </button>
                    <div class="topbar-notification-menu js-topbar-notification-menu"
                         id="topbarNotificationMenu"
                         data-notifications="<?= e(json_encode(array_values(array_slice($notifications ?? [], 0, 24)), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
                         hidden>
                        <h2 class="topbar-notification-menu__title">Notifikasi</h2>
                        <div class="topbar-notification-tabs js-topbar-notification-tabs" aria-label="Filter notifikasi">
                            <button class="is-active" type="button" data-notification-filter="all">Semua</button>
                            <button type="button" data-notification-filter="agenda">Agenda</button>
                            <button type="button" data-notification-filter="produk">Produk</button>
                            <button type="button" data-notification-filter="subscriptions">Subscriptions</button>
                        </div>
                        <div class="topbar-notification-list js-topbar-notification-list"></div>
                        <div class="topbar-notification-empty js-topbar-notification-empty" hidden>
                            <i class="bi bi-bell-slash"></i>
                            <strong>No Result</strong>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <div class="topbar-search-layer js-topbar-search-layer" hidden>
            <button class="topbar-search-layer__backdrop js-topbar-search-close" type="button" aria-label="Tutup pencarian"></button>
            <div class="topbar-search-panel" role="dialog" aria-modal="true" aria-label="Pencarian cepat">
                <form class="topbar-search-box js-topbar-search-box" action="<?= e(url('/services')) ?>" method="get" role="search">
                    <button class="topbar-search-box__back js-topbar-search-close" type="button" aria-label="Tutup pencarian"><i class="bi bi-arrow-left"></i></button>
                    <input class="js-topbar-search-input" name="q" type="search" placeholder="Cari..." autocomplete="off" aria-label="Cari pelanggan atau agenda">
                    <i class="bi bi-search"></i>
                </form>
                <div class="topbar-search-results">
                    <section class="topbar-search-card">
                        <h2>Pelanggan (Baru Ditambahkan)</h2>
                        <div class="topbar-search-list js-topbar-search-customers"></div>
                        <p class="topbar-search-empty js-topbar-search-customers-empty" hidden>Tidak ada pelanggan.</p>
                    </section>
                    <section class="topbar-search-card">
                        <h2>Agenda Yang Akan Datang</h2>
                        <div class="topbar-search-list js-topbar-search-agenda"></div>
                        <p class="topbar-search-empty js-topbar-search-agenda-empty" hidden>Tidak ada agenda.</p>
                    </section>
                </div>
            </div>
        </div>

        <section class="content-panel">
            <?php if (!empty($success)): ?>
                <div class="alert alert-success border-0 rounded-4"><?= e($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger border-0 rounded-4"><?= e($error) ?></div>
            <?php endif; ?>
            <?= $content ?>
        </section>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
