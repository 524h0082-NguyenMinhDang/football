<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/standings.php';

$pageTitle = 'Bảng xếp hạng';
$isGuestArea = true;
$assetsPrefix = '../';
$guestNavActive = 'standings';

$pdo = getPdo();
$standings = getLeagueStandings($pdo);

require_once dirname(__DIR__) . '/includes/header.php';
?>

<h1 class="h3 mb-2">Bảng xếp hạng <?= htmlspecialchars(LEAGUE_DISPLAY_NAME, ENT_QUOTES, 'UTF-8') ?></h1>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-success text-white py-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span class="fw-semibold small mb-0">Mùa giải</span>
        <select id="season-select" class="form-select form-select-sm w-auto bg-white border-0 text-dark" disabled title="Một mùa — có thể mở rộng sau">
            <option><?= htmlspecialchars(LEAGUE_SEASON_LABEL, ENT_QUOTES, 'UTF-8') ?></option>
        </select>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle bg-white mb-0">
            <thead class="table-success">
                <tr>
                    <th class="text-center" scope="col" style="width:3rem">#</th>
                    <th scope="col">Câu lạc bộ</th>
                    <th class="text-center" scope="col">ĐĐ</th>
                    <th class="text-center" scope="col">Thắng</th>
                    <th class="text-center" scope="col">H</th>
                    <th class="text-center" scope="col">Thua</th>
                    <th class="text-center" scope="col">BT</th>
                    <th class="text-center" scope="col">SBT</th>
                    <th class="text-center" scope="col">HS</th>
                    <th class="text-center fw-bold" scope="col" style="width:3.5rem">Đ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($standings as $row): ?>
                    <?php
                    $nm = (string) $row['name'];
                    $initial = $nm !== '' && function_exists('mb_substr')
                        ? mb_strtoupper(mb_substr($nm, 0, 1, 'UTF-8'), 'UTF-8')
                        : strtoupper(substr($nm, 0, 1));
                    ?>
                    <tr>
                        <td class="text-center text-muted fw-semibold"><?= (int) $row['rank'] ?></td>
                        <td>
                            <a class="standings-club-link text-decoration-none text-body" href="club.php?id=<?= (int) $row['clubId'] ?>">
                                <?php if ($row['logoUrl']): ?>
                                    <span class="standings-crest standings-crest--img">
                                        <img src="<?= htmlspecialchars($row['logoUrl'], ENT_QUOTES, 'UTF-8') ?>" alt="" width="36" height="36" loading="lazy">
                                    </span>
                                <?php else: ?>
                                    <span class="standings-crest"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                                <span class="standings-club-name"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></span>
                            </a>
                        </td>
                        <td class="text-center"><?= (int) $row['played'] ?></td>
                        <td class="text-center"><?= (int) $row['won'] ?></td>
                        <td class="text-center"><?= (int) $row['drawn'] ?></td>
                        <td class="text-center"><?= (int) $row['lost'] ?></td>
                        <td class="text-center"><?= (int) $row['gf'] ?></td>
                        <td class="text-center"><?= (int) $row['ga'] ?></td>
                        <td class="text-center"><?= (int) $row['gd'] >= 0 ? '+' . (int) $row['gd'] : (string) (int) $row['gd'] ?></td>
                        <td class="text-center fw-bold text-success"><?= (int) $row['pts'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (count($standings) === 0): ?>
        <div class="card-body">
            <p class="text-muted mb-0">Chưa có câu lạc bộ nào. Quản trị viên thêm CLB tại khu vực quản trị.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
