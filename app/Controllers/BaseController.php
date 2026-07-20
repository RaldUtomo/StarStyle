<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Services\AuthService;
use App\Services\PermissionService;
use App\Services\SalonRepository;

abstract class BaseController
{
    protected function view(string $view, array $data = [], string $layout = 'app'): void
    {
        echo View::render($view, $data, $layout);
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . url($path));
        exit;
    }

    protected function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    protected function repo(): SalonRepository
    {
        return app('repository');
    }

    protected function auth(): AuthService
    {
        return app('auth');
    }

    protected function permissions(): PermissionService
    {
        return app('permission');
    }

    protected function internalUser(): array
    {
        $user = $this->auth()->user('internal');

        if ($user === null) {
            $this->redirect('/login');
        }

        return $user;
    }

    protected function customerUser(): array
    {
        $user = $this->auth()->user('customer');

        if ($user === null) {
            $this->redirect('/customer/login');
        }

        return $user;
    }

    protected function authorize(string $permission): void
    {
        if ($this->permissions()->can($permission, 'internal')) {
            return;
        }

        http_response_code(403);
        echo View::render('pages/admin/forbidden', [
            'title' => 'Akses Ditolak',
            'page' => 'forbidden',
            'message' => 'Hak akses Anda belum diaktifkan oleh admin.',
        ], 'guest');
        exit;
    }

    protected function internalPage(string $page, string $title, array $data = []): void
    {
        $user = $this->internalUser();
        $modules = $this->permissions()->visibleModules(config('internal_nav'), 'internal');
        $accountState = $this->repo()->accountState((int) ($user['id'] ?? 0));

        $this->view($page, array_merge($data, [
            'title' => $title,
            'pageTitle' => $title,
            'page' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH),
            'currentUser' => $user,
            'accountState' => $accountState,
            'sidebarModules' => $modules,
            'notifications' => $this->repo()->getNotifications(),
            'fastCheckout' => $this->fastCheckoutPayload(),
            'topbarSearch' => $this->topbarSearchPayload(),
            'success' => flash('success'),
            'error' => flash('error'),
        ]));
    }

    private function topbarSearchPayload(): array
    {
        $today = date('Y-m-d');
        $customers = $this->repo()->getCustomers();
        usort($customers, static fn (array $left, array $right): int => ((int) ($right['id'] ?? 0)) <=> ((int) ($left['id'] ?? 0)));

        $customerItems = array_map(static function (array $customer): array {
            return [
                'id' => (int) ($customer['id'] ?? 0),
                'name' => (string) ($customer['name'] ?? 'Pelanggan'),
                'phone' => (string) ($customer['phone'] ?? ''),
                'email' => (string) ($customer['email'] ?? ''),
                'badge' => (int) ($customer['loyalty_points'] ?? 0) > 0 ? number_format((float) ($customer['loyalty_points'] ?? 0), 2, ',', '.') : '',
                'url' => url('/customers'),
            ];
        }, array_slice($customers, 0, 8));

        $servicesById = [];
        foreach ($this->repo()->getServices() as $service) {
            $servicesById[(int) ($service['id'] ?? 0)] = $service;
        }

        $customersById = [];
        foreach ($customers as $customer) {
            $customersById[(int) ($customer['id'] ?? 0)] = $customer;
        }

        $agendaItems = [];
        foreach ($this->repo()->getBookings() as $booking) {
            $startAt = (string) ($booking['start_at'] ?? '');
            if (substr($startAt, 0, 10) < $today) {
                continue;
            }

            $serviceNames = [];
            $duration = 0;
            foreach (($booking['service_items'] ?? []) as $serviceItem) {
                if (!is_array($serviceItem)) {
                    continue;
                }
                $service = $servicesById[(int) ($serviceItem['service_id'] ?? 0)] ?? null;
                $serviceNames[] = (string) ($service['name'] ?? 'Layanan');
                $duration += (int) ($serviceItem['duration'] ?? $service['duration'] ?? 0);
            }
            if ($serviceNames === []) {
                foreach ((array) ($booking['service_ids'] ?? []) as $serviceId) {
                    $service = $servicesById[(int) $serviceId] ?? null;
                    if (!is_array($service)) {
                        continue;
                    }
                    $serviceNames[] = (string) ($service['name'] ?? 'Layanan');
                    $duration += (int) ($service['duration'] ?? 0);
                }
            }

            $customer = $customersById[(int) ($booking['customer_id'] ?? 0)] ?? null;
            $agendaItems[] = [
                'id' => (int) ($booking['id'] ?? 0),
                'reference' => (string) ($booking['reference'] ?? ''),
                'status' => strtoupper(str_replace('_', ' ', $this->fastCheckoutStatus((string) ($booking['status'] ?? 'new')))),
                'service' => implode(', ', array_slice($serviceNames, 0, 2)) ?: 'Agenda',
                'duration' => $duration,
                'date' => substr($startAt, 0, 10),
                'time' => substr($startAt, 11, 5),
                'customerName' => (string) ($customer['name'] ?? 'Walk-In'),
                'url' => url('/calendar?date=' . substr($startAt, 0, 10)),
            ];
        }

        usort($agendaItems, static fn (array $left, array $right): int => strcmp(($left['date'] ?? '') . ($left['time'] ?? ''), ($right['date'] ?? '') . ($right['time'] ?? '')));

        return [
            'customers' => $customerItems,
            'agenda' => array_slice($agendaItems, 0, 8),
        ];
    }

    private function fastCheckoutPayload(): array
    {
        $today = date('Y-m-d');
        $servicesById = [];
        foreach ($this->repo()->getServices() as $service) {
            $servicesById[(int) ($service['id'] ?? 0)] = $service;
        }

        $customersById = [];
        foreach ($this->repo()->getCustomers() as $customer) {
            $customersById[(int) ($customer['id'] ?? 0)] = $customer;
        }

        $staffById = [];
        foreach ($this->repo()->getStaff() as $staff) {
            $staffById[(int) ($staff['id'] ?? 0)] = $staff;
        }

        $paidBookingIds = [];
        foreach ($this->repo()->getTransactions() as $transaction) {
            $bookingId = (int) ($transaction['booking_id'] ?? 0);
            if ($bookingId > 0 && strtolower((string) ($transaction['status'] ?? '')) === 'paid') {
                $paidBookingIds[$bookingId] = true;
            }
        }

        $items = [];
        foreach ($this->repo()->getBookings() as $booking) {
            $bookingDate = substr((string) ($booking['start_at'] ?? ''), 0, 10);
            if ($bookingDate !== $today) {
                continue;
            }

            $status = $this->fastCheckoutStatus((string) ($booking['status'] ?? 'new'));
            $customer = $customersById[(int) ($booking['customer_id'] ?? 0)] ?? null;
            $staff = $staffById[(int) ($booking['staff_id'] ?? 0)] ?? null;
            $services = [];
            $total = 0.0;

            foreach (($booking['service_items'] ?? []) as $serviceItem) {
                if (!is_array($serviceItem)) {
                    continue;
                }

                $service = $servicesById[(int) ($serviceItem['service_id'] ?? 0)] ?? null;
                $serviceName = (string) ($service['name'] ?? 'Layanan');
                $price = (float) ($serviceItem['price'] ?? $service['price'] ?? 0);
                $duration = (int) ($serviceItem['duration'] ?? $service['duration'] ?? 60);
                $itemStaff = $staffById[(int) ($serviceItem['staff_id'] ?? $booking['staff_id'] ?? 0)] ?? $staff;

                $services[] = [
                    'name' => $serviceName,
                    'startTime' => substr((string) ($serviceItem['start_at'] ?? $booking['start_at'] ?? ''), 11, 5),
                    'duration' => $duration,
                    'price' => $price,
                    'staffId' => (string) ($itemStaff['id'] ?? $booking['staff_id'] ?? ''),
                    'staffName' => (string) ($itemStaff['name'] ?? $staff['name'] ?? ''),
                    'resourceId' => (string) ($serviceItem['resource_id'] ?? ''),
                    'resourceName' => (string) ($serviceItem['resource_name'] ?? ''),
                ];
                $total += $price;
            }

            if ($services === []) {
                foreach ((array) ($booking['service_ids'] ?? []) as $serviceId) {
                    $service = $servicesById[(int) $serviceId] ?? null;
                    if (!is_array($service)) {
                        continue;
                    }
                    $price = (float) ($service['price'] ?? 0);
                    $services[] = [
                        'name' => (string) ($service['name'] ?? 'Layanan'),
                        'startTime' => substr((string) ($booking['start_at'] ?? ''), 11, 5),
                        'duration' => (int) ($service['duration'] ?? 60),
                        'price' => $price,
                        'staffId' => (string) ($staff['id'] ?? $booking['staff_id'] ?? ''),
                        'staffName' => (string) ($staff['name'] ?? ''),
                        'resourceId' => '',
                        'resourceName' => '',
                    ];
                    $total += $price;
                }
            }

            $items[] = [
                'id' => (int) ($booking['id'] ?? 0),
                'reference' => (string) ($booking['reference'] ?? ''),
                'status' => $status,
                'paid' => isset($paidBookingIds[(int) ($booking['id'] ?? 0)]),
                'date' => $bookingDate,
                'time' => substr((string) ($booking['start_at'] ?? ''), 11, 5),
                'customerName' => (string) ($customer['name'] ?? 'Walk-In'),
                'staffId' => (string) ($staff['id'] ?? $booking['staff_id'] ?? ''),
                'staffName' => (string) ($staff['name'] ?? ''),
                'total' => $total,
                'services' => $services,
            ];
        }

        usort($items, static fn (array $left, array $right): int => strcmp((string) $left['time'], (string) $right['time']));

        return [
            'date' => $today,
            'dateLabel' => date('j M Y'),
            'items' => $items,
        ];
    }

    private function fastCheckoutStatus(string $status): string
    {
        $status = strtolower(trim(str_replace(['_', '-'], ' ', $status)));

        return match ($status) {
            '', 'pending' => 'new',
            'cancelled' => 'canceled',
            'no show' => 'no_show',
            default => in_array($status, ['new', 'confirmed', 'arrived', 'started', 'completed', 'canceled', 'no_show'], true) ? $status : 'new',
        };
    }
}
