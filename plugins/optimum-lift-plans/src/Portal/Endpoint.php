<?php

/**
 * URLs under My Account › Plans:
 *
 *   /my-account/plans/                      the Customer's Plans
 *   /my-account/plans/{plan}/               one Plan
 *   /my-account/plans/{plan}/download/      the Download
 *   /my-account/plans/{plan}/workout/{uid}/ one Workout, where it is logged
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Portal;

final class Endpoint
{
    public const NAME = 'plans';

    public static function url(int $planId = 0, string $action = '', string $arg = ''): string
    {
        $path = implode('/', array_filter([$planId > 0 ? (string) $planId : '', $action, $arg]));

        return wc_get_endpoint_url(self::NAME, $path, wc_get_page_permalink('myaccount'));
    }

    public static function workoutUrl(int $planId, string $workoutUid): string
    {
        return self::url($planId, 'workout', $workoutUid);
    }

    /**
     * The current request's route, or null when this is not the Plans endpoint.
     *
     * @return array{plan: int, action: string, arg: string}|null
     */
    public static function current(): ?array
    {
        global $wp;

        if (!is_account_page() || !isset($wp->query_vars[self::NAME])) {
            return null;
        }

        return self::parse((string) $wp->query_vars[self::NAME]);
    }

    /**
     * @return array{plan: int, action: string, arg: string}
     */
    public static function parse(string $value): array
    {
        $parts = array_values(array_filter(explode('/', trim($value, '/')), static fn (string $part): bool => $part !== ''));

        return [
            'plan'   => isset($parts[0]) && ctype_digit($parts[0]) ? (int) $parts[0] : 0,
            'action' => sanitize_key($parts[1] ?? ''),
            'arg'    => isset($parts[2]) && wp_is_uuid($parts[2]) ? $parts[2] : '',
        ];
    }
}
