-- Chạy một lần nếu database đã tạo trước khi có bảng MatchTeamStat (phpMyAdmin → SQL).
USE FootballLeague;

CREATE TABLE IF NOT EXISTS MatchTeamStat (
    StatId          INT AUTO_INCREMENT PRIMARY KEY,
    MatchId         INT NOT NULL,
    IsHomeTeam      TINYINT(1) NOT NULL,
    Shots           SMALLINT UNSIGNED NULL,
    ShotsOnTarget   SMALLINT UNSIGNED NULL,
    Possession      TINYINT UNSIGNED NULL,
    Passes          INT UNSIGNED NULL,
    PassAccuracy    TINYINT UNSIGNED NULL,
    Fouls           SMALLINT UNSIGNED NULL,
    YellowCards     SMALLINT UNSIGNED NULL,
    RedCards        SMALLINT UNSIGNED NULL,
    Offsides        SMALLINT UNSIGNED NULL,
    Corners         SMALLINT UNSIGNED NULL,
    UpdatedAt       DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    CONSTRAINT fk_stat_match FOREIGN KEY (MatchId) REFERENCES `Match`(MatchId) ON DELETE CASCADE,
    CONSTRAINT UQ_Match_Team UNIQUE (MatchId, IsHomeTeam)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS IX_Stat_Match ON MatchTeamStat(MatchId);

