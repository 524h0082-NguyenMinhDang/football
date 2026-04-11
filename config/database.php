<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Kết nối MySQL/MariaDB qua PDO — extension pdo_mysql (bật sẵn trong XAMPP).
 */
function getPdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!extension_loaded('pdo_mysql')) {
        throw new RuntimeException(
            'PHP chưa có PDO MySQL (pdo_mysql). Mở C:\\xampp\\php\\php.ini, '
            . 'bỏ dấu ; trước dòng extension=pdo_mysql và extension=mysqli, khởi động lại Apache.'
        );
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . DB_CHARSET,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    return $pdo;
}
