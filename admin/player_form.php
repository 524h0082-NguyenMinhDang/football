<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireAdmin();

$pdo = getPdo();
$clubs = $pdo->query('SELECT ClubId, Name FROM `Club` ORDER BY Name')->fetchAll();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$player = null;
if ($id > 0) {
    $st = $pdo->prepare('SELECT * FROM `Player` WHERE PlayerId = ?');
    $st->execute([$id]);
    $player = $st->fetch();
    if (!$player) {
        header('Location: players.php');
        exit;
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clubId = (int) ($_POST['club_id'] ?? 0);
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $position = trim((string) ($_POST['position'] ?? ''));
    $shirt = $_POST['shirt_number'] === '' ? null : (int) $_POST['shirt_number'];
    $nat = trim((string) ($_POST['nationality'] ?? ''));
    $dob = trim((string) ($_POST['date_of_birth'] ?? ''));
    $photoUrl = trim((string) ($_POST['photo_url'] ?? ''));

    if ($clubId < 1 || $fullName === '') {
        $error = 'Chọn CLB và nhập họ tên.';
    } else {
        $dobSql = $dob === '' ? null : $dob;
        $photoSql = $photoUrl !== '' ? $photoUrl : null;
        if ($id > 0) {
            $up = $pdo->prepare('UPDATE `Player` SET ClubId=?, FullName=?, Position=?, ShirtNumber=?, Nationality=?, DateOfBirth=?, PhotoUrl=? WHERE PlayerId=?');
            $up->execute([
                $clubId, $fullName,
                $position !== '' ? $position : null,
                $shirt,
                $nat !== '' ? $nat : null,
                $dobSql,
                $photoSql,
                $id,
            ]);
        } else {
            $ins = $pdo->prepare('INSERT INTO `Player` (ClubId, FullName, Position, ShirtNumber, Nationality, DateOfBirth, PhotoUrl) VALUES (?,?,?,?,?,?,?)');
            $ins->execute([
                $clubId, $fullName,
                $position !== '' ? $position : null,
                $shirt,
                $nat !== '' ? $nat : null,
                $dobSql,
                $photoSql,
            ]);
        }
        header('Location: players.php');
        exit;
    }
}

$pageTitle = $id > 0 ? 'Sửa cầu thủ' : 'Thêm cầu thủ';
$isAdminArea = true;
$assetsPrefix = '../';

$clubSel = $player ? (int) $player['ClubId'] : (int) ($_POST['club_id'] ?? 0);

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="mb-3"><a href="players.php" class="text-decoration-none">← Danh sách cầu thủ</a></div>
<h1 class="h3 mb-4"><?= $id > 0 ? 'Sửa cầu thủ' : 'Thêm cầu thủ' ?></h1>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" class="row g-3" style="max-width: 560px;">
    <div class="col-12">
        <label class="form-label" for="club_id">Câu lạc bộ *</label>
        <select name="club_id" id="club_id" class="form-select" required>
            <option value="">— Chọn —</option>
            <?php foreach ($clubs as $c): ?>
                <option value="<?= (int) $c['ClubId'] ?>" <?= $clubSel === (int) $c['ClubId'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $c['Name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12">
        <label class="form-label" for="full_name">Họ tên *</label>
        <input type="text" name="full_name" id="full_name" class="form-control" required value="<?= htmlspecialchars((string) ($player['FullName'] ?? $_POST['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="shirt_number">Số áo</label>
        <input type="number" name="shirt_number" id="shirt_number" class="form-control" min="0" max="99" value="<?= $player && $player['ShirtNumber'] !== null ? (int) $player['ShirtNumber'] : ($_POST['shirt_number'] ?? '') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="position">Vị trí</label>
        <input type="text" name="position" id="position" class="form-control" value="<?= htmlspecialchars((string) ($player['Position'] ?? $_POST['position'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="nationality">Quốc tịch</label>
        <input type="text" name="nationality" id="nationality" class="form-control" value="<?= htmlspecialchars((string) ($player['Nationality'] ?? $_POST['nationality'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="date_of_birth">Ngày sinh</label>
        <input type="date" name="date_of_birth" id="date_of_birth" class="form-control" value="<?= !empty($player['DateOfBirth']) ? htmlspecialchars(substr((string) $player['DateOfBirth'], 0, 10), ENT_QUOTES, 'UTF-8') : ($_POST['date_of_birth'] ?? '') ?>">
    </div>
    <div class="col-12">
        <label class="form-label" for="photo_url">URL ảnh đại diện</label>
        <input type="url" name="photo_url" id="photo_url" class="form-control" inputmode="url" placeholder="https://…" value="<?= htmlspecialchars((string) ($player['PhotoUrl'] ?? $_POST['photo_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <p class="form-text mb-0">Tùy chọn — hiển thị trên trang đội hình &amp; nhân sự (ảnh vuông, khuyến nghị tối thiểu 128×128 px).</p>
    </div>
    <div class="col-12">
        <button type="submit" class="btn btn-success">Lưu</button>
        <a href="players.php" class="btn btn-outline-secondary">Hủy</a>
    </div>
</form>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
