<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Giải bóng đá — Chọn vai trò</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light d-flex min-vh-100 align-items-center">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="text-center mb-5">
                <h1 class="display-5 fw-bold text-success">Hệ thống quản lý giải bóng đá</h1>
                <p class="text-muted">Chọn cách bạn muốn truy cập</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body text-center p-4">
                            <h2 class="h4 mb-3">Khách</h2>
                            <p class="text-muted small mb-4">Xem lịch thi đấu, bảng xếp hạng, cầu thủ — không cần đăng nhập.</p>
                            <a href="guest/index.php" class="btn btn-outline-success btn-lg w-100">Vào xem</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm border-success border-2">
                        <div class="card-body text-center p-4">
                            <h2 class="h4 mb-3">Quản trị viên</h2>
                            <p class="text-muted small mb-4">CRUD trận đấu, đội hình, nhân sự — cần đăng nhập.</p>
                            <a href="admin/login.php" class="btn btn-success btn-lg w-100">Đăng nhập</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
