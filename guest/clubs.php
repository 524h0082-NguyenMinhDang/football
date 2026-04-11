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

<div class="standings-page-wrap mb-4">
    <div class="standings-tabs-bar">
        <ul class="standings-tabs nav">
            <li class="nav-item"><a class="nav-link" href="index.php">Trận đấu</a></li>
            <li class="nav-item"><span class="nav-link active" aria-current="page">Bảng xếp hạng</span></li>
            <li class="nav-item"><a class="nav-link" href="players.php">Số liệu thống kê</a></li>
        </ul>
    </div>

    <div class="standings-shell">
        <header class="standings-head">
            <div class="standings-head-icon" aria-hidden="true">⚽</div>
            <div>
                <h1 class="standings-head-title mb-0">Bảng xếp hạng <?= htmlspecialchars(LEAGUE_DISPLAY_NAME, ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="standings-head-sub mb-0">Cập nhật theo tỷ số các trận đã ghi nhận</p>
            </div>
        </header>

        <div class="standings-toolbar">
            <label class="standings-season-label" for="season-select">Mùa giải</label>
            <select id="season-select" class="form-select form-select-sm standings-season-select" disabled title="Một mùa — có thể mở rộng sau">
                <option><?= htmlspecialchars(LEAGUE_SEASON_LABEL, ENT_QUOTES, 'UTF-8') ?></option>
            </select>
        </div>

        <div class="standings-table-wrap">
            <table class="standings-table">
                <thead>
                    <tr>
                        <th class="col-rank" scope="col"></th>
                        <th class="col-club" scope="col">Câu lạc bộ</th>
                        <th scope="col">ĐĐ</th>
                        <th scope="col">Thắng</th>
                        <th scope="col">H</th>
                        <th scope="col">Thua</th>
                        <th scope="col">BT</th>
                        <th scope="col">SBT</th>
                        <th scope="col">HS</th>
                        <th scope="col col-pts">Đ</th>
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
                            <td class="col-rank"><?= (int) $row['rank'] ?></td>
                            <td class="col-club">
                                <a class="standings-club-link" href="club.php?id=<?= (int) $row['clubId'] ?>">
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
                            <td><?= (int) $row['played'] ?></td>
                            <td><?= (int) $row['won'] ?></td>
                            <td><?= (int) $row['drawn'] ?></td>
                            <td><?= (int) $row['lost'] ?></td>
                            <td><?= (int) $row['gf'] ?></td>
                            <td><?= (int) $row['ga'] ?></td>
                            <td><?= (int) $row['gd'] >= 0 ? '+' . (int) $row['gd'] : (string) (int) $row['gd'] ?></td>
                            <td class="col-pts"><?= (int) $row['pts'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (count($standings) === 0): ?>
            <p class="standings-empty mb-0">Chưa có câu lạc bộ nào. Quản trị viên thêm CLB tại khu vực quản trị.</p>
        <?php endif; ?>

        <div class="standings-foot text-center">
            <span class="standings-foot-hint text-muted small">Điểm = 3×Thắng + 1×Hòa · HS = BT − SBT</span>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
