<?php

/**
 * Custom tables for Access and training performance (ADR-0004).
 *
 * Bump VERSION whenever a CREATE TABLE statement changes. dbDelta() adds
 * columns and indexes but never drops them; removals need an explicit query.
 */

declare(strict_types=1);

namespace OptimumLift\Plans;

final class Schema
{
    public const VERSION = 1;

    private const OPTION = 'ol_plans_schema_version';

    public static function access(): string
    {
        return $GLOBALS['wpdb']->prefix . 'ol_access';
    }

    public static function workoutLogs(): string
    {
        return $GLOBALS['wpdb']->prefix . 'ol_workout_logs';
    }

    public static function loggedSets(): string
    {
        return $GLOBALS['wpdb']->prefix . 'ol_logged_sets';
    }

    public static function maybeUpgrade(): void
    {
        if ((int) get_option(self::OPTION) === self::VERSION) {
            return;
        }

        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();

        // dbDelta is strict about formatting: two spaces after PRIMARY KEY,
        // one field per line, KEY not INDEX.
        dbDelta("CREATE TABLE " . self::access() . " (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(20) unsigned NOT NULL,
  plan_id bigint(20) unsigned NOT NULL,
  order_id bigint(20) unsigned DEFAULT NULL,
  product_id bigint(20) unsigned DEFAULT NULL,
  granted_at datetime NOT NULL,
  revoked_at datetime DEFAULT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY order_plan (order_id,plan_id),
  KEY user_plan (user_id,plan_id)
) $charset;");

        dbDelta("CREATE TABLE " . self::workoutLogs() . " (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(20) unsigned NOT NULL,
  plan_id bigint(20) unsigned NOT NULL,
  workout_uid char(36) NOT NULL,
  week_number smallint(5) unsigned NOT NULL,
  workout_name varchar(191) NOT NULL,
  status varchar(20) NOT NULL,
  notes text,
  started_at datetime NOT NULL,
  completed_at datetime DEFAULT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY user_plan (user_id,plan_id),
  KEY user_workout (user_id,workout_uid)
) $charset;");

        dbDelta("CREATE TABLE " . self::loggedSets() . " (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  workout_log_id bigint(20) unsigned NOT NULL,
  user_id bigint(20) unsigned NOT NULL,
  exercise_id bigint(20) unsigned NOT NULL,
  prescription_uid char(36) NOT NULL,
  set_number tinyint(3) unsigned NOT NULL,
  load_kg decimal(6,2) DEFAULT NULL,
  reps smallint(5) unsigned DEFAULT NULL,
  seconds smallint(5) unsigned DEFAULT NULL,
  performed_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY log_set (workout_log_id,prescription_uid,set_number),
  KEY user_exercise (user_id,exercise_id,performed_at)
) $charset;");

        update_option(self::OPTION, self::VERSION);

        // The Portal's My Account endpoint needs its rewrite rules.
        add_action('wp_loaded', static fn () => flush_rewrite_rules(false));
    }
}
