/* Dữ liệu mẫu — chạy sau schema.sql (tùy chọn) */
USE FootballLeague;

/* TRUNCATE không dùng được cho bảng bị FK tham chiếu (#1701) — dùng DELETE */
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM MatchLineup;
DELETE FROM `Match`;
DELETE FROM Player;
DELETE FROM Club;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO Club (ClubId, Name, ShortName, Stadium, FoundedYear, LogoUrl) VALUES
(1, 'Câu lạc bộ A', 'CLA', 'Sân A', 1990, NULL),
(2, 'Câu lạc bộ B', 'CLB', 'Sân B', 1985, NULL);

ALTER TABLE Club AUTO_INCREMENT = 3;

INSERT INTO Player (PlayerId, ClubId, FullName, Position, ShirtNumber, Nationality, DateOfBirth) VALUES
(1, 1, 'Nguyễn Văn A', 'Tiền đạo', 9, 'Việt Nam', '1998-05-01'),
(2, 1, 'Trần Văn B', 'Thủ môn', 1, 'Việt Nam', '1996-01-15'),
(3, 2, 'Lê Văn C', 'Tiền vệ', 10, 'Việt Nam', '1999-08-20'),
(4, 2, 'Phạm Văn D', 'Hậu vệ', 4, 'Việt Nam', '1997-03-10');

ALTER TABLE Player AUTO_INCREMENT = 5;

INSERT INTO `Match` (MatchId, HomeClubId, AwayClubId, MatchDateTime, RefereeName, Venue, HomeScore, AwayScore, Status) VALUES
(1, 1, 2, '2026-04-15 19:00:00.000', 'Ông Trọng tài X', 'Sân A', NULL, NULL, 'Scheduled');

ALTER TABLE `Match` AUTO_INCREMENT = 2;
