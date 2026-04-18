/* Dữ liệu mẫu — chạy sau schema.sql (tùy chọn) */
USE FootballLeague;

/* TRUNCATE không dùng được cho bảng bị FK tham chiếu (#1701) — dùng DELETE */
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM MatchEvent;
DELETE FROM MatchLineup;
DELETE FROM MatchTeamStat;
DELETE FROM `Match`;
DELETE FROM Player;
DELETE FROM Club;
DELETE FROM AdminUser;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO `AdminUser` (Username, PasswordHash) VALUES (
  'admin',
  '$2y$10$w4RoMlDtiiHH2LO.pexEGue6iRD6jFOJ1SjvDXg7EaU5nmNylNo7S'
);

INSERT INTO Club (ClubId, Name, ShortName, Stadium, FoundedYear) VALUES
(1, 'Hà Nội FC', 'HNFC', 'Hàng Đẫy', 2006),
(2, 'Hoàng Anh Gia Lai', 'HAGL', 'Pleiku', 2001),
(3, 'Becamex Bình Dương', 'BBD', 'Gò Đậu', 1997),
(4, 'Thép Xanh Nam Định', 'NĐ', 'Thiên Trường', 1965);

ALTER TABLE Club AUTO_INCREMENT = 5;

INSERT INTO Player (PlayerId, ClubId, FullName, Position, ShirtNumber, Nationality, DateOfBirth) VALUES
-- Hà Nội FC
(1, 1, 'Quan Văn Chuẩn', 'GK', 1, 'Việt Nam', '2001-11-07'),
(2, 1, 'Nguyễn Thành Chung', 'CB', 16, 'Việt Nam', '1997-09-08'),
(3, 1, 'Đỗ Duy Mạnh', 'CB', 2, 'Việt Nam', '1996-09-29'),
(4, 1, 'Vũ Văn Thanh', 'RB', 17, 'Việt Nam', '1996-04-14'),
(5, 1, 'Lê Văn Xuân', 'LB', 5, 'Việt Nam', '1999-11-08'),
(6, 1, 'Nguyễn Hùng Dũng', 'CM', 88, 'Việt Nam', '1993-10-08'),
(7, 1, 'Nguyễn Văn Trường', 'CAM', 19, 'Việt Nam', '2003-05-27'),
(8, 1, 'Phạm Tuấn Hải', 'ST', 9, 'Việt Nam', '1998-05-19'),
(9, 1, 'Hai Long', 'LW', 11, 'Việt Nam', '2000-08-27'),
(10, 1, 'Tô Văn Vũ', 'RM', 24, 'Việt Nam', '1993-07-01'),
(11, 1, 'Bùi Hoàng Việt Anh', 'CB', 68, 'Việt Nam', '1999-11-01'),

-- Hoàng Anh Gia Lai
(12, 2, 'Trần Trung Kiên', 'GK', 25, 'Việt Nam', '2003-12-09'),
(13, 2, 'Đặng Văn Tới', 'CB', 3, 'Việt Nam', '1999-08-15'),
(14, 2, 'Lê Đức Lương', 'LB', 4, 'Việt Nam', '1995-09-12'),
(15, 2, 'Nguyễn Thanh Nhân', 'RB', 2, 'Việt Nam', '2000-03-11'),
(16, 2, 'Châu Ngọc Quang', 'CM', 10, 'Việt Nam', '1996-11-05'),
(17, 2, 'Trần Minh Vương', 'CAM', 8, 'Việt Nam', '1995-03-28'),
(18, 2, 'Nguyễn Quốc Việt', 'ST', 9, 'Việt Nam', '2003-05-19'),
(19, 2, 'Marciel', 'CDM', 6, 'Brazil', '1994-02-10'),
(20, 2, 'Bảo Toàn', 'LW', 11, 'Việt Nam', '1999-01-14'),
(21, 2, 'Đinh Thanh Bình', 'RW', 7, 'Việt Nam', '1998-04-03'),
(22, 2, 'Trần Bảo Toàn', 'CM', 15, 'Việt Nam', '2000-01-09'),

-- Becamex Bình Dương
(23, 3, 'Trần Đức Cường', 'GK', 26, 'Việt Nam', '1999-01-21'),
(24, 3, 'Trần Văn Kiệt', 'CB', 4, 'Việt Nam', '1998-02-11'),
(25, 3, 'Nguyễn Anh Tài', 'CB', 5, 'Việt Nam', '2000-06-17'),
(26, 3, 'Trần Minh Hiếu', 'LB', 12, 'Việt Nam', '1997-08-02'),
(27, 3, 'Nguyễn Tiến Linh', 'ST', 22, 'Việt Nam', '1997-10-20'),
(28, 3, 'Bùi Vĩnh Nguyên', 'CM', 8, 'Việt Nam', '1996-12-25'),
(29, 3, 'Lê Thanh Phong', 'CAM', 18, 'Việt Nam', '1999-09-09'),
(30, 3, 'Trần Quốc Hưng', 'RB', 2, 'Việt Nam', '1998-04-18'),
(31, 3, 'Phạm Minh Khoa', 'LW', 11, 'Việt Nam', '2001-03-30'),
(32, 3, 'Lê Quang Vinh', 'RW', 7, 'Việt Nam', '1997-05-06'),
(33, 3, 'Ngô Hoàng Long', 'CDM', 6, 'Việt Nam', '1996-10-13'),

-- Thép Xanh Nam Định
(34, 4, 'Nguyễn Xuân Việt', 'GK', 1, 'Việt Nam', '1998-01-30'),
(35, 4, 'Nguyễn Hữu Tuấn', 'CB', 3, 'Việt Nam', '1994-04-20'),
(36, 4, 'Trần Đình Trọng', 'CB', 44, 'Việt Nam', '1997-07-25'),
(37, 4, 'Hạ Long', 'RB', 2, 'Việt Nam', '1999-02-14'),
(38, 4, 'Nguyễn Văn Toàn', 'RW', 9, 'Việt Nam', '1996-04-07'),
(39, 4, 'Phạm Văn Thành', 'CM', 8, 'Việt Nam', '1995-08-30'),
(40, 4, 'Lê Sỹ Minh', 'CAM', 10, 'Việt Nam', '1998-12-11'),
(41, 4, 'Trần Văn Huy', 'ST', 19, 'Việt Nam', '2000-05-16'),
(42, 4, 'Nguyễn Văn Duy', 'LW', 11, 'Việt Nam', '2001-01-05'),
(43, 4, 'Bùi Hồng Quân', 'CDM', 6, 'Việt Nam', '1996-09-19'),
(44, 4, 'Phạm Thành Long', 'LB', 15, 'Việt Nam', '1997-11-23');

ALTER TABLE Player AUTO_INCREMENT = 45;

INSERT INTO `Match` (MatchId, HomeClubId, AwayClubId, MatchDateTime, RefereeName, Venue, HomeScore, AwayScore, Status) VALUES
(1, 1, 2, '2026-04-15 19:00:00.000', 'Nguyễn Văn A', 'Hàng Đẫy', 2, 1, 'Finished'),
(2, 3, 4, '2026-04-16 19:00:00.000', 'Trần Văn B', 'Gò Đậu', 1, 1, 'Finished');

ALTER TABLE `Match` AUTO_INCREMENT = 3;

INSERT INTO MatchLineup (MatchId, PlayerId, IsHomeTeam, IsStarter, FieldPosition) VALUES
-- Match 1: Hà Nội FC vs HAGL
(1, 1, 1, 1, 'GK'),
(1, 2, 1, 1, 'CB'),
(1, 3, 1, 1, 'CB'),
(1, 4, 1, 1, 'RB'),
(1, 5, 1, 1, 'LB'),
(1, 6, 1, 1, 'CM'),
(1, 7, 1, 1, 'CAM'),
(1, 8, 1, 1, 'ST'),
(1, 9, 1, 1, 'LW'),
(1, 10, 1, 1, 'RM'),
(1, 11, 1, 0, 'CB'),
(1, 12, 0, 1, 'GK'),
(1, 13, 0, 1, 'CB'),
(1, 14, 0, 1, 'LB'),
(1, 15, 0, 1, 'RB'),
(1, 16, 0, 1, 'CM'),
(1, 17, 0, 1, 'CAM'),
(1, 18, 0, 1, 'ST'),
(1, 19, 0, 1, 'CDM'),
(1, 20, 0, 1, 'LW'),
(1, 21, 0, 1, 'RW'),
(1, 22, 0, 0, 'CM'),

-- Match 2: BBD vs Nam Định
(2, 23, 1, 1, 'GK'),
(2, 24, 1, 1, 'CB'),
(2, 25, 1, 1, 'CB'),
(2, 26, 1, 1, 'LB'),
(2, 27, 1, 1, 'ST'),
(2, 28, 1, 1, 'CM'),
(2, 29, 1, 1, 'CAM'),
(2, 30, 1, 1, 'RB'),
(2, 31, 1, 1, 'LW'),
(2, 32, 1, 1, 'RW'),
(2, 33, 1, 0, 'CDM'),
(2, 34, 0, 1, 'GK'),
(2, 35, 0, 1, 'CB'),
(2, 36, 0, 1, 'CB'),
(2, 37, 0, 1, 'RB'),
(2, 38, 0, 1, 'RW'),
(2, 39, 0, 1, 'CM'),
(2, 40, 0, 1, 'CAM'),
(2, 41, 0, 1, 'ST'),
(2, 42, 0, 1, 'LW'),
(2, 43, 0, 1, 'CDM'),
(2, 44, 0, 0, 'LB');
