<!-- ===== Customer Login Page — Premium Navy Theme ===== -->
<section class="login-page">
    <!-- Left: Decorative Navy Panel (Hidden on Mobile) -->
    <div class="login-page__decor">
        <div class="login-page__decor-content">
            <div class="login-page__decor-logo">S</div>
            <h2 class="login-page__decor-title">StarStyle</h2>
            <p class="login-page__decor-tagline">Nikmati layanan salon kecantikan dan perawatan rambut premium kelas dunia dengan penjadwalan yang mudah.</p>
            <div class="login-page__decor-dots">
                <span class="active"></span><span></span><span></span>
            </div>
        </div>
        <!-- Decorative gradients and circles -->
        <div class="login-page__decor-circle login-page__decor-circle--1"></div>
        <div class="login-page__decor-circle login-page__decor-circle--2"></div>
        <div class="login-page__decor-glow"></div>
    </div>

    <!-- Right: Login Form Panel -->
    <div class="login-page__form-wrap">
        <div class="login-page__form-inner">
            
            <!-- Mobile Brand Header (Visible only on Mobile) -->
            <div class="login-page__mobile-brand">
                <div class="login-page__mobile-logo">S</div>
                <span class="login-page__mobile-title">StarStyle</span>
            </div>

            <!-- Back Link -->
            <a class="login-page__back" href="<?= e(url((string) ($redirectAfterLogin ?: '/'))) ?>" aria-label="Kembali">
                <i class="bi bi-arrow-left-short"></i>
                <span>Kembali</span>
            </a>

            <!-- Card Container for Form (Gives structure on desktop and mobile) -->
            <div class="login-page__card">
                <div class="login-page__heading">
                    <h1 class="login-page__title">Selamat Datang</h1>
                    <p class="login-page__subtitle">Silakan masuk ke akun Anda</p>
                </div>

                <form method="post" action="<?= e(url('/customer/login')) ?>" class="login-page__form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect" value="<?= e((string) ($redirectAfterLogin ?? '')) ?>">

                    <div class="login-page__field">
                        <label class="login-page__label" for="login-email">Email</label>
                        <div class="login-page__input-wrapper">
                            <i class="bi bi-envelope login-page__input-icon"></i>
                            <input class="login-page__input" id="login-email" type="email" name="email" placeholder="nama@email.com" required>
                        </div>
                    </div>

                    <div class="login-page__field">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="login-page__label" for="login-password">Password</label>
                        </div>
                        <div class="login-page__input-wrapper">
                            <i class="bi bi-lock login-page__input-icon"></i>
                            <input class="login-page__input" id="login-password" type="password" name="password" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button class="login-page__submit" type="submit">
                        <span>Masuk ke Akun</span>
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </form>

                <div class="login-page__divider">
                    <span>atau masuk menggunakan</span>
                </div>

                <!-- Google login button -->
                <a
                    class="login-page__google <?= !empty($googleLoginEnabled) ? '' : 'is-disabled' ?>"
                    href="<?= !empty($googleLoginEnabled) ? e(url('/customer/google?redirect=' . rawurlencode((string) ($redirectAfterLogin ?? '')))) : '#' ?>"
                    aria-disabled="<?= !empty($googleLoginEnabled) ? 'false' : 'true' ?>"
                >
                    <svg class="login-page__google-svg" viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/>
                    </svg>
                    <span>Google</span>
                </a>
            </div>

            <!-- Footer links -->
            <p class="login-page__footer-text">
                Belum memiliki akun? <a href="<?= e(url('/booking')) ?>">Daftar &amp; Booking Sekarang</a>
            </p>
        </div>
    </div>
</section>
