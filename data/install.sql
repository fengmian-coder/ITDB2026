-- WorldCup Journey - 数据库结构（15 表冻结草案）
-- 模块：数据库与球队；负责人：C
-- 依赖：MySQL 8.0（utf8mb4 / utf8mb4_0900_ai_ci）、InnoDB
-- 最近修改：2026-11-05
--
-- 使用说明：
-- 1. 本脚本只包含表结构，不包含演示数据；数据由导入程序写入。
-- 2. 请在全新空库 worldcup_journey 中执行，不要对已有真实数据的库直接执行。
-- 3. 如尚未建库，先执行：
--    CREATE DATABASE worldcup_journey
--      CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
--    USE worldcup_journey;
-- 4. MySQL 8.0.12 对 CHECK 只解析、不强制执行；8.0.16+ 才真正校验。
--    最终验收建议使用 8.0.16 及以上版本。

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ---------------------------------------------------------------------------
-- 1. 赛事届次（B）
-- ---------------------------------------------------------------------------
CREATE TABLE `tournament` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `year`        SMALLINT UNSIGNED NOT NULL,
    `name`        VARCHAR(120) NOT NULL,
    `start_date`  DATE NOT NULL,
    `end_date`    DATE NOT NULL,
    `description` TEXT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_tournament_year` (`year`),
    CONSTRAINT `chk_tournament_year` CHECK (`year` > 0),
    CONSTRAINT `chk_tournament_dates` CHECK (`start_date` <= `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------------
-- 2. 国家队（C）
-- ---------------------------------------------------------------------------
CREATE TABLE `national_team` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `fifa_code`   CHAR(3) NOT NULL,
    `name_zh`     VARCHAR(80) NOT NULL,
    `name_en`     VARCHAR(100) NOT NULL,
    `flag_path`   VARCHAR(255) NULL,
    `description` TEXT NULL,
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_national_team_fifa_code` (`fifa_code`),
    KEY `idx_national_team_name_zh` (`name_zh`),
    CONSTRAINT `chk_national_team_active` CHECK (`is_active` IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------------
-- 3. 比赛场馆（B）
-- ---------------------------------------------------------------------------
CREATE TABLE `stadium` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(120) NOT NULL,
    `city`          VARCHAR(100) NOT NULL,
    `country_code`  CHAR(2) NOT NULL,
    `timezone_name` VARCHAR(64) NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `chk_stadium_country` CHECK (`country_code` IN ('CA', 'MX', 'US'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------------
-- 4. 赛事参赛关系（C）
-- ---------------------------------------------------------------------------
CREATE TABLE `tournament_team` (
    `tournament_id`     BIGINT UNSIGNED NOT NULL,
    `team_id`           BIGINT UNSIGNED NOT NULL,
    `group_code`        CHAR(1) NOT NULL,
    `final_result_code` VARCHAR(20) NULL,
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`tournament_id`, `team_id`),
    KEY `idx_tournament_team_team` (`team_id`, `tournament_id`),
    CONSTRAINT `fk_tournament_team_tournament` FOREIGN KEY (`tournament_id`)
        REFERENCES `tournament` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `fk_tournament_team_team` FOREIGN KEY (`team_id`)
        REFERENCES `national_team` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `chk_tournament_team_group` CHECK (`group_code` BETWEEN 'A' AND 'L'),
    CONSTRAINT `chk_tournament_team_result` CHECK (
        `final_result_code` IS NULL OR `final_result_code` IN
        ('GROUP', 'R32', 'R16', 'QF', 'FOURTH', 'THIRD', 'RUNNER_UP', 'CHAMPION')
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------------
-- 5. 比赛赛程与结果（B）
-- ---------------------------------------------------------------------------
CREATE TABLE `wc_match` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tournament_id`  BIGINT UNSIGNED NOT NULL,
    `match_no`       SMALLINT UNSIGNED NOT NULL,
    `home_team_id`   BIGINT UNSIGNED NOT NULL,
    `away_team_id`   BIGINT UNSIGNED NOT NULL,
    `stadium_id`     BIGINT UNSIGNED NOT NULL,
    `kickoff_utc`    DATETIME NOT NULL,
    `stage_code`     VARCHAR(10) NOT NULL,
    `group_code`     CHAR(1) NULL,
    `status`         VARCHAR(16) NOT NULL DEFAULT 'SCHEDULED',
    `home_score_90`  TINYINT UNSIGNED NULL,
    `away_score_90`  TINYINT UNSIGNED NULL,
    `home_score_120` TINYINT UNSIGNED NULL,
    `away_score_120` TINYINT UNSIGNED NULL,
    `home_penalties` TINYINT UNSIGNED NULL,
    `away_penalties` TINYINT UNSIGNED NULL,
    `winner_team_id` BIGINT UNSIGNED NULL,
    `summary`        VARCHAR(300) NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_wc_match_tournament_no` (`tournament_id`, `match_no`),
    KEY `idx_wc_match_tournament_kickoff` (`tournament_id`, `kickoff_utc`),
    KEY `idx_wc_match_home_team` (`home_team_id`),
    KEY `idx_wc_match_away_team` (`away_team_id`),
    KEY `idx_wc_match_stage` (`stage_code`),
    KEY `idx_wc_match_stadium` (`stadium_id`),
    KEY `idx_wc_match_winner` (`winner_team_id`),
    CONSTRAINT `fk_wc_match_tournament` FOREIGN KEY (`tournament_id`)
        REFERENCES `tournament` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `fk_wc_match_home_team` FOREIGN KEY (`home_team_id`)
        REFERENCES `national_team` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `fk_wc_match_away_team` FOREIGN KEY (`away_team_id`)
        REFERENCES `national_team` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `fk_wc_match_stadium` FOREIGN KEY (`stadium_id`)
        REFERENCES `stadium` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `fk_wc_match_winner` FOREIGN KEY (`winner_team_id`)
        REFERENCES `national_team` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `chk_wc_match_no` CHECK (`match_no` BETWEEN 1 AND 104),
    CONSTRAINT `chk_wc_match_teams_different` CHECK (`home_team_id` <> `away_team_id`),
    CONSTRAINT `chk_wc_match_stage` CHECK (
        `stage_code` IN ('GROUP', 'R32', 'R16', 'QF', 'SF', 'THIRD', 'FINAL')
    ),
    CONSTRAINT `chk_wc_match_status` CHECK (
        `status` IN ('SCHEDULED', 'IN_PROGRESS', 'FINISHED', 'POSTPONED', 'CANCELLED')
    ),
    CONSTRAINT `chk_wc_match_group` CHECK (
        (`stage_code` = 'GROUP' AND (`group_code` IS NULL OR `group_code` BETWEEN 'A' AND 'L'))
        OR (`stage_code` <> 'GROUP' AND `group_code` IS NULL)
    ),
    CONSTRAINT `chk_wc_match_score_90_pair` CHECK (
        (`home_score_90` IS NULL AND `away_score_90` IS NULL)
        OR (`home_score_90` IS NOT NULL AND `away_score_90` IS NOT NULL)
    ),
    CONSTRAINT `chk_wc_match_score_120_pair` CHECK (
        (`home_score_120` IS NULL AND `away_score_120` IS NULL)
        OR (`home_score_120` IS NOT NULL AND `away_score_120` IS NOT NULL)
    ),
    CONSTRAINT `chk_wc_match_score_120_requires_90` CHECK (
        (`home_score_120` IS NULL OR `home_score_90` IS NOT NULL)
        AND (`away_score_120` IS NULL OR `away_score_90` IS NOT NULL)
    ),
    CONSTRAINT `chk_wc_match_score_120_ge_90` CHECK (
        (`home_score_120` IS NULL OR `home_score_90` IS NULL OR `home_score_120` >= `home_score_90`)
        AND (`away_score_120` IS NULL OR `away_score_90` IS NULL OR `away_score_120` >= `away_score_90`)
    ),
    CONSTRAINT `chk_wc_match_penalties_pair` CHECK (
        (`home_penalties` IS NULL AND `away_penalties` IS NULL)
        OR (`home_penalties` IS NOT NULL AND `away_penalties` IS NOT NULL)
    ),
    CONSTRAINT `chk_wc_match_penalties_decided` CHECK (
        `home_penalties` IS NULL OR `home_penalties` <> `away_penalties`
    ),
    CONSTRAINT `chk_wc_match_penalties_winner` CHECK (
        `home_penalties` IS NULL
        OR (`home_penalties` > `away_penalties` AND `winner_team_id` = `home_team_id`)
        OR (`away_penalties` > `home_penalties` AND `winner_team_id` = `away_team_id`)
    ),
    CONSTRAINT `chk_wc_match_winner_participant` CHECK (
        `winner_team_id` IS NULL OR `winner_team_id` IN (`home_team_id`, `away_team_id`)
    ),
    CONSTRAINT `chk_wc_match_finished_scores` CHECK (
        `status` <> 'FINISHED' OR (`home_score_90` IS NOT NULL AND `away_score_90` IS NOT NULL)
    ),
    CONSTRAINT `chk_wc_match_unplayed_no_result` CHECK (
        `status` NOT IN ('SCHEDULED', 'POSTPONED', 'CANCELLED')
        OR (`home_score_90` IS NULL AND `away_score_90` IS NULL
            AND `home_score_120` IS NULL AND `away_score_120` IS NULL
            AND `home_penalties` IS NULL AND `away_penalties` IS NULL
            AND `winner_team_id` IS NULL)
    ),
    CONSTRAINT `chk_wc_match_knockout_winner` CHECK (
        `status` <> 'FINISHED' OR `stage_code` = 'GROUP' OR `winner_team_id` IS NOT NULL
    ),
    CONSTRAINT `chk_wc_match_group_no_winner` CHECK (
        `stage_code` <> 'GROUP'
        OR (`winner_team_id` IS NULL AND `home_penalties` IS NULL AND `away_penalties` IS NULL)
    ),
    CONSTRAINT `chk_wc_match_winner_matches_score` CHECK (
        `status` <> 'FINISHED' OR `stage_code` = 'GROUP' OR `home_penalties` IS NOT NULL
        OR (COALESCE(`home_score_120`, `home_score_90`) > COALESCE(`away_score_120`, `away_score_90`)
            AND `winner_team_id` = `home_team_id`)
        OR (COALESCE(`away_score_120`, `away_score_90`) > COALESCE(`home_score_120`, `home_score_90`)
            AND `winner_team_id` = `away_team_id`)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------------
-- 6. 精选比赛故事（B）
-- ---------------------------------------------------------------------------
CREATE TABLE `match_story` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `match_id`     BIGINT UNSIGNED NOT NULL,
    `title`        VARCHAR(150) NOT NULL,
    `summary`      VARCHAR(500) NOT NULL,
    `body`         TEXT NOT NULL,
    `cover_path`   VARCHAR(255) NULL,
    `source_url`   VARCHAR(600) NULL,
    `is_published` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_match_story_match` (`match_id`),
    CONSTRAINT `fk_match_story_match` FOREIGN KEY (`match_id`)
        REFERENCES `wc_match` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `chk_match_story_published` CHECK (`is_published` IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------------
-- 7. 标签字典（B）
-- ---------------------------------------------------------------------------
CREATE TABLE `match_tag` (
    `id`   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(40) NOT NULL,
    `name` VARCHAR(40) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_match_tag_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------------
-- 8. 故事与标签关联（B）
-- ---------------------------------------------------------------------------
CREATE TABLE `match_story_tag` (
    `story_id` BIGINT UNSIGNED NOT NULL,
    `tag_id`   BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`story_id`, `tag_id`),
    KEY `idx_match_story_tag_tag` (`tag_id`, `story_id`),
    CONSTRAINT `fk_match_story_tag_story` FOREIGN KEY (`story_id`)
        REFERENCES `match_story` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
    CONSTRAINT `fk_match_story_tag_tag` FOREIGN KEY (`tag_id`)
        REFERENCES `match_tag` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------------
-- 9. 精选比赛事件（B）
-- ---------------------------------------------------------------------------
CREATE TABLE `match_event` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `match_id`     BIGINT UNSIGNED NOT NULL,
    `team_id`      BIGINT UNSIGNED NULL,
    `player_name`  VARCHAR(100) NULL,
    `minute`       SMALLINT UNSIGNED NULL,
    `added_minute` SMALLINT UNSIGNED NULL,
    `period_code`  VARCHAR(12) NOT NULL,
    `event_type`   VARCHAR(20) NOT NULL,
    `description`  VARCHAR(500) NULL,
    `sort_order`   INT NOT NULL DEFAULT 0,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_match_event_match_sort` (`match_id`, `sort_order`),
    KEY `idx_match_event_team` (`team_id`),
    CONSTRAINT `fk_match_event_match` FOREIGN KEY (`match_id`)
        REFERENCES `wc_match` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `fk_match_event_team` FOREIGN KEY (`team_id`)
        REFERENCES `national_team` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `chk_match_event_period` CHECK (
        `period_code` IN ('H1', 'H2', 'ET1', 'ET2', 'SHOOTOUT')
    ),
    CONSTRAINT `chk_match_event_type` CHECK (
        `event_type` IN ('GOAL', 'OWN_GOAL', 'YELLOW', 'RED', 'PENALTY_GOAL', 'PENALTY_MISS', 'OTHER')
    ),
    CONSTRAINT `chk_match_event_minute` CHECK (
        `period_code` = 'SHOOTOUT' OR `minute` IS NOT NULL
    ),
    CONSTRAINT `chk_match_event_added_minute` CHECK (
        `added_minute` IS NULL OR `minute` IS NOT NULL
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------------
-- 10. 课程团队（A）
-- ---------------------------------------------------------------------------
CREATE TABLE `project_team` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `logo_path`   VARCHAR(255) NULL,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------------
-- 11. 团队成员（A）
-- ---------------------------------------------------------------------------
CREATE TABLE `project_member` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `project_team_id` BIGINT UNSIGNED NOT NULL,
    `display_name`    VARCHAR(80) NOT NULL,
    `role_title`      VARCHAR(100) NOT NULL,
    `bio`             TEXT NULL,
    `sort_order`      INT NOT NULL DEFAULT 0,
    `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_project_member_team` (`project_team_id`),
    CONSTRAINT `fk_project_member_team` FOREIGN KEY (`project_team_id`)
        REFERENCES `project_team` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------------
-- 12. 团队/个人作业文件（A）
-- ---------------------------------------------------------------------------
CREATE TABLE `project_document` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `project_team_id` BIGINT UNSIGNED NOT NULL,
    `member_id`       BIGINT UNSIGNED NULL,
    `document_type`   VARCHAR(10) NOT NULL,
    `title`           VARCHAR(160) NOT NULL,
    `relative_path`   VARCHAR(255) NOT NULL,
    `original_name`   VARCHAR(255) NOT NULL,
    `file_size`       BIGINT UNSIGNED NOT NULL,
    `uploaded_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_project_document_team` (`project_team_id`),
    KEY `idx_project_document_member` (`member_id`),
    CONSTRAINT `fk_project_document_team` FOREIGN KEY (`project_team_id`)
        REFERENCES `project_team` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `fk_project_document_member` FOREIGN KEY (`member_id`)
        REFERENCES `project_member` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `chk_project_document_type` CHECK (`document_type` IN ('TEAM', 'PERSONAL')),
    CONSTRAINT `chk_project_document_member` CHECK (
        (`document_type` = 'PERSONAL' AND `member_id` IS NOT NULL)
        OR (`document_type` = 'TEAM' AND `member_id` IS NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------------
-- 13. 管理员账号（D）
-- ---------------------------------------------------------------------------
CREATE TABLE `admin_user` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username`      VARCHAR(60) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `auth_key`      VARCHAR(100) NOT NULL,
    `status`        TINYINT(1) NOT NULL DEFAULT 1,
    `last_login_at` DATETIME NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_admin_user_username` (`username`),
    CONSTRAINT `chk_admin_user_status` CHECK (`status` IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------------
-- 14. 游客留言（D）
-- ---------------------------------------------------------------------------
CREATE TABLE `guestbook_message` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nickname`         VARCHAR(50) NOT NULL,
    `content`          TEXT NOT NULL,
    `related_match_id` BIGINT UNSIGNED NULL,
    `status`           VARCHAR(12) NOT NULL DEFAULT 'PENDING',
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_guestbook_message_match` (`related_match_id`),
    KEY `idx_guestbook_message_status` (`status`, `created_at`),
    CONSTRAINT `fk_guestbook_message_match` FOREIGN KEY (`related_match_id`)
        REFERENCES `wc_match` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `chk_guestbook_message_status` CHECK (
        `status` IN ('PENDING', 'APPROVED', 'REJECTED')
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------------
-- 15. 管理员回复（D）
-- ---------------------------------------------------------------------------
CREATE TABLE `guestbook_reply` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `message_id`    BIGINT UNSIGNED NOT NULL,
    `admin_user_id` BIGINT UNSIGNED NOT NULL,
    `content`       TEXT NOT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_guestbook_reply_message` (`message_id`),
    KEY `idx_guestbook_reply_admin` (`admin_user_id`),
    CONSTRAINT `fk_guestbook_reply_message` FOREIGN KEY (`message_id`)
        REFERENCES `guestbook_message` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `fk_guestbook_reply_admin` FOREIGN KEY (`admin_user_id`)
        REFERENCES `admin_user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

SET FOREIGN_KEY_CHECKS = 1;
