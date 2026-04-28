<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: players.php');
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id > 0) {
    $pdo = getPdo();
    
    // Check if the player is in any match lineup or event
    $stmt1 = $pdo->prepare('SELECT COUNT(*) FROM `MatchLineup` WHERE PlayerId = ?');
    $stmt1->execute([$id]);
    $lineupCount = (int) $stmt1->fetchColumn();
    
    $stmt2 = $pdo->prepare('SELECT COUNT(*) FROM `MatchEvent` WHERE PlayerId = ? OR AssistPlayerId = ?');
    $stmt2->execute([$id, $id]);
    $eventCount = (int) $stmt2->fetchColumn();
    
    if ($lineupCount > 0 || $eventCount > 0) {
        $_SESSION['flash_error'] = 'Không thể xóa cầu thủ này vì cầu thủ đang có mặt trong đội hình hoặc sự kiện trận đấu.';
    } else {
        try {
            $pdo->prepare('DELETE FROM `Player` WHERE PlayerId = ?')->execute([$id]);
            $_SESSION['flash_success'] = 'Đã xóa cầu thủ thành công.';
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = 'Lỗi khi xóa cầu thủ. Vui lòng thử lại sau.';
        }
    }
}

header('Location: players.php');
exit;
