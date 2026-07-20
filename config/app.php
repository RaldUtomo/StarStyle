<?php

declare(strict_types=1);

if (!function_exists('env_value')) {
    function env_value(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        if (is_string($value)) {
            $normalized = strtolower($value);

            return match ($normalized) {
                'true', '(true)' => true,
                'false', '(false)' => false,
                'null', '(null)' => null,
                default => $value,
            };
        }

        return $value;
    }
}

return [
    'name' => 'StarStyle',
    'tagline' => 'Salon Management System',
    'description' => 'Platform reservasi dan operasional salon modern dengan dashboard, POS, CRM, inventory, analytics, serta kontrol akses staf.',
    'timezone' => (string) env_value('APP_TIMEZONE', 'Asia/Bangkok'),
    // Switch data source:
    // - demo: in-memory seeded data + session writes (default)
    // - db:   MySQL/MariaDB via PDO (XAMPP)
    'data_source' => (string) env_value('APP_DATA_SOURCE', 'db'),
    'db' => [
        'driver' => (string) env_value('DB_DRIVER', 'mysql'),
        'host' => (string) env_value('DB_HOST', '127.0.0.1'),
        'port' => (int) env_value('DB_PORT', 3306),
        // NOTE: schema.sql uses "starstyle" as database name.
        // If you created a different db name in phpMyAdmin (eg: db_starstyle),
        // update this value to match.
        'database' => (string) env_value('DB_DATABASE', 'starstyle'),
        'username' => (string) env_value('DB_USERNAME', 'root'),
        'password' => (string) env_value('DB_PASSWORD', ''),
        'charset' => (string) env_value('DB_CHARSET', 'utf8mb4'),
    ],
    'theme' => [
        'primary' => '#63b4ff',
        'secondary' => '#dff1ff',
        'accent' => '#4f84ff',
        'dark' => '#17324d',
        'soft' => '#f5faff',
    ],
    'business' => [
        'name' => (string) env_value('BUSINESS_NAME', 'StarStyle Salon'),
        'city' => (string) env_value('BUSINESS_CITY', 'Bangkok'),
        'hotline' => (string) env_value('BUSINESS_HOTLINE', '+66 2 555 0101'),
        'email' => (string) env_value('BUSINESS_EMAIL', 'hello@starstyle.test'),
        'hours' => (string) env_value('BUSINESS_HOURS', '09:00 - 20:00'),
        'address' => (string) env_value('BUSINESS_ADDRESS', 'Silom Creative Avenue, Bangkok'),
    ],
    'google_oauth' => [
        'client_id' => (string) env_value('GOOGLE_CLIENT_ID', ''),
        'client_secret' => (string) env_value('GOOGLE_CLIENT_SECRET', ''),
        'redirect_uri' => (string) env_value('GOOGLE_REDIRECT_URI', ''),
    ],
    'internal_nav' => [
        ['label' => 'Beranda', 'icon' => 'house-door', 'path' => '/dashboard', 'permission' => 'dashboard.view'],
        ['label' => 'Kalender', 'icon' => 'calendar3', 'path' => '/calendar', 'permission' => 'calendar.view'],
        ['label' => 'Penjualan', 'icon' => 'receipt', 'path' => '/sales', 'permission' => 'sales.view'],
        ['label' => 'Pelanggan', 'icon' => 'emoji-smile', 'path' => '/customers', 'permission' => 'customers.view'],
        ['label' => 'Staf', 'icon' => 'people', 'path' => '/staff', 'permission' => 'staff.view'],
        ['label' => 'Layanan', 'icon' => 'scissors', 'path' => '/services', 'permission' => 'services.view'],
        ['label' => 'Inventory', 'icon' => 'box-seam', 'path' => '/inventory', 'permission' => 'inventory.view'],
        ['label' => 'Voucher', 'icon' => 'ticket-perforated', 'path' => '/vouchers', 'permission' => 'vouchers.view'],
        ['label' => 'Analitik', 'icon' => 'graph-up-arrow', 'path' => '/analytics', 'permission' => 'analytics.view'],
        ['label' => 'Review & Logs', 'icon' => 'chat-left-heart', 'path' => '/reviews', 'permission' => 'reviews.view'],
        ['label' => 'Pengaturan', 'icon' => 'gear', 'path' => '/settings', 'permission' => 'settings.view'],
    ],
    'public_nav' => [
        ['label' => 'Beranda', 'path' => '/'],
        ['label' => 'Layanan', 'path' => '/services-catalog'],
        ['label' => 'Booking', 'path' => '/booking'],
    ],
    'date_presets' => [
        '7d' => '7 hari terakhir',
        '30d' => '30 hari terakhir',
        '90d' => '90 hari terakhir',
    ],
];
