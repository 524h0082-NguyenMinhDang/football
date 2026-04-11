<?php
/**
 * Chạy một lần sau khi đã tạo database và bảng (sql/schema.sql).
 * Tạo tài khoản admin mặc định: admin / admin123 — ĐỔI MẬT KHẨU sau khi vào được.
 * XÓA hoặc đổi tên file này trên môi trường thật.
 */
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$username = 'admin';
$plainPassword = 'admin123';

try {
    $pdo = getPdo();
    $check = $pdo->prepare('SELECT COUNT(*) AS c FROM `AdminUser` WHERE Username = ?');
    $check->execute([$username]);
    $exists = (int) $check->fetchColumn() > 0;
    if ($exists) {
        echo 'Tài khoản admin đã tồn tại. Không tạo lại.';
        exit;
    }
    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    $ins = $pdo->prepare('INSERT INTO `AdminUser` (Username, PasswordHash) VALUES (?, ?)');
    $ins->execute([$username, $hash]);
    echo 'Đã tạo admin: <strong>' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '</strong> / mật khẩu: <strong>' . htmlspecialchars($plainPassword, ENT_QUOTES, 'UTF-8') . '</strong><br>Hãy xóa install.php sau khi dùng.';
} catch (Throwable $e) {
    echo 'Lỗi: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
