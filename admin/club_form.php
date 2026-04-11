<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireAdmin();

$pdo = getPdo();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$club = null;
if ($id > 0) {
    $st = $pdo->prepare('SELECT * FROM `Club` WHERE ClubId = ?');
    $st->execute([$id]);
    $club = $st->fetch();
    if (!$club) {
        header('Location: clubs.php');
        exit;
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $short = trim((string) ($_POST['short_name'] ?? ''));
    $stadium = trim((string) ($_POST['stadium'] ?? ''));
    $year = $_POST['founded_year'] === '' ? null : (int) $_POST['founded_year'];
    $logo = trim((string) ($_POST['logo_url'] ?? ''));

    if ($name === '') {
        $error = 'Nhập tên CLB.';
    } else {
        if ($id > 0) {
            $up = $pdo->prepare('UPDATE `Club` SET Name=?, ShortName=?, Stadium=?, FoundedYear=?, LogoUrl=? WHERE ClubId=?');
            $up->execute([
                $name,
                $short !== '' ? $short : null,
                $stadium !== '' ? $stadium : null,
                $year,
                $logo !== '' ? $logo : null,
                $id,
            ]);
        } else {
            $ins = $pdo->prepare('INSERT INTO `Club` (Name, ShortName, Stadium, FoundedYear, LogoUrl) VALUES (?,?,?,?,?)');
            $ins->execute([
                $name,
                $short !== '' ? $short : null,
                $stadium !== '' ? $stadium : null,
                $year,
                $logo !== '' ? $logo : null,
            ]);
        }
        header('Location: clubs.php');
        exit;
    }
}

$pageTitle = $id > 0 ? 'Sửa CLB' : 'Thêm CLB';
$isAdminArea = true;
$assetsPrefix = '../';

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="mb-3"><a href="clubs.php" class="text-decoration-none">← Danh sách CLB</a></div>
<h1 class="h3 mb-4"><?= $id > 0 ? 'Sửa câu lạc bộ' : 'Thêm câu lạc bộ' ?></h1>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" class="row g-3" style="max-width: 560px;">
    <div class="col-12">
        <label class="form-label" for="name">Tên CLB *</label>
        <input type="text" name="name" id="name" class="form-control" required value="<?= htmlspecialchars((string) ($club['Name'] ?? $_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="short_name">Tên viết tắt</label>
        <input type="text" name="short_name" id="short_name" class="form-control" value="<?= htmlspecialchars((string) ($club['ShortName'] ?? $_POST['short_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="founded_year">Năm thành lập</label>
        <input type="number" name="founded_year" id="founded_year" class="form-control" min="1800" max="2100" value="<?= $club && $club['FoundedYear'] !== null ? (int) $club['FoundedYear'] : ($_POST['founded_year'] ?? '') ?>">
    </div>
    <div class="col-12">
        <label class="form-label" for="stadium">Sân nhà</label>
        <input type="text" name="stadium" id="stadium" class="form-control" value="<?= htmlspecialchars((string) ($club['Stadium'] ?? $_POST['stadium'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-12">
        <label class="form-label" for="logo_url">URL logo</label>
        <input type="url" name="logo_url" id="logo_url" class="form-control" placeholder="https://..." value="<?= htmlspecialchars((string) ($club['LogoUrl'] ?? $_POST['logo_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-12">
        <button type="submit" class="btn btn-success">Lưu</button>
        <a href="clubs.php" class="btn btn-outline-secondary">Hủy</a>
    </div>
</form>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
