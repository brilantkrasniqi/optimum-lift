<?php

/**
 * How Plan and performance values read, identically in the Portal and the
 * Download.
 */

declare(strict_types=1);

namespace OptimumLift\Plans;

use OptimumLift\Plans\Content\Choices;
use OptimumLift\Plans\Plan\Exercise;
use OptimumLift\Plans\Plan\Prescription;

final class Format
{
    /**
     * "4 × 8-10" or "3 × 30 s".
     */
    public static function volume(Prescription $prescription): string
    {
        return sprintf(
            '%d × %s%s',
            $prescription->sets,
            $prescription->target,
            $prescription->isTimed() && is_numeric($prescription->target) ? ' s' : ''
        );
    }

    /**
     * "90 s" under two minutes, "2:30" from two minutes up.
     */
    public static function rest(?int $seconds): string
    {
        if ($seconds === null || $seconds <= 0) {
            return '';
        }

        if ($seconds < 120) {
            return $seconds . ' s';
        }

        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }

    public static function load(?float $kg): string
    {
        if ($kg === null) {
            return __('BW', 'optimum-lift-plans');
        }

        return rtrim(rtrim(number_format($kg, 2, '.', ''), '0'), '.') . ' kg';
    }

    /**
     * "80 kg × 8" or "BW × 30 s".
     */
    public static function loggedSet(?float $loadKg, ?int $reps, ?int $seconds): string
    {
        $result = $seconds !== null ? $seconds . ' s' : (string) $reps;

        return self::load($loadKg) . ' × ' . $result;
    }

    /**
     * A UTC MySQL datetime as ISO 8601 with its offset, for the REST API.
     */
    public static function utc(string $mysqlGmt): string
    {
        return (new \DateTimeImmutable($mysqlGmt, new \DateTimeZone('UTC')))->format(DATE_ATOM);
    }

    public static function muscles(Exercise $exercise): string
    {
        return Choices::labels(Choices::muscles(), array_merge([$exercise->primaryMuscle], $exercise->secondaryMuscles));
    }

    public static function equipment(Exercise $exercise): string
    {
        return Choices::labels(Choices::equipment(), $exercise->equipment);
    }

    /**
     * Filesystem path of an image size, for Dompdf, which reads local files
     * only.
     */
    public static function imagePath(int $attachmentId, string $size = 'medium'): string
    {
        if ($attachmentId <= 0) {
            return '';
        }

        $intermediate = image_get_intermediate_size($attachmentId, $size);

        if (is_array($intermediate) && !empty($intermediate['path'])) {
            $path = wp_get_upload_dir()['basedir'] . '/' . $intermediate['path'];

            if (is_file($path)) {
                return $path;
            }
        }

        $original = get_attached_file($attachmentId);

        return is_string($original) && is_file($original) ? $original : '';
    }
}
