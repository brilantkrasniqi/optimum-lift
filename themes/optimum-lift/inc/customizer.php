<?php
/**
 * Theme settings: the Customizer section "Optimum Lift".
 *
 * Values are theme mods. Read them with optimum_lift_setting(), never with
 * get_theme_mod(), so every caller gets the same default.
 */

declare(strict_types=1);

/**
 * The settings, keyed by name. Built on call because some defaults and every
 * label are translated.
 *
 * @return array<string, array{default: mixed, control: string, label: string, description?: string, sanitize: callable(mixed): mixed}>
 */
function optimum_lift_settings(): array
{
    $text = static fn (mixed $value): string => sanitize_text_field(is_scalar($value) ? (string) $value : '');

    return [
        'tagline' => [
            'default'  => 'Trupi · Plani · Rezultati',
            'control'  => 'text',
            'label'    => __('Header tagline', 'optimum-lift'),
            'sanitize' => $text,
        ],
        'contact_email' => [
            'default'  => 'info@optimumlift.com',
            'control'  => 'email',
            'label'    => __('Contact email', 'optimum-lift'),
            'sanitize' => static fn (mixed $value): string => sanitize_email(is_string($value) ? $value : ''),
        ],
        'whatsapp' => [
            'default'     => '',
            'control'     => 'text',
            'label'       => __('WhatsApp number', 'optimum-lift'),
            'description' => __('International format, digits only (e.g. 38344123456). Leave empty to hide every WhatsApp link.', 'optimum-lift'),
            'sanitize'    => static fn (mixed $value): string => (string) preg_replace('/\D+/', '', is_scalar($value) ? (string) $value : ''),
        ],
        'instagram_url' => [
            'default'  => '',
            'control'  => 'url',
            'label'    => __('Instagram URL', 'optimum-lift'),
            'sanitize' => static fn (mixed $value): string => esc_url_raw(is_string($value) ? $value : ''),
        ],
        'tiktok_url' => [
            'default'  => '',
            'control'  => 'url',
            'label'    => __('TikTok URL', 'optimum-lift'),
            'sanitize' => static fn (mixed $value): string => esc_url_raw(is_string($value) ? $value : ''),
        ],
        'guarantee_days' => [
            'default'  => 30,
            'control'  => 'number',
            'label'    => __('Money-back guarantee (days)', 'optimum-lift'),
            'sanitize' => static fn (mixed $value): int => absint($value),
        ],
        'offer_label' => [
            'default'  => __('Launch offer', 'optimum-lift'),
            'control'  => 'text',
            'label'    => __('Offer label', 'optimum-lift'),
            'sanitize' => $text,
        ],
        'offer_ends_at' => [
            'default'     => '',
            'control'     => 'datetime-local',
            'label'       => __('Site offer ends at', 'optimum-lift'),
            'description' => __('In the site timezone. The offer bar and countdowns show only while this date is in the future, or while a Product has a scheduled sale.', 'optimum-lift'),
            'sanitize'    => 'optimum_lift_sanitize_datetime_local',
        ],
        'customers_baseline' => [
            'default'     => 600,
            'control'     => 'number',
            'label'       => __('Customers before the online store', 'optimum-lift'),
            'description' => __('Added to paid online orders for the customer count.', 'optimum-lift'),
            'sanitize'    => static fn (mixed $value): int => absint($value),
        ],
        'exit_coupon' => [
            'default'     => '',
            'control'     => 'text',
            'label'       => __('Exit-intent coupon code', 'optimum-lift'),
            'description' => __('The homepage exit offer shows only while this coupon exists and is valid.', 'optimum-lift'),
            'sanitize'    => $text,
        ],
        'payment_badges' => [
            'default'     => 'Visa, Mastercard',
            'control'     => 'text',
            'label'       => __('Payment badges', 'optimum-lift'),
            'description' => __('Comma-separated. List only the methods checkout actually offers.', 'optimum-lift'),
            'sanitize'    => $text,
        ],
    ];
}

/**
 * A theme setting, or its default when it was never saved.
 */
function optimum_lift_setting(string $key): mixed
{
    $settings = optimum_lift_settings();

    if (!isset($settings[$key])) {
        return null;
    }

    return get_theme_mod($key, $settings[$key]['default']);
}

/**
 * Keeps a `datetime-local` value (2026-09-30T23:59) or clears it.
 */
function optimum_lift_sanitize_datetime_local(mixed $value): string
{
    if (!is_string($value) || $value === '') {
        return '';
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', substr($value, 0, 16), wp_timezone());

    return $date instanceof DateTimeImmutable ? $date->format('Y-m-d\TH:i') : '';
}

add_action('customize_register', static function (WP_Customize_Manager $wp_customize): void {
    $wp_customize->add_section('optimum_lift', [
        'title'    => __('Optimum Lift', 'optimum-lift'),
        'priority' => 30,
    ]);

    foreach (optimum_lift_settings() as $key => $setting) {
        $wp_customize->add_setting($key, [
            'default'           => $setting['default'],
            'sanitize_callback' => $setting['sanitize'],
        ]);

        $wp_customize->add_control($key, [
            'section'     => 'optimum_lift',
            'type'        => $setting['control'],
            'label'       => $setting['label'],
            'description' => $setting['description'] ?? '',
            'input_attrs' => $setting['control'] === 'number' ? ['min' => 0, 'step' => 1] : [],
        ]);
    }
});
