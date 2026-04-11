<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$pageTitle = 'Trận đấu';
$isGuestArea = true;
$assetsPrefix = '../';

$pdo = getPdo();
$sql = <<<SQL
SELECT m.MatchId, m.MatchDateTime, m.RefereeName, m.Venue, m.HomeScore, m.AwayScore, m.Status,
       hc.Name AS HomeName, ac.Name AS AwayName
FROM `Match` m
JOIN `Club` hc ON m.HomeClubId = hc.ClubId
JOIN `Club` ac ON m.AwayClubId = ac.ClubId
ORDER BY m.MatchDateTime DESC
SQL;
$matches = $pdo->query($sql)->fetchAll();

require_once dirname(__DIR__) . '/includes/header.php';
?>

<h1 class="h3 mb-4">Lịch &amp; kết quả trận đấu</h1>

<div class="row g-3">
    <?php foreach ($matches as $row): ?>
        <div class="col-12">
            <div class="card card-match border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4 text-md-end fw-semibold"><?= htmlspecialchars((string) $row['HomeName'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="col-md-4 text-center">
                            <?php if ($row['HomeScore'] !== null && $row['AwayScore'] !== null): ?>
                                <span class="fs-4"><?= (int) $row['HomeScore'] ?> - <?= (int) $row['AwayScore'] ?></span>
                            <?php else: ?>
                                <span class="text-muted">vs</span>
                            <?php endif; ?>
                            <div class="small text-muted mt-1"><?= htmlspecialchars((string) $row['Status'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-md-4 fw-semibold"><?= htmlspecialchars((string) $row['AwayName'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <hr class="my-3">
                    <div class="small text-muted">
                        <strong>Thời gian:</strong> <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $row['MatchDateTime'])), ENT_QUOTES, 'UTF-8') ?>
                        &nbsp;|&nbsp; <strong>Trọng tài:</strong> <?= htmlspecialchars((string) ($row['RefereeName'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                        &nbsp;|&nbsp; <strong>Sân:</strong> <?= htmlspecialchars((string) ($row['Venue'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <a class="btn btn-sm btn-outline-success mt-2" href="match.php?id=<?= (int) $row['MatchId'] ?>">Chi tiết</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (count($matches) === 0): ?>
        <p class="text-muted">Chưa có trận đấu nào.</p>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
