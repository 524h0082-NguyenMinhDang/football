<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/lineup_visual.php';

requireAdmin();

$pdo = getPdo();
$clubs = $pdo->query('SELECT ClubId, Name FROM `Club` ORDER BY Name')->fetchAll();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$match = null;
if ($id > 0) {
    $st = $pdo->prepare('SELECT * FROM `Match` WHERE MatchId = ?');
    $st->execute([$id]);
    $match = $st->fetch();
    if (!$match) {
        header('Location: matches.php');
        exit;
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_match') {
    $homeId = (int) ($_POST['home_club_id'] ?? 0);
    $awayId = (int) ($_POST['away_club_id'] ?? 0);
    $dt = trim((string) ($_POST['match_datetime'] ?? ''));
    $referee = trim((string) ($_POST['referee_name'] ?? ''));
    $venue = trim((string) ($_POST['venue'] ?? ''));
    $homeScore = $_POST['home_score'] === '' ? null : (int) $_POST['home_score'];
    $awayScore = $_POST['away_score'] === '' ? null : (int) $_POST['away_score'];
    $status = trim((string) ($_POST['status'] ?? 'Scheduled'));

    if ($homeId < 1 || $awayId < 1 || $homeId === $awayId) {
        $error = 'Chọn hai đội khác nhau.';
    } elseif ($dt === '') {
        $error = 'Nhập ngày giờ trận đấu.';
    } else {
        try {
            $dtSql = (new DateTimeImmutable($dt))->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $error = 'Ngày giờ không hợp lệ.';
            $dtSql = '';
        }
        if ($error === '' && $dtSql !== '') {
            if ($id > 0) {
                $up = $pdo->prepare(<<<SQL
UPDATE `Match` SET HomeClubId=?, AwayClubId=?, MatchDateTime=?, RefereeName=?, Venue=?, HomeScore=?, AwayScore=?, Status=?
WHERE MatchId=?
SQL);
                $up->execute([$homeId, $awayId, $dtSql, $referee !== '' ? $referee : null, $venue !== '' ? $venue : null, $homeScore, $awayScore, $status !== '' ? $status : 'Scheduled', $id]);
            } else {
                $ins = $pdo->prepare(<<<SQL
INSERT INTO `Match` (HomeClubId, AwayClubId, MatchDateTime, RefereeName, Venue, HomeScore, AwayScore, Status)
VALUES (?, ?, ?, ?, ?, ?, ?, ?)
SQL);
                $ins->execute([$homeId, $awayId, $dtSql, $referee !== '' ? $referee : null, $venue !== '' ? $venue : null, $homeScore, $awayScore, $status !== '' ? $status : 'Scheduled']);
                $id = (int) $pdo->lastInsertId();
            }
            header('Location: match_form.php?id=' . $id);
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_card') {
    $cardMatchId = (int) ($_POST['match_id'] ?? 0);
    if ($cardMatchId !== $id) {
        $error = 'Trận đấu không hợp lệ.';
    } else {
        $playerId = (int) ($_POST['player_id'] ?? 0);
        $minute = (int) ($_POST['event_minute'] ?? 0);
        $cardType = (string) ($_POST['card_type'] ?? '');
        if ($playerId < 1 || $minute < 0 || !in_array($cardType, ['Yellow', 'Red'], true)) {
            $error = 'Vui lòng chọn cầu thủ và loại thẻ hợp lệ.';
        } else {
            $cardStmt = $pdo->prepare(<<<SQL
SELECT
    SUM(CASE WHEN CardType='Yellow' THEN 1 ELSE 0 END) AS YellowCount,
    SUM(CASE WHEN CardType='Red' THEN 1 ELSE 0 END) AS RedCount
FROM MatchEvent
WHERE MatchId = ? AND PlayerId = ? AND EventType = 'Card'
SQL);
            $cardStmt->execute([$id, $playerId]);
            $existing = $cardStmt->fetch() ?: ['YellowCount' => 0, 'RedCount' => 0];
            if ($cardType === 'Yellow' && (int) $existing['YellowCount'] >= 2) {
                $error = 'Cầu thủ này đã đủ 2 thẻ vàng.';
            } elseif ($cardType === 'Red' && (int) $existing['RedCount'] >= 1) {
                $error = 'Cầu thủ này đã nhận thẻ đỏ.';
            } else {
                $ins = $pdo->prepare('INSERT INTO MatchEvent (MatchId, EventMinute, EventType, PlayerId, CardType) VALUES (?,?,?,?,?)');
                $ins->execute([$id, $minute, 'Card', $playerId, $cardType]);
                header('Location: match_form.php?id=' . $id);
                exit;
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_card') {
    $deleteId = (int) ($_POST['event_id'] ?? 0);
    $matchId = (int) ($_POST['match_id'] ?? 0);
    if ($matchId !== $id) {
        $error = 'Trận đấu không hợp lệ.';
    } elseif ($deleteId < 1) {
        $error = 'Thẻ không hợp lệ.';
    } else {
        $del = $pdo->prepare('DELETE FROM MatchEvent WHERE EventId = ? AND MatchId = ? AND EventType = \'Card\'');
        $del->execute([$deleteId, $id]);
        header('Location: match_form.php?id=' . $id);
        exit;
    }
}

$pageTitle = $id > 0 ? 'Sửa trận đấu' : 'Thêm trận đấu';
$isAdminArea = true;
$assetsPrefix = '../';

$dtValue = $match ? date('Y-m-d\TH:i', strtotime((string) $match['MatchDateTime'])) : (isset($_POST['match_datetime']) ? (string) $_POST['match_datetime'] : date('Y-m-d\TH:i'));
$homeSel = $match ? (int) $match['HomeClubId'] : (int) ($_POST['home_club_id'] ?? 0);
$awaySel = $match ? (int) $match['AwayClubId'] : (int) ($_POST['away_club_id'] ?? 0);
$statusValue = (string) ($match['Status'] ?? $_POST['status'] ?? 'Scheduled');

$lineupRows = [];
$homePitch = [];
$homeBench = [];
$awayPitch = [];
$awayBench = [];
$events = [];
$playerCardCounts = [];

if ($match) {
    $st = $pdo->prepare(<<<SQL
SELECT ml.*, p.FullName, p.ShirtNumber, p.Position, p.ClubId
FROM MatchLineup ml
JOIN Player p ON p.PlayerId = ml.PlayerId
WHERE ml.MatchId = ?
ORDER BY ml.IsHomeTeam DESC, ml.IsStarter DESC, p.ShirtNumber IS NULL, p.ShirtNumber, p.FullName
SQL);
    $st->execute([$id]);
    $lineupRows = $st->fetchAll();
    [$homePitch, $homeBench, $awayPitch, $awayBench] = lineupComputePitchSides($lineupRows);

    $eventStmt = $pdo->prepare(<<<SQL
SELECT e.*, p.FullName, p.ShirtNumber, p.ClubId,
       CASE WHEN p.ClubId = m.HomeClubId THEN 'home' ELSE 'away' END AS TeamSide
FROM MatchEvent e
JOIN `Match` m ON m.MatchId = e.MatchId
JOIN Player p ON p.PlayerId = e.PlayerId
WHERE e.MatchId = ?
ORDER BY e.EventMinute ASC, e.EventId ASC
SQL);
    $eventStmt->execute([$id]);
    $events = $eventStmt->fetchAll();

    $cardStmt = $pdo->prepare(<<<SQL
SELECT PlayerId,
       SUM(CASE WHEN EventType='Card' AND CardType='Yellow' THEN 1 ELSE 0 END) AS YellowCount,
       SUM(CASE WHEN EventType='Card' AND CardType='Red' THEN 1 ELSE 0 END) AS RedCount
FROM MatchEvent
WHERE MatchId = ?
GROUP BY PlayerId
SQL);
    $cardStmt->execute([$id]);
    foreach ($cardStmt->fetchAll() as $row) {
        $playerCardCounts[(int) $row['PlayerId']] = [
            'yellow' => (int) ($row['YellowCount'] ?? 0),
            'red' => (int) ($row['RedCount'] ?? 0),
        ];
    }
}

function lineupFilterActivePlayers(array $rows, array $eventsByPlayer): array
{
    $out = [];
    foreach ($rows as $r) {
        $pid = (int) $r['PlayerId'];
        $yellowCount = (int) ($eventsByPlayer[$pid]['yellow'] ?? 0);
        $redCount = (int) ($eventsByPlayer[$pid]['red'] ?? 0);
        $out[] = array_merge($r, [
            'is_out' => ($yellowCount >= 2) || ($redCount >= 1),
            'yellow_count' => $yellowCount,
            'red_count' => $redCount,
        ]);
    }
    return $out;
}

function lineupColorizePlayers(array $rows): array
{
    foreach ($rows as &$r) {
        $r['node_class'] = !empty($r['is_out']) ? 'admin-lineup-node--out' : '';
    }
    unset($r);
    return $rows;
}

$homePitch = lineupColorizePlayers(lineupFilterActivePlayers($homePitch, $playerCardCounts));
$homeBench = lineupColorizePlayers(lineupFilterActivePlayers($homeBench, $playerCardCounts));
$awayPitch = lineupColorizePlayers(lineupFilterActivePlayers($awayPitch, $playerCardCounts));
$awayBench = lineupColorizePlayers(lineupFilterActivePlayers($awayBench, $playerCardCounts));

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="mb-3"><a href="matches.php" class="text-decoration-none">← Danh sách trận</a></div>
<h1 class="h3 mb-4"><?= $id > 0 ? 'Sửa trận đấu' : 'Thêm trận đấu' ?></h1>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-6">
        <form method="post" class="row g-3">
            <input type="hidden" name="action" value="save_match">
            <div class="col-md-6">
                <label class="form-label" for="home_club_id">Đội nhà</label>
                <select name="home_club_id" id="home_club_id" class="form-select" required>
                    <option value="">— Chọn —</option>
                    <?php foreach ($clubs as $c): ?>
                        <option value="<?= (int) $c['ClubId'] ?>" <?= $homeSel === (int) $c['ClubId'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $c['Name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="away_club_id">Đội khách</label>
                <select name="away_club_id" id="away_club_id" class="form-select" required>
                    <option value="">— Chọn —</option>
                    <?php foreach ($clubs as $c): ?>
                        <option value="<?= (int) $c['ClubId'] ?>" <?= $awaySel === (int) $c['ClubId'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $c['Name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="status">Trạng thái</label>
                <select name="status" id="status" class="form-select">
                    <option value="Scheduled" <?= $statusValue === 'Scheduled' ? 'selected' : '' ?>>Scheduled - Lên lịch</option>
                    <option value="Live" <?= $statusValue === 'Live' ? 'selected' : '' ?>>Live - Đang chơi</option>
                    <option value="Finished" <?= $statusValue === 'Finished' ? 'selected' : '' ?>>Finished - Kết thúc</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="match_datetime">Ngày giờ</label>
                <input type="datetime-local" name="match_datetime" id="match_datetime" class="form-control" required value="<?= htmlspecialchars($dtValue, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="referee_name">Trọng tài</label>
                <input type="text" name="referee_name" id="referee_name" class="form-control" value="<?= htmlspecialchars((string) ($match['RefereeName'] ?? $_POST['referee_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="venue">Sân</label>
                <input type="text" name="venue" id="venue" class="form-control" value="<?= htmlspecialchars((string) ($match['Venue'] ?? $_POST['venue'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="home_score">Bàn đội nhà</label>
                <input type="number" name="home_score" id="home_score" class="form-control" min="0" value="<?= $match && $match['HomeScore'] !== null ? (int) $match['HomeScore'] : ($_POST['home_score'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="away_score">Bàn đội khách</label>
                <input type="number" name="away_score" id="away_score" class="form-control" min="0" value="<?= $match && $match['AwayScore'] !== null ? (int) $match['AwayScore'] : ($_POST['away_score'] ?? '') ?>">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-success">Lưu</button>
                <a href="matches.php" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>

    <div class="col-lg-6">
        <?php if ($match): ?>
            <div class="row g-3">
                <div class="col-12">
                    <h2 class="h5 mb-3">Sơ đồ trận đấu</h2>
                </div>
                <div class="col-md-6">
                    <h3 class="h6 mb-2">Đội nhà</h3>
                    <?= lineupRenderPitchMarkup($homePitch, $homeBench, 'home', 'Sơ đồ đội nhà') ?>
                    <div class="mt-2 small">
                        <?php foreach (array_merge($homePitch, $homeBench) as $pl): ?>
                            <div class="d-flex justify-content-between align-items-center border rounded px-2 py-1 mb-1 <?= !empty($pl['is_out']) ? 'text-muted bg-light' : '' ?>">
                                <div>
                                    <strong><?= htmlspecialchars((string) $pl['FullName'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span class="ms-2 text-muted">#<?= htmlspecialchars((string) ($pl['ShirtNumber'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div><?= !empty($pl['is_out']) ? '<span class="badge text-bg-danger">Ra sân</span>' : '' ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <h3 class="h6 mb-2">Đội khách</h3>
                    <?= lineupRenderPitchMarkup($awayPitch, $awayBench, 'away', 'Sơ đồ đội khách') ?>
                    <div class="mt-2 small">
                        <?php foreach (array_merge($awayPitch, $awayBench) as $pl): ?>
                            <div class="d-flex justify-content-between align-items-center border rounded px-2 py-1 mb-1 <?= !empty($pl['is_out']) ? 'text-muted bg-light' : '' ?>">
                                <div>
                                    <strong><?= htmlspecialchars((string) $pl['FullName'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span class="ms-2 text-muted">#<?= htmlspecialchars((string) ($pl['ShirtNumber'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div><?= !empty($pl['is_out']) ? '<span class="badge text-bg-danger">Ra sân</span>' : '' ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <hr class="my-4">
            <h2 class="h5 mb-3">Phạt thẻ</h2>
            <form method="post" class="row g-3 mb-4">
                <input type="hidden" name="action" value="add_card">
                <input type="hidden" name="match_id" value="<?= (int) $id ?>">
                <div class="col-md-4">
                    <label class="form-label" for="event_minute">Phút</label>
                    <input type="number" name="event_minute" id="event_minute" class="form-control" min="0" max="130" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="card_type">Loại thẻ</label>
                    <select name="card_type" id="card_type" class="form-select" required>
                        <option value="">— Chọn —</option>
                        <option value="Yellow">Yellow</option>
                        <option value="Red">Red</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="player_id">Cầu thủ</label>
                    <select name="player_id" id="player_id" class="form-select" required>
                        <option value="">— Chọn —</option>
                        <optgroup label="Đội nhà">
                            <?php foreach (array_merge($homePitch, $homeBench) as $pl): ?>
                                <?php if (!empty($pl['is_out'])): continue; endif; ?>
                                <option value="<?= (int) $pl['PlayerId'] ?>"><?= htmlspecialchars((string) $pl['FullName'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Đội khách">
                            <?php foreach (array_merge($awayPitch, $awayBench) as $pl): ?>
                                <?php if (!empty($pl['is_out'])): continue; endif; ?>
                                <option value="<?= (int) $pl['PlayerId'] ?>"><?= htmlspecialchars((string) $pl['FullName'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Thêm thẻ</button>
                </div>
            </form>

            <h2 class="h6 mb-2">Lịch sử thẻ</h2>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Phút</th>
                            <th>Thẻ</th>
                            <th>Cầu thủ</th>
                            <th class="text-end">Xử lý</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($events as $ev): ?>
                        <tr>
                            <td><?= (int) $ev['EventMinute'] ?></td>
                            <td><?= htmlspecialchars((string) ($ev['CardType'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) $ev['FullName'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end">
                                <form method="post" class="d-inline" onsubmit="return confirm('Xóa thẻ này?');">
                                    <input type="hidden" name="action" value="delete_card">
                                    <input type="hidden" name="match_id" value="<?= (int) $id ?>">
                                    <input type="hidden" name="event_id" value="<?= (int) $ev['EventId'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
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

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
