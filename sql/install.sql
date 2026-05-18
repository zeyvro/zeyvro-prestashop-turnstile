CREATE TABLE IF NOT EXISTS `PREFIX_zeyvro_turnstile_log` (
    `id_log`      INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip`          VARCHAR(45)  NOT NULL DEFAULT '',
    `user_agent`  VARCHAR(255) NOT NULL DEFAULT '',
    `date_add`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `success`     TINYINT(1)   NOT NULL DEFAULT 0,
    `score`       DECIMAL(4,2) NULL,
    `error_codes` TEXT         NULL,
    PRIMARY KEY (`id_log`),
    KEY `idx_date` (`date_add`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
