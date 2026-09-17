<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#main"><?php esc_html_e('Skip to content', 'optimum-lift'); ?></a>

<?php
if (optimum_lift_is_checkout_chrome()) {
    get_template_part('template-parts/header/checkout-header');
} else {
    // A ticking offer right after payment (order received) only adds pressure.
    if (!function_exists('is_checkout') || !is_checkout()) {
        get_template_part('template-parts/header/urgency-bar');
    }
    get_template_part('template-parts/header/site-header');
    get_template_part('template-parts/header/mobile-menu');
}
