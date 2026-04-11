<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$pageTitle = 'Câu lạc bộ';
$isGuestArea = true;
$assetsPrefix = '../';

$pdo = getPdo();
$clubs = $pdo->query('SELECT ClubId, Name, ShortName, Stadium, FoundedYear FROM `Club` ORDER BY Name')->fetchAll();

require_once dirname(__DIR__) . '/includes/header.php';
?>

<h1 class="h3 mb-4">Danh sách câu lạc bộ</h1>

<div class="table-responsive">
    <table class="table table-hover align-middle bg-white shadow-sm">
        <thead class="table-success">
            <tr>
                <th>Tên</th>
                <th>Tên viết tắt</th>
                <th>Sân nhà</th>
                <th>Năm thành lập</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clubs as $c): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $c['Name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($c['ShortName'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($c['Stadium'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $c['FoundedYear'] !== null ? (int) $c['FoundedYear'] : '—' ?></td>
                    <td><a class="btn btn-sm btn-outline-success" href="club.php?id=<?= (int) $c['ClubId'] ?>">Cầu thủ</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if (count($clubs) === 0): ?>
    <p class="text-muted">Chưa có dữ liệu CLB.</p>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
