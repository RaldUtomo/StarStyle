<?php
$accountUser = $accountUser ?? $currentUser ?? [];
$accountStaff = $accountStaff ?? null;
$accountState = $accountState ?? [];
$displayName = $accountState['name'] ?? $accountStaff['name'] ?? $accountUser['name'] ?? 'Rayhan Doni Pramana';
$displayRole = $accountState['role'] ?? $accountStaff['role_title'] ?? $accountStaff['role'] ?? ucfirst((string) ($accountUser['role'] ?? 'Owner'));
$displayEmail = $accountState['email'] ?? $accountStaff['email'] ?? $accountUser['email'] ?? 'rayhandonipramana01@gmail.com';
$displayPhone = $accountState['phone'] ?? $accountStaff['phone'] ?? '+6281221000156';
$displayPhoneLocal = ltrim(str_replace([' ', '-', '+62'], '', (string) $displayPhone), '0');
$joinedYear = (string) ($accountState['joined_year'] ?? '2026');
$photoDataUrl = (string) ($accountState['photo_data_url'] ?? '');
$emailVerified = !empty($accountState['email_verified']);
$phoneVerified = !empty($accountState['phone_verified']);
$accountVerified = !empty($accountState['account_verified']);
$otpEnabled = !empty($accountState['otp_email_enabled']);
$googleEnabled = !empty($accountState['google_authenticator_enabled']);
$facebookConnected = !empty($accountState['facebook_connected']);
?>

<section class="account-shell js-account-page"
    data-account-name="<?= e($displayName) ?>"
    data-account-email="<?= e($displayEmail) ?>"
    data-account-phone="<?= e($displayPhoneLocal) ?>"
    data-account-photo="<?= e($photoDataUrl) ?>"
>
    <div class="account-profile-card">
        <div class="account-profile-card__left">
            <div class="account-avatar js-account-avatar">
                <?php if ($photoDataUrl !== ''): ?>
                    <img src="<?= e($photoDataUrl) ?>" alt="<?= e($displayName) ?>">
                <?php else: ?>
                    <i class="bi bi-person"></i>
                <?php endif; ?>
            </div>
            <div class="account-badges">
                <span><i class="bi bi-person-plus"></i> <?= e($joinedYear) ?></span>
                <span><i class="bi bi-person"></i> <span class="js-account-role"><?= e($displayRole) ?></span></span>
            </div>
            <button class="account-action js-account-photo-button" type="button"><i class="bi bi-image"></i> Pilih Foto</button>
            <input class="js-account-photo-input" type="file" accept="image/*" hidden>
            <button class="account-action js-account-password-button" type="button"><i class="bi bi-key"></i> Ganti Password</button>
        </div>

        <div class="account-profile-card__right">
            <h2>Data Pribadi</h2>
            <p>Informasi yang hanya Anda yang mengetahui. Hanya untuk sign in</p>

            <label>Nama</label>
            <input class="account-field account-input js-account-name" type="text" value="<?= e($displayName) ?>">

            <label>Email</label>
            <div class="account-field account-field--with-link">
                <input class="account-inline-input js-account-email" type="email" value="<?= e($displayEmail) ?>">
                <button class="account-inline-link js-account-verify-email" type="button"><?= $emailVerified ? 'Terverifikasi' : 'Verifikasi' ?></button>
            </div>

            <label>Nomor Ponsel</label>
            <div class="account-phone-row">
                <div class="account-phone-code">+62</div>
                <input class="account-field account-field--phone account-input js-account-phone" type="text" value="<?= e($displayPhoneLocal) ?>">
                <button class="js-account-change-phone" type="button"><?= $phoneVerified ? 'Terverifikasi' : 'Ganti' ?></button>
            </div>
        </div>
    </div>

    <div class="account-save-status js-account-save-status" hidden></div>

    <div class="account-card account-verify-card">
        <div class="account-verify-card__icon">
            <i class="bi bi-person-vcard"></i>
            <span><i class="bi bi-check-lg"></i></span>
        </div>
        <div>
            <h2>Yuk, Verifikasi Akun Anda!</h2>
            <p class="js-account-verify-copy"><?= $accountVerified ? 'Profil Anda sudah lengkap dan terverifikasi.' : 'Verifikasi identitas Anda hanya dalam beberapa langkah untuk menikmati semua fitur platform kami. Prosesnya cepat, aman, dan hanya memerlukan waktu satu menit!' ?></p>
        </div>
        <button class="js-account-complete-profile" type="button"><?= $accountVerified ? 'Profil Lengkap' : 'Lengkapi Profil' ?></button>
    </div>

    <div class="account-card">
        <h2>Opsi sign-in</h2>
        <p>Gunakan Facebook untuk sign in</p>
        <button class="account-social js-account-facebook" type="button"><i class="bi bi-facebook"></i> <span><?= $facebookConnected ? 'Facebook Terhubung' : 'Gunakan Facebook' ?></span></button>
    </div>

    <div class="account-card">
        <h2>Keamanan</h2>
        <p>Lindungi akun anda dari akses tanpa izin</p>

        <div class="account-security-row">
            <i class="bi bi-envelope"></i>
            <div>
                <strong>Aktifkan Kode OTP Email</strong>
                <span>Anda wajib memasukan Kode OTP yang dikirim ke email Anda saat login</span>
            </div>
            <label class="account-switch">
                <input class="js-account-security-toggle" type="checkbox" data-account-security="otp_email_enabled" <?= $otpEnabled ? 'checked' : '' ?>>
                <span></span>
            </label>
        </div>

        <div class="account-security-row">
            <i class="bi bi-asterisk"></i>
            <div>
                <strong>Aktifkan Google Authenticator</strong>
                <span>Anda wajib memasukan kode Authentikasi dari aplikasi Google Authenticator saat login</span>
            </div>
            <label class="account-switch">
                <input class="js-account-security-toggle" type="checkbox" data-account-security="google_authenticator_enabled" <?= $googleEnabled ? 'checked' : '' ?>>
                <span></span>
            </label>
        </div>
    </div>
</section>