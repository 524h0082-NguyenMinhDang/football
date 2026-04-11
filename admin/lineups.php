<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireAdmin();

$pdo = getPdo();
$matches = $pdo->query(<<<SQL
SELECT m.MatchId, m.MatchDateTime, hc.Name AS HomeName, ac.Name AS AwayName, m.HomeClubId, m.AwayClubId
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
$homePlayers = [];
$awayPlayers = [];
$lineupRows = [];

if ($matchId > 0) {
    foreach ($matches as $m) {
        if ((int) $m['MatchId'] === $matchId) {
            $current = $m;
            break;
        }
    }
    if ($current) {
        $hp = $pdo->prepare('SELECT PlayerId, FullName, ShirtNumber, Position FROM `Player` WHERE ClubId = ? ORDER BY ShirtNumber, FullName');
        $hp->execute([(int) $current['HomeClubId']]);
        $homePlayers = $hp->fetchAll();
        $hp->execute([(int) $current['AwayClubId']]);
        $awayPlayers = $hp->fetchAll();

        $ln = $pdo->prepare(<<<SQL
SELECT l.LineupId, l.PlayerId, l.IsHomeTeam, l.IsStarter, l.FieldPosition, p.FullName, p.ShirtNumber
FROM `MatchLineup` l
JOIN `Player` p ON l.PlayerId = p.PlayerId
WHERE l.MatchId = ?
ORDER BY l.IsHomeTeam DESC, l.IsStarter DESC, p.ShirtNumber
SQL);
        $ln->execute([$matchId]);
        $lineupRows = $ln->fetchAll();
    }
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $mid = (int) ($_POST['match_id'] ?? 0);
    $action = (string) $_POST['action'];

    if ($mid > 0 && $action === 'add') {
        $pid = (int) ($_POST['player_id'] ?? 0);
        $isHome = isset($_POST['is_home']) ? 1 : 0;
        $isStarter = isset($_POST['is_starter']) ? 1 : 0;
        $fieldPos = trim((string) ($_POST['field_position'] ?? ''));
        if ($pid > 0) {
            try {
                $ins = $pdo->prepare('INSERT INTO `MatchLineup` (MatchId, PlayerId, IsHomeTeam, IsStarter, FieldPosition) VALUES (?,?,?,?,?)');
                $ins->execute([
                    $mid,
                    $pid,
                    $isHome,
                    $isStarter,
                    $fieldPos !== '' ? $fieldPos : null,
                ]);
                $message = 'Đã thêm vào đội hình.';
            } catch (Throwable $e) {
                $message = 'Không thêm được (có thể cầu thủ đã có trong trận).';
            }
        }
    }

    if ($mid > 0 && $action === 'delete') {
        $lid = (int) ($_POST['lineup_id'] ?? 0);
        if ($lid > 0) {
            $pdo->prepare('DELETE FROM `MatchLineup` WHERE LineupId = ? AND MatchId = ?')->execute([$lid, $mid]);
            $message = 'Đã xóa khỏi đội hình.';
        }
    }

    header('Location: lineups.php?match_id=' . $mid . ($message ? '&msg=' . rawurlencode($message) : ''));
    exit;
}

if (isset($_GET['msg'])) {
    $message = (string) $_GET['msg'];
}

$pageTitle = 'Đội hình trận';
$isAdminArea = true;
$assetsPrefix = '../';

require_once dirname(__DIR__) . '/includes/header.php';
?>

<h1 class="h3 mb-4">Đội hình &amp; nhân sự trận</h1>

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

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Thêm cầu thủ vào đội hình</div>
            <div class="card-body">
                <form method="post" class="row g-2">
                    <input type="hidden" name="match_id" value="<?= $matchId ?>">
                    <input type="hidden" name="action" value="add">
                    <div class="col-12">
                        <label class="form-label small">Cầu thủ</label>
                        <select name="player_id" class="form-select form-select-sm" required>
                            <option value="">— Chọn —</option>
                            <optgroup label="<?= htmlspecialchars((string) $current['HomeName'], ENT_QUOTES, 'UTF-8') ?>">
                                <?php foreach ($homePlayers as $p): ?>
                                    <option value="<?= (int) $p['PlayerId'] ?>"><?= (int) ($p['ShirtNumber'] ?? 0) ?> — <?= htmlspecialchars((string) $p['FullName'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="<?= htmlspecialchars((string) $current['AwayName'], ENT_QUOTES, 'UTF-8') ?>">
                                <?php foreach ($awayPlayers as $p): ?>
                                    <option value="<?= (int) $p['PlayerId'] ?>"><?= (int) ($p['ShirtNumber'] ?? 0) ?> — <?= htmlspecialchars((string) $p['FullName'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_home" id="is_home" checked>
                            <label class="form-check-label small" for="is_home">Đội nhà</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_starter" id="is_starter" checked>
                            <label class="form-check-label small" for="is_starter">Đá chính</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <input type="text" name="field_position" class="form-control form-control-sm" placeholder="Vị trí (VD: ST, CM)">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-success btn-sm">Thêm</button>
                    </div>
                </form>
                <p class="small text-muted mt-3 mb-0">Chọn cầu thủ đúng CLB; tick &quot;Đội nhà&quot; nếu thuộc đội chủ nhà.</p>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Đội hình đã đăng ký</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Đội</th>
                                <th>Cầu thủ</th>
                                <th>Chính/Dự</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lineupRows as $lr): ?>
                                <tr>
                                    <td><?= $lr['IsHomeTeam'] ? 'Nhà' : 'Khách' ?></td>
                                    <td><?= (int) ($lr['ShirtNumber'] ?? 0) ?> <?= htmlspecialchars((string) $lr['FullName'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= $lr['IsStarter'] ? 'Chính' : 'Dự bị' ?></td>
                                    <td>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Xóa?');">
                                            <input type="hidden" name="match_id" value="<?= $matchId ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="lineup_id" value="<?= (int) $lr['LineupId'] ?>">
                                            <button type="submit" class="btn btn-link btn-sm text-danger p-0">Xóa</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($lineupRows) === 0): ?>
                    <p class="text-muted small p-3 mb-0">Chưa có ai trong đội hình.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
