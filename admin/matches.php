<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireAdmin();

$pageTitle = 'Quản lý trận đấu';
$isAdminArea = true;
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

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Trận đấu</h1>
    <a href="match_form.php" class="btn btn-success">+ Thêm trận</a>
</div>

<div class="table-responsive">
    <table class="table table-hover align-middle bg-white shadow-sm">
        <thead class="table-success">
            <tr>
                <th>Thời gian</th>
                <th>Trận</th>
                <th>Tỷ số</th>
                <th>Trọng tài</th>
                <th>Trạng thái</th>
                <th class="text-end">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($matches as $row): ?>
                <tr>
                    <td class="text-nowrap small"><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $row['MatchDateTime'])), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) $row['HomeName'], ENT_QUOTES, 'UTF-8') ?> vs <?= htmlspecialchars((string) $row['AwayName'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ($row['HomeScore'] !== null && $row['AwayScore'] !== null): ?>
                            <?= (int) $row['HomeScore'] ?> - <?= (int) $row['AwayScore'] ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= htmlspecialchars((string) ($row['RefereeName'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars((string) $row['Status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="match_form.php?id=<?= (int) $row['MatchId'] ?>">Sửa</a>
                        <form class="d-inline" method="post" action="match_delete.php" onsubmit="return confirm('Xóa trận này?');">
                            <input type="hidden" name="id" value="<?= (int) $row['MatchId'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if (count($matches) === 0): ?>
    <p class="text-muted">Chưa có trận. <a href="match_form.php">Thêm trận mới</a>.</p>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
