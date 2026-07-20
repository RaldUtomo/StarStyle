<?php
declare(strict_types=1);

namespace App\Controllers;

final class PublicController extends BaseController
{
    public function home(): void
    {
        $groups = [];

        foreach ($this->repo()->getServiceGroups() as $group) {
            $groups[] = [
                'group' => $group,
                'services' => array_values(array_filter($this->repo()->getServices(), fn (array $service): bool => $service['group_id'] === $group['id'])),
            ];
        }

        $this->view('pages/public/home', array_merge($this->repo()->getLandingData(), [
            'title' => 'StarStyle Salon',
            'page' => '/',
            'publicNav' => config('public_nav'),
            'groups' => $groups,
            'customerUser' => $this->auth()->user('customer'),
            'success' => flash('success'),
            'error' => flash('error'),
        ]), 'public');
    }

    public function services(): void
    {
        $groups = [];

        foreach ($this->repo()->getServiceGroups() as $group) {
            $groups[] = [
                'group' => $group,
                'services' => array_values(array_filter($this->repo()->getServices(), fn (array $service): bool => $service['group_id'] === $group['id'])),
            ];
        }

        $this->view('pages/public/services', [
            'title' => 'Daftar Layanan',
            'page' => '/services-catalog',
            'publicNav' => config('public_nav'),
            'groups' => $groups,
        ], 'public');
    }

    public function booking(): void
    {
        $this->clearBookingCustomerContext();
        $settings = $this->repo()->getSettings();
        $business = config('business');
        $locations = $this->repo()->getLocations();
        $primaryLocation = $locations[0] ?? [];

        $this->view('pages/public/booking', [
            'title' => 'Reservasi Salon',
            'page' => '/booking',
            'publicNav' => config('public_nav'),
            'bookingBusiness' => [
                'name' => (string) ($settings['business_name'] ?? $business['name'] ?? 'StarStyle'),
                'location_name' => (string) ($settings['business_name'] ?? $primaryLocation['name'] ?? $business['name'] ?? 'Star Salon'),
                'hours' => (string) ($settings['hours'] ?? $business['hours'] ?? '09:00 - 20:00'),
                'address' => (string) ($settings['address'] ?? $primaryLocation['address'] ?? $business['address'] ?? ''),
                'hotline' => (string) ($business['hotline'] ?? ''),
                'email' => (string) ($business['email'] ?? ''),
                'cover_image_url' => $this->resolveBookingCoverImage(),
            ],
            'success' => flash('success'),
            'error' => flash('error'),
        ], 'public');
    }

    public function bookingServices(): void
    {
        $settings = $this->repo()->getSettings();
        $business = config('business');
        $locations = $this->repo()->getLocations();
        $primaryLocation = $locations[0] ?? [];
        $services = array_values(array_filter(
            $this->repo()->getServices(),
            static fn (array $service): bool => (($service['status'] ?? 'Aktif') === 'Aktif') && (($service['online_bookable'] ?? true) === true)
        ));
        $groups = $this->repo()->getServiceGroups();
        $servicesByGroup = [];

        foreach ($services as $service) {
            $servicesByGroup[(int) ($service['group_id'] ?? 0)][] = $service;
        }

        $bundles = [];
        foreach ($groups as $group) {
            $groupId = (int) ($group['id'] ?? 0);
            if ($groupId <= 0 || empty($servicesByGroup[$groupId])) {
                continue;
            }

            $bundles[] = [
                'group' => $group,
                'services' => $servicesByGroup[$groupId],
            ];
        }

        $selectedGroupId = (int) ($_GET['group'] ?? ($bundles[0]['group']['id'] ?? 0));
        $selectedBundle = $bundles[0] ?? ['group' => ['id' => 0, 'name' => 'Layanan'], 'services' => []];

        foreach ($bundles as $bundle) {
            if ((int) ($bundle['group']['id'] ?? 0) === $selectedGroupId) {
                $selectedBundle = $bundle;
                break;
            }
        }

        $this->view('pages/public/booking-services', [
            'title' => 'Pilih Tanggal & Item',
            'page' => '/booking/services',
            'publicNav' => config('public_nav'),
            'bookingBusiness' => [
                'name' => (string) ($settings['business_name'] ?? $business['name'] ?? 'StarStyle'),
                'location_name' => (string) ($settings['business_name'] ?? $primaryLocation['name'] ?? $business['name'] ?? 'Star Salon'),
                'hours' => (string) ($settings['hours'] ?? $business['hours'] ?? '09:00 - 20:00'),
                'address' => (string) ($settings['address'] ?? $primaryLocation['address'] ?? $business['address'] ?? ''),
                'hotline' => (string) ($business['hotline'] ?? ''),
                'email' => (string) ($business['email'] ?? ''),
                'cover_image_url' => $this->resolveBookingCoverImage(),
            ],
            'serviceBundles' => $bundles,
            'selectedServiceBundle' => $selectedBundle,
            'success' => flash('success'),
            'error' => flash('error'),
        ], 'public');
    }

    public function storeBookingTimeSelection(): void
    {
        verify_csrf();

        $date = (string) ($_POST['date'] ?? '');
        $groupId = (int) ($_POST['group_id'] ?? 0);
        $itemsRaw = (string) ($_POST['items'] ?? '[]');
        $items = json_decode($itemsRaw, true);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->redirect('/booking/services');
        }

        if (!is_array($items) || $items === []) {
            $target = '/booking/services?date=' . rawurlencode($date);
            if ($groupId > 0) {
                $target .= '&group=' . $groupId;
            }

            $this->redirect($target);
        }

        $normalizedItems = array_values(array_filter(array_map(static function ($item): ?array {
            if (!is_array($item)) {
                return null;
            }

            return [
                'service_id' => (int) ($item['service_id'] ?? 0),
                'name' => (string) ($item['name'] ?? 'Layanan'),
                'price' => (float) ($item['price'] ?? 0),
                'duration' => (int) ($item['duration'] ?? 0),
                'qty' => max(1, (int) ($item['qty'] ?? 1)),
                'image' => (string) ($item['image'] ?? ''),
            ];
        }, $items)));

        $_SESSION['booking_time_selection'] = [
            'date' => $date,
            'group_id' => $groupId,
            'items' => $normalizedItems,
        ];

        $this->redirect('/booking/time');
    }

    public function bookingTime(): void
    {
        $selection = $_SESSION['booking_time_selection'] ?? null;
        if (!is_array($selection) || empty($selection['items']) || empty($selection['date'])) {
            $this->redirect('/booking/services');
        }

        $date = new \DateTimeImmutable((string) $selection['date']);
        $items = array_values(array_filter((array) ($selection['items'] ?? []), static fn ($item): bool => is_array($item)));
        $primaryItem = $items[0] ?? [
            'name' => 'Layanan',
            'duration' => 0,
            'price' => 0,
            'qty' => 1,
            'image' => '',
        ];
        $serviceIds = $this->expandSelectedServiceIds($items);
        $availability = $this->repo()->bookingTimeAvailability($serviceIds, $date->format('Y-m-d'));
        $slots = is_array($availability['slots'] ?? null) ? $availability['slots'] : [];
        $availableSlots = array_values(array_filter($slots, static fn (array $slot): bool => !empty($slot['available'])));
        $selectedTime = (string) ($selection['selected_time'] ?? '');

        if ($selectedTime === '' || !in_array($selectedTime, array_map(static fn (array $slot): string => (string) ($slot['time'] ?? ''), $availableSlots), true)) {
            $selectedTime = (string) ($availableSlots[0]['time'] ?? '');
            $selection['selected_time'] = $selectedTime;
            $_SESSION['booking_time_selection'] = $selection;
        }

        $this->view('pages/public/booking-time', [
            'title' => 'Pilih Waktu',
            'page' => '/booking/time',
            'publicNav' => config('public_nav'),
            'bookingSelection' => [
                'date' => $date,
                'items' => $items,
                'primary_item' => $primaryItem,
                'selected_time' => $selectedTime,
                'total_qty' => array_reduce($items, static fn (int $sum, array $item): int => $sum + (int) ($item['qty'] ?? 1), 0),
                'total_duration' => array_reduce($items, static fn (int $sum, array $item): int => $sum + ((int) ($item['duration'] ?? 0) * (int) ($item['qty'] ?? 1)), 0),
                'slots' => $slots,
                'current_time' => (string) ($availability['current_time'] ?? ''),
                'business_hours' => (string) ($availability['business_hours'] ?? ''),
                'timezone' => (string) ($availability['timezone'] ?? ''),
                'is_closed' => !empty($availability['is_closed']),
            ],
            'success' => flash('success'),
            'error' => flash('error'),
        ], 'public');
    }

    public function storeBookingSummarySelection(): void
    {
        verify_csrf();

        $selection = $_SESSION['booking_time_selection'] ?? null;
        if (!is_array($selection) || empty($selection['items']) || empty($selection['date'])) {
            $this->redirect('/booking/services');
        }

        $selectedTime = trim((string) ($_POST['selected_time'] ?? ''));
        $serviceIds = $this->expandSelectedServiceIds((array) ($selection['items'] ?? []));
        $availability = $this->repo()->bookingTimeAvailability($serviceIds, (string) $selection['date']);
        $availableSlots = array_values(array_filter((array) ($availability['slots'] ?? []), static fn (array $slot): bool => !empty($slot['available'])));
        $availableTimes = array_map(static fn (array $slot): string => (string) ($slot['time'] ?? ''), $availableSlots);

        if (preg_match('/^\d{2}:\d{2}$/', $selectedTime) !== 1 || !in_array($selectedTime, $availableTimes, true)) {
            $selectedTime = (string) ($availableSlots[0]['time'] ?? '');
        }

        if ($selectedTime === '') {
            flash('error', 'Belum ada slot waktu yang tersedia untuk tanggal ini.');
            $this->redirect('/booking/time');
        }

        $selection['selected_time'] = $selectedTime;
        $_SESSION['booking_time_selection'] = $selection;

        $this->redirect('/booking/summary');
    }

    public function bookingSummary(): void
    {
        $selection = $_SESSION['booking_time_selection'] ?? null;
        if (!is_array($selection) || empty($selection['items']) || empty($selection['date'])) {
            $this->redirect('/booking/services');
        }

        $date = new \DateTimeImmutable((string) $selection['date']);
        $items = array_values(array_filter((array) ($selection['items'] ?? []), static fn ($item): bool => is_array($item)));
        $primaryItem = $items[0] ?? [
            'name' => 'Layanan',
            'duration' => 0,
            'price' => 0,
            'qty' => 1,
            'image' => '',
        ];
        $serviceIds = $this->expandSelectedServiceIds($items);
        $selectedTime = (string) ($selection['selected_time'] ?? '');
        $totalDuration = array_reduce($items, static fn (int $sum, array $item): int => $sum + ((int) ($item['duration'] ?? 0) * (int) ($item['qty'] ?? 1)), 0);
        $totalPrice = array_reduce($items, static fn (float $sum, array $item): float => $sum + ((float) ($item['price'] ?? 0) * (int) ($item['qty'] ?? 1)), 0.0);
        $availability = $this->repo()->bookingTimeAvailability($serviceIds, $date->format('Y-m-d'));
        $timeSlots = is_array($availability['slots'] ?? null) ? $availability['slots'] : [];
        $availableSlots = array_values(array_filter($timeSlots, static fn (array $slot): bool => !empty($slot['available'])));
        $availableTimes = array_map(static fn (array $slot): string => (string) ($slot['time'] ?? ''), $availableSlots);
        if ($selectedTime === '' || !in_array($selectedTime, $availableTimes, true)) {
            $selectedTime = (string) ($availableSlots[0]['time'] ?? '');
            $selection['selected_time'] = $selectedTime;
        }

        if ($selectedTime === '') {
            flash('error', 'Belum ada slot waktu yang tersedia untuk tanggal ini.');
            $_SESSION['booking_time_selection'] = $selection;
            $this->redirect('/booking/time');
        }

        $selectedSlot = null;
        foreach ($availableSlots as $slot) {
            if ((string) ($slot['time'] ?? '') === $selectedTime) {
                $selectedSlot = $slot;
                break;
            }
        }

        $availableStaffIds = array_values(array_unique(array_map('intval', (array) ($selectedSlot['available_staff_ids'] ?? []))));
        $allAvailableStaffIds = array_values(array_unique(array_merge([], ...array_map(
            static fn (array $slot): array => array_values(array_map('intval', (array) ($slot['available_staff_ids'] ?? []))),
            $availableSlots
        ))));
        $staff = array_values(array_filter(array_map(fn (int $staffId): ?array => $this->repo()->findStaff($staffId), $allAvailableStaffIds), static fn ($staffMember): bool => is_array($staffMember)));
        $selectedStaffId = max(0, (int) ($selection['selected_staff_id'] ?? 0));
        if ($selectedStaffId <= 0 || !in_array($selectedStaffId, $availableStaffIds, true)) {
            $selectedStaffId = (int) ($availableStaffIds[0] ?? 0);
            $selection['selected_staff_id'] = $selectedStaffId;
        }
        $_SESSION['booking_time_selection'] = $selection;
        $endTime = $this->calculateBookingEndTime($selectedTime, $totalDuration);
        $selectedStaff = $selectedStaffId > 0 ? $this->repo()->findStaff($selectedStaffId) : null;

        $timeOptions = array_map(fn (array $slot): array => [
            'start' => (string) ($slot['time'] ?? ''),
            'end' => $this->calculateBookingEndTime((string) ($slot['time'] ?? ''), $totalDuration),
            'available_staff_ids' => array_values(array_map('intval', (array) ($slot['available_staff_ids'] ?? []))),
        ], $availableSlots);

        $this->view('pages/public/booking-summary', [
            'title' => 'Ringkasan',
            'page' => '/booking/summary',
            'publicNav' => config('public_nav'),
            'bookingSummary' => [
                'date' => $date,
                'items' => $items,
                'primary_item' => $primaryItem,
                'selected_time' => $selectedTime,
                'end_time' => $endTime,
                'total_duration' => $totalDuration,
                'total_price' => $totalPrice,
                'staff_name' => (string) (($selectedStaff['name'] ?? $staff[0]['name'] ?? 'Staff')),
                'selected_staff_id' => $selectedStaffId,
                'available_staff' => array_map(static function (array $staffMember): array {
                    return [
                        'id' => (int) ($staffMember['id'] ?? 0),
                        'name' => (string) ($staffMember['name'] ?? 'Staff'),
                        'role' => (string) ($staffMember['public_title'] ?? $staffMember['role'] ?? ''),
                        'photo_data_url' => (string) ($staffMember['photo_data_url'] ?? ''),
                    ];
                }, $staff),
                'time_options' => $timeOptions,
            ],
            'success' => flash('success'),
            'error' => flash('error'),
        ], 'public');
    }

    public function storeBookingConfirmationSelection(): void
    {
        verify_csrf();

        $selection = $_SESSION['booking_time_selection'] ?? null;
        if (!is_array($selection) || empty($selection['items']) || empty($selection['date'])) {
            $this->redirect('/booking/services');
        }

        $selectedStaffId = max(0, (int) ($_POST['selected_staff_id'] ?? 0));
        $selectedTime = trim((string) ($_POST['selected_time'] ?? ''));
        $serviceIds = $this->expandSelectedServiceIds((array) ($selection['items'] ?? []));
        $availability = $this->repo()->bookingTimeAvailability($serviceIds, (string) $selection['date']);
        $availableSlots = array_values(array_filter((array) ($availability['slots'] ?? []), static fn (array $slot): bool => !empty($slot['available'])));
        $selectedSlot = null;

        foreach ($availableSlots as $slot) {
            if ((string) ($slot['time'] ?? '') === $selectedTime) {
                $selectedSlot = $slot;
                break;
            }
        }

        if (preg_match('/^\d{2}:\d{2}$/', $selectedTime) !== 1 || !is_array($selectedSlot)) {
            $selectedSlot = $availableSlots[0] ?? null;
            $selectedTime = (string) ($selectedSlot['time'] ?? '');
        }

        if ($selectedTime === '' || !is_array($selectedSlot)) {
            flash('error', 'Belum ada staff atau waktu yang tersedia untuk booking ini.');
            $this->redirect('/booking/time');
        }

        $availableStaffIds = array_values(array_map('intval', (array) ($selectedSlot['available_staff_ids'] ?? [])));
        if ($selectedStaffId <= 0 || !in_array($selectedStaffId, $availableStaffIds, true)) {
            $selectedStaffId = (int) ($availableStaffIds[0] ?? 0);
        }

        if ($selectedStaffId <= 0) {
            flash('error', 'Belum ada staff yang tersedia pada jam tersebut.');
            $this->redirect('/booking/summary');
        }

        $selection['selected_staff_id'] = $selectedStaffId;
        $selection['selected_time'] = $selectedTime;
        $_SESSION['booking_time_selection'] = $selection;

        $this->redirect('/booking/confirmation');
    }

    public function bookingConfirmation(): void
    {
        $selection = $_SESSION['booking_time_selection'] ?? null;
        if (!is_array($selection) || empty($selection['items']) || empty($selection['date'])) {
            $this->redirect('/booking/services');
        }

        $date = new \DateTimeImmutable((string) $selection['date']);
        $items = array_values(array_filter((array) ($selection['items'] ?? []), static fn ($item): bool => is_array($item)));
        $primaryItem = $items[0] ?? [
            'service_id' => 0,
            'name' => 'Layanan',
            'duration' => 0,
            'price' => 0,
            'qty' => 1,
            'image' => '',
        ];
        $selectedTime = (string) ($selection['selected_time'] ?? '03:00');
        $selectedStaffId = max(0, (int) ($selection['selected_staff_id'] ?? 0));
        $totalDuration = array_reduce($items, static fn (int $sum, array $item): int => $sum + ((int) ($item['duration'] ?? 0) * (int) ($item['qty'] ?? 1)), 0);
        $totalPrice = array_reduce($items, static fn (float $sum, array $item): float => $sum + ((float) ($item['price'] ?? 0) * (int) ($item['qty'] ?? 1)), 0.0);
        $selectedStaff = $selectedStaffId > 0 ? $this->repo()->findStaff($selectedStaffId) : null;
        $customerUser = $this->activeBookingCustomerUser();
        $customer = ($customerUser !== null && !empty($customerUser['customer_id']))
            ? $this->repo()->findCustomer((int) $customerUser['customer_id'])
            : null;
        $settings = $this->repo()->getSettings();
        $business = config('business');
        $locations = $this->repo()->getLocations();
        $primaryLocation = $locations[0] ?? [];
        $expandedItems = [];

        foreach ($items as $item) {
            $qty = max(1, (int) ($item['qty'] ?? 1));
            for ($index = 0; $index < $qty; $index += 1) {
                $expandedItems[] = [
                    'service_id' => (int) ($item['service_id'] ?? 0),
                    'duration' => (int) ($item['duration'] ?? 0),
                    'price' => (float) ($item['price'] ?? 0),
                ];
            }
        }

        $this->view('pages/public/booking-confirmation', [
            'title' => 'Konfirmasi Pemesanan',
            'page' => '/booking/confirmation',
            'publicNav' => config('public_nav'),
            'bookingConfirmation' => [
                'date' => $date,
                'selected_time' => $selectedTime,
                'total_duration' => $totalDuration,
                'total_price' => $totalPrice,
                'items' => $items,
                'expanded_items' => $expandedItems,
                'primary_item' => $primaryItem,
                'selected_staff' => $selectedStaff,
                'customer' => $customer,
                'is_logged_in' => $customerUser !== null,
                'location_name' => (string) ($settings['business_name'] ?? $primaryLocation['name'] ?? $business['name'] ?? 'Star Salon'),
                'business_name' => (string) ($settings['business_name'] ?? $business['name'] ?? 'StarStyle'),
                'business_hours' => (string) ($settings['hours'] ?? $business['hours'] ?? '09:00 - 20:00'),
                'business_address' => (string) ($settings['address'] ?? $primaryLocation['address'] ?? $business['address'] ?? ''),
            ],
            'success' => flash('success'),
            'error' => flash('error'),
        ], 'public');
    }

    public function storeBookingPaymentSelection(): void
    {
        verify_csrf();

        $selection = $_SESSION['booking_time_selection'] ?? null;
        if (!is_array($selection) || empty($selection['items']) || empty($selection['date'])) {
            $this->redirect('/booking/services');
        }

        $customerUser = $this->activeBookingCustomerUser();
        $payload = [
            'customer_name' => trim((string) ($_POST['customer_name'] ?? '')),
            'customer_email' => trim((string) ($_POST['customer_email'] ?? '')),
            'customer_phone' => trim((string) ($_POST['customer_phone'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];

        if ($customerUser !== null && !empty($customerUser['customer_id'])) {
            $customer = $this->repo()->findCustomer((int) $customerUser['customer_id']);
            if ($customer !== null) {
                $payload['customer_name'] = (string) ($customer['name'] ?? '');
                $payload['customer_email'] = (string) ($customer['email'] ?? '');
                $payload['customer_phone'] = (string) ($customer['phone'] ?? '');
                $payload['customer_id'] = (int) ($customer['id'] ?? $customerUser['customer_id']);
            }
        }

        if ($payload['customer_name'] === '' || $payload['customer_phone'] === '') {
            remember_old_input($_POST);
            flash('error', 'Mohon lengkapi nama lengkap dan nomor telepon.');
            $this->redirect('/booking/confirmation');
        }

        $items = array_values(array_filter((array) ($selection['items'] ?? []), static fn ($item): bool => is_array($item)));
        $staffId = max(0, (int) ($selection['selected_staff_id'] ?? 0));
        $serviceIds = [];
        $serviceDurations = [];
        $servicePrices = [];
        $serviceStaffIds = [];

        foreach ($items as $item) {
            $qty = max(1, (int) ($item['qty'] ?? 1));
            for ($index = 0; $index < $qty; $index += 1) {
                $serviceIds[] = (int) ($item['service_id'] ?? 0);
                $serviceDurations[] = (int) ($item['duration'] ?? 0);
                $servicePrices[] = (float) ($item['price'] ?? 0);
                $serviceStaffIds[] = $staffId;
            }
        }

        $bookingPayload = [
            'booking_reference' => (string) ($_SESSION['booking_checkout_payload']['booking_reference'] ?? ''),
            'date' => (string) ($selection['date'] ?? ''),
            'time' => (string) ($selection['selected_time'] ?? ''),
            'staff_id' => $staffId,
            'service_ids' => $serviceIds,
            'service_durations' => $serviceDurations,
            'service_prices' => $servicePrices,
            'service_staff_ids' => $serviceStaffIds,
            'customer_name' => $payload['customer_name'],
            'customer_email' => $payload['customer_email'],
            'customer_phone' => $payload['customer_phone'],
            'customer_id' => (int) ($payload['customer_id'] ?? 0),
            'notes' => $payload['notes'],
            'payment_review_status' => 'waiting_admin',
        ];

        $result = $this->repo()->createBooking($bookingPayload, 'customer');
        if (!$result['success']) {
            remember_old_input($_POST);
            flash('error', $result['message']);
            $this->redirect('/booking/confirmation');
        }

        if (!empty($result['booking']['reference'])) {
            $payload['booking_reference'] = (string) $result['booking']['reference'];
        }
        if (!empty($result['booking']['customer_id'])) {
            $payload['customer_id'] = (int) $result['booking']['customer_id'];
        }

        $_SESSION['booking_checkout_payload'] = $payload;
        clear_old_input();

        $this->redirect('/booking/payment');
    }

    public function bookingPayment(): void
    {
        $selection = $_SESSION['booking_time_selection'] ?? null;
        $checkout = $_SESSION['booking_checkout_payload'] ?? null;
        if (!is_array($selection) || empty($selection['items']) || empty($selection['date'])) {
            $this->redirect('/booking/services');
        }
        if (!is_array($checkout) || empty($checkout['customer_name']) || empty($checkout['customer_phone'])) {
            $this->redirect('/booking/confirmation');
        }

        $items = array_values(array_filter((array) ($selection['items'] ?? []), static fn ($item): bool => is_array($item)));
        $totalPrice = array_reduce($items, static fn (float $sum, array $item): float => $sum + ((float) ($item['price'] ?? 0) * (int) ($item['qty'] ?? 1)), 0.0);

        $this->view('pages/public/booking-payment', [
            'title' => 'Pembayaran',
            'page' => '/booking/payment',
            'publicNav' => config('public_nav'),
            'bookingPayment' => [
                'total_price' => $totalPrice,
                'customer_name' => (string) ($checkout['customer_name'] ?? ''),
            ],
            'success' => flash('success'),
            'error' => flash('error'),
        ], 'public');
    }

    public function storeBookingPaymentQris(): void
    {
        verify_csrf();

        $this->redirect('/booking/payment');
    }

    public function bookingPaymentQris(): void
    {
        $this->redirect('/booking/payment');
    }

    public function bookingPaymentProof(): void
    {
        $this->redirect('/booking/payment');
    }

    public function bookingPaymentPending(): void
    {
        $this->redirect('/booking/payment/success');
    }

    public function bookingPaymentSuccess(): void
    {
        $this->view('pages/public/booking-payment-success', [
            'title' => 'Booking Sukses',
            'page' => '/booking/payment/success',
            'publicNav' => config('public_nav'),
            'success' => flash('success'),
            'error' => flash('error'),
        ], 'public');
    }

    public function completeBookingPayment(): void
    {
        verify_csrf();

        $selection = $_SESSION['booking_time_selection'] ?? null;
        $checkout = $_SESSION['booking_checkout_payload'] ?? null;
        if (!is_array($selection) || empty($selection['items']) || empty($selection['date'])) {
            $this->redirect('/booking/services');
        }
        if (!is_array($checkout) || empty($checkout['customer_name']) || empty($checkout['customer_phone'])) {
            $this->redirect('/booking/confirmation');
        }

        $paymentMethod = strtoupper(trim((string) ($_POST['payment_method'] ?? '')));
        if (!in_array($paymentMethod, ['PAY_AT_VENUE', 'PAY_LATER', 'ON_SITE'], true)) {
            flash('error', 'Metode pembayaran booking harus bayar di tempat.');
            $this->redirect('/booking/payment');
        }

        $_SESSION['booking_checkout_payload']['payment_method'] = 'PAY_AT_VENUE';

        $items = array_values(array_filter((array) ($selection['items'] ?? []), static fn ($item): bool => is_array($item)));
        $staffId = max(0, (int) ($selection['selected_staff_id'] ?? 0));
        $serviceIds = [];
        $serviceDurations = [];
        $servicePrices = [];
        $serviceStaffIds = [];

        foreach ($items as $item) {
            $qty = max(1, (int) ($item['qty'] ?? 1));
            for ($index = 0; $index < $qty; $index += 1) {
                $serviceIds[] = (int) ($item['service_id'] ?? 0);
                $serviceDurations[] = (int) ($item['duration'] ?? 0);
                $servicePrices[] = (float) ($item['price'] ?? 0);
                $serviceStaffIds[] = $staffId;
            }
        }

        $payload = [
            'date' => (string) ($selection['date'] ?? ''),
            'time' => (string) ($selection['selected_time'] ?? ''),
            'staff_id' => $staffId,
            'service_ids' => $serviceIds,
            'service_durations' => $serviceDurations,
            'service_prices' => $servicePrices,
            'service_staff_ids' => $serviceStaffIds,
            'customer_name' => (string) ($checkout['customer_name'] ?? ''),
            'customer_email' => (string) ($checkout['customer_email'] ?? ''),
            'customer_phone' => (string) ($checkout['customer_phone'] ?? ''),
            'customer_id' => (int) ($checkout['customer_id'] ?? 0),
            'notes' => (string) ($checkout['notes'] ?? ''),
            'payment_method' => 'PAY_AT_VENUE',
            'payment_proof_path' => '',
            'payment_review_status' => 'complete',
            'booking_reference' => (string) ($checkout['booking_reference'] ?? ''),
        ];

        $customerUser = $this->activeBookingCustomerUser();
        if ($customerUser !== null && !empty($customerUser['customer_id'])) {
            $customer = $this->repo()->findCustomer((int) $customerUser['customer_id']);
            if ($customer !== null) {
                $payload['customer_name'] = (string) ($customer['name'] ?? $payload['customer_name']);
                $payload['customer_phone'] = (string) ($customer['phone'] ?? $payload['customer_phone']);
                $payload['customer_email'] = (string) ($customer['email'] ?? $payload['customer_email']);
                $payload['customer_id'] = (int) ($customer['id'] ?? $payload['customer_id']);
            }
        }

        $result = $this->repo()->createBooking($payload, 'customer');
        if ($result['success']) {
            flash('booking_completion_email', (string) ($payload['customer_email'] ?? $checkout['customer_email'] ?? ''));
            clear_old_input();
            unset($_SESSION['booking_time_selection'], $_SESSION['booking_checkout_payload']);
        }
        flash($result['success'] ? 'success' : 'error', $result['success'] ? 'Reservasi berhasil. Pembayaran dilakukan di lokasi booking.' : $result['message']);
        $this->redirect($result['success'] ? '/booking/payment/success' : '/booking/payment');
    }

    public function bookingNext(): void
    {
        verify_csrf();

        $next = (string) ($_POST['next_target'] ?? 'services');
        $wantsDefitLogin = !empty($_POST['is_defit']);

        if ($next === 'contact') {
            $this->redirect('/booking?tab=contact');
        }

        if ($wantsDefitLogin) {
            $_SESSION['booking_customer_login_enabled'] = true;
            $this->redirect('/customer/login?redirect=' . rawurlencode('/booking/services'));
        }

        $this->clearBookingCustomerContext();
        $this->redirect('/booking/services');
    }

    public function createBooking(): void
    {
        verify_csrf();
        $payload = $_POST;
        $customerUser = $this->auth()->user('customer');

        if ($customerUser !== null && !empty($customerUser['customer_id'])) {
            $customer = $this->repo()->findCustomer((int) $customerUser['customer_id']);
            if ($customer !== null) {
                $payload['customer_name'] = (string) ($customer['name'] ?? '');
                $payload['customer_phone'] = (string) ($customer['phone'] ?? '');
            }
        }

        $result = $this->repo()->createBooking($payload, 'customer');
        if ($result['success']) {
            clear_old_input();
            unset($_SESSION['booking_time_selection']);
        } else {
            remember_old_input($_POST);
        }
        flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect($result['success'] ? '/booking' : '/booking/confirmation');
    }

    public function customerLogin(): void
    {
        $googleOAuth = $this->googleOAuthConfig();

        $this->view('pages/public/customer-login', [
            'title' => 'Login Customer dan Admin',
            'page' => '/customer/login',
            'publicNav' => config('public_nav'),
            'redirectAfterLogin' => $this->sanitizeCustomerRedirect((string) ($_GET['redirect'] ?? '')),
            'googleLoginEnabled' => (bool) $googleOAuth['enabled'],
            'googleRedirectUri' => (string) $googleOAuth['redirect_uri'],
            'success' => flash('success'),
            'error' => flash('error'),
        ], 'public');
    }

    public function authenticateCustomer(): void
    {
        verify_csrf();
        $email = (string) ($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $redirectTarget = $this->sanitizeCustomerRedirect((string) ($_POST['redirect'] ?? ''));

        if ($this->auth()->attempt($email, $password, 'customer')) {
            $_SESSION['booking_customer_login_enabled'] = str_starts_with($redirectTarget, '/booking');
            flash('success', 'Login customer berhasil.');
            $this->redirect($redirectTarget !== '' ? $redirectTarget : '/customer/account');
        }

        if ($this->auth()->attempt($email, $password, 'internal')) {
            clear_old_input();
            flash('success', 'Login admin berhasil.');
            $this->redirect('/dashboard');
        }

        flash('error', 'Email atau password tidak sesuai untuk akun customer maupun admin.');
        $target = '/customer/login';
        if ($redirectTarget !== '') {
            $target .= '?redirect=' . rawurlencode($redirectTarget);
        }

        $this->redirect($target);
    }

    public function customerGoogleRedirect(): void
    {
        $redirectTarget = $this->sanitizeCustomerRedirect((string) ($_GET['redirect'] ?? ''));
        $oauth = $this->googleOAuthConfig();
        if (!$oauth['enabled']) {
            flash('error', 'Login Google belum dikonfigurasi. Isi GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET di file .env.');
            $target = '/customer/login';
            if ($redirectTarget !== '') {
                $target .= '?redirect=' . rawurlencode($redirectTarget);
            }
            $this->redirect($target);
        }

        $state = bin2hex(random_bytes(24));
        $_SESSION['customer_google_oauth'][$state] = [
            'redirect' => $redirectTarget,
            'created_at' => time(),
        ];

        $query = http_build_query([
            'client_id' => $oauth['client_id'],
            'redirect_uri' => $oauth['redirect_uri'],
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $query);
        exit;
    }

    public function customerGoogleCallback(): void
    {
        $state = (string) ($_GET['state'] ?? '');
        $storedState = $_SESSION['customer_google_oauth'][$state] ?? null;
        unset($_SESSION['customer_google_oauth'][$state]);

        if (!is_array($storedState) || (time() - (int) ($storedState['created_at'] ?? 0)) > 600) {
            flash('error', 'Sesi login Google kedaluwarsa. Silakan coba lagi.');
            $this->redirect('/customer/login');
        }

        $redirectTarget = $this->sanitizeCustomerRedirect((string) ($storedState['redirect'] ?? ''));
        if (!empty($_GET['error'])) {
            flash('error', 'Login Google dibatalkan atau gagal.');
            $this->redirect($this->customerLoginTarget($redirectTarget));
        }

        $code = trim((string) ($_GET['code'] ?? ''));
        if ($code === '') {
            flash('error', 'Kode login Google tidak ditemukan.');
            $this->redirect($this->customerLoginTarget($redirectTarget));
        }

        try {
            $oauth = $this->googleOAuthConfig();
            $token = $this->googleTokenExchange($code, $oauth);
            $profile = $this->googleUserInfo((string) ($token['access_token'] ?? ''));
            if (empty($profile['email_verified'])) {
                throw new \RuntimeException('Email Google belum terverifikasi.');
            }

            $user = $this->repo()->findOrCreateGoogleCustomer([
                'google_id' => (string) ($profile['sub'] ?? ''),
                'email' => (string) ($profile['email'] ?? ''),
                'name' => (string) ($profile['name'] ?? ''),
                'avatar_url' => (string) ($profile['picture'] ?? ''),
            ]);

            if (!$this->auth()->loginUserId((int) $user['id'], 'customer')) {
                throw new \RuntimeException('Akun customer Google gagal dimuat.');
            }
        } catch (\Throwable $throwable) {
            flash('error', 'Login Google gagal: ' . $throwable->getMessage());
            $this->redirect($this->customerLoginTarget($redirectTarget));
        }

        $_SESSION['booking_customer_login_enabled'] = str_starts_with($redirectTarget, '/booking');
        flash('success', 'Login Google berhasil.');
        $this->redirect($redirectTarget !== '' ? $redirectTarget : '/customer/account');
    }

    public function customerAccount(): void
    {
        $user = $this->customerUser();
        $account = $this->repo()->customerAccount((int) $user['customer_id']);

        $this->view('pages/public/customer-account', [
            'title' => 'Akun Pelanggan',
            'page' => '/customer/account',
            'publicNav' => config('public_nav'),
            'success' => flash('success'),
            'error' => flash('error'),
        ] + $account, 'public');
    }

    public function customerLogout(): void
    {
        $this->auth()->logout('customer');
        $this->clearBookingCustomerContext();
        flash('success', 'Sampai jumpa lagi.');
        $this->redirect('/');
    }

    private function sanitizeCustomerRedirect(string $path): string
    {
        if ($path === '' || !str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return '';
        }

        return preg_match('/[\r\n]/', $path) === 1 ? '' : $path;
    }

    private function customerLoginTarget(string $redirectTarget): string
    {
        $target = '/customer/login';
        if ($redirectTarget !== '') {
            $target .= '?redirect=' . rawurlencode($redirectTarget);
        }

        return $target;
    }

    private function googleOAuthConfig(): array
    {
        $config = config('google_oauth', []);
        $redirectUri = trim((string) ($config['redirect_uri'] ?? ''));
        if ($redirectUri === '') {
            $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
            $scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https') ? 'https' : 'http';
            $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $redirectUri = $scheme . '://' . $host . url('/customer/google/callback');
        }

        $clientId = trim((string) ($config['client_id'] ?? ''));
        $clientSecret = trim((string) ($config['client_secret'] ?? ''));

        return [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'enabled' => $clientId !== '' && $clientSecret !== '',
        ];
    }

    private function googleTokenExchange(string $code, array $oauth): array
    {
        $payload = [
            'code' => $code,
            'client_id' => (string) ($oauth['client_id'] ?? ''),
            'client_secret' => (string) ($oauth['client_secret'] ?? ''),
            'redirect_uri' => (string) ($oauth['redirect_uri'] ?? ''),
            'grant_type' => 'authorization_code',
        ];

        return $this->googleJsonRequest('https://oauth2.googleapis.com/token', [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n",
            'content' => http_build_query($payload),
        ]);
    }

    private function googleUserInfo(string $accessToken): array
    {
        if ($accessToken === '') {
            throw new \RuntimeException('Access token Google kosong.');
        }

        return $this->googleJsonRequest('https://www.googleapis.com/oauth2/v3/userinfo', [
            'method' => 'GET',
            'header' => "Authorization: Bearer {$accessToken}\r\nAccept: application/json\r\n",
        ]);
    }

    private function googleJsonRequest(string $url, array $httpOptions): array
    {
        $context = stream_context_create([
            'http' => $httpOptions + [
                'ignore_errors' => true,
                'timeout' => 12,
            ],
        ]);
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            throw new \RuntimeException('Tidak bisa menghubungi Google.');
        }

        $statusLine = (string) ($http_response_header[0] ?? '');
        if (!str_contains($statusLine, ' 200 ')) {
            $payload = json_decode($response, true);
            $message = is_array($payload) ? (string) ($payload['error_description'] ?? $payload['error'] ?? 'respons tidak valid') : 'respons tidak valid';
            throw new \RuntimeException($message);
        }

        $payload = json_decode($response, true);
        if (!is_array($payload)) {
            throw new \RuntimeException('Respons Google tidak valid.');
        }

        return $payload;
    }

    private function activeBookingCustomerUser(): ?array
    {
        if (empty($_SESSION['booking_customer_login_enabled'])) {
            return null;
        }

        return $this->auth()->user('customer');
    }

    private function clearBookingCustomerContext(): void
    {
        unset($_SESSION['booking_customer_login_enabled']);
    }

    private function resolveBookingCoverImage(): string
    {
        $path = dirname(__DIR__, 2) . '/img/Salon.jpeg';
        if (!is_file($path)) {
            return '';
        }

        $mime = mime_content_type($path) ?: 'image/jpeg';
        $content = file_get_contents($path);
        if ($content === false) {
            return '';
        }

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }

    private function resolveBookingQrisImage(): string
    {
        $path = dirname(__DIR__, 2) . '/img/Qris.jpeg';
        if (!is_file($path)) {
            return '';
        }

        $mime = mime_content_type($path) ?: 'image/jpeg';
        $content = file_get_contents($path);
        if ($content === false) {
            return '';
        }

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }

    private function expandSelectedServiceIds(array $items): array
    {
        $serviceIds = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $serviceId = (int) ($item['service_id'] ?? 0);
            $qty = max(1, (int) ($item['qty'] ?? 1));
            if ($serviceId <= 0) {
                continue;
            }

            for ($index = 0; $index < $qty; $index += 1) {
                $serviceIds[] = $serviceId;
            }
        }

        return $serviceIds;
    }

    private function calculateBookingEndTime(string $startTime, int $durationMinutes): string
    {
        if (preg_match('/^(\d{2}):(\d{2})$/', $startTime, $matches) !== 1) {
            return $startTime;
        }

        $minutes = (((int) $matches[1]) * 60) + (int) $matches[2] + max(0, $durationMinutes);

        return sprintf('%02d:%02d', (int) floor(($minutes / 60) % 24), $minutes % 60);
    }
}

