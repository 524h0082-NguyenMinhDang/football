<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireAdmin();

$pageTitle = 'Quản lý CLB';
$isAdminArea = true;
$assetsPrefix = '../';

$pdo = getPdo();
$rows = $pdo->query('SELECT * FROM `Club` ORDER BY Name')->fetchAll();

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Câu lạc bộ</h1>
    <a href="club_form.php" class="btn btn-success">+ Thêm CLB</a>
</div>

<div class="table-responsive">
    <table class="table table-hover align-middle bg-white shadow-sm">
        <thead class="table-success">
            <tr>
                <th>Tên</th>
                <th>Viết tắt</th>
                <th>Sân</th>
                <th>Năm TL</th>
                <th class="text-end">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $r['Name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($r['ShortName'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($r['Stadium'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $r['FoundedYear'] !== null ? (int) $r['FoundedYear'] : '—' ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="club_form.php?id=<?= (int) $r['ClubId'] ?>">Sửa</a>
                        <form class="d-inline" method="post" action="club_delete.php" onsubmit="return confirm('Xóa CLB và toàn bộ cầu thủ thuộc CLB?');">
                            <input type="hidden" name="id" value="<?= (int) $r['ClubId'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
