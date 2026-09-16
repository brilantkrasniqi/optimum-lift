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

<header class="site-header">
    <div class="site-header__inner">
        <p class="site-header__brand">
            <a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php bloginfo('name'); ?></a>
        </p>

        <?php
        if (has_nav_menu('primary')) {
            wp_nav_menu([
                'theme_location'  => 'primary',
                'container'       => 'nav',
                'container_class' => 'site-nav',
                'menu_class'      => 'site-nav__list',
                'depth'           => 2,
            ]);
        }

        if (function_exists('optimum_lift_cart_link')) {
            optimum_lift_cart_link();
        }
        ?>
    </div>
</header>
