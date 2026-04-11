<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$pageTitle = 'Tất cả cầu thủ';
$isGuestArea = true;
$assetsPrefix = '../';
$guestNavActive = 'players';

$pdo = getPdo();
$sql = <<<SQL
SELECT p.PlayerId, p.FullName, p.Position, p.ShirtNumber, p.Nationality, c.Name AS ClubName, c.ClubId
FROM `Player` p
JOIN `Club` c ON p.ClubId = c.ClubId
ORDER BY c.Name, p.ShirtNumber, p.FullName
SQL;
$rows = $pdo->query($sql)->fetchAll();

require_once dirname(__DIR__) . '/includes/header.php';
?>

<h1 class="h3 mb-4">Danh sách cầu thủ theo CLB</h1>

<div class="table-responsive">
    <table class="table table-hover align-middle bg-white shadow-sm">
        <thead class="table-success">
            <tr>
                <th>CLB</th>
                <th>Số áo</th>
                <th>Họ tên</th>
                <th>Vị trí</th>
                <th>Quốc tịch</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td>
                        <a href="club.php?id=<?= (int) $r['ClubId'] ?>"><?= htmlspecialchars((string) $r['ClubName'], ENT_QUOTES, 'UTF-8') ?></a>
                    </td>
                    <td><?= $r['ShirtNumber'] !== null ? (int) $r['ShirtNumber'] : '—' ?></td>
                    <td><?= htmlspecialchars((string) $r['FullName'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($r['Position'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($r['Nationality'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if (count($rows) === 0): ?>
    <p class="text-muted">Chưa có cầu thủ.</p>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
