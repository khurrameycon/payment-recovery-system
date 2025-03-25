-- Main tables
CREATE TABLE IF NOT EXISTS `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `timezone` varchar(50) DEFAULT 'UTC',
  `country` varchar(2) DEFAULT 'US',
  `segment` varchar(20) DEFAULT 'standard',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `failed_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `transaction_reference` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `failure_reason` varchar(255) NOT NULL,
  `transaction_date` datetime NOT NULL,
  `recovery_status` varchar(20) NOT NULL DEFAULT 'pending',
  `organization_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `organization_id` (`organization_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `payment_recovery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `recovery_link` varchar(255) NOT NULL,
  `recovery_token` varchar(100) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expiry_date` datetime NOT NULL,
  `recovery_date` datetime DEFAULT NULL,
  `recovered_amount` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_id` (`transaction_id`),
  UNIQUE KEY `recovery_token` (`recovery_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `communication_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `channel` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `scheduled_at` datetime NOT NULL,
  `sent_at` datetime DEFAULT NULL,
  `opened_at` datetime DEFAULT NULL,
  `clicked_at` datetime DEFAULT NULL,
  `message_template` varchar(50) DEFAULT NULL,
  `tracking_id` varchar(50) DEFAULT NULL,
  `external_id` varchar(100) DEFAULT NULL,
  `organization_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_id` (`transaction_id`),
  KEY `tracking_id` (`tracking_id`),
  KEY `organization_id` (`organization_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Organization and user tables
CREATE TABLE IF NOT EXISTS `organizations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `subdomain` varchar(50) NOT NULL,
  `custom_domain` varchar(100) DEFAULT NULL,
  `plan` varchar(20) NOT NULL DEFAULT 'standard',
  `owner_id` int(11) DEFAULT NULL,
  `api_key` varchar(64) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subdomain` (`subdomain`),
  UNIQUE KEY `custom_domain` (`custom_domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'user',
  `organization_id` int(11) DEFAULT NULL,
  `organization_role` varchar(20) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `organization_id` (`organization_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Segmentation and optimization tables
CREATE TABLE IF NOT EXISTS `customer_segmentation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `value_segment` varchar(20) DEFAULT NULL,
  `loyalty_segment` varchar(20) DEFAULT NULL,
  `behavior_segment` varchar(20) DEFAULT NULL,
  `combined_segment` varchar(20) DEFAULT NULL,
  `metrics` json DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `segment_strategies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `segment` varchar(20) NOT NULL,
  `primary_channel` varchar(20) NOT NULL DEFAULT 'email',
  `fallback_channel` varchar(20) DEFAULT NULL,
  `max_attempts` int(11) NOT NULL DEFAULT 3,
  `min_hours_between` int(11) NOT NULL DEFAULT 24,
  `preferred_time` varchar(20) DEFAULT 'business_hours',
  `template_set` varchar(20) DEFAULT 'standard',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `organization_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `segment_org` (`segment`,`organization_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `holidays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `country` varchar(2) NOT NULL,
  `holiday_date` date NOT NULL,
  `name` varchar(100) NOT NULL,
  `organization_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `country_date_org` (`country`,`holiday_date`,`organization_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Settings and configuration tables
CREATE TABLE IF NOT EXISTS `organization_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(20) DEFAULT 'string',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `org_key` (`organization_id`,`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `organization_branding` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `favicon_url` varchar(255) DEFAULT NULL,
  `primary_color` varchar(20) DEFAULT '#2563eb',
  `secondary_color` varchar(20) DEFAULT '#4f46e5',
  `accent_color` varchar(20) DEFAULT '#16a34a',
  `email_header` text DEFAULT NULL,
  `email_footer` text DEFAULT NULL,
  `support_email` varchar(255) DEFAULT NULL,
  `support_phone` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `organization_id` (`organization_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- API and integration tables
CREATE TABLE IF NOT EXISTS `api_access_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `token` varchar(64) NOT NULL,
  `scopes` json DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `organization_id` (`organization_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Analytics tables
CREATE TABLE IF NOT EXISTS `recovery_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `recovery_date` datetime NOT NULL,
  `channel` varchar(20) DEFAULT NULL,
  `organization_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_id` (`transaction_id`),
  KEY `organization_id` (`organization_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `organization_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `year_month` varchar(7) NOT NULL,
  `transactions_count` int(11) NOT NULL DEFAULT 0,
  `messages_sent` int(11) NOT NULL DEFAULT 0,
  `sms_count` int(11) NOT NULL DEFAULT 0,
  `whatsapp_count` int(11) NOT NULL DEFAULT 0,
  `recovered_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `recovered_count` int(11) NOT NULL DEFAULT 0,
  `api_calls` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `org_month` (`organization_id`,`year_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- System tables
CREATE TABLE IF NOT EXISTS `tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(255) NOT NULL,
  `token_type` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `data` text DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `consumed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `token_type` (`token_type`),
  KEY `user_id` (`user_id`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `system_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_name` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL,
  `run_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `duration` float DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `details` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_name` (`task_name`),
  KEY `run_date` (`run_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default segment strategies
INSERT INTO `segment_strategies` 
(`segment`, `primary_channel`, `fallback_channel`, `max_attempts`, `min_hours_between`, `preferred_time`, `template_set`, `active`) 
VALUES
('vip', 'email', 'sms', 5, 24, 'business_hours', 'premium', 1),
('high_priority', 'email', 'sms', 4, 24, 'business_hours', 'premium', 1),
('standard', 'email', NULL, 3, 24, 'business_hours', 'standard', 1),
('nurture', 'email', NULL, 4, 48, 'business_hours', 'standard', 1),
('low_priority', 'email', NULL, 2, 48, 'morning', 'standard', 1);