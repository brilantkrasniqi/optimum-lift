<?php

/**
 * The middle of a Product page that has no section blocks: its description,
 * then the guarantee band (the Customizer promise), rendered by the guarantee
 * block so the two cannot drift apart in style.
 */

declare(strict_types=1);

/** @var array{product: WC_Product} $args */

$product = $args['product'] ?? null;
if (!$product instanceof WC_Product) {
    return;
}

$description = $product->get_description();
$guarantee   = optimum_lift_guarantee_label();
?>
<?php if (trim($description) !== '') : ?>
    <section class="py-16 md:py-20">
        <div class="prose-ol mx-auto max-w-3xl px-4">
            <?php echo wp_kses_post(wc_format_content($description)); ?>
        </div>
    </section>
<?php endif; ?>

<?php
if ($guarantee !== '') {
    get_template_part('template-parts/blocks/guarantee', null, [
        'block'   => [
            'acf_fc_layout' => 'guarantee',
            'anchor'        => '',
            'nav_label'     => '',
            'eyebrow'       => '',
            'heading'       => $guarantee,
            'intro'         => '',
            'tone'          => 'default',
            'style'         => 'band',
            'body'          => optimum_lift_guarantee_text(),
            'chips'         => implode("\n", [
                __('No hidden subscription', 'optimum-lift'),
                __('No complicated cancellation', 'optimum-lift'),
                __('Instant access', 'optimum-lift'),
            ]),
            'cta_label'     => __('Start now', 'optimum-lift'),
            'cta_anchor'    => 'blej',
        ],
        'product' => $product,
        'post_id' => $product->get_id(),
        'index'   => 0,
    ]);
}
