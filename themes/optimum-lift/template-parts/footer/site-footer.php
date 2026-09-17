<?php
/**
 * The site footer: brand and socials, the catalogue, help and legal links,
 * copyright and the medical disclaimer.
 */

declare(strict_types=1);

$socials = array_filter([
    'instagram' => ['url' => (string) optimum_lift_setting('instagram_url'), 'label' => 'Instagram'],
    'tiktok'    => ['url' => (string) optimum_lift_setting('tiktok_url'), 'label' => 'TikTok'],
    'chat'      => ['url' => optimum_lift_whatsapp_url(), 'label' => 'WhatsApp'],
], static fn (array $social): bool => $social['url'] !== '');

$products = optimum_lift_catalogue_products();
$email    = sanitize_email((string) optimum_lift_setting('contact_email'));
$faq_url  = optimum_lift_faq_url();
$help     = [];

if ($faq_url !== '') {
    $help[] = ['label' => __('FAQ', 'optimum-lift'), 'url' => $faq_url];
}

if ($email !== '') {
    $help[] = ['label' => $email, 'url' => 'mailto:' . $email];
}

$help = array_merge($help, optimum_lift_legal_links(), optimum_lift_menu_links('footer'));

// Block links with the list gap as padding: the same 29.5px rhythm as the mock, with
// tap targets that fill it instead of 16px-tall lines.
$link_class = 'block py-[5px] transition hover:text-white';
?>
<footer class="border-t border-white/[.07] bg-surface">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 md:grid-cols-4">
        <div class="md:col-span-2">
            <?php get_template_part('template-parts/header/logo'); ?>
            <p class="mt-4 max-w-sm text-[13px] leading-relaxed text-zinc-500">
                <?php esc_html_e('Training programs and meal plans in Albanian, for real people with real schedules. No miracles, no detox teas, no empty promises — just a plan and discipline.', 'optimum-lift'); ?>
            </p>

            <?php if ($socials !== []) : ?>
                <div class="mt-5 flex gap-2.5">
                    <?php foreach ($socials as $icon => $social) : ?>
                        <a href="<?php echo esc_url($social['url']); ?>" target="_blank" rel="noopener" class="grid h-10 w-10 place-items-center rounded-xl border border-white/10 text-zinc-400 transition hover:bg-white/5 hover:text-white">
                            <?php echo optimum_lift_icon($icon, 'w-5 h-5', $icon === 'chat' ? ['stroke-width' => '2'] : []); ?>
                            <span class="screen-reader-text"><?php echo esc_html($social['label']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($products !== []) : ?>
            <div>
                <h2 class="text-[10px] font-extrabold uppercase tracking-[.2em] text-zinc-500"><?php esc_html_e('Products', 'optimum-lift'); ?></h2>
                <ul class="mt-3 grid text-[13px] font-semibold text-zinc-400">
                    <li><a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="<?php echo esc_attr($link_class); ?>"><?php esc_html_e('All products', 'optimum-lift'); ?></a></li>
                    <?php foreach ($products as $product) : ?>
                        <li><a href="<?php echo esc_url($product->get_permalink()); ?>" class="<?php echo esc_attr($link_class); ?>"><?php echo esc_html($product->get_name()); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($help !== []) : ?>
            <div>
                <h2 class="text-[10px] font-extrabold uppercase tracking-[.2em] text-zinc-500"><?php esc_html_e('Help', 'optimum-lift'); ?></h2>
                <ul class="mt-3 grid text-[13px] font-semibold text-zinc-400">
                    <?php foreach ($help as $link) : ?>
                        <li><a href="<?php echo esc_url($link['url']); ?>" class="<?php echo esc_attr($link_class); ?>"><?php echo esc_html($link['label']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <div class="border-t border-white/[.07]">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 py-6 sm:flex-row">
            <p class="text-[11px] text-zinc-600">
                <?php
                /* translators: 1: year, 2: site name. */
                echo esc_html(sprintf(__('© %1$s %2$s. All rights reserved.', 'optimum-lift'), wp_date('Y'), get_bloginfo('name')));
                ?>
            </p>
            <p class="max-w-xl text-[11px] leading-relaxed text-zinc-600 sm:text-right">
                <?php esc_html_e('Results vary from person to person. This information does not replace medical advice — talk to your doctor before starting a training program or a diet, especially if you have a health condition or are pregnant.', 'optimum-lift'); ?>
            </p>
        </div>
    </div>

    <?php if (optimum_lift_has_sticky_bar()) : ?>
        <div class="h-20 md:hidden" aria-hidden="true"></div>
    <?php endif; ?>
</footer>
