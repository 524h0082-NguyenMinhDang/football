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

$positionOptions = [
    '' => '— Chọn vị trí —',
    'GK' => 'GK - Thủ môn',
    'SW' => 'SW - Hậu vệ quét',
    'LB' => 'LB - Hậu vệ trái',
    'LCB' => 'LCB - Trung vệ trái',
    'CB' => 'CB - Trung vệ',
    'RCB' => 'RCB - Trung vệ phải',
    'RB' => 'RB - Hậu vệ phải',
    'LWB' => 'LWB - Chạy cánh trái',
    'RWB' => 'RWB - Chạy cánh phải',
    'LDM' => 'LDM - Tiền vệ trụ trái',
    'CDM' => 'CDM - Tiền vệ trụ',
    'RDM' => 'RDM - Tiền vệ trụ phải',
    'LM' => 'LM - Tiền vệ trái',
    'LCM' => 'LCM - Tiền vệ trung tâm trái',
    'CM' => 'CM - Tiền vệ trung tâm',
    'RCM' => 'RCM - Tiền vệ trung tâm phải',
    'RM' => 'RM - Tiền vệ phải',
    'LAM' => 'LAM - Hộ công trái',
    'CAM' => 'CAM - Hộ công',
    'RAM' => 'RAM - Hộ công phải',
    'LW' => 'LW - Tiền đạo cánh trái',
    'LF' => 'LF - Tiền đạo trái',
    'CF' => 'CF - Tiền đạo trung tâm',
    'RF' => 'RF - Tiền đạo phải',
    'RW' => 'RW - Tiền đạo cánh phải',
    'LS' => 'LS - Tiền đạo trái',
    'ST' => 'ST - Tiền đạo cắm',
    'RS' => 'RS - Tiền đạo phải',
];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clubId = (int) ($_POST['club_id'] ?? 0);
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $position = trim((string) ($_POST['position'] ?? ''));
    $shirt = $_POST['shirt_number'] === '' ? null : (int) $_POST['shirt_number'];
    $nat = trim((string) ($_POST['nationality'] ?? ''));
    $dob = trim((string) ($_POST['date_of_birth'] ?? ''));
    if ($clubId < 1 || $fullName === '') {
        $error = 'Chọn CLB và nhập họ tên.';
    } elseif ($position !== '' && !array_key_exists($position, $positionOptions)) {
        $error = 'Vị trí không hợp lệ.';
    } else {
        $dobSql = $dob === '' ? null : $dob;
        if ($id > 0) {
            $up = $pdo->prepare('UPDATE `Player` SET ClubId=?, FullName=?, Position=?, ShirtNumber=?, Nationality=?, DateOfBirth=? WHERE PlayerId=?');
            $up->execute([
                $clubId, $fullName,
                $position !== '' ? $position : null,
                $shirt,
                $nat !== '' ? $nat : null,
                $dobSql,
                $id,
            ]);
        } else {
            $ins = $pdo->prepare('INSERT INTO `Player` (ClubId, FullName, Position, ShirtNumber, Nationality, DateOfBirth) VALUES (?,?,?,?,?,?)');
            $ins->execute([
                $clubId, $fullName,
                $position !== '' ? $position : null,
                $shirt,
                $nat !== '' ? $nat : null,
                $dobSql,
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
        <select name="position" id="position" class="form-select">
            <?php $selectedPosition = (string) ($player['Position'] ?? $_POST['position'] ?? ''); ?>
            <?php foreach ($positionOptions as $value => $label): ?>
                <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedPosition === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
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
        <button type="submit" class="btn btn-success">Lưu</button>
        <a href="players.php" class="btn btn-outline-secondary">Hủy</a>
    </div>
</form>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
