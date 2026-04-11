/*
  Chèn tài khoản admin (mật khẩu: admin123) — chạy trong phpMyAdmin khi bảng AdminUser còn trống.
  Nếu báo trùng Username, xóa dòng cũ trong AdminUser hoặc đổi tên user bên dưới.
*/
USE FootballLeague;

INSERT INTO `AdminUser` (Username, PasswordHash) VALUES (
  'admin',
  '$2y$10$w4RoMlDtiiHH2LO.pexEGue6iRD6jFOJ1SjvDXg7EaU5nmNylNo7S'
);
