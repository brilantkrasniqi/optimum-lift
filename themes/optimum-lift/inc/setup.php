<?php
/**
 * Theme supports, menus, and widget areas.
 */

declare(strict_types=1);

add_action('after_setup_theme', static function (): void {
    load_theme_textdomain('optimum-lift', OPTIMUM_LIFT_DIR . '/languages');

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('customize-selective-refresh-widgets');
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');
    add_theme_support('html5', [
        'search-form',
        'gallery',
        'caption',
        'style',
        'script',
        'navigation-widgets',
    ]);

    // WooCommerce. Declaring gallery support opts the single Product page into
    // the zoom / lightbox / slider behaviour instead of a plain image stack.
    add_theme_support('woocommerce', [
        'thumbnail_image_width' => 400,
        'single_image_width'    => 800,
        'product_grid'          => [
            'default_columns' => 3,
            'default_rows'    => 4,
        ],
    ]);
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');

    add_editor_style('assets/css/editor.css');

    register_nav_menus([
        'primary' => __('Primary', 'optimum-lift'),
        'footer'  => __('Footer', 'optimum-lift'),
    ]);
});

// Content width, used by WordPress when sizing embeds and large images.
add_action('after_setup_theme', static function (): void {
    $GLOBALS['content_width'] ??= 768;
}, 0);

add_action('widgets_init', static function (): void {
    // WooCommerce ships filter widgets (price, attribute, rating) that need
    // somewhere to live on Product archives.
    register_sidebar([
        'name'          => __('Shop sidebar', 'optimum-lift'),
        'id'            => 'shop',
        'description'   => __('Shown alongside Product archives.', 'optimum-lift'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget__title">',
        'after_title'   => '</h2>',
    ]);

    register_sidebar([
        'name'          => __('Footer', 'optimum-lift'),
        'id'            => 'footer',
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget__title">',
        'after_title'   => '</h2>',
    ]);
});
