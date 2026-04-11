<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim((string) ($_POST['username'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');
    if ($user === '' || $pass === '') {
        $error = 'Nhập đủ tên đăng nhập và mật khẩu.';
    } else {
        try {
            $pdo = getPdo();
            $st = $pdo->prepare('SELECT AdminId, Username, PasswordHash FROM `AdminUser` WHERE Username = ?');
            $st->execute([$user]);
            $row = $st->fetch();
            if ($row && password_verify($pass, (string) $row['PasswordHash'])) {
                $_SESSION['admin_id'] = (int) $row['AdminId'];
                $_SESSION['admin_username'] = (string) $row['Username'];
                header('Location: index.php');
                exit;
            }
            $error = 'Sai tên đăng nhập hoặc mật khẩu.';
        } catch (Throwable $e) {
            $error = 'Lỗi kết nối CSDL. Kiểm tra cấu hình.';
        }
    }
}

$pageTitle = 'Đăng nhập quản trị';
$isAdminArea = true;
$assetsPrefix = '../';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h1 class="h4 mb-4 text-center">Đăng nhập Admin</h1>
                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <form method="post" action="">
                    <div class="mb-3">
                        <label class="form-label" for="username">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="username" name="username" required autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Mật khẩu</label>
                        <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn btn-success w-100">Đăng nhập</button>
                </form>
                <p class="text-center mt-3 mb-0 small"><a href="../index.php">← Về trang chủ</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
