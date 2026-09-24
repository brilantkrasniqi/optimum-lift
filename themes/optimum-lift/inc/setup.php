<?php
/**
 * Theme supports and menus.
 */

declare(strict_types=1);

add_action('after_setup_theme', static function (): void {
    load_theme_textdomain('optimum-lift', OPTIMUM_LIFT_DIR . '/languages');

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
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

    // No wc-product-gallery-* supports: the Product page has its own gallery
    // (modules/gallery.js), so WooCommerce's zoom, lightbox and slider scripts
    // would only add weight.
    add_theme_support('woocommerce', [
        'thumbnail_image_width' => 400,
        'single_image_width'    => 800,
    ]);

    // add_editor_style() only takes effect with editor-styles support.
    add_theme_support('editor-styles');
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
