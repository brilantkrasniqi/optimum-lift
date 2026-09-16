<?php
/**
 * Constants PHPStan cannot infer, because WordPress defines them at runtime.
 */

declare(strict_types=1);

define('OPTIMUM_LIFT_VERSION', '0.0.0');
define('OPTIMUM_LIFT_DIR', __DIR__ . '/themes/optimum-lift');
define('OPTIMUM_LIFT_URI', 'http://localhost:8080/wp-content/themes/optimum-lift');

define('OptimumLift\Plans\VERSION', '0.0.0');
define('OptimumLift\Plans\FILE', __DIR__ . '/plugins/optimum-lift-plans/optimum-lift-plans.php');
define('OptimumLift\Plans\DIR', __DIR__ . '/plugins/optimum-lift-plans');
