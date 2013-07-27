=>[tlx_accounts]
`username` CHAR(32) NOT NULL PRIMARY KEY,
`email` TEXT,
`site_url` TEXT,
`domain` TEXT,
`banner_url` TEXT,
`banner_url_local` TEXT,
`banner_height` INT,
`banner_width` INT,
`title` TEXT,
`description` TEXT,
`keywords` TEXT,
`date_added` DATETIME NOT NULL,
`date_activated` DATETIME,
`date_scanned` DATETIME,
`password` CHAR(40),
`return_percent` DECIMAL(4,2),
`status` ENUM('unconfirmed','pending','active'),
`locked` TINYINT NOT NULL,
`disabled` TINYINT NOT NULL,
`edited` TINYINT NOT NULL,
`category_id` INT,
`last_rank` INT,
`last_category_rank` INT,
`ratings` INT,
`ratings_total` INT,
`inactive` INT,
`edit_data` BLOB,
`admin_comments` TEXT,
FULLTEXT(`title`,`description`,`keywords`),
INDEX(`domain`(200))

=>[tlx_account_hourly_stats]
`username` CHAR(32) NOT NULL PRIMARY KEY,
`raw_in_0` INT UNSIGNED NOT NULL,
`unique_in_0` INT UNSIGNED NOT NULL,
`raw_out_0` INT UNSIGNED NOT NULL,
`unique_out_0` INT UNSIGNED NOT NULL,
`clicks_0` INT UNSIGNED NOT NULL,
`raw_in_1` INT UNSIGNED NOT NULL,
`unique_in_1` INT UNSIGNED NOT NULL,
`raw_out_1` INT UNSIGNED NOT NULL,
`unique_out_1` INT UNSIGNED NOT NULL,
`clicks_1` INT UNSIGNED NOT NULL,
`raw_in_2` INT UNSIGNED NOT NULL,
`unique_in_2` INT UNSIGNED NOT NULL,
`raw_out_2` INT UNSIGNED NOT NULL,
`unique_out_2` INT UNSIGNED NOT NULL,
`clicks_2` INT UNSIGNED NOT NULL,
`raw_in_3` INT UNSIGNED NOT NULL,
`unique_in_3` INT UNSIGNED NOT NULL,
`raw_out_3` INT UNSIGNED NOT NULL,
`unique_out_3` INT UNSIGNED NOT NULL,
`clicks_3` INT UNSIGNED NOT NULL,
`raw_in_4` INT UNSIGNED NOT NULL,
`unique_in_4` INT UNSIGNED NOT NULL,
`raw_out_4` INT UNSIGNED NOT NULL,
`unique_out_4` INT UNSIGNED NOT NULL,
`clicks_4` INT UNSIGNED NOT NULL,
`raw_in_5` INT UNSIGNED NOT NULL,
`unique_in_5` INT UNSIGNED NOT NULL,
`raw_out_5` INT UNSIGNED NOT NULL,
`unique_out_5` INT UNSIGNED NOT NULL,
`clicks_5` INT UNSIGNED NOT NULL,
`raw_in_6` INT UNSIGNED NOT NULL,
`unique_in_6` INT UNSIGNED NOT NULL,
`raw_out_6` INT UNSIGNED NOT NULL,
`unique_out_6` INT UNSIGNED NOT NULL,
`clicks_6` INT UNSIGNED NOT NULL,
`raw_in_7` INT UNSIGNED NOT NULL,
`unique_in_7` INT UNSIGNED NOT NULL,
`raw_out_7` INT UNSIGNED NOT NULL,
`unique_out_7` INT UNSIGNED NOT NULL,
`clicks_7` INT UNSIGNED NOT NULL,
`raw_in_8` INT UNSIGNED NOT NULL,
`unique_in_8` INT UNSIGNED NOT NULL,
`raw_out_8` INT UNSIGNED NOT NULL,
`unique_out_8` INT UNSIGNED NOT NULL,
`clicks_8` INT UNSIGNED NOT NULL,
`raw_in_9` INT UNSIGNED NOT NULL,
`unique_in_9` INT UNSIGNED NOT NULL,
`raw_out_9` INT UNSIGNED NOT NULL,
`unique_out_9` INT UNSIGNED NOT NULL,
`clicks_9` INT UNSIGNED NOT NULL,
`raw_in_10` INT UNSIGNED NOT NULL,
`unique_in_10` INT UNSIGNED NOT NULL,
`raw_out_10` INT UNSIGNED NOT NULL,
`unique_out_10` INT UNSIGNED NOT NULL,
`clicks_10` INT UNSIGNED NOT NULL,
`raw_in_11` INT UNSIGNED NOT NULL,
`unique_in_11` INT UNSIGNED NOT NULL,
`raw_out_11` INT UNSIGNED NOT NULL,
`unique_out_11` INT UNSIGNED NOT NULL,
`clicks_11` INT UNSIGNED NOT NULL,
`raw_in_12` INT UNSIGNED NOT NULL,
`unique_in_12` INT UNSIGNED NOT NULL,
`raw_out_12` INT UNSIGNED NOT NULL,
`unique_out_12` INT UNSIGNED NOT NULL,
`clicks_12` INT UNSIGNED NOT NULL,
`raw_in_13` INT UNSIGNED NOT NULL,
`unique_in_13` INT UNSIGNED NOT NULL,
`raw_out_13` INT UNSIGNED NOT NULL,
`unique_out_13` INT UNSIGNED NOT NULL,
`clicks_13` INT UNSIGNED NOT NULL,
`raw_in_14` INT UNSIGNED NOT NULL,
`unique_in_14` INT UNSIGNED NOT NULL,
`raw_out_14` INT UNSIGNED NOT NULL,
`unique_out_14` INT UNSIGNED NOT NULL,
`clicks_14` INT UNSIGNED NOT NULL,
`raw_in_15` INT UNSIGNED NOT NULL,
`unique_in_15` INT UNSIGNED NOT NULL,
`raw_out_15` INT UNSIGNED NOT NULL,
`unique_out_15` INT UNSIGNED NOT NULL,
`clicks_15` INT UNSIGNED NOT NULL,
`raw_in_16` INT UNSIGNED NOT NULL,
`unique_in_16` INT UNSIGNED NOT NULL,
`raw_out_16` INT UNSIGNED NOT NULL,
`unique_out_16` INT UNSIGNED NOT NULL,
`clicks_16` INT UNSIGNED NOT NULL,
`raw_in_17` INT UNSIGNED NOT NULL,
`unique_in_17` INT UNSIGNED NOT NULL,
`raw_out_17` INT UNSIGNED NOT NULL,
`unique_out_17` INT UNSIGNED NOT NULL,
`clicks_17` INT UNSIGNED NOT NULL,
`raw_in_18` INT UNSIGNED NOT NULL,
`unique_in_18` INT UNSIGNED NOT NULL,
`raw_out_18` INT UNSIGNED NOT NULL,
`unique_out_18` INT UNSIGNED NOT NULL,
`clicks_18` INT UNSIGNED NOT NULL,
`raw_in_19` INT UNSIGNED NOT NULL,
`unique_in_19` INT UNSIGNED NOT NULL,
`raw_out_19` INT UNSIGNED NOT NULL,
`unique_out_19` INT UNSIGNED NOT NULL,
`clicks_19` INT UNSIGNED NOT NULL,
`raw_in_20` INT UNSIGNED NOT NULL,
`unique_in_20` INT UNSIGNED NOT NULL,
`raw_out_20` INT UNSIGNED NOT NULL,
`unique_out_20` INT UNSIGNED NOT NULL,
`clicks_20` INT UNSIGNED NOT NULL,
`raw_in_21` INT UNSIGNED NOT NULL,
`unique_in_21` INT UNSIGNED NOT NULL,
`raw_out_21` INT UNSIGNED NOT NULL,
`unique_out_21` INT UNSIGNED NOT NULL,
`clicks_21` INT UNSIGNED NOT NULL,
`raw_in_22` INT UNSIGNED NOT NULL,
`unique_in_22` INT UNSIGNED NOT NULL,
`raw_out_22` INT UNSIGNED NOT NULL,
`unique_out_22` INT UNSIGNED NOT NULL,
`clicks_22` INT UNSIGNED NOT NULL,
`raw_in_23` INT UNSIGNED NOT NULL,
`unique_in_23` INT UNSIGNED NOT NULL,
`raw_out_23` INT UNSIGNED NOT NULL,
`unique_out_23` INT UNSIGNED NOT NULL,
`clicks_23` INT UNSIGNED NOT NULL,
`raw_in_total` INT UNSIGNED NOT NULL,
`unique_in_total` INT UNSIGNED NOT NULL,
`raw_out_total` INT UNSIGNED NOT NULL,
`unique_out_total` INT UNSIGNED NOT NULL,
`clicks_total` INT UNSIGNED NOT NULL,
`proxy_percent` FLOAT NOT NULL,
`robot_percent` FLOAT NOT NULL

=>[tlx_account_daily_stats]
`username` CHAR(32) NOT NULL,
`date_stats` DATE NOT NULL,
`raw_in` INT UNSIGNED NOT NULL,
`unique_in` INT UNSIGNED NOT NULL,
`raw_out` INT UNSIGNED NOT NULL,
`unique_out` INT UNSIGNED NOT NULL,
`clicks` INT UNSIGNED NOT NULL,
INDEX(`username`,`date_stats`),
INDEX(`date_stats`)

=>[tlx_account_country_stats]
`username` CHAR(32) NOT NULL,
`country` CHAR(2) NOT NULL,
`raw_in` INT UNSIGNED NOT NULL,
`unique_in` INT UNSIGNED NOT NULL,
`raw_out` INT UNSIGNED NOT NULL,
`unique_out` INT UNSIGNED NOT NULL,
`clicks` INT UNSIGNED NOT NULL,
PRIMARY KEY(`username`,`country`)

=>[tlx_account_referrer_stats]
`username` CHAR(32) NOT NULL,
`referrer` TEXT NOT NULL,
`raw_in` INT UNSIGNED NOT NULL,
PRIMARY KEY(`username`,`referrer`(150))

=>[tlx_account_field_defs]
`field_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`name` VARCHAR(32),
`label` TEXT,
`type` VARCHAR(64),
`tag_attributes` TEXT,
`options` TEXT,
`validation` INT NOT NULL DEFAULT 0,
`validation_extras` TEXT,
`validation_message` TEXT,        
`on_create` TINYINT NOT NULL DEFAULT 0,
`required_create` TINYINT NOT NULL DEFAULT 0,
`on_edit` TINYINT NOT NULL DEFAULT 0,
`required_edit` TINYINT NOT NULL DEFAULT 0

=>[tlx_account_fields]
`username` CHAR(32) NOT NULL PRIMARY KEY

=>[tlx_account_confirms]
`username` CHAR(32) NOT NULL PRIMARY KEY,
`confirm_id` CHAR(32),
`date_sent` DATETIME,
INDEX(`confirm_id`)

=>[tlx_account_icons]
`username` CHAR(32) NOT NULL,
`icon_id` INT NOT NULL,
PRIMARY KEY(`username`,`icon_id`),
INDEX(`icon_id`)

=>[tlx_account_logins]
`username` CHAR(32) NOT NULL PRIMARY KEY,
`session` CHAR(40),
`session_start` INT,
INDEX(`session`)

=>[tlx_account_comments]
`comment_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`username` CHAR(32) NOT NULL,
`date_submitted` DATETIME,
`ip_address` CHAR(16),
`name` VARCHAR(255),
`email` VARCHAR(128),
`status` ENUM('pending','approved'),
`comment` TEXT,
FULLTEXT(`comment`)

=>[tlx_account_ranks]
`username` CHAR(32) NOT NULL PRIMARY KEY,
`rank` INT NOT NULL

=>[tlx_account_category_ranks]
`username` CHAR(32) NOT NULL PRIMARY KEY,
`category_rank` INT NOT NULL

=>[tlx_blacklist]
`blacklist_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`type` CHAR(32) NOT NULL,
`regex` TINYINT NOT NULL,
`value` TEXT,
`reason` TEXT,
INDEX(`type`),
INDEX(`value`(50))

=>[tlx_rejections]
`email_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`identifier` VARCHAR(128),
`plain` TEXT,
`compiled` TEXT

=>[tlx_captcha]
`session` VARCHAR(40) NOT NULL,
`code` VARCHAR(64) NOT NULL,
`time_stamp` INT NOT NULL,
INDEX(`session`)

=>[tlx_categories]
`category_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`name` TEXT,
`hidden` TINYINT NOT NULL,
`forward_url` TEXT,
`page_url` TEXT,
`banner_max_width` INT NOT NULL,
`banner_max_height` INT NOT NULL,
`banner_max_bytes` INT NOT NULL,
`banner_force_size` TINYINT NOT NULL,
`download_banners` TINYINT NOT NULL,
`host_banners` TINYINT NOT NULL,
`allow_redirect` TINYINT NOT NULL,
`title_min_length` INT NOT NULL,
`title_max_length` INT NOT NULL,
`desc_min_length` INT NOT NULL,
`desc_max_length` INT NOT NULL,
`recip_required` TINYINT NOT NULL

=>[tlx_administrators]
`username` CHAR(32) NOT NULL PRIMARY KEY,
`password` CHAR(40) NOT NULL,
`session` CHAR(40),
`session_start` INT,
`name` CHAR(80),
`email` CHAR(100),
`type` ENUM('administrator','editor') NOT NULL,
`date_login` DATETIME,
`date_last_login` DATETIME,
`login_ip` CHAR(18),
`last_login_ip` CHAR(18),
`notifications` INT,
`rights` INT,
INDEX(`email`),
INDEX(`name`)

=>[tlx_icons]
`icon_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`identifier` TEXT,
`icon_html` TEXT

=>[tlx_stored_values]
`name` VARCHAR(128) NOT NULL PRIMARY KEY,
`value` TEXT

=>[tlx_scanner_configs]
`config_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`identifier` VARCHAR(255),
`current_status` TEXT,
`status_updated` INT,
`pid` INT NOT NULL DEFAULT 0,
`date_last_run` DATETIME,
`configuration` TEXT

=>[tlx_scanner_results]
`config_id` INT NOT NULL,
`username` CHAR(32) NOT NULL,
`site_url` TEXT,
`http_status` VARCHAR(255),
`date_scanned` DATETIME NOT NULL,
`action` TEXT,
`message` TEXT,
INDEX(`config_id`),
INDEX(`username`),
INDEX(`site_url`(100)),
INDEX(`http_status`)

=>[tlx_scanner_history]
`history_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`config_id` INT NOT NULL,
`date_start` DATETIME,
`date_end` DATETIME,
`selected` INT,
`scanned` INT,
`exceptions` INT,
`disabled` INT,
`deleted` INT,
`blacklisted` INT,
INDEX(`config_id`),
INDEX(`date_start`)

=>[tlx_reports]
`report_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`username` CHAR(32) NOT NULL,
`report_ip` CHAR(16),
`date_reported` DATETIME NOT NULL,
`reason` TEXT

=>[tlx_pages]
`page_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`filename` TEXT,
`category_id` INT,
`build_order` INT NOT NULL,
`tags` TEXT,
`template` MEDIUMTEXT,
`compiled` MEDIUMTEXT,
INDEX(`build_order`),
FULLTEXT(`tags`)

=>[tlx_categories_build]
`category_id` INT NOT NULL PRIMARY KEY,
`name` TEXT,
`accounts` INT,
`filename` TEXT

=>[tlx_ip2country]
`ip_start` INT UNSIGNED NOT NULL,
`ip_end` INT UNSIGNED NOT NULL,
`country` VARCHAR(2) NOT NULL,
PRIMARY KEY(`ip_start`,`ip_end`)

=>[tlx_countries]
`country` CHAR(2) NOT NULL PRIMARY KEY,
`country_name` VARCHAR(100) NOT NULL

=>[tlx_country_stats]
`country` CHAR(2) NOT NULL PRIMARY KEY,
`raw_in` INT UNSIGNED NOT NULL,
`unique_in` INT UNSIGNED NOT NULL,
`raw_out` INT UNSIGNED NOT NULL,
`unique_out` INT UNSIGNED NOT NULL,
`clicks` INT UNSIGNED NOT NULL

=>[tlx_daily_stats]
`date_stats` DATE NOT NULL PRIMARY KEY,
`raw_in` INT UNSIGNED NOT NULL,
`unique_in` INT UNSIGNED NOT NULL,
`raw_out` INT UNSIGNED NOT NULL,
`unique_out` INT UNSIGNED NOT NULL,
`clicks` INT UNSIGNED NOT NULL

=>[tlx_bookmarker_stats]
`date_stats` DATE NOT NULL PRIMARY KEY,
`visits` INT UNSIGNED NOT NULL

=>[tlx_ip_log_in]
`username` CHAR(32) NOT NULL,
`ip_address` INT UNSIGNED NOT NULL,
`raw_in` INT UNSIGNED NOT NULL,
`proxy` TINYINT NOT NULL,
`robot` TINYINT NOT NULL,
`last_visit` DATETIME NOT NULL,
 PRIMARY KEY(`username`,`ip_address`)

=>[tlx_ip_log_out]
`username` CHAR(32) NOT NULL,
`ip_address` INT UNSIGNED NOT NULL,
`raw_out` INT UNSIGNED NOT NULL,
`last_visit` DATETIME NOT NULL,
 PRIMARY KEY(`username`,`ip_address`)

=>[tlx_ip_log_clicks]
`username` CHAR(32) NOT NULL,
`ip_address` INT UNSIGNED NOT NULL,
`url_hash` CHAR(40) NOT NULL,
`clicks` INT UNSIGNED NOT NULL,
`last_visit` DATETIME NOT NULL,
PRIMARY KEY(`username`,`ip_address`,`url_hash`)

=>[tlx_ip_log_ratings]
`username` CHAR(32) NOT NULL,
`ip_address` INT UNSIGNED NOT NULL,
`ratings` INT UNSIGNED NOT NULL,
`last_rating` DATETIME NOT NULL,
 PRIMARY KEY(`username`,`ip_address`)

=>[tlx_skim_ratio]
`sent_trades` INT UNSIGNED NOT NULL,
`sent_total` INT UNSIGNED NOT NULL