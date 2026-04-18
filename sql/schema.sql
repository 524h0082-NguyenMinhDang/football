/*
  Chạy trong phpMyAdmin (tab SQL) hoặc: mysql -u root -p < schema.sql
  Tạo database FootballLeague và các bảng cho ứng dụng PHP.
*/
CREATE DATABASE IF NOT EXISTS FootballLeague
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE FootballLeague;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS MatchEvent;
DROP TABLE IF EXISTS MatchTeamStat;
DROP TABLE IF EXISTS MatchLineup;
DROP TABLE IF EXISTS `Match`;
DROP TABLE IF EXISTS Player;
DROP TABLE IF EXISTS Club;
DROP TABLE IF EXISTS AdminUser;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE AdminUser (
    AdminId       INT AUTO_INCREMENT PRIMARY KEY,
    Username      VARCHAR(64) NOT NULL,
    PasswordHash  VARCHAR(255) NOT NULL,
    CreatedAt     DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    UNIQUE KEY uq_admin_username (Username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Club (
    ClubId        INT AUTO_INCREMENT PRIMARY KEY,
    Name          VARCHAR(128) NOT NULL,
    ShortName     VARCHAR(16) NULL,
    Stadium       VARCHAR(256) NULL,
    FoundedYear   SMALLINT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Player (
    PlayerId      INT AUTO_INCREMENT PRIMARY KEY,
    ClubId        INT NOT NULL,
    FullName      VARCHAR(128) NOT NULL,
    Position      VARCHAR(32) NULL,
    ShirtNumber   TINYINT UNSIGNED NULL,
    Nationality   VARCHAR(64) NULL,
    DateOfBirth   DATE NULL,
    CONSTRAINT fk_player_club FOREIGN KEY (ClubId) REFERENCES Club(ClubId) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Match` (
    MatchId       INT AUTO_INCREMENT PRIMARY KEY,
    HomeClubId    INT NOT NULL,
    AwayClubId    INT NOT NULL,
    MatchDateTime DATETIME(3) NOT NULL,
    RefereeName   VARCHAR(128) NULL,
    Venue         VARCHAR(256) NULL,
    HomeScore     TINYINT UNSIGNED NULL,
    AwayScore     TINYINT UNSIGNED NULL,
    Status        VARCHAR(32) NOT NULL DEFAULT 'Scheduled',
    CONSTRAINT fk_match_home FOREIGN KEY (HomeClubId) REFERENCES Club(ClubId),
    CONSTRAINT fk_match_away FOREIGN KEY (AwayClubId) REFERENCES Club(ClubId),
    CONSTRAINT CK_Match_DifferentClubs CHECK (HomeClubId <> AwayClubId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE MatchLineup (
    LineupId      INT AUTO_INCREMENT PRIMARY KEY,
    MatchId       INT NOT NULL,
    PlayerId      INT NOT NULL,
    IsHomeTeam    TINYINT(1) NOT NULL,
    IsStarter     TINYINT(1) NOT NULL DEFAULT 1,
    FieldPosition VARCHAR(32) NULL,
    CONSTRAINT fk_lineup_match FOREIGN KEY (MatchId) REFERENCES `Match`(MatchId) ON DELETE CASCADE,
    CONSTRAINT fk_lineup_player FOREIGN KEY (PlayerId) REFERENCES Player(PlayerId),
    CONSTRAINT UQ_Match_Player UNIQUE (MatchId, PlayerId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE MatchEvent (
    EventId         INT AUTO_INCREMENT PRIMARY KEY,
    MatchId         INT NOT NULL,
    EventMinute     SMALLINT UNSIGNED NOT NULL,
    EventType       VARCHAR(24) NOT NULL,
    PlayerId        INT NOT NULL,
    AssistPlayerId  INT NULL,
    CardType        VARCHAR(16) NULL,
    CreatedAt       DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    CONSTRAINT fk_event_match FOREIGN KEY (MatchId) REFERENCES `Match`(MatchId) ON DELETE CASCADE,
    CONSTRAINT fk_event_player FOREIGN KEY (PlayerId) REFERENCES Player(PlayerId),
    CONSTRAINT fk_event_assist FOREIGN KEY (AssistPlayerId) REFERENCES Player(PlayerId) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE MatchTeamStat (
    StatId          INT AUTO_INCREMENT PRIMARY KEY,
    MatchId         INT NOT NULL,
    IsHomeTeam      TINYINT(1) NOT NULL,
    Shots           SMALLINT UNSIGNED NULL,
    ShotsOnTarget   SMALLINT UNSIGNED NULL,
    Possession      TINYINT UNSIGNED NULL,     -- 0..100
    Passes          INT UNSIGNED NULL,
    PassAccuracy    TINYINT UNSIGNED NULL,     -- 0..100
    Fouls           SMALLINT UNSIGNED NULL,
    YellowCards     SMALLINT UNSIGNED NULL,
    RedCards        SMALLINT UNSIGNED NULL,
    Offsides        SMALLINT UNSIGNED NULL,
    Corners         SMALLINT UNSIGNED NULL,
    UpdatedAt       DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    CONSTRAINT fk_stat_match FOREIGN KEY (MatchId) REFERENCES `Match`(MatchId) ON DELETE CASCADE,
    CONSTRAINT UQ_Match_Team UNIQUE (MatchId, IsHomeTeam)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IX_Player_ClubId ON Player(ClubId);
CREATE INDEX IX_Match_Date ON `Match`(MatchDateTime);
CREATE INDEX IX_MatchLineup_Match ON MatchLineup(MatchId);
CREATE INDEX IX_MatchEvent_Match ON MatchEvent(MatchId);
CREATE INDEX IX_Stat_Match ON MatchTeamStat(MatchId);
