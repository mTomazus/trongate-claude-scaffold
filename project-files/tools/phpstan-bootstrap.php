<?php
/**
 * Constants Trongate defines at runtime, declared so static analysis sees them.
 *
 * The values here are placeholders and are never used by the application.
 * Anything environment-specific is also listed under dynamicConstantNames in
 * phpstan.neon, so PHPStan treats its value as unknown rather than as the
 * literal below.
 */

define('BASE_URL', 'http://localhost/');
define('ENV', 'dev');
define('DEFAULT_MODULE', 'welcome');
define('DEFAULT_METHOD', 'index');
define('MODULE_ASSETS_TRIGGER', '_module');
define('ERROR_404', 'templates/error_404');
define('WEBSITE_NAME', 'Site');
define('APPPATH', '/');
define('REQUEST_TYPE', 'GET');
