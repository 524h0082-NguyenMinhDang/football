<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireAdmin();

$pageTitle = 'Bảng điều khiển';
$isAdminArea = true;
$assetsPrefix = '../';

$pdo = getPdo();
$countMatches = (int) $pdo->query('SELECT COUNT(*) FROM `Match`')->fetchColumn();
$countClubs = (int) $pdo->query('SELECT COUNT(*) FROM `Club`')->fetchColumn();
$countPlayers = (int) $pdo->query('SELECT COUNT(*) FROM `Player`')->fetchColumn();

require_once dirname(__DIR__) . '/includes/header.php';
?>

<h1 class="h3 mb-4">Xin chào, <?= htmlspecialchars((string) $_SESSION['admin_username'], ENT_QUOTES, 'UTF-8') ?></h1>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Trận đấu</div>
                <div class="fs-2 fw-bold text-success"><?= $countMatches ?></div>
                <a href="matches.php" class="stretched-link text-decoration-none small">Quản lý →</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Câu lạc bộ</div>
                <div class="fs-2 fw-bold text-success"><?= $countClubs ?></div>
                <a href="clubs.php" class="stretched-link text-decoration-none small">Quản lý →</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Cầu thủ</div>
                <div class="fs-2 fw-bold text-success"><?= $countPlayers ?></div>
                <a href="players.php" class="stretched-link text-decoration-none small">Quản lý →</a>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-light border">
    <strong>Gợi ý:</strong> Cập nhật trận đấu (tỷ số, trọng tài, giờ) tại <a href="matches.php">Trận đấu</a>.
    Đội hình chính thức / nhân sự trận tại <a href="lineups.php">Đội hình trận</a>.
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
