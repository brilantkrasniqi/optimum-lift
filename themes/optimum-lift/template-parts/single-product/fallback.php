<?php

/**
 * The middle of a Product page that has no section blocks: its description,
 * then the guarantee band, rendered by the guarantee block with the same copy
 * as the homepage's so the two cannot drift apart in style.
 */

declare(strict_types=1);

/** @var array{product: WC_Product} $args */

$product = $args['product'] ?? null;
if (!$product instanceof WC_Product) {
    return;
}

$description = $product->get_description();
$days        = (int) optimum_lift_setting('guarantee_days');
?>
<?php if (trim($description) !== '') : ?>
    <section class="py-16 md:py-20">
        <div class="prose-ol mx-auto max-w-3xl px-4">
            <?php echo wp_kses_post(wc_format_content($description)); ?>
        </div>
    </section>
<?php endif; ?>

<?php
if ($days > 0) {
    get_template_part('template-parts/blocks/guarantee', null, [
        'block'   => [
            'acf_fc_layout' => 'guarantee',
            'anchor'        => '',
            'nav_label'     => '',
            'eyebrow'       => '',
            /* translators: %d: number of days of the money-back guarantee. */
            'heading'       => sprintf(_n('%d-day guarantee — no questions asked', '%d-day guarantee — no questions asked', $days, 'optimum-lift'), $days),
            'intro'         => '',
            'tone'          => 'default',
            'style'         => 'band',
            /* translators: Keep {guarantee_days} as it is: it becomes the number of days. */
            'body'          => __('Follow the plan for {guarantee_days} days. If you see no change and do not feel better, send us an email and we refund 100% of your money — no forms, no excuses, no awkward conversation. The risk is ours, not yours.', 'optimum-lift'),
            'chips'         => implode("\n", [
                __('No hidden subscription', 'optimum-lift'),
                __('No complicated cancellation', 'optimum-lift'),
                __('You keep the materials', 'optimum-lift'),
            ]),
            'cta_label'     => __('Try it risk-free', 'optimum-lift'),
            'cta_anchor'    => 'blej',
        ],
        'product' => $product,
        'post_id' => $product->get_id(),
        'index'   => 0,
    ]);
}
