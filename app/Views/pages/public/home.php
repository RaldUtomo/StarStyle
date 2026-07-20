<section class="hero-section" style="background-image: url('<?= e(asset('img/hero.jpg')) ?>');">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <h1 class="hero-title">Ciptakan Cerita Rambut Unik Anda</h1>
        <p class="hero-copy">Sistem reservasi modern berbasis web. Nikmati kemudahan booking jadwal, manajemen promo, dan layanan terpusat dari HP maupun laptop Anda tanpa instalasi.</p>
        <div class="d-flex gap-3">
            <a class="btn btn-ref-solid" href="<?= e(url('/booking')) ?>">Reservasi Sekarang</a>
            <a class="btn btn-ref-outline" href="#our-services">Lihat Layanan</a>
        </div>
    </div>
</section>

<section class="ref-section ref-section-white">
    <div class="container">
        <h2 class="ref-section-title">Pengalaman Salon Terbaik</h2>
        <p class="ref-section-subtitle">Kami memastikan proses dari pemesanan hingga perawatan berjalan dengan sangat rapi dan eksklusif. Nikmati fitur unggulan kami yang dirancang khusus untuk kenyamanan Anda.</p>
        
        <div class="row g-5 mt-4">
            <div class="col-md-4">
                <div class="info-col">
                    <div class="info-icon"><i class="bi bi-calendar-check"></i></div>
                    <div class="info-title">Reservasi Online</div>
                    <div class="info-desc">Atur jadwal secara mandiri, pilih tanggal, jam, dan layanan tanpa harus menunggu balasan admin.</div>
                    <a class="btn btn-ref-outline mt-2" href="<?= e(url('/booking')) ?>">Atur Jadwal</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-col">
                    <div class="info-icon"><i class="bi bi-person-lines-fill"></i></div>
                    <div class="info-title">Manajemen Akun</div>
                    <div class="info-desc">Daftarkan akun untuk menikmati kemudahan melihat dan melacak riwayat layanan favorit Anda.</div>
                    <a class="btn btn-ref-outline mt-2" href="<?= e(url(!empty($customerUser) ? '/customer/account' : '/customer/login')) ?>">Masuk</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-col">
                    <div class="info-icon"><i class="bi bi-tag"></i></div>
                    <div class="info-title">Promo Digital</div>
                    <div class="info-desc">Dapatkan akses ke promosi eksklusif yang hanya tersedia melalui sistem website kami.</div>
                    <a class="btn btn-ref-outline mt-2" href="#our-services">Lihat Promo</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="ref-section ref-section-cream">
    <div class="container">
        <div class="staggered-block flex-column flex-lg-row">
            <div class="staggered-images w-100 mb-5 mb-lg-0">
                <img src="<?= e(asset('img/treatment.png')) ?>" alt="Treatment">
                <img src="<?= e(asset('img/ladies.png')) ?>" alt="Styling">
            </div>
            <div class="staggered-text w-100">
                <h2>Tingkatkan Penampilan Anda di Oasis Kemewahan</h2>
                <p>StarStyle memberikan dedikasi penuh pada setiap helai rambut Anda. Sistem digital kami mendukung para staf profesional untuk memberikan layanan yang konsisten dan sesuai dengan preferensi Anda dari sejak kunjungan pertama.</p>
                <p>Duduk, bersantai, dan biarkan sistem kami yang mengatur segala sesuatunya, dari pengingat jadwal otomatis hingga manajemen pembayaran yang mudah.</p>
                <a class="btn btn-ref-solid mt-4" href="#our-services">Lihat Katalog Layanan</a>
            </div>
        </div>
    </div>
</section>

<section class="ref-section ref-section-white" id="our-services">
    <div class="container">
        <h2 class="ref-section-title">Layanan Kami</h2>
        <p class="ref-section-subtitle">Tersedia berbagai kategori layanan untuk memenuhi segala kebutuhan kecantikan dan ketampanan Anda, dari perawatan dasar hingga styling eksklusif.</p>
        
        <div class="row g-4 mt-2 justify-content-center">
            <?php 
            $images = ['ladies.png', 'mens.png', 'treatment.png'];
            $i = 0;
            foreach ($groups as $groupItem): 
                if (empty($groupItem['services'])) continue;
                $img = $images[$i % count($images)];
                $i++;
            ?>
            <div class="col-md-4">
                <div class="service-card">
                    <img src="<?= e(asset('img/' . $img)) ?>" alt="<?= e($groupItem['group']['name']) ?>">
                    <h3><?= e($groupItem['group']['name']) ?></h3>
                    <p><?= e($groupItem['services'][0]['description'] ?? 'Layanan profesional dengan standar tertinggi dari staf kami.') ?></p>
                    <a class="btn btn-ref-outline mt-3" href="<?= e(url(!empty($customerUser) ? '/booking?group=' . $groupItem['group']['id'] : '/customer/login?redirect=' . rawurlencode('/booking?group=' . $groupItem['group']['id']))) ?>">Lihat Detail</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="cta-bar">
    <div class="container text-center">
        <div class="d-flex align-items-center justify-content-center gap-4 flex-wrap">
            <h2>Siap Untuk Melakukan Reservasi?</h2>
            <a class="btn btn-ref-outline" href="<?= e(url('/booking')) ?>">Atur Jadwal Sekarang</a>
        </div>
    </div>
</section>
