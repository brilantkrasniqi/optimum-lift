<?php
/**
 * The sticky site header: logo and tagline, nav, contextual CTA, cart toggle
 * and the mobile menu button.
 *
 * The nav's store links (Shop, the training and diet archives) show from lg,
 * the page's section links only from xl, and below xl the menu button opens
 * all of them: between lg and xl there is no room for every link on one line,
 * and the store links are the ones that sell.
 *
 * The cart control is a link to the cart page until JavaScript runs, then the
 * drawer toggle (modules/cart.js); `js:` swaps them before first paint. The
 * menu button also needs JavaScript to open anything, so it only shows with
 * it; without, the footer carries the links.
 */

declare(strict_types=1);

$links      = optimum_lift_nav_links();
$cta        = optimum_lift_header_cta();
$guarantee  = is_front_page() ? optimum_lift_guarantee_label() : '';
$has_cart   = function_exists('wc_get_cart_url') && function_exists('optimum_lift_cart_badge_html');
$badge      = $has_cart ? optimum_lift_cart_badge_html(optimum_lift_cart_count()) : '';
?>
<header data-site-header class="sticky top-(--ol-sticky-top) z-40 border-b border-white/[.07] bg-paper/80 backdrop-blur-xl">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4">
        <?php get_template_part('template-parts/header/logo', null, ['tagline' => true, 'glow' => true]); ?>

        <?php if ($links !== []) : ?>
            <nav class="hidden items-center gap-7 text-[13px] font-semibold text-zinc-400 lg:flex" aria-label="<?php esc_attr_e('Main', 'optimum-lift'); ?>">
                <?php
                foreach ($links as $link) :
                    $class = trim(implode(' ', [
                        'whitespace-nowrap',
                        $link['current'] ? 'text-white' : 'transition hover:text-white',
                        $link['section'] ? 'hidden xl:inline' : '',
                    ]));
                    ?>
                    <a href="<?php echo esc_url($link['url']); ?>" class="<?php echo esc_attr($class); ?>"<?php echo $link['current'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html($link['label']); ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <div class="flex items-center gap-2">
            <?php if ($guarantee !== '') : ?>
                <span class="mr-1 hidden items-center gap-1.5 text-[11px] font-bold text-zinc-400 xl:flex">
                    <?php echo optimum_lift_icon('shield-check', 'w-4 h-4 text-acid', ['stroke-width' => '2.2']); ?>
                    <?php echo esc_html($guarantee); ?>
                </span>
            <?php endif; ?>

            <?php if ($cta !== null) : ?>
                <a href="<?php echo esc_url($cta['url']); ?>" data-cta="header-cta" class="btn btn-light btn-sm hidden text-[13px] whitespace-nowrap sm:inline-flex">
                    <?php echo esc_html($cta['label']); ?>
                    <?php echo $cta['arrow'] ? optimum_lift_icon('arrow-right') : ''; ?>
                </a>
            <?php endif; ?>

            <?php if ($has_cart) : ?>
                <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="relative grid h-11 w-11 place-items-center rounded-xl border border-white/10 text-white transition hover:bg-white/5 js:hidden">
                    <?php echo optimum_lift_icon('cart', 'w-5 h-5'); ?>
                    <span class="screen-reader-text"><?php esc_html_e('View cart', 'optimum-lift'); ?></span>
                    <?php echo $badge; ?>
                </a>
                <button type="button" data-cart-toggle aria-controls="ol-cart-drawer" aria-expanded="false" class="relative hidden h-11 w-11 place-items-center rounded-xl border border-white/10 text-white transition hover:bg-white/5 js:grid">
                    <?php echo optimum_lift_icon('cart', 'w-5 h-5'); ?>
                    <span class="screen-reader-text"><?php esc_html_e('Open cart', 'optimum-lift'); ?></span>
                    <?php echo $badge; ?>
                </button>
            <?php endif; ?>

            <button type="button" data-menu-toggle aria-controls="ol-mobile-menu" aria-expanded="false" class="hidden h-11 w-11 place-items-center rounded-xl border border-white/10 text-white js:max-xl:grid">
                <?php echo optimum_lift_icon('menu', 'w-5 h-5'); ?>
                <span class="screen-reader-text"><?php esc_html_e('Open menu', 'optimum-lift'); ?></span>
            </button>
        </div>
    </div>
</header>
