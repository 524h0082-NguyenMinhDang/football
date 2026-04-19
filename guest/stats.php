<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$pageTitle = 'Thống kê đội tuyển';
$isGuestArea = true;
$assetsPrefix = '../';
$guestNavActive = 'stats';

$pdo = getPdo();

$matches = $pdo->query(<<<SQL
SELECT m.MatchId, m.MatchDateTime, hc.Name AS HomeName, ac.Name AS AwayName
FROM `Match` m
JOIN `Club` hc ON m.HomeClubId = hc.ClubId
JOIN `Club` ac ON m.AwayClubId = ac.ClubId
ORDER BY m.MatchDateTime DESC
SQL)->fetchAll();

$matchId = isset($_GET['match_id']) ? (int) $_GET['match_id'] : 0;
if ($matchId < 1 && count($matches) > 0) {
    $matchId = (int) $matches[0]['MatchId'];
}

$current = null;
foreach ($matches as $m) {
    if ((int) $m['MatchId'] === $matchId) {
        $current = $m;
        break;
    }
}

$home = [];
$away = [];
if ($matchId > 0) {
    $st = $pdo->prepare('SELECT * FROM MatchTeamStat WHERE MatchId = ?');
    $st->execute([$matchId]);
    foreach ($st->fetchAll() as $r) {
        if ((int) $r['IsHomeTeam'] === 1) {
            $home = $r;
        } else {
            $away = $r;
        }
    }
}

$rows = [
    ['key' => 'Shots', 'label' => 'Số lần sút', 'unit' => ''],
    ['key' => 'ShotsOnTarget', 'label' => 'Sút trúng đích', 'unit' => ''],
    ['key' => 'Possession', 'label' => 'Kiểm soát bóng', 'unit' => '%'],
    ['key' => 'Passes', 'label' => 'Lượt chuyền bóng', 'unit' => ''],
    ['key' => 'PassAccuracy', 'label' => 'Tỷ lệ chuyền bóng chính xác', 'unit' => '%'],
    ['key' => 'Fouls', 'label' => 'Phạm lỗi', 'unit' => ''],
    ['key' => 'YellowCards', 'label' => 'Thẻ vàng', 'unit' => ''],
    ['key' => 'RedCards', 'label' => 'Thẻ đỏ', 'unit' => ''],
    ['key' => 'Offsides', 'label' => 'Việt vị', 'unit' => ''],
    ['key' => 'Corners', 'label' => 'Phạt góc', 'unit' => ''],
];

$fmt = static function (array $src, string $k, string $unit): string {
    if (!isset($src[$k]) || $src[$k] === null) {
        return '—';
    }
    $v = (int) $src[$k];
    return $unit === '%' ? $v . '%' : (string) $v;
};

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Thống kê đội tuyển</h1>
    </div>
    <form method="get" class="d-flex gap-2 align-items-end">
        <div>
            <label class="form-label small mb-1" for="match_id">Chọn trận</label>
            <select class="form-select form-select-sm" name="match_id" id="match_id" onchange="this.form.submit()">
                <?php foreach ($matches as $m): ?>
                    <option value="<?= (int) $m['MatchId'] ?>" <?= $matchId === (int) $m['MatchId'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $m['MatchDateTime'])), ENT_QUOTES, 'UTF-8') ?>
                        — <?= htmlspecialchars((string) $m['HomeName'], ENT_QUOTES, 'UTF-8') ?> vs <?= htmlspecialchars((string) $m['AwayName'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if (!$current): ?>
    <p class="text-muted">Chưa có trận đấu.</p>
<?php else: ?>
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-success text-white">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2 min-w-0">
                    <div class="team-stats-logo team-stats-logo--light team-stats-logo--ph" aria-hidden="true"></div>
                    <span class="fw-semibold text-truncate"><?= htmlspecialchars((string) $current['HomeName'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="text-center flex-grow-1 d-none d-md-block">
                    <div class="fw-bold">THỐNG KÊ ĐỘI TUYỂN</div>
                    <div class="small opacity-75"><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $current['MatchDateTime'])), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="d-flex align-items-center gap-2 justify-content-end min-w-0">
                    <span class="fw-semibold text-truncate"><?= htmlspecialchars((string) $current['AwayName'], ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="team-stats-logo team-stats-logo--light team-stats-logo--ph" aria-hidden="true"></div>
                </div>
            </div>
        </div>

        <?php if ($home === [] && $away === []): ?>
            <div class="card-body">
                <p class="text-muted mb-0">Chưa có dữ liệu thống kê cho trận này. Quản trị viên nhập tại khu vực admin.</p>
            </div>
        <?php else: ?>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 team-stats-table">
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td class="text-center" style="width:6rem">
                                        <span class="team-stats-pill team-stats-pill--home">
                                            <?= htmlspecialchars($fmt($home, $r['key'], $r['unit']), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td class="text-center text-muted small fw-semibold"><?= htmlspecialchars($r['label'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-center" style="width:6rem">
                                        <span class="team-stats-pill team-stats-pill--away">
                                            <?= htmlspecialchars($fmt($away, $r['key'], $r['unit']), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>

