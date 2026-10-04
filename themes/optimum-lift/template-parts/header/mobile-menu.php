<?php
/**
 * The mobile menu: a backdrop and a panel fixed over the page, outside the
 * header, so opening it never moves the layout (modules/menu.js).
 *
 * Below the links, the account in words: log in or register, or once signed
 * in, the Customer's Plans and orders. Its closing call to action follows the
 * page: buy this Product, go to the homepage pricing, or see the bundle.
 */

declare(strict_types=1);

$links   = optimum_lift_nav_links();
$product = optimum_lift_current_product();
$shop    = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
$bundle  = $product === null && function_exists('optimum_lift_find_bundle') ? optimum_lift_find_bundle() : null;

// `html` is escaped here because the Product price is markup.
if ($product !== null) {
    $cta = [
        'url'     => function_exists('optimum_lift_buy_now_url') ? optimum_lift_buy_now_url($product) : '#blej',
        /* translators: %s: the Product's price. */
        'html'    => sprintf(esc_html__('Buy now — %s', 'optimum-lift'), wp_kses_post(wc_price(wc_get_price_to_display($product)))),
        'buy_now' => $product->get_id(),
        'id'      => 'menu-buy-now',
    ];
} elseif (is_front_page()) {
    $anchor = optimum_lift_section_anchor(optimum_lift_front_page_id(), 'pricing');
    $saving = $bundle !== null && function_exists('optimum_lift_saving') ? optimum_lift_saving($bundle) : null;
    $cta    = [
        'url'     => $anchor !== '' ? '#' . $anchor : $shop,
        /* translators: %d: discount percent. */
        'html'    => esc_html($saving !== null ? sprintf(__('Get my plan — %d%% off', 'optimum-lift'), $saving['percent']) : __('Get my plan', 'optimum-lift')),
        'buy_now' => 0,
        'id'      => 'menu-pricing',
    ];
} else {
    $cta = [
        'url'     => $bundle !== null ? $bundle->get_permalink() : $shop,
        'html'    => $bundle !== null ? esc_html__('Get the full bundle', 'optimum-lift') : esc_html__('See all products', 'optimum-lift'),
        'buy_now' => 0,
        'id'      => $bundle !== null ? 'menu-bundle' : 'menu-shop',
    ];
}

$guarantee     = optimum_lift_guarantee_label();
$account_links = optimum_lift_account_menu_links();
$signed_in_as  = is_user_logged_in() ? wp_get_current_user()->display_name : '';
?>
<div data-menu-backdrop class="pointer-events-none fixed inset-0 z-[55] bg-black/70 opacity-0 backdrop-blur-sm transition-opacity duration-300 xl:hidden"></div>
<aside id="ol-mobile-menu" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Menu', 'optimum-lift'); ?>" inert
       class="fixed top-0 right-0 bottom-0 z-[56] flex w-[min(21rem,86%)] translate-x-full flex-col border-l border-white/10 bg-surface transition-transform duration-300 xl:hidden">
    <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
        <span class="h-display text-lg text-white"><?php esc_html_e('Menu', 'optimum-lift'); ?></span>
        <button type="button" data-menu-close class="grid h-11 w-11 place-items-center rounded-lg border border-white/10 text-zinc-400 transition hover:bg-white/5 hover:text-white">
            <?php echo optimum_lift_icon('close'); ?>
            <span class="screen-reader-text"><?php esc_html_e('Close menu', 'optimum-lift'); ?></span>
        </button>
    </div>

    <nav class="grid flex-1 content-start gap-1 overflow-y-auto px-5 py-3 text-[15px] font-semibold" aria-label="<?php esc_attr_e('Main', 'optimum-lift'); ?>">
        <?php foreach ($links as $link) : ?>
            <a href="<?php echo esc_url($link['url']); ?>" class="border-b border-white/5 py-3 <?php echo $link['shop'] || $link['current'] ? 'text-white' : 'text-zinc-300'; ?>"<?php echo $link['current'] ? ' aria-current="page"' : ''; ?>>
                <?php echo esc_html($link['shop'] ? __('Shop · all products', 'optimum-lift') : $link['label']); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if ($account_links !== []) : ?>
        <nav class="border-t border-white/10 px-5 pt-4 pb-1" aria-labelledby="ol-menu-account">
            <h2 id="ol-menu-account" class="flex items-center gap-2 text-[10px] font-extrabold uppercase tracking-[.2em] text-zinc-500">
                <?php echo optimum_lift_icon('user', 'w-3.5 h-3.5'); ?>
                <?php esc_html_e('Account', 'optimum-lift'); ?>
                <?php if ($signed_in_as !== '') : ?>
                    <span class="truncate font-bold normal-case tracking-normal text-zinc-400">· <?php echo esc_html($signed_in_as); ?></span>
                <?php endif; ?>
            </h2>
            <ul class="mt-3 flex flex-wrap gap-2">
                <?php foreach ($account_links as $link) : ?>
                    <li><a href="<?php echo esc_url($link['url']); ?>" class="btn btn-ghost btn-sm min-h-11 px-4 text-[13px]"><?php echo esc_html($link['label']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
    <?php endif; ?>

    <div class="border-t border-white/10 p-5">
        <a href="<?php echo esc_url($cta['url']); ?>" data-cta="<?php echo esc_attr($cta['id']); ?>"<?php echo $cta['buy_now'] > 0 ? ' data-buy-now="' . esc_attr((string) $cta['buy_now']) . '" rel="nofollow"' : ''; ?> class="btn btn-primary btn-block rounded-xl px-4 py-3.5 text-base">
            <span><?php echo $cta['html']; ?></span>
        </a>
        <?php if ($guarantee !== '') : ?>
            <p class="mt-3 text-center text-[11px] font-bold uppercase tracking-wider text-zinc-500"><?php echo esc_html($guarantee); ?></p>
        <?php endif; ?>
    </div>
</aside>
