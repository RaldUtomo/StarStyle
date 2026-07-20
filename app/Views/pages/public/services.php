<!-- ===== Services Catalog Page ===== -->
<section class="svc-catalog">
    <!-- Page Hero -->
    <div class="svc-catalog__hero">
        <div class="container">
            <div class="svc-catalog__hero-inner">
                <div>
                    <span class="svc-catalog__eyebrow">Catalog Layanan</span>
                    <h1 class="svc-catalog__title">Daftar Layanan StarStyle</h1>
                    <p class="svc-catalog__subtitle">Temukan layanan terbaik kami untuk merawat &amp; memperindah penampilan Anda.</p>
                </div>
                <a class="svc-catalog__cta-btn" href="<?= e(url('/booking')) ?>">
                    <i class="bi bi-calendar-check"></i>
                    Buat Booking
                </a>
            </div>
        </div>
    </div>

    <!-- Category Sections -->
    <div class="container svc-catalog__body">
        <?php
        $groupIcons = [
            'Hair Signature' => 'bi-scissors',
            'Color Studio'   => 'bi-palette',
            'Spa & Nail'     => 'bi-gem',
        ];
        foreach ($groups as $bundle):
            if (empty($bundle['services'])) continue;
            $groupName = $bundle['group']['name'];
            $icon = $groupIcons[$groupName] ?? 'bi-star';
        ?>
        <div class="svc-category">
            <!-- Category Header -->
            <div class="svc-category__header">
                <div class="svc-category__icon-wrap">
                    <i class="bi <?= $icon ?>"></i>
                </div>
                <div>
                    <h2 class="svc-category__name"><?= e($groupName) ?></h2>
                    <span class="svc-category__count"><?= count($bundle['services']) ?> layanan tersedia</span>
                </div>
            </div>

            <!-- Service Cards Grid -->
            <div class="svc-grid">
                <?php foreach ($bundle['services'] as $service): ?>
                <div class="svc-card">
                    <div class="svc-card__top">
                        <span class="svc-card__status svc-card__status--<?= strtolower(e($service['status'])) ?>">
                            <span class="svc-card__status-dot"></span>
                            <?= e($service['status']) ?>
                        </span>
                        <span class="svc-card__duration">
                            <i class="bi bi-clock"></i>
                            <?= e((string) $service['duration']) ?> menit
                        </span>
                    </div>

                    <div class="svc-card__body">
                        <h3 class="svc-card__name"><?= e($service['name']) ?></h3>
                        <p class="svc-card__desc"><?= e($service['description']) ?></p>

                        <?php if (!empty($service['variants'])): ?>
                        <div class="svc-card__variants">
                            <?php foreach ($service['variants'] as $variant): ?>
                            <span class="svc-card__variant-tag"><?= e($variant) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="svc-card__footer">
                        <div class="svc-card__price"><?= money($service['price']) ?></div>
                        <a href="<?= e(url('/booking')) ?>" class="svc-card__book-btn">
                            Booking <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
