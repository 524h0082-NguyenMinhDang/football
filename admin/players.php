<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireAdmin();

$pageTitle = 'Quản lý cầu thủ';
$isAdminArea = true;
$assetsPrefix = '../';

$pdo = getPdo();
$sql = <<<SQL
SELECT p.*, c.Name AS ClubName
FROM `Player` p
JOIN `Club` c ON p.ClubId = c.ClubId
ORDER BY c.Name, p.ShirtNumber, p.FullName
SQL;
$rows = $pdo->query($sql)->fetchAll();

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Cầu thủ</h1>
    <a href="player_form.php" class="btn btn-success">+ Thêm cầu thủ</a>
</div>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-hover align-middle bg-white shadow-sm small">
        <thead class="table-success">
            <tr>
                <th>CLB</th>
                <th>Số áo</th>
                <th>Họ tên</th>
                <th>Vị trí</th>
                <th>Quốc tịch</th>
                <th class="text-end">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $r['ClubName'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $r['ShirtNumber'] !== null ? (int) $r['ShirtNumber'] : '—' ?></td>
                    <td><?= htmlspecialchars((string) $r['FullName'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($r['Position'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($r['Nationality'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="player_form.php?id=<?= (int) $r['PlayerId'] ?>">Sửa</a>
                        <form class="d-inline" method="post" action="player_delete.php" onsubmit="return confirm('Xóa cầu thủ này?');">
                            <input type="hidden" name="id" value="<?= (int) $r['PlayerId'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
