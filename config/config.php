<?php
/**
 * Cấu hình kết nối MySQL / MariaDB (XAMPP — quản lý qua phpMyAdmin)
 *
 * Tạo database: mở http://localhost/phpmyadmin → New → tên FootballLeague,
 * hoặc chạy sql/schema.sql (tạo DB + bảng).
 */
declare(strict_types=1);

session_start();

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'FootballLeague');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', '/');

/** Tên hiển thị trên bảng xếp hạng (khách) */
define('LEAGUE_DISPLAY_NAME', 'Giải bóng đá');
/** Nhãn mùa giải (hiển thị; lọc theo mùa có thể bổ sung sau) */
define('LEAGUE_SEASON_LABEL', '2025–26');
