<?php
$reviews = $reviews ?? [];
$messageLogs = $messageLogs ?? [];
$logOptionsSource = $logOptionsSource ?? $messageLogs;
$logs = $logs ?? [];

$reviewRowTimestamp = static function (array $row, string $key): int {
    $value = trim((string) ($row[$key] ?? ''));
    $timestamp = $value !== '' ? strtotime($value) : false;

    return $timestamp !== false ? $timestamp : 0;
};

usort($reviews, static function (array $left, array $right) use ($reviewRowTimestamp): int {
    return $reviewRowTimestamp($right, 'date') <=> $reviewRowTimestamp($left, 'date');
});

usort($messageLogs, static function (array $left, array $right) use ($reviewRowTimestamp): int {
    return $reviewRowTimestamp($right, 'created_at') <=> $reviewRowTimestamp($left, 'created_at');
});

$activeTab = in_array(($activeTab ?? 'customer'), ['customer', 'logs'], true) ? $activeTab : 'customer';
$reviewFilters = is_array($reviewFilters ?? null) ? $reviewFilters : [];
$logFilters = is_array($logFilters ?? null) ? $logFilters : [];
$activeRating = (int) ($reviewFilters['rating'] ?? 0);
$reviewSearch = (string) ($reviewFilters['search'] ?? '');
$logSearch = (string) ($logFilters['search'] ?? '');
$activeLogCustomer = trim((string) ($logFilters['customer'] ?? ''));
$activeLogCustomer = $activeLogCustomer === '' ? 'Pelanggan' : $activeLogCustomer;
$activePreset = (string) ($reviewFilters['preset'] ?? '7d');
$today = new DateTimeImmutable('today');
$rangeStart = new DateTimeImmutable((string) ($reviewFilters['start_date'] ?? $today->modify('-6 days')->format('Y-m-d')));
$rangeEnd = new DateTimeImmutable((string) ($reviewFilters['end_date'] ?? $today->format('Y-m-d')));
$presetLabels = [
    'today' => 'Hari ini',
    'yesterday' => 'Kemarin',
    'this_month' => 'Bulan ini',
    '7d' => '7 hari sebelumnya',
    '30d' => '30 hari sebelumnya',
    'last_month' => 'Bulan kemarin',
    'this_year' => 'Tahun ini',
    'last_year' => 'Tahun kemarin',
];
$rangeLabel = sprintf('%s, %s - %s', $presetLabels[$activePreset] ?? 'Custom', $rangeStart->format('j M Y'), $rangeEnd->format('j M Y'));
$reviewUrl = static function (array $overrides = []) use ($activeRating, $reviewSearch, $rangeStart, $rangeEnd, $activePreset): string {
    $params = array_merge([
        'tab' => 'customer',
        'rating' => $activeRating > 0 ? (string) $activeRating : '',
        'review_search' => $reviewSearch,
        'start_date' => $rangeStart->format('Y-m-d'),
        'end_date' => $rangeEnd->format('Y-m-d'),
        'preset' => $activePreset,
    ], $overrides);
    $params = array_filter($params, static fn (mixed $value): bool => trim((string) $value) !== '');

    return url('/reviews') . ($params === [] ? '' : '?' . http_build_query($params));
};
$logUrl = static function (array $overrides = []) use ($activeLogCustomer, $logSearch): string {
    $params = array_merge([
        'tab' => 'logs',
        'log_customer' => $activeLogCustomer !== 'Pelanggan' ? $activeLogCustomer : '',
        'log_search' => $logSearch,
    ], $overrides);
    $params = array_filter($params, static fn (mixed $value): bool => trim((string) $value) !== '');

    return url('/reviews') . ($params === [] ? '' : '?' . http_build_query($params));
};
$averageRating = count($reviews) > 0
    ? array_sum(array_map(fn (array $review): int => (int) ($review['rating'] ?? 0), $reviews)) / count($reviews)
    : 0;
$ratingSummary = [];
for ($rating = 5; $rating >= 1; $rating--) {
    $ratingSummary[$rating] = count(array_filter($reviews, static fn (array $review): bool => (int) ($review['rating'] ?? 0) === $rating));
}
$logOptions = [];
foreach ($logOptionsSource as $notification) {
    $customer = trim((string) ($notification['customer'] ?? 'Pelanggan'));
    if ($customer === '') {
        $customer = 'Pelanggan';
    }
    $logOptions[$customer] = [
        'customer' => $customer,
        'email' => (string) ($notification['email'] ?? '-'),
        'type' => (string) ($notification['type_label'] ?? ucfirst((string) ($notification['type'] ?? 'Notification'))),
    ];
}
?>

<section class="reviews-shell js-reviews-shell" data-active-tab="<?= e($activeTab) ?>" data-active-rating="<?= e((string) $activeRating) ?>" data-active-log-customer="<?= e($activeLogCustomer) ?>" data-active-start-date="<?= e($rangeStart->format('Y-m-d')) ?>" data-active-end-date="<?= e($rangeEnd->format('Y-m-d')) ?>" data-active-preset="<?= e($activePreset) ?>">
    <div class="reviews-tabs">
        <button class="reviews-tab <?= $activeTab === 'customer' ? 'is-active' : '' ?>" type="button" data-reviews-tab="customer">Customer Review</button>
        <button class="reviews-tab <?= $activeTab === 'logs' ? 'is-active' : '' ?>" type="button" data-reviews-tab="logs">Message Logs</button>
    </div>

    <div class="reviews-panels">
        <section class="reviews-panel <?= $activeTab === 'customer' ? 'is-active' : '' ?>" data-reviews-panel="customer">
            <div class="reviews-toolbar">
                <div class="reviews-toolbar__group">
                    <div class="dropdown">
                        <button class="dashboard-filter dashboard-filter--shop" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-shop"></i>
                            <span data-reviews-shop-label>Star Salon</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu reviews-filter-menu">
                            <button class="dropdown-item analytics-filter-option is-active" type="button" data-reviews-shop-option="Star Salon">Star Salon</button>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="dashboard-filter reviews-filter-rating" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span data-reviews-rating-label><?= $activeRating > 0 ? e((string) $activeRating . ' Star') : 'All Ratings' ?></span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu reviews-filter-menu reviews-rating-menu">
                            <a class="reviews-rating-option <?= $activeRating === 0 ? 'is-active' : '' ?>" href="<?= e($reviewUrl(['rating' => ''])) ?>" data-review-rating-option="All Ratings">
                                <strong>All Ratings</strong>
                            </a>
                            <?php for ($rating = 1; $rating <= 5; $rating++): ?>
                                <a class="reviews-rating-option <?= $activeRating === $rating ? 'is-active' : '' ?>" href="<?= e($reviewUrl(['rating' => (string) $rating])) ?>" data-review-rating-option="<?= e((string) $rating) ?>">
                                    <strong><?= e((string) $rating) ?></strong>
                                    <span class="reviews-rating-option__stars" aria-hidden="true">
                                        <?php for ($starIndex = 1; $starIndex <= 5; $starIndex++): ?>
                                            <i class="bi bi-star-fill <?= $starIndex <= $rating ? 'is-filled' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </span>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <button class="dashboard-filter dashboard-filter--wide" type="button" data-bs-toggle="modal" data-bs-target="#reviewsDateFilterModal">
                        <i class="bi bi-calendar3"></i>
                        <span data-reviews-range-label><?= e($rangeLabel) ?></span>
                    </button>
                </div>
                <div class="reviews-toolbar__group reviews-toolbar__group--end">
                    <form class="reviews-search-form" method="get" action="<?= e(url('/reviews')) ?>">
                        <input type="hidden" name="tab" value="customer">
                        <?php if ($activeRating > 0): ?>
                            <input type="hidden" name="rating" value="<?= e((string) $activeRating) ?>">
                        <?php endif; ?>
                        <input type="hidden" name="start_date" value="<?= e($rangeStart->format('Y-m-d')) ?>">
                        <input type="hidden" name="end_date" value="<?= e($rangeEnd->format('Y-m-d')) ?>">
                        <input type="hidden" name="preset" value="<?= e($activePreset) ?>">
                    <label class="sales-search-field reviews-search">
                        <input class="js-reviews-search" name="review_search" type="search" placeholder="Cari review atau pelanggan" value="<?= e($reviewSearch) ?>" autocomplete="off">
                        <i class="bi bi-search"></i>
                    </label>
                    </form>
                </div>
            </div>

            <div class="reviews-summary-card">
                <div class="reviews-summary-cell reviews-summary-cell--brand">
                    <div class="reviews-summary-icon"><i class="bi bi-shop"></i></div>
                    <strong>Star Salon</strong>
                </div>
                <div class="reviews-summary-cell">
                    <strong><?= e(number_format($averageRating, 1)) ?></strong>
                    <div class="reviews-stars">
                        <?php for ($index = 1; $index <= 5; $index++): ?>
                            <i class="bi bi-star-fill <?= $index <= round($averageRating) ? 'is-filled' : '' ?>"></i>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="reviews-summary-cell">
                    <i class="bi bi-chat-left-text"></i>
                    <strong><?= e((string) count($reviews)) ?> Review(s)</strong>
                </div>
                <div class="reviews-summary-cell">
                    <i class="bi bi-bar-chart"></i>
                    <strong><?= e((string) ($ratingSummary[5] ?? 0)) ?> Bintang 5</strong>
                </div>
            </div>

            <div class="customers-table-card reviews-review-shell">
                <div class="reviews-review-board">
                    <div class="reviews-review-list js-reviews-list">
                        <?php foreach ($reviews as $review): ?>
                            <?php
                            $search = strtolower(trim(implode(' ', [
                                (string) ($review['customer'] ?? ''),
                                (string) ($review['feedback'] ?? ''),
                                (string) ($review['email'] ?? ''),
                                (string) ($review['agenda'] ?? ''),
                            ])));
                            $ratingValue = max(0, min(5, (int) ($review['rating'] ?? 0)));
                            ?>
                            <article
                                class="reviews-review-card js-review-card"
                                data-review-rating="<?= e((string) $ratingValue) ?>"
                                data-review-date="<?= e(substr((string) ($review['date'] ?? ''), 0, 10)) ?>"
                                data-search="<?= e($search) ?>"
                            >
                                <div class="reviews-review-card__top">
                                    <div class="reviews-review-card__identity">
                                        <strong><?= e((string) ($review['customer'] ?? 'Pelanggan')) ?></strong>
                                        <span><?= e((string) ($review['agenda'] ?? 'Review')) ?></span>
                                    </div>
                                    <span class="reviews-review-card__date"><?= e((string) ($review['date'] ?? '-')) ?></span>
                                </div>
                                <div class="reviews-review-card__rating" aria-label="Rating <?= e((string) $ratingValue) ?> dari 5 bintang">
                                    <span class="reviews-review-card__stars" aria-hidden="true">
                                        <?php for ($starIndex = 1; $starIndex <= 5; $starIndex++): ?>
                                            <i class="bi bi-star-fill <?= $starIndex <= $ratingValue ? 'is-filled' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </span>
                                    <span class="reviews-review-card__rating-value"><?= e(number_format((float) $ratingValue, 1)) ?></span>
                                </div>
                                <p class="reviews-review-card__feedback"><?= e((string) ($review['feedback'] ?? 'Tanpa komentar')) ?></p>
                            </article>
                        <?php endforeach; ?>
                        <div class="reviews-empty-card js-reviews-empty" <?= $reviews !== [] ? 'hidden' : '' ?>>
                            <div class="reviews-empty-card__icon"><i class="bi bi-chat-left"></i></div>
                            <h2>Belum ada review</h2>
                            <p>Review pelanggan yang cocok dengan filter akan tampil di sini.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="reviews-panel <?= $activeTab === 'logs' ? 'is-active' : '' ?>" data-reviews-panel="logs">
            <div class="reviews-toolbar">
                <div class="reviews-toolbar__group">
                    <div class="dropdown">
                        <button class="dashboard-filter reviews-filter-rating reviews-filter-rating--logs" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span data-reviews-log-label><?= e($activeLogCustomer) ?></span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu reviews-filter-menu reviews-log-filter-menu">
                            <a class="reviews-log-filter-option <?= $activeLogCustomer === 'Pelanggan' ? 'is-active' : '' ?>" href="<?= e($logUrl(['log_customer' => ''])) ?>" data-review-log-option="Pelanggan" data-review-log-email="-" data-review-log-type="-">
                                <strong>Pelanggan</strong>
                                <span>Email</span>
                                <span>Tipe Notifikasi</span>
                            </a>
                            <?php foreach ($logOptions as $option): ?>
                                <a class="reviews-log-filter-option <?= $activeLogCustomer === $option['customer'] ? 'is-active' : '' ?>" href="<?= e($logUrl(['log_customer' => $option['customer']])) ?>" data-review-log-option="<?= e($option['customer']) ?>" data-review-log-email="<?= e($option['email']) ?>" data-review-log-type="<?= e($option['type']) ?>">
                                    <strong><?= e($option['customer']) ?></strong>
                                    <span><?= e($option['email']) ?></span>
                                    <span><?= e($option['type']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <form class="reviews-search-form" method="get" action="<?= e(url('/reviews')) ?>">
                        <input type="hidden" name="tab" value="logs">
                        <?php if ($activeLogCustomer !== 'Pelanggan'): ?>
                            <input type="hidden" name="log_customer" value="<?= e($activeLogCustomer) ?>">
                        <?php endif; ?>
                        <label class="sales-search-field reviews-search">
                            <input class="js-reviews-log-search" name="log_search" type="search" placeholder="Cari log pesan" value="<?= e($logSearch) ?>" autocomplete="off">
                            <i class="bi bi-search"></i>
                        </label>
                    </form>
                </div>
            </div>

            <div class="reviews-log-stack">
                <div class="customers-table-card reviews-log-card reviews-log-card--notifications">
                    <div class="customers-table-scroll reviews-log-scroll">
                        <table class="customers-list-table reviews-log-table reviews-log-table--notifications">
                            <thead>
                                <tr>
                                    <th>Pelanggan</th>
                                    <th>Email</th>
                                    <th>Tipe Notifikasi</th>
                                    <th>Pesan</th>
                                    <th>Waktu Terkirim</th>
                                    <th>Agenda</th>
                                </tr>
                            </thead>
                            <tbody class="js-review-log-body">
                                <?php foreach ($messageLogs as $notification): ?>
                                    <?php
                                    $typeLabel = (string) ($notification['type_label'] ?? ucfirst((string) ($notification['type'] ?? 'Notification')));
                                    $search = strtolower(trim(implode(' ', [
                                        (string) ($notification['customer'] ?? ''),
                                        (string) ($notification['email'] ?? ''),
                                        $typeLabel,
                                        (string) ($notification['title'] ?? ''),
                                        (string) ($notification['agenda'] ?? ''),
                                    ])));
                                    ?>
                                    <tr
                                        class="js-review-log-row"
                                        data-log-customer="<?= e((string) ($notification['customer'] ?? 'Pelanggan')) ?>"
                                        data-search="<?= e($search) ?>"
                                    >
                                        <td><?= e((string) ($notification['customer'] ?? 'Pelanggan')) ?></td>
                                        <td><?= e((string) ($notification['email'] ?? '-')) ?></td>
                                        <td><?= e($typeLabel) ?></td>
                                        <td><?= e((string) ($notification['title'] ?? '-')) ?></td>
                                        <td><?= e((string) ($notification['created_at'] ?? '-')) ?></td>
                                        <td><?= e((string) ($notification['agenda'] ?? 'Notification')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="js-review-log-empty" <?= $messageLogs !== [] ? 'hidden' : '' ?>>
                                    <td colspan="6" class="sales-no-data">No Data</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="sales-pagination sales-pagination--services sales-pagination--services-primary sales-pagination--fixed reviews-log-footer js-reviews-log-pagination">
                        <div class="sales-pagination__meta">Total <span class="js-reviews-log-total"><?= e((string) count($messageLogs)) ?></span></div>
                        <div class="sales-pagination__select-wrap">
                            <button type="button" class="sales-pagination__select js-reviews-log-page-size-toggle" aria-expanded="false">
                                <span class="sales-pagination__select-label js-reviews-log-page-size-label">20/page</span><i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="sales-pagination__select-menu js-reviews-log-page-size-menu" hidden>
                                <button type="button" class="sales-pagination__select-option" data-reviews-log-page-size="10">10/page</button>
                                <button type="button" class="sales-pagination__select-option is-active" data-reviews-log-page-size="20">20/page</button>
                                <button type="button" class="sales-pagination__select-option" data-reviews-log-page-size="30">30/page</button>
                                <button type="button" class="sales-pagination__select-option" data-reviews-log-page-size="50">50/page</button>
                            </div>
                        </div>
                        <button type="button" class="sales-pagination__nav js-reviews-log-prev" aria-label="Halaman sebelumnya"><i class="bi bi-chevron-left"></i></button>
                        <div class="sales-pagination__pages js-reviews-log-pages" aria-label="Pagination"><span class="sales-pagination__page is-active">1</span></div>
                        <button type="button" class="sales-pagination__nav js-reviews-log-next" aria-label="Halaman berikutnya"><i class="bi bi-chevron-right"></i></button>
                        <div class="sales-pagination__goto">Go to</div>
                        <input class="sales-pagination__input js-reviews-log-page-current" type="text" value="1" inputmode="numeric" pattern="[0-9]*" aria-label="Go to message log page">
                        <button type="button" class="sales-pagination__top js-reviews-log-top" aria-label="Scroll ke atas"><i class="bi bi-chevron-up"></i></button>
                    </div>
                </div>
            </div>
        </section>
    </div>
</section>

<div class="modal fade" id="reviewsDateFilterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content customers-date-modal">
            <div class="customers-date-modal__header">
                <h2>Date Filter</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="customers-date-modal__body">
                <div class="customers-date-grid">
                    <div class="customers-date-presets">
                        <button class="customers-date-preset js-reviews-date-preset <?= $activePreset === 'today' ? 'is-active' : '' ?>" type="button" data-preset="today">Hari ini</button>
                        <div class="customers-date-presets__row">
                            <button class="customers-date-preset js-reviews-date-preset <?= $activePreset === 'this_month' ? 'is-active' : '' ?>" type="button" data-preset="this_month">Bulan ini</button>
                            <button class="customers-date-preset js-reviews-date-preset <?= $activePreset === 'yesterday' ? 'is-active' : '' ?>" type="button" data-preset="yesterday">Kemarin</button>
                        </div>
                        <button class="customers-date-preset js-reviews-date-preset <?= $activePreset === '7d' ? 'is-active' : '' ?>" type="button" data-preset="7d">7 hari sebelumnya</button>
                        <button class="customers-date-preset js-reviews-date-preset <?= $activePreset === '30d' ? 'is-active' : '' ?>" type="button" data-preset="30d">30 hari sebelumnya</button>
                        <div class="customers-date-presets__row">
                            <button class="customers-date-preset js-reviews-date-preset <?= $activePreset === 'last_month' ? 'is-active' : '' ?>" type="button" data-preset="last_month">Bulan kemarin</button>
                            <button class="customers-date-preset js-reviews-date-preset <?= $activePreset === 'last_year' ? 'is-active' : '' ?>" type="button" data-preset="last_year">Tahun kemarin</button>
                        </div>
                        <button class="customers-date-preset js-reviews-date-preset <?= $activePreset === 'this_year' ? 'is-active' : '' ?>" type="button" data-preset="this_year">Tahun ini</button>
                    </div>

                    <div class="customers-date-picker">
                        <div class="customers-date-fields">
                            <div>
                                <label>Mulai Tanggal</label>
                                <input class="form-control customers-date-input js-reviews-start" type="text" value="<?= e($rangeStart->format('Y-m-d')) ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                            </div>
                            <div>
                                <label>Sampai Tanggal</label>
                                <input class="form-control customers-date-input js-reviews-end" type="text" value="<?= e($rangeEnd->format('Y-m-d')) ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                            </div>
                        </div>

                        <div class="customers-date-inline">
                            <input class="js-reviews-date-range customers-date-range-input" type="text" aria-hidden="true" tabindex="-1">
                        </div>
                    </div>
                </div>
            </div>
            <div class="customers-date-modal__footer">
                <button type="button" class="customer-footer-btn js-reviews-date-reset">Reset</button>
                <button type="button" class="customer-footer-btn customers-date-apply js-reviews-date-apply" data-bs-dismiss="modal">Terapkan</button>
            </div>
        </div>
    </div>
</div>


