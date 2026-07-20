<?php

declare(strict_types=1);

namespace App\Services;

final class EmbeddedMariaDb
{
    public static function bootIfNeeded(array $config): void
    {
        static $attempted = false;

        if ($attempted) {
            return;
        }

        $attempted = true;

        if (PHP_OS_FAMILY !== 'Windows' || !defined('BASE_PATH')) {
            return;
        }

        $db = $config['db'] ?? [];
        $host = strtolower((string) ($db['host'] ?? '127.0.0.1'));
        $port = (int) ($db['port'] ?? 3306);

        if (!in_array($host, ['127.0.0.1', 'localhost'], true) || $port !== 3307) {
            return;
        }

        $dataRoot = dirname(BASE_PATH) . DIRECTORY_SEPARATOR . '.local-mariadb' . DIRECTORY_SEPARATOR . 'data';
        $myIni = $dataRoot . DIRECTORY_SEPARATOR . 'my.ini';
        $mysqld = 'C:\\xampp\\mysql\\bin\\mysqld.exe';

        if (!is_file($myIni) || !is_file($mysqld)) {
            return;
        }

        $command = 'cmd /c start "" /b "' . $mysqld . '" --defaults-file=' . str_replace('/', '\\', $myIni) . '';

        @pclose(@popen($command, 'r'));
        usleep(2000000);
    }
}