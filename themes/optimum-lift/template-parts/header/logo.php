<?php
/**
 * The brand mark and name, linking home: site header, checkout header, footer.
 *
 * `tagline` adds the Customizer tagline under the name; `glow` the red shadow.
 * The logo with a tagline is the site header's, which shares a phone-width row
 * with three 44px buttons (account, cart, menu): the tagline shows from 410px
 * and the name from 360px, so the buttons never leave the screen.
 */

declare(strict_types=1);

/** @var array{tagline?: bool, glow?: bool} $args */

$in_header = !empty($args['tagline']);
$tagline   = $in_header ? (string) optimum_lift_setting('tagline') : '';
?>
<a href="<?php echo esc_url(home_url('/')); ?>" class="flex shrink-0 items-center gap-2.5" rel="home">
    <span class="grid h-9 w-9 place-items-center rounded-xl bg-accent <?php echo !empty($args['glow']) ? 'shadow-glow' : ''; ?>">
        <?php echo optimum_lift_icon('logo', 'w-5 h-5 text-white'); ?>
    </span>
    <span class="leading-none">
        <span class="h-display block text-lg text-white <?php echo $in_header ? 'max-[359px]:sr-only' : ''; ?>"><?php bloginfo('name'); ?></span>
        <?php if ($tagline !== '') : ?>
            <span class="block text-[9px] font-bold uppercase tracking-[.2em] text-zinc-500 max-[409px]:hidden"><?php echo esc_html($tagline); ?></span>
        <?php endif; ?>
    </span>
</a>
