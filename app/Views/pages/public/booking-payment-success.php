<?php $confirmationEmail = trim((string) flash('booking_completion_email')); ?>
<section class="booking-pending-screen">
    <div class="booking-pending-screen__shell">
        <div class="booking-pending-card">
            <span class="booking-pending-card__icon"><i class="bi bi-check-lg"></i></span>
            <h1>Booking sukses!</h1>
            <p>Reservasi Anda sudah masuk ke jadwal internal admin dan staff.</p>
            <strong class="booking-pending-card__email"><?= e($confirmationEmail !== '' ? $confirmationEmail : 'email Anda') ?></strong>
            <a class="booking-pending-card__action" href="<?= e(url('/booking')) ?>">Buat booking lain</a>
        </div>
    </div>
</section>
