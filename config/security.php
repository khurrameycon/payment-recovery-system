<?php
// Security configuration for Payment Recovery System

// CSRF Protection
define('CSRF_PROTECTION_ENABLED', true);

// Recovery link protection
define('RECOVERY_SALT', 'ea9d7251d415f601543ed8dbb734900f');

// Session security
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', 3600); // 1 hour

// Password policies
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_NUMBER', true);
define('PASSWORD_REQUIRE_SPECIAL', true);

// Error reporting in production
define('PRODUCTION_MODE', false); // Set to true in production

// Rate limiting
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_MAX_REQUESTS', 100);
define('RATE_LIMIT_PERIOD', 60); // 1 minute