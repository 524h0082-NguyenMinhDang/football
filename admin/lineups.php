<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/lineup_visual.php';

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
$pitchHome = [];
$pitchAway = [];
$benchHome = [];
$benchAway = [];

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
SELECT l.LineupId, l.PlayerId, l.IsHomeTeam, l.IsStarter, l.FieldPosition,
       p.FullName, p.ShirtNumber, p.Position
FROM `MatchLineup` l
JOIN `Player` p ON l.PlayerId = p.PlayerId
WHERE l.MatchId = ?
ORDER BY l.IsHomeTeam DESC, l.IsStarter DESC, p.ShirtNumber
SQL);
        $ln->execute([$matchId]);
        $lineupRows = $ln->fetchAll();

        [$pitchHome, $benchHome, $pitchAway, $benchAway] = lineupComputePitchSides($lineupRows);
    }
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $mid = (int) ($_POST['match_id'] ?? 0);
    $action = (string) $_POST['action'];

    if ($mid > 0 && $action === 'add') {
        $pid = (int) ($_POST['player_id'] ?? 0);
        $addTeam = (string) ($_POST['add_team'] ?? 'home');
        if ($addTeam !== 'home' && $addTeam !== 'away') {
            $addTeam = 'home';
        }
        $isHome = $addTeam === 'home' ? 1 : 0;
        $role = (string) ($_POST['role'] ?? 'starter');
        $isStarter = $role === 'bench' ? 0 : 1;
        $fieldPos = trim((string) ($_POST['field_position'] ?? ''));
        if ($isStarter !== 1) {
            $fieldPos = '';
        }
        if ($pid > 0) {
            $mst = $pdo->prepare('SELECT HomeClubId, AwayClubId FROM `Match` WHERE MatchId = ?');
            $mst->execute([$mid]);
            $mrow = $mst->fetch();
            $clubOk = false;
            if ($mrow) {
                $expectClub = $isHome ? (int) $mrow['HomeClubId'] : (int) $mrow['AwayClubId'];
                $chk = $pdo->prepare('SELECT 1 FROM `Player` WHERE PlayerId = ? AND ClubId = ? LIMIT 1');
                $chk->execute([$pid, $expectClub]);
                $clubOk = (bool) $chk->fetchColumn();
            }
            if (!$clubOk) {
                $message = 'Cầu thủ không thuộc đội đã chọn (nhà/khách).';
            } else {
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

$fieldPosOptions = ['GK', 'LB', 'LCB', 'CB', 'RCB', 'RB', 'LWB', 'RWB', 'CDM', 'LCDM', 'RCDM', 'DM', 'CM', 'LCM', 'RCM', 'CAM', 'LAM', 'RAM', 'LM', 'RM', 'LW', 'RW', 'ST', 'CF'];
$fieldPosGroups = [
    'Thủ môn' => ['GK'],
    'Hậu vệ' => ['LB', 'LCB', 'CB', 'RCB', 'RB', 'LWB', 'RWB'],
    'Tiền vệ' => ['CDM', 'LCDM', 'RCDM', 'DM', 'CM', 'LCM', 'RCM', 'CAM', 'LAM', 'RAM', 'LM', 'RM'],
    'Tiền đạo' => ['LW', 'RW', 'ST', 'CF'],
];
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

<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-success text-white fw-semibold">Thêm cầu thủ vào đội hình</div>
            <div class="card-body">
                <form method="post" class="row g-2">
                    <input type="hidden" name="match_id" value="<?= $matchId ?>">
                    <input type="hidden" name="action" value="add">
                    <div class="col-12">
                        <label class="form-label small mb-1">Thêm cầu thủ cho</label>
                        <div class="btn-group w-100 flex-wrap" role="group" aria-label="Chọn đội">
                            <input type="radio" class="btn-check" name="add_team" id="add_team_home" value="home" checked autocomplete="off">
                            <label class="btn btn-outline-primary btn-sm" for="add_team_home"><?= htmlspecialchars((string) $current['HomeName'], ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="radio" class="btn-check" name="add_team" id="add_team_away" value="away" autocomplete="off">
                            <label class="btn btn-outline-danger btn-sm" for="add_team_away"><?= htmlspecialchars((string) $current['AwayName'], ENT_QUOTES, 'UTF-8') ?></label>
                        </div>
                        <p class="form-text small mb-0 mt-1">Chỉ hiện cầu thủ của đội được chọn; sơ đồ tương ứng đội nhà (xanh) / đội khách (đỏ).</p>
                    </div>
                    <div class="col-12">
                        <label class="form-label small" for="add_player_id">Cầu thủ</label>
                        <select name="player_id" id="add_player_id" class="form-select form-select-sm" required>
                            <option value="">— Chọn —</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small mb-1">Vai trò</label>
                        <div class="btn-group w-100" role="group" aria-label="Vai trò cầu thủ">
                            <input type="radio" class="btn-check" name="role" id="role_starter" value="starter" checked autocomplete="off">
                            <label class="btn btn-outline-success btn-sm" for="role_starter">Đá chính</label>
                            <input type="radio" class="btn-check" name="role" id="role_bench" value="bench" autocomplete="off">
                            <label class="btn btn-outline-secondary btn-sm" for="role_bench">Dự bị</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div id="field-pos-block">
                            <label class="form-label small mb-1">Vị trí trên sân (radio)</label>
                            <?php foreach ($fieldPosGroups as $gName => $gList): ?>
                                <div class="mb-2">
                                    <div class="text-muted small fw-semibold mb-1"><?= htmlspecialchars($gName, ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach ($gList as $fp): ?>
                                            <input type="radio" class="btn-check" name="field_position" id="fp_<?= htmlspecialchars($fp, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($fp, ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
                                            <label class="btn btn-outline-dark btn-sm" for="fp_<?= htmlspecialchars($fp, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($fp, ENT_QUOTES, 'UTF-8') ?></label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <p class="form-text small mb-0">Chỉ chọn vị trí khi <strong>đá chính</strong>. Nhớ nhập <strong>số áo</strong> cho cầu thủ tại <a href="players.php">Cầu thủ</a> để hiển thị trên sơ đồ.</p>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-success btn-sm">Thêm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-primary text-white py-2 fw-semibold small">Sơ đồ — <?= htmlspecialchars((string) $current['HomeName'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="card-body p-2">
                        <?= lineupRenderPitchMarkup($pitchHome, $benchHome, 'home', 'Sơ đồ đội nhà') ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header text-white py-2 fw-semibold small admin-lineup-header-away">Sơ đồ — <?= htmlspecialchars((string) $current['AwayName'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="card-body p-2">
                        <?= lineupRenderPitchMarkup($pitchAway, $benchAway, 'away', 'Sơ đồ đội khách') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<h2 class="h5 mb-3">Nhân sự đã đăng ký</h2>
<div class="row g-4">
    <div class="col-xl-5">
        <div class="row g-3">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-semibold d-flex align-items-center gap-2">
                        <span class="badge text-bg-primary">Nhà</span>
                        <?= htmlspecialchars((string) $current['HomeName'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="card-body p-0">
                        <?php
                        $homeRows = array_values(array_filter($lineupRows, static fn ($r) => (int) $r['IsHomeTeam'] === 1));
                        ?>
                        <?php if (count($homeRows) === 0): ?>
                            <p class="text-muted small p-3 mb-0">Chưa có cầu thủ đội nhà.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0 admin-personnel-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="border-0 text-center" style="width:3.25rem">Số</th>
                                            <th class="border-0">Cầu thủ</th>
                                            <th class="border-0 text-center">Vị trí</th>
                                            <th class="border-0 text-center">Chính</th>
                                            <th class="border-0"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($homeRows as $lr): ?>
                                            <tr>
                                                <td class="text-center"><?= lineupPlayerShirtBadgeHtml($lr['ShirtNumber'] ?? null, (string) $lr['FullName'], 'home') ?></td>
                                                <td><?= htmlspecialchars((string) $lr['FullName'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="text-center small"><?= htmlspecialchars((string) ($lr['FieldPosition'] ?: ($lr['Position'] ?? '—')), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="text-center"><?= (int) $lr['IsStarter'] ? '<span class="text-success">✓</span>' : '<span class="text-muted">Dự</span>' ?></td>
                                                <td class="text-end">
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
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-semibold d-flex align-items-center gap-2">
                        <span class="badge text-bg-danger">Khách</span>
                        <?= htmlspecialchars((string) $current['AwayName'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="card-body p-0">
                        <?php
                        $awayRows = array_values(array_filter($lineupRows, static fn ($r) => (int) $r['IsHomeTeam'] === 0));
                        ?>
                        <?php if (count($awayRows) === 0): ?>
                            <p class="text-muted small p-3 mb-0">Chưa có cầu thủ đội khách.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0 admin-personnel-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="border-0 text-center" style="width:3.25rem">Số</th>
                                            <th class="border-0">Cầu thủ</th>
                                            <th class="border-0 text-center">Vị trí</th>
                                            <th class="border-0 text-center">Chính</th>
                                            <th class="border-0"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($awayRows as $lr): ?>
                                            <tr>
                                                <td class="text-center"><?= lineupPlayerShirtBadgeHtml($lr['ShirtNumber'] ?? null, (string) $lr['FullName'], 'away') ?></td>
                                                <td><?= htmlspecialchars((string) $lr['FullName'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="text-center small"><?= htmlspecialchars((string) ($lr['FieldPosition'] ?: ($lr['Position'] ?? '—')), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="text-center"><?= (int) $lr['IsStarter'] ? '<span class="text-success">✓</span>' : '<span class="text-muted">Dự</span>' ?></td>
                                                <td class="text-end">
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
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$addOptsHome = [];
foreach ($homePlayers as $p) {
    $sn = $p['ShirtNumber'] !== null ? (string) (int) $p['ShirtNumber'] : '—';
    $addOptsHome[] = [
        'id' => (int) $p['PlayerId'],
        'label' => $sn . ' — ' . $p['FullName'],
    ];
}
$addOptsAway = [];
foreach ($awayPlayers as $p) {
    $sn = $p['ShirtNumber'] !== null ? (string) (int) $p['ShirtNumber'] : '—';
    $addOptsAway[] = [
        'id' => (int) $p['PlayerId'],
        'label' => $sn . ' — ' . $p['FullName'],
    ];
}
?>
<script>
(function () {
    var byTeam = {
        home: <?= json_encode($addOptsHome, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) ?>,
        away: <?= json_encode($addOptsAway, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) ?>
    };
    var sel = document.getElementById('add_player_id');
    var radios = document.querySelectorAll('input[name="add_team"]');
    if (!sel || !radios.length) return;

    function fillSelect(team) {
        var list = byTeam[team] || [];
        sel.innerHTML = '<option value="">— Chọn —</option>';
        for (var i = 0; i < list.length; i++) {
            var o = document.createElement('option');
            o.value = String(list[i].id);
            o.textContent = list[i].label;
            sel.appendChild(o);
        }
    }

    function currentTeam() {
        var v = 'home';
        radios.forEach(function (r) {
            if (r.checked) v = r.value;
        });
        return v;
    }

    radios.forEach(function (r) {
        r.addEventListener('change', function () {
            fillSelect(currentTeam());
        });
    });
    fillSelect(currentTeam());

    var roleStarter = document.getElementById('role_starter');
    var roleBench = document.getElementById('role_bench');
    var fieldBlock = document.getElementById('field-pos-block');

    function syncRole() {
        if (!fieldBlock) return;
        var isBench = roleBench && roleBench.checked;
        fieldBlock.classList.toggle('opacity-50', isBench);
        fieldBlock.querySelectorAll('input[name=\"field_position\"]').forEach(function (el) {
            el.disabled = isBench;
            if (isBench) el.checked = false;
        });
    }

    if (roleStarter) roleStarter.addEventListener('change', syncRole);
    if (roleBench) roleBench.addEventListener('change', syncRole);
    syncRole();
})();
</script>

<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
