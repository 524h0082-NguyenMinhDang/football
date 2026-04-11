<?php
/**
 * Chẩn đoán kết nối — mở bằng trình duyệt (http://localhost/.../db_test.php).
 * XONG nhớ XÓA file này trên máy chủ thật.
 */
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

echo '<h1>Kiểm tra PHP ↔ MySQL (phpMyAdmin)</h1><pre>';

echo 'PHP version: ' . PHP_VERSION . "\n\n";

$pdoMysql = extension_loaded('pdo_mysql');
$mysqli   = extension_loaded('mysqli');

echo 'extension pdo_mysql: ' . ($pdoMysql ? 'CÓ ✓' : 'KHÔNG — bật trong php.ini') . "\n";
echo 'extension mysqli:    ' . ($mysqli ? 'CÓ ✓' : 'KHÔNG') . "\n\n";

if (!$pdoMysql) {
    echo "---\n";
    echo "Trong C:\\xampp\\php\\php.ini bỏ dấu ; trước:\n";
    echo "  extension=pdo_mysql\n";
    echo "  extension=mysqli\n";
    echo "Khởi động lại Apache.\n";
    echo '</pre>';
    exit;
}

require_once __DIR__ . '/config/config.php';

echo 'DB_HOST: ' . DB_HOST . "\n";
echo 'DB_NAME: ' . DB_NAME . "\n";
echo 'DB_USER: ' . DB_USER . "\n\n";

try {
    require_once __DIR__ . '/config/database.php';
    $pdo = getPdo();
    echo "Kết nối PDO: THÀNH CÔNG ✓\n";
    $v = $pdo->query('SELECT VERSION() AS v')->fetch();
    if ($v) {
        echo "\nMySQL/MariaDB: " . htmlspecialchars((string) $v['v'], ENT_QUOTES, 'UTF-8') . "\n";
    }
} catch (Throwable $e) {
    echo 'LỖI: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "\n\n";
    echo "--- Gợi ý ---\n";
    echo "- Tạo database: mở http://localhost/phpmyadmin → chạy sql/schema.sql\n";
    echo "- Sai mật khẩu root: sửa DB_USER / DB_PASS trong config/config.php\n";
    echo "- MySQL chưa chạy: bật MySQL trong XAMPP Control Panel\n";
}

echo '</pre>';
echo '<p><a href="index.php">Về trang chủ</a></p>';
