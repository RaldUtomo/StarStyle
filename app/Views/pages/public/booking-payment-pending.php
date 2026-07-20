<?php $confirmationEmail = trim((string) flash('booking_completion_email')); ?>
<section class="booking-pending-screen">
    <div class="booking-pending-screen__shell">
        <div class="booking-pending-card">
            <span class="booking-pending-card__icon"><i class="bi bi-check-lg"></i></span>
            <h1>Pesanan Anda telah berhasil dibuat!</h1>
            <p>Konfirmasi email telah dikirim ke</p>
            <strong class="booking-pending-card__email"><?= e($confirmationEmail !== '' ? $confirmationEmail : 'email Anda') ?></strong>
            <a class="booking-pending-card__action" href="<?= e(url('/booking')) ?>">Buat pesanan lain</a>
        </div>
    </div>
</section>
