<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/standings.php';

requireAdmin();

$pageTitle = 'Bảng xếp hạng';
$isAdminArea = true;
$assetsPrefix = '../';

$pdo = getPdo();
$standings = getLeagueStandings($pdo);

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Bảng xếp hạng</h1>
    <div class="btn-group btn-group-sm">
        <a href="matches.php" class="btn btn-outline-success">Trận đấu (tỷ số)</a>
        <a href="clubs.php" class="btn btn-outline-success">CLB</a>
    </div>
</div>

<div class="alert alert-info py-2 mb-4">
    Bảng <strong>tự tính</strong> từ mọi trận đã có đủ tỷ số (đội nhà &amp; đội khách). Thêm / sửa / xóa trận hoặc CLB ở menu tương ứng sẽ phản ánh ngay tại đây và trên trang khách.
</div>

<div class="table-responsive shadow-sm rounded border bg-white">
    <table class="table table-hover table-sm align-middle mb-0">
        <thead class="table-success">
            <tr>
                <th class="text-nowrap">#</th>
                <th>Câu lạc bộ</th>
                <th>ĐĐ</th>
                <th>Thắng</th>
                <th>H</th>
                <th>Thua</th>
                <th>BT</th>
                <th>SBT</th>
                <th>HS</th>
                <th>Đ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($standings as $row): ?>
                <tr>
                    <td class="text-muted"><?= (int) $row['rank'] ?></td>
                    <td>
                        <a href="club_form.php?id=<?= (int) $row['clubId'] ?>"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></a>
                    </td>
                    <td><?= (int) $row['played'] ?></td>
                    <td><?= (int) $row['won'] ?></td>
                    <td><?= (int) $row['drawn'] ?></td>
                    <td><?= (int) $row['lost'] ?></td>
                    <td><?= (int) $row['gf'] ?></td>
                    <td><?= (int) $row['ga'] ?></td>
                    <td><?= (int) $row['gd'] >= 0 ? '+' . (int) $row['gd'] : (string) (int) $row['gd'] ?></td>
                    <td class="fw-semibold"><?= (int) $row['pts'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if (count($standings) === 0): ?>
    <p class="text-muted mt-3 mb-0">Chưa có CLB. <a href="club_form.php">Thêm CLB</a>.</p>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
