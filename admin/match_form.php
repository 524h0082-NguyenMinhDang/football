<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
UPDATE `Match` SET HomeClubId=?, AwayClubId=?, MatchDateTime=?, RefereeName=?, Venue=?,
    HomeScore=?, AwayScore=?, Status=?
WHERE MatchId=?
SQL);
                $up->execute([
                    $homeId, $awayId, $dtSql,
                    $referee !== '' ? $referee : null,
                    $venue !== '' ? $venue : null,
                    $homeScore, $awayScore,
                    $status !== '' ? $status : 'Scheduled',
                    $id,
                ]);
            } else {
                $ins = $pdo->prepare(<<<SQL
INSERT INTO `Match` (HomeClubId, AwayClubId, MatchDateTime, RefereeName, Venue, HomeScore, AwayScore, Status)
VALUES (?, ?, ?, ?, ?, ?, ?, ?)
SQL);
                $ins->execute([
                    $homeId, $awayId, $dtSql,
                    $referee !== '' ? $referee : null,
                    $venue !== '' ? $venue : null,
                    $homeScore, $awayScore,
                    $status !== '' ? $status : 'Scheduled',
                ]);
            }
            header('Location: matches.php');
            exit;
        }
    }
}

$pageTitle = $id > 0 ? 'Sửa trận đấu' : 'Thêm trận đấu';
$isAdminArea = true;
$assetsPrefix = '../';

$dtValue = '';
if ($match) {
    $dtValue = date('Y-m-d\TH:i', strtotime((string) $match['MatchDateTime']));
} elseif (isset($_POST['match_datetime'])) {
    $dtValue = (string) $_POST['match_datetime'];
} else {
    $dtValue = date('Y-m-d\TH:i');
}

$homeSel = $match ? (int) $match['HomeClubId'] : (int) ($_POST['home_club_id'] ?? 0);
$awaySel = $match ? (int) $match['AwayClubId'] : (int) ($_POST['away_club_id'] ?? 0);

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="mb-3">
    <a href="matches.php" class="text-decoration-none">← Danh sách trận</a>
</div>
<h1 class="h3 mb-4"><?= $id > 0 ? 'Sửa trận đấu' : 'Thêm trận đấu' ?></h1>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" class="row g-3" style="max-width: 640px;">
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
        <?php $statusValue = (string) ($match['Status'] ?? $_POST['status'] ?? 'Scheduled'); ?>
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
    <div class="col-12">
        <button type="submit" class="btn btn-success">Lưu</button>
        <a href="matches.php" class="btn btn-outline-secondary">Hủy</a>
    </div>
</form>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
