<?php

declare(strict_types=1);

namespace App\Controllers;

final class AdminController extends BaseController
{
    public function dashboard(): void
    {
        $this->authorize('dashboard.view');
        $range = (string) ($_GET['range'] ?? '7d');
        $this->internalPage('pages/admin/dashboard', 'Beranda', [
            'range' => $range,
            'data' => $this->repo()->dashboard($range),
            'presets' => config('date_presets'),
        ]);
    }

    public function account(): void
    {
        $user = $this->internalUser();
        $staff = isset($user['staff_id']) ? $this->repo()->findStaff((int) $user['staff_id']) : null;

        $this->internalPage('pages/admin/account', 'My Account', [
            'accountUser' => $user,
            'accountStaff' => $staff,
            'accountState' => $this->repo()->accountState((int) $user['id']),
        ]);
    }

    public function calendar(): void
    {
        $this->authorize('calendar.view');
        $date = (string) ($_GET['date'] ?? date('Y-m-d'));
        $this->internalPage('pages/admin/calendar', 'Kalender', $this->repo()->calendarPagePayload($date));
    }

    public function createInternalBooking(): void
    {
        $this->authorize('calendar.create');
        verify_csrf();
        $result = $this->repo()->createBooking($_POST, 'internal');
        flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/calendar');
    }

    public function createBlock(): void
    {
        $this->authorize('calendar.block');
        verify_csrf();
        $result = $this->repo()->createBlock($_POST, $this->internalUser());
        flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/calendar');
    }

    public function updateBlock(): void
    {
        $this->authorize('calendar.block');
        verify_csrf();
        $result = $this->repo()->updateBlock($_POST, $this->internalUser());
        flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/calendar');
    }

    public function deleteBlock(): void
    {
        $this->authorize('calendar.block');
        verify_csrf();
        $result = $this->repo()->deleteBlock($_POST, $this->internalUser());
        flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/calendar');
    }

    public function sales(): void
    {
        $this->authorize('sales.view');
        $this->internalPage('pages/admin/sales', 'Penjualan', $this->repo()->sales());
    }

    public function checkout(): void
    {
        $this->authorize('sales.checkout');
        verify_csrf();
        $result = $this->repo()->checkout($_POST, $this->internalUser());
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        if (str_contains($accept, 'application/json')) {
            header('Content-Type: application/json');
            echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }
        flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/sales');
    }

    public function customers(): void
    {
        $this->authorize('customers.view');
        $this->internalPage('pages/admin/customers', 'Pelanggan', [
            'customers' => $this->repo()->getCustomers(),
        ]);
    }

    public function staff(): void
    {
        $this->authorize('staff.view');
        $this->internalPage('pages/admin/staff', 'Staf', $this->repo()->staffDirectory());
    }

    public function services(): void
    {
        $this->authorize('services.view');
        $this->internalPage('pages/admin/services', 'Layanan', [
            'groups' => $this->repo()->getServiceGroups(),
            'services' => $this->repo()->getServices(),
            'packages' => $this->repo()->getPackages(),
            'staff' => $this->repo()->getStaff(),
            'products' => $this->repo()->getProducts(),
        ]);
    }

    public function inventory(): void
    {
        $this->authorize('inventory.view');
        $this->internalPage('pages/admin/inventory', 'Inventori', $this->repo()->inventoryPayload());
    }

    public function vouchers(): void
    {
        $this->authorize('vouchers.view');
        $this->internalPage('pages/admin/vouchers', 'Voucher', [
            'vouchers' => $this->repo()->getVouchers(),
            'discounts' => $this->repo()->getVoucherDiscounts(),
            'classes' => $this->repo()->getClasses(),
            'services' => $this->repo()->getServices(),
            'locations' => $this->repo()->getLocations(),
        ]);
    }

    public function analytics(): void
    {
        $this->authorize('analytics.view');
        $payload = $this->analyticsPayload();
        $this->internalPage('pages/admin/analytics', 'Analitik', $payload);
    }

    public function analyticsExport(): never
    {
        $this->authorize('analytics.view');
        $payload = $this->analyticsPayload();
        $filters = $payload['analyticsFilters'] ?? [];
        $transactions = array_values(array_filter(
            $payload['transactions'] ?? [],
            static fn (array $transaction): bool => strtolower((string) ($transaction['status'] ?? '')) === 'paid'
        ));
        $customersById = [];
        foreach (($payload['customers'] ?? []) as $customer) {
            $customersById[(int) ($customer['id'] ?? 0)] = (string) ($customer['name'] ?? '-');
        }
        $staffById = [];
        foreach (($payload['staff'] ?? []) as $staffMember) {
            $staffById[(int) ($staffMember['id'] ?? 0)] = (string) ($staffMember['name'] ?? '-');
        }

        $filename = sprintf(
            'analytics-report-%s-%s.csv',
            (string) ($filters['start_date'] ?? date('Y-m-d')),
            (string) ($filters['end_date'] ?? date('Y-m-d'))
        );

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $handle = fopen('php://output', 'w');
        if ($handle === false) {
            exit;
        }

        fputcsv($handle, ['Laporan Analitik']);
        fputcsv($handle, ['Lokasi', (string) ($filters['location_label'] ?? 'Semua Lokasi')]);
        fputcsv($handle, ['Periode', (string) ($filters['range_label'] ?? '-')]);
        fputcsv($handle, ['Basis tanggal', (string) ($filters['date_basis_label'] ?? '-')]);
        fputcsv($handle, []);
        fputcsv($handle, ['Tanggal', 'Ref No.', 'Pelanggan', 'Staff', 'Metode Pembayaran', 'Status', 'Gross', 'Diskon', 'Net']);

        foreach ($transactions as $transaction) {
            $gross = array_reduce(($transaction['items'] ?? []), static function (float $sum, array $item): float {
                return $sum + (((float) ($item['qty'] ?? 0)) * ((float) ($item['price'] ?? 0)));
            }, 0.0);
            $discount = (float) ($transaction['discount'] ?? 0);

            fputcsv($handle, [
                substr((string) ($transaction['date'] ?? ''), 0, 10),
                (string) ($transaction['reference'] ?? '-'),
                $customersById[(int) ($transaction['customer_id'] ?? 0)] ?? '-',
                $staffById[(int) ($transaction['staff_id'] ?? 0)] ?? '-',
                (string) ($transaction['payment_method'] ?? '-'),
                (string) ($transaction['status'] ?? '-'),
                $gross,
                $discount,
                max(0, $gross - $discount),
            ]);
        }

        fclose($handle);
        exit;
    }

    public function reviews(): void
    {
        $this->authorize('reviews.view');
        $today = new \DateTimeImmutable('today');
        $defaultStart = $today->modify('-6 days');
        $rating = (int) ($_GET['rating'] ?? 0);
        $reviewFilters = [
            'rating' => $rating >= 1 && $rating <= 5 ? $rating : null,
            'search' => trim((string) ($_GET['review_search'] ?? '')),
            'start_date' => $this->queryDate((string) ($_GET['start_date'] ?? $defaultStart->format('Y-m-d')), $defaultStart->format('Y-m-d')),
            'end_date' => $this->queryDate((string) ($_GET['end_date'] ?? $today->format('Y-m-d')), $today->format('Y-m-d')),
            'preset' => (string) ($_GET['preset'] ?? '7d'),
        ];
        $logFilters = [
            'customer' => trim((string) ($_GET['log_customer'] ?? '')),
            'search' => trim((string) ($_GET['log_search'] ?? '')),
        ];
        $activeTab = (string) ($_GET['tab'] ?? 'customer');

        $this->internalPage('pages/admin/reviews', 'Review & Logs', [
            'reviews' => $this->repo()->getReviews($reviewFilters),
            'logs' => $this->repo()->getLogs(),
            'messageLogs' => $this->repo()->getNotifications($logFilters),
            'logOptionsSource' => $this->repo()->getNotifications(),
            'reviewFilters' => $reviewFilters,
            'logFilters' => $logFilters,
            'activeTab' => in_array($activeTab, ['customer', 'logs'], true) ? $activeTab : 'customer',
        ]);
    }

    private function queryDate(string $value, string $fallback): string
    {
        $value = trim($value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }

        return $fallback;
    }

    private function analyticsPayload(): array
    {
        $payload = $this->repo()->analytics();
        $filters = $this->analyticsFilters($payload['locations'] ?? []);
        $bookingsById = [];
        foreach (($payload['bookings'] ?? []) as $booking) {
            $bookingsById[(int) ($booking['id'] ?? 0)] = $booking;
        }
        foreach (($payload['transactions'] ?? []) as $index => $transaction) {
            $bookingId = (int) ($transaction['booking_id'] ?? 0);
            $booking = $bookingsById[$bookingId] ?? null;
            if (!is_array($booking)) {
                continue;
            }
            $payload['transactions'][$index]['location_id'] = (int) ($transaction['location_id'] ?? $booking['location_id'] ?? 0);
            $payload['transactions'][$index]['booking_start_at'] = (string) ($transaction['booking_start_at'] ?? $booking['start_at'] ?? $transaction['date'] ?? '');
        }

        $payload['transactions'] = array_values(array_filter(
            $payload['transactions'] ?? [],
            fn (array $transaction): bool => $this->analyticsRowMatchesFilters($transaction, $filters, 'transaction')
        ));
        $payload['bookings'] = array_values(array_filter(
            $payload['bookings'] ?? [],
            fn (array $booking): bool => $this->analyticsRowMatchesFilters($booking, $filters, 'booking')
        ));
        $payload['analyticsFilters'] = $filters;

        return $payload;
    }

    private function analyticsFilters(array $locations): array
    {
        $today = new \DateTimeImmutable('today');
        $preset = (string) ($_GET['range'] ?? 'this_year');
        [$start, $end] = $this->analyticsPresetRange($preset, $today);

        $startDate = $this->queryDate((string) ($_GET['start_date'] ?? $start->format('Y-m-d')), $start->format('Y-m-d'));
        $endDate = $this->queryDate((string) ($_GET['end_date'] ?? $end->format('Y-m-d')), $end->format('Y-m-d'));
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $defaultLocationId = (int) ($locations[0]['id'] ?? 0);
        $locationId = (int) ($_GET['location_id'] ?? $defaultLocationId);
        $locationLabel = 'Semua Lokasi';
        foreach ($locations as $location) {
            if ((int) ($location['id'] ?? 0) === $locationId) {
                $locationLabel = (string) ($location['name'] ?? 'Semua Lokasi');
                break;
            }
        }
        if ($locationId <= 0) {
            $locationLabel = 'Semua Lokasi';
        }

        $dateBasis = (string) ($_GET['date_basis'] ?? 'payment');
        $dateBasis = $dateBasis === 'booking' ? 'booking' : 'payment';

        return [
            'location_id' => $locationId,
            'location_label' => $locationLabel,
            'range' => $preset,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'range_label' => $this->analyticsRangeLabel($preset, $startDate, $endDate),
            'date_basis' => $dateBasis,
            'date_basis_label' => $dateBasis === 'payment' ? 'Tanggal pembayaran' : 'Tanggal booking',
        ];
    }

    private function analyticsPresetRange(string $preset, \DateTimeImmutable $today): array
    {
        return match ($preset) {
            'today' => [$today, $today],
            'yesterday' => [$today->modify('-1 day'), $today->modify('-1 day')],
            'this_month' => [$today->modify('first day of this month'), $today],
            '30d' => [$today->modify('-29 days'), $today],
            'last_month' => [$today->modify('first day of last month'), $today->modify('last day of last month')],
            'last_year' => [$today->modify('first day of january last year'), $today->modify('last day of december last year')],
            'this_year' => [$today->modify('first day of january this year'), $today],
            default => [$today->modify('-6 days'), $today],
        };
    }

    private function analyticsRangeLabel(string $preset, string $startDate, string $endDate): string
    {
        $labels = [
            'today' => 'Hari ini',
            'yesterday' => 'Kemarin',
            'this_month' => 'Bulan ini',
            '30d' => '30 hari sebelumnya',
            'last_month' => 'Bulan kemarin',
            'last_year' => 'Tahun kemarin',
            'this_year' => 'Tahun ini',
            '7d' => '7 hari sebelumnya',
        ];
        $start = date('j M Y', strtotime($startDate));
        $end = date('j M Y', strtotime($endDate));

        return sprintf('%s, %s - %s', $labels[$preset] ?? 'Custom', $start, $end);
    }

    private function analyticsRowMatchesFilters(array $row, array $filters, string $type): bool
    {
        $locationId = (int) ($filters['location_id'] ?? 0);
        $rowLocationId = (int) ($row['location_id'] ?? 0);
        if ($locationId > 0 && $rowLocationId > 0 && $rowLocationId !== $locationId) {
            return false;
        }

        $dateValue = $type === 'transaction' && ($filters['date_basis'] ?? 'payment') === 'booking'
            ? (string) ($row['booking_start_at'] ?? $row['date'] ?? '')
            : (string) ($row[$type === 'booking' ? 'start_at' : 'date'] ?? '');
        $date = substr($dateValue, 0, 10);

        return $date >= (string) ($filters['start_date'] ?? '0000-00-00')
            && $date <= (string) ($filters['end_date'] ?? '9999-99-99');
    }

    public function settings(): void
    {
        $this->authorize('settings.view');
        $this->internalPage('pages/admin/settings', 'Settings', $this->repo()->settingsPayload());
    }

    public function updateBusinessProfile(): void
    {
        $this->authorize('settings.view');
        verify_csrf();

        $result = $this->repo()->updateBusinessProfile([
            'business_name' => (string) ($_POST['business_name'] ?? ''),
            'address' => (string) ($_POST['address'] ?? ''),
            'notification_channel' => (string) ($_POST['notification_channel'] ?? ''),
            'timezone' => (string) ($_POST['timezone'] ?? ''),
            'hours_schedule_json' => (string) ($_POST['hours_schedule_json'] ?? '[]'),
        ], (string) ($this->internalUser()['name'] ?? 'Admin'));

        flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/settings');
    }

    public function updateStaffPermissions(): void
    {
        $this->authorize('settings.permissions');
        verify_csrf();

        $staffId = (int) ($_POST['staff_id'] ?? 0);
        $granted = array_map('strval', $_POST['permissions'] ?? []);

        $this->repo()->updateStaffPermissions($staffId, $granted, $this->internalUser()['name']);
        flash('success', 'Hak akses staff berhasil diperbarui.');
        $this->redirect('/settings');
    }
}

