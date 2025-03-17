-- File: setup/sql/multi_tenant.sql

-- Organizations table (tenants)
CREATE TABLE IF NOT EXISTS `organizations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `subdomain` varchar(50) NOT NULL,
  `plan` varchar(20) NOT NULL DEFAULT 'standard',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `settings` json DEFAULT NULL,
  `max_users` int(11) DEFAULT 5,
  `api_key` varchar(64) DEFAULT NULL,
  `custom_domain` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subdomain` (`subdomain`),
  KEY `owner_id` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Organization billing table
CREATE TABLE IF NOT EXISTS `organization_billing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `plan` varchar(20) NOT NULL DEFAULT 'standard',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `next_billing_date` date DEFAULT NULL,
  `last_billing_date` date DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_details` text DEFAULT NULL,
  `subscription_id` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `organization_id` (`organization_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Organization usage statistics
CREATE TABLE IF NOT EXISTS `organization_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `year_month` char(7) NOT NULL,
  `transactions_count` int(11) NOT NULL DEFAULT 0,
  `messages_sent` int(11) NOT NULL DEFAULT 0,
  `sms_count` int(11) NOT NULL DEFAULT 0,
  `whatsapp_count` int(11) NOT NULL DEFAULT 0,
  `recovered_amount` decimal(10,2) NOT NULL DEFAULT 0,
  `recovered_count` int(11) NOT NULL DEFAULT 0,
  `api_calls` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `org_month` (`organization_id`, `year_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update users table to include organization_id
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `organization_id` int(11) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `organization_role` varchar(20) DEFAULT 'member';

-- Create index for users organization lookup
CREATE INDEX IF NOT EXISTS `idx_users_organization` ON `users` (`organization_id`);

-- Organization settings table
CREATE TABLE IF NOT EXISTS `organization_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(20) NOT NULL DEFAULT 'string',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `org_key` (`organization_id`, `setting_key`),
  KEY `organization_id` (`organization_id`),
  KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Branding settings table
CREATE TABLE IF NOT EXISTS `organization_branding` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `primary_color` char(7) DEFAULT '#2563eb',
  `secondary_color` char(7) DEFAULT '#4f46e5',
  `accent_color` char(7) DEFAULT '#16a34a',
  `email_header` text DEFAULT NULL,
  `email_footer` text DEFAULT NULL,
  `email_template` text DEFAULT NULL,
  `favicon_url` varchar(255) DEFAULT NULL,
  `support_email` varchar(100) DEFAULT NULL,
  `support_phone` varchar(20) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `organization_id` (`organization_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- API access tokens
CREATE TABLE IF NOT EXISTS `api_access_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `token` varchar(64) NOT NULL,
  `scopes` json DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `organization_id` (`organization_id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Webhooks
CREATE TABLE IF NOT EXISTS `organization_webhooks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `events` json NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `secret` varchar(64) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `organization_id` (`organization_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Webhook events
CREATE TABLE IF NOT EXISTS `webhook_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webhook_id` int(11) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `payload` json NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `response_code` int(11) DEFAULT NULL,
  `response_body` text DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `last_attempt` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `webhook_id` (`webhook_id`),
  KEY `event_type` (`event_type`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Organization audit log
CREATE TABLE IF NOT EXISTS `organization_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `organization_id` (`organization_id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `entity_type` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add organization_id to existing tables
ALTER TABLE `customers` 
ADD COLUMN IF NOT EXISTS `organization_id` int(11) DEFAULT NULL;

ALTER TABLE `failed_transactions` 
ADD COLUMN IF NOT EXISTS `organization_id` int(11) DEFAULT NULL;

ALTER TABLE `payment_recovery` 
ADD COLUMN IF NOT EXISTS `organization_id` int(11) DEFAULT NULL;

ALTER TABLE `communication_attempts` 
ADD COLUMN IF NOT EXISTS `organization_id` int(11) DEFAULT NULL;

-- Add indexes for organization_id on all tables
CREATE INDEX IF NOT EXISTS `idx_customers_organization_id` ON `customers` (`organization_id`);
CREATE INDEX IF NOT EXISTS `idx_failed_transactions_organization_id` ON `failed_transactions` (`organization_id`);
CREATE INDEX IF NOT EXISTS `idx_payment_recovery_organization_id` ON `payment_recovery` (`organization_id`);
CREATE INDEX IF NOT EXISTS `idx_communication_attempts_organization_id` ON `communication_attempts` (`organization_id`);