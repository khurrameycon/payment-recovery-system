
-- Create customer_segmentation table if it doesn't exist
CREATE TABLE IF NOT EXISTS `customer_segmentation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `value_segment` varchar(50) NOT NULL DEFAULT 'unknown',
  `loyalty_segment` varchar(50) NOT NULL DEFAULT 'new',
  `behavior_segment` varchar(50) NOT NULL DEFAULT 'unknown',
  `combined_segment` varchar(50) NOT NULL DEFAULT 'standard',
  `metrics` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_id` (`customer_id`),
  KEY `combined_segment` (`combined_segment`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add holidays table for better timezone intelligence
CREATE TABLE IF NOT EXISTS `holidays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `holiday_date` date NOT NULL,
  `name` varchar(100) NOT NULL,
  `country` varchar(2) NOT NULL DEFAULT 'US',
  PRIMARY KEY (`id`),
  KEY `holiday_date` (`holiday_date`),
  KEY `country` (`country`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add segment_strategies table for customizable communication rules
CREATE TABLE IF NOT EXISTS `segment_strategies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `segment` varchar(50) NOT NULL,
  `primary_channel` varchar(50) NOT NULL DEFAULT 'email',
  `fallback_channel` varchar(50) DEFAULT NULL,
  `max_attempts` int(11) NOT NULL DEFAULT 3,
  `min_hours_between` int(11) NOT NULL DEFAULT 24,
  `preferred_time` varchar(50) DEFAULT NULL,
  `template_set` varchar(50) DEFAULT 'standard',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `segment` (`segment`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default segment strategies
INSERT INTO `segment_strategies` 
(`segment`, `primary_channel`, `fallback_channel`, `max_attempts`, `min_hours_between`, `preferred_time`, `template_set`, `active`)
VALUES
('vip', 'email', 'sms', 5, 24, 'business_hours', 'premium', 1),
('high_priority', 'email', 'sms', 4, 24, 'business_hours', 'personalized', 1),
('standard', 'email', NULL, 3, 24, 'business_hours', 'standard', 1),
('nurture', 'email', NULL, 4, 48, 'business_hours', 'educational', 1),
('low_priority', 'email', NULL, 2, 48, 'business_hours', 'basic', 1);