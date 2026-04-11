<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? 'Giải bóng đá';
$isGuestArea = $isGuestArea ?? false;
$isAdminArea = $isAdminArea ?? false;
$assetsPrefix = $assetsPrefix ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars($assetsPrefix . 'assets/css/style.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-success mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= ($isAdminArea && isAdminLoggedIn()) ? 'index.php' : ($isAdminArea ? '../index.php' : $assetsPrefix . 'index.php') ?>">Giải bóng đá</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto">
                <?php if ($isGuestArea): ?>
                    <li class="nav-item"><a class="nav-link" href="index.php">Trận đấu</a></li>
                    <li class="nav-item"><a class="nav-link" href="clubs.php">Câu lạc bộ</a></li>
                    <li class="nav-item"><a class="nav-link" href="players.php">Cầu thủ</a></li>
                <?php elseif ($isAdminArea && isAdminLoggedIn()): ?>
                    <li class="nav-item"><a class="nav-link" href="index.php">Bảng điều khiển</a></li>
                    <li class="nav-item"><a class="nav-link" href="matches.php">Trận đấu</a></li>
                    <li class="nav-item"><a class="nav-link" href="clubs.php">CLB</a></li>
                    <li class="nav-item"><a class="nav-link" href="players.php">Cầu thủ</a></li>
                    <li class="nav-item"><a class="nav-link" href="lineups.php">Đội hình trận</a></li>
                <?php endif; ?>
            </ul>
            <?php if ($isAdminArea && isAdminLoggedIn()): ?>
                <span class="navbar-text text-white me-3"><?= htmlspecialchars((string) $_SESSION['admin_username'], ENT_QUOTES, 'UTF-8') ?></span>
                <a class="btn btn-outline-light btn-sm" href="logout.php">Đăng xuất</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="container pb-5">
