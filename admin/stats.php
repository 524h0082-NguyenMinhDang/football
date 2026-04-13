<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireAdmin();

$pdo = getPdo();

$matches = $pdo->query(<<<SQL
SELECT m.MatchId, m.MatchDateTime, m.Venue, hc.Name AS HomeName, ac.Name AS AwayName
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

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && (string) $_POST['action'] === 'save') {
    $mid = (int) ($_POST['match_id'] ?? 0);
    if ($mid > 0) {
        $fields = [
            'Shots' => ['min' => 0, 'max' => 9999],
            'ShotsOnTarget' => ['min' => 0, 'max' => 9999],
            'Possession' => ['min' => 0, 'max' => 100],
            'Passes' => ['min' => 0, 'max' => 1000000],
            'PassAccuracy' => ['min' => 0, 'max' => 100],
            'Fouls' => ['min' => 0, 'max' => 9999],
            'YellowCards' => ['min' => 0, 'max' => 999],
            'RedCards' => ['min' => 0, 'max' => 999],
            'Offsides' => ['min' => 0, 'max' => 9999],
            'Corners' => ['min' => 0, 'max' => 9999],
        ];

        $norm = static function ($v, int $min, int $max): ?int {
            if ($v === null || $v === '') {
                return null;
            }
            if (!is_numeric($v)) {
                return null;
            }
            $n = (int) $v;
            if ($n < $min) {
                $n = $min;
            }
            if ($n > $max) {
                $n = $max;
            }
            return $n;
        };

        $saveSide = static function (PDO $pdo, int $mid, int $isHome, array $fields, array $src, callable $norm): void {
            $vals = [];
            foreach ($fields as $k => $r) {
                $vals[$k] = $norm($src[$k] ?? null, (int) $r['min'], (int) $r['max']);
            }
            $pdo->prepare(<<<SQL
INSERT INTO MatchTeamStat
  (MatchId, IsHomeTeam, Shots, ShotsOnTarget, Possession, Passes, PassAccuracy, Fouls, YellowCards, RedCards, Offsides, Corners)
VALUES
  (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
  Shots=VALUES(Shots),
  ShotsOnTarget=VALUES(ShotsOnTarget),
  Possession=VALUES(Possession),
  Passes=VALUES(Passes),
  PassAccuracy=VALUES(PassAccuracy),
  Fouls=VALUES(Fouls),
  YellowCards=VALUES(YellowCards),
  RedCards=VALUES(RedCards),
  Offsides=VALUES(Offsides),
  Corners=VALUES(Corners)
SQL)->execute([
                $mid,
                $isHome,
                $vals['Shots'],
                $vals['ShotsOnTarget'],
                $vals['Possession'],
                $vals['Passes'],
                $vals['PassAccuracy'],
                $vals['Fouls'],
                $vals['YellowCards'],
                $vals['RedCards'],
                $vals['Offsides'],
                $vals['Corners'],
            ]);
        };

        $saveSide($pdo, $mid, 1, $fields, (array) ($_POST['home'] ?? []), $norm);
        $saveSide($pdo, $mid, 0, $fields, (array) ($_POST['away'] ?? []), $norm);

        $message = 'Đã lưu thống kê.';
    }
    header('Location: stats.php?match_id=' . $mid . ($message ? '&msg=' . rawurlencode($message) : ''));
    exit;
}

if (isset($_GET['msg'])) {
    $message = (string) $_GET['msg'];
}

$statsHome = [];
$statsAway = [];
if ($matchId > 0) {
    $st = $pdo->prepare('SELECT * FROM MatchTeamStat WHERE MatchId = ?');
    $st->execute([$matchId]);
    foreach ($st->fetchAll() as $r) {
        if ((int) $r['IsHomeTeam'] === 1) {
            $statsHome = $r;
        } else {
            $statsAway = $r;
        }
    }
}

$pageTitle = 'Thống kê đội tuyển';
$isAdminArea = true;
$assetsPrefix = '../';

require_once dirname(__DIR__) . '/includes/header.php';

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

$val = static function (array $src, string $k): string {
    return isset($src[$k]) && $src[$k] !== null ? (string) (int) $src[$k] : '';
};
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Thống kê đội tuyển</h1>
</div>

<?php if ($message !== ''): ?>
    <div class="alert alert-info py-2"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="get" class="row g-2 align-items-end mb-4">
    <div class="col-md-8">
        <label class="form-label" for="match_id">Chọn trận</label>
        <select name="match_id" id="match_id" class="form-select" onchange="this.form.submit()">
            <?php foreach ($matches as $m): ?>
                <option value="<?= (int) $m['MatchId'] ?>" <?= $matchId === (int) $m['MatchId'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $m['MatchDateTime'])), ENT_QUOTES, 'UTF-8') ?>
                    — <?= htmlspecialchars((string) $m['HomeName'], ENT_QUOTES, 'UTF-8') ?> vs <?= htmlspecialchars((string) $m['AwayName'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<?php if (!$current): ?>
    <p class="text-muted">Chưa có trận đấu. <a href="matches.php">Tạo trận</a> trước.</p>
<?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-success text-white fw-semibold">
            Nhập dữ liệu thủ công — <?= htmlspecialchars((string) $current['HomeName'], ENT_QUOTES, 'UTF-8') ?> vs <?= htmlspecialchars((string) $current['AwayName'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="match_id" value="<?= (int) $matchId ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 rounded border bg-light">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge text-bg-primary">Nhà</span>
                                <span class="fw-semibold"><?= htmlspecialchars((string) $current['HomeName'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <?php foreach ($rows as $r): ?>
                                <div class="mb-2">
                                    <label class="form-label small mb-1" for="home_<?= htmlspecialchars($r['key'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($r['label'], ENT_QUOTES, 'UTF-8') ?></label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control" id="home_<?= htmlspecialchars($r['key'], ENT_QUOTES, 'UTF-8') ?>" name="home[<?= htmlspecialchars($r['key'], ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars($val($statsHome, $r['key']), ENT_QUOTES, 'UTF-8') ?>" min="0">
                                        <?php if ($r['unit'] !== ''): ?><span class="input-group-text"><?= htmlspecialchars($r['unit'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded border bg-light">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge text-bg-danger">Khách</span>
                                <span class="fw-semibold"><?= htmlspecialchars((string) $current['AwayName'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <?php foreach ($rows as $r): ?>
                                <div class="mb-2">
                                    <label class="form-label small mb-1" for="away_<?= htmlspecialchars($r['key'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($r['label'], ENT_QUOTES, 'UTF-8') ?></label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control" id="away_<?= htmlspecialchars($r['key'], ENT_QUOTES, 'UTF-8') ?>" name="away[<?= htmlspecialchars($r['key'], ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars($val($statsAway, $r['key']), ENT_QUOTES, 'UTF-8') ?>" min="0">
                                        <?php if ($r['unit'] !== ''): ?><span class="input-group-text"><?= htmlspecialchars($r['unit'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-success">Lưu</button>
                    <a href="stats.php?match_id=<?= (int) $matchId ?>" class="btn btn-outline-secondary">Tải lại</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>

