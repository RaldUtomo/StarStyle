<?php
$payment = $bookingPayment ?? [];
$totalPrice = (float) ($payment['total_price'] ?? 0);
?>

<section class="booking-payment-screen">
    <div class="booking-payment-screen__shell">
        <header class="booking-picker-header booking-payment-screen__header">
            <a class="booking-picker-back" href="<?= e(url('/booking/confirmation')) ?>" aria-label="Kembali ke konfirmasi pesanan">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h1>Pembayaran</h1>
            <span class="booking-picker-header__spacer" aria-hidden="true"></span>
        </header>

        <form method="post" action="<?= e(url('/booking/payment/complete')) ?>" class="booking-payment-form js-booking-payment-form">
            <?= csrf_field() ?>
            <input class="js-booking-payment-method-input" type="hidden" name="payment_method" value="PAY_AT_VENUE">

            <section class="booking-payment-total-card">
                <div>
                    <small>Bayar</small>
                    <strong><?= e(money($totalPrice)) ?></strong>
                </div>
                <i class="bi bi-chevron-right"></i>
            </section>

            <button class="booking-payment-method is-selected js-booking-payment-method" type="button" data-payment-method="PAY_AT_VENUE" aria-pressed="true">
                <span class="booking-payment-method__radio"></span>
                <span class="booking-payment-method__logo booking-payment-method__logo--onsite">
                    <i class="bi bi-wallet2"></i>
                </span>
                <span class="booking-payment-method__copy">
                    <strong>Bayar Di Tempat</strong>
                    <small>Pay later at your booking location</small>
                </span>
            </button>

            <button class="booking-payment-submit js-booking-payment-submit" type="submit">Continue</button>
        </form>
    </div>
</section>
