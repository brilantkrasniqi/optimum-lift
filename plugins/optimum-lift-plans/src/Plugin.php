<?php

/**
 * Wires every component to WordPress. Nothing else in the plugin calls
 * add_action() at file load.
 */

declare(strict_types=1);

namespace OptimumLift\Plans;

use OptimumLift\Plans\Access\AccessRepository;
use OptimumLift\Plans\Access\OrderAccess;
use OptimumLift\Plans\Access\ProductFields;
use OptimumLift\Plans\Cli\SeedCommand;
use OptimumLift\Plans\Content\ExerciseFields;
use OptimumLift\Plans\Content\PlanFields;
use OptimumLift\Plans\Content\PostTypes;
use OptimumLift\Plans\Download\Delivery;
use OptimumLift\Plans\Download\PdfRenderer;
use OptimumLift\Plans\Logging\RestController;
use OptimumLift\Plans\Logging\WorkoutLogRepository;
use OptimumLift\Plans\Plan\PlanRepository;
use OptimumLift\Plans\Portal\Portal;
use OptimumLift\Plans\Privacy\Privacy;

final class Plugin
{
    public static function boot(): void
    {
        load_plugin_textdomain('optimum-lift-plans', false, dirname(plugin_basename(FILE)) . '/languages');

        $missing = self::missingDependencies();

        if ($missing !== []) {
            add_action('admin_notices', static function () use ($missing): void {
                printf(
                    '<div class="notice notice-error"><p>%s</p></div>',
                    esc_html(sprintf(
                        /* translators: %s: comma-separated plugin names */
                        __('Optimum Lift Plans is inactive until these plugins are active: %s.', 'optimum-lift-plans'),
                        implode(', ', $missing)
                    ))
                );
            });

            return;
        }

        add_action('init', [Schema::class, 'maybeUpgrade'], 1);

        $plans     = new PlanRepository();
        $access    = new AccessRepository();
        $logs      = new WorkoutLogRepository();
        $renderer  = new PdfRenderer();

        (new PostTypes())->register();
        (new ExerciseFields())->register();
        (new PlanFields())->register();
        (new ProductFields())->register();
        (new OrderAccess($access))->register();
        (new Delivery($plans, $access, $renderer))->register();
        (new Portal($plans, $access, $logs))->register();
        (new RestController($plans, $access, $logs))->register();
        (new Privacy($logs, $access))->register();

        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('ol-plans', new SeedCommand());
        }
    }

    /**
     * @return list<string>
     */
    private static function missingDependencies(): array
    {
        $missing = [];

        if (!class_exists('WooCommerce')) {
            $missing[] = 'WooCommerce';
        }

        // Repeaters are an ACF Pro feature; the free plugin is not enough.
        if (!defined('ACF_PRO') || !function_exists('acf_add_local_field_group')) {
            $missing[] = 'Advanced Custom Fields PRO';
        }

        return $missing;
    }
}
