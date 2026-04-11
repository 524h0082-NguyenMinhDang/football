<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$clubId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($clubId < 1) {
    header('Location: clubs.php');
    exit;
}

$pdo = getPdo();
$st = $pdo->prepare('SELECT * FROM `Club` WHERE ClubId = ?');
$st->execute([$clubId]);
$club = $st->fetch();
if (!$club) {
    header('Location: clubs.php');
    exit;
}

$players = $pdo->prepare('SELECT * FROM `Player` WHERE ClubId = ? ORDER BY ShirtNumber, FullName');
$players->execute([$clubId]);
$playerRows = $players->fetchAll();

$pageTitle = htmlspecialchars((string) $club['Name'], ENT_QUOTES, 'UTF-8');
$isGuestArea = true;
$assetsPrefix = '../';
$guestNavActive = 'standings';

require_once dirname(__DIR__) . '/includes/header.php';
?>

<h1 class="h3 mb-2"><?= htmlspecialchars((string) $club['Name'], ENT_QUOTES, 'UTF-8') ?></h1>
<p class="text-muted mb-4">
    Sân: <?= htmlspecialchars((string) ($club['Stadium'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
    &nbsp;|&nbsp; Thành lập: <?= $club['FoundedYear'] !== null ? (int) $club['FoundedYear'] : '—' ?>
</p>

<h2 class="h5 mb-3">Cầu thủ</h2>
<div class="table-responsive">
    <table class="table table-sm table-hover bg-white shadow-sm">
        <thead class="table-light">
            <tr>
                <th>Số áo</th>
                <th>Họ tên</th>
                <th>Vị trí</th>
                <th>Quốc tịch</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($playerRows as $p): ?>
                <tr>
                    <td><?= $p['ShirtNumber'] !== null ? (int) $p['ShirtNumber'] : '—' ?></td>
                    <td><?= htmlspecialchars((string) $p['FullName'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($p['Position'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($p['Nationality'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if (count($playerRows) === 0): ?>
    <p class="text-muted">Chưa có cầu thủ.</p>
<?php endif; ?>

<a href="clubs.php" class="btn btn-outline-secondary mt-3">← Bảng xếp hạng</a>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
