<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: clubs.php');
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id > 0) {
    $pdo = getPdo();
    
    // Check if the club has any players
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM `Player` WHERE ClubId = ?');
    $stmt->execute([$id]);
    $playerCount = (int) $stmt->fetchColumn();
    
    if ($playerCount > 0) {
        $_SESSION['flash_error'] = 'Không thể xóa câu lạc bộ này vì vẫn còn cầu thủ thuộc câu lạc bộ.';
    } else {
        try {
            $pdo->prepare('DELETE FROM `Club` WHERE ClubId = ?')->execute([$id]);
            $_SESSION['flash_success'] = 'Đã xóa câu lạc bộ thành công.';
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = 'Lỗi khi xóa câu lạc bộ. Vui lòng thử lại sau.';
        }
    }
}

header('Location: clubs.php');
exit;
