<?php
/**
 * The brand mark and name, linking home: site header, checkout header, footer.
 *
 * `tagline` adds the Customizer tagline under the name, hidden below 360px
 * where it would push the header buttons off-screen; `glow` the red shadow.
 */

declare(strict_types=1);

/** @var array{tagline?: bool, glow?: bool} $args */

$tagline = !empty($args['tagline']) ? (string) optimum_lift_setting('tagline') : '';
?>
<a href="<?php echo esc_url(home_url('/')); ?>" class="flex shrink-0 items-center gap-2.5" rel="home">
    <span class="grid h-9 w-9 place-items-center rounded-xl bg-accent <?php echo !empty($args['glow']) ? 'shadow-glow' : ''; ?>">
        <?php echo optimum_lift_icon('logo', 'w-5 h-5 text-white'); ?>
    </span>
    <span class="leading-none">
        <span class="h-display block text-lg text-white"><?php bloginfo('name'); ?></span>
        <?php if ($tagline !== '') : ?>
            <span class="block text-[9px] font-bold uppercase tracking-[.2em] text-zinc-500 max-[359px]:hidden"><?php echo esc_html($tagline); ?></span>
        <?php endif; ?>
    </span>
</a>
