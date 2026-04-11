<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id < 1) {
    header('Location: index.php');
    exit;
}

$pdo = getPdo();
$st = $pdo->prepare(<<<SQL
SELECT m.*, hc.Name AS HomeName, ac.Name AS AwayName
FROM `Match` m
JOIN `Club` hc ON m.HomeClubId = hc.ClubId
JOIN `Club` ac ON m.AwayClubId = ac.ClubId
WHERE m.MatchId = ?
SQL);
$st->execute([$id]);
$match = $st->fetch();
if (!$match) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Trận đấu chi tiết';
$isGuestArea = true;
$assetsPrefix = '../';

require_once dirname(__DIR__) . '/includes/header.php';
?>

<h1 class="h3 mb-4">Chi tiết trận đấu</h1>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row text-center mb-3">
            <div class="col"><span class="fs-5"><?= htmlspecialchars((string) $match['HomeName'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="col-auto fs-3">
                <?php if ($match['HomeScore'] !== null && $match['AwayScore'] !== null): ?>
                    <?= (int) $match['HomeScore'] ?> - <?= (int) $match['AwayScore'] ?>
                <?php else: ?>
                    vs
                <?php endif; ?>
            </div>
            <div class="col"><span class="fs-5"><?= htmlspecialchars((string) $match['AwayName'], ENT_QUOTES, 'UTF-8') ?></span></div>
        </div>
        <ul class="list-unstyled mb-0 small">
            <li><strong>Ngày giờ:</strong> <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $match['MatchDateTime'])), ENT_QUOTES, 'UTF-8') ?></li>
            <li><strong>Trọng tài:</strong> <?= htmlspecialchars((string) ($match['RefereeName'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></li>
            <li><strong>Sân:</strong> <?= htmlspecialchars((string) ($match['Venue'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></li>
            <li><strong>Trạng thái:</strong> <?= htmlspecialchars((string) $match['Status'], ENT_QUOTES, 'UTF-8') ?></li>
        </ul>
    </div>
</div>

<a href="index.php" class="btn btn-outline-secondary">← Quay lại</a>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
