<?php

/**
 * Testimonials: native WooCommerce reviews first, then the editor's
 * testimonials (`source` picks one or both).
 *
 * Native reviews are approved, rated 4 or more, have text and are newest
 * first, up to `limit`. On a Product they are its own; on the front page, whose
 * context Product is the bundle, they come from every Product, best rated
 * first. "Verified purchase" shows only on reviews WooCommerce has verified,
 * never on testimonials. The summary box is the Product's rating (the store's
 * on the front page) and hides below the review threshold (ADR-0008).
 *
 * The review form: the design has none, and on a page bought from cold traffic
 * an open form invites reviews from people who never used the Product. So
 * only a signed-in customer who bought it gets one, collapsed under the cards.
 * It keeps WooCommerce's native rating <select> (no star widget, no jQuery).
 */

declare(strict_types=1);

/** @var array{block: array<string, mixed>, product: ?WC_Product, post_id: int, index: int} $args */

$block   = $args['block'] ?? [];
$product = $args['product'] ?? null;
$product = $product instanceof WC_Product ? $product : null;
$post_id = (int) ($args['post_id'] ?? 0);

$text  = static fn (mixed $value): string => is_string($value) ? trim($value) : '';
$front = $post_id > 0 && $post_id === optimum_lift_front_page_id();
$own   = !$front && $product !== null && $product->get_id() === $post_id;

$source = $text($block['source'] ?? null);
$source = in_array($source, ['native', 'manual', 'both'], true) ? $source : 'both';
$limit  = is_numeric($block['limit'] ?? null) ? max(1, (int) $block['limit']) : 3;

/** @var list<array{id: string, quote: string, name: string, meta: string, rating: int}> $cards */
$cards = [];

if ($source !== 'manual' && ($front || $own)) {
    $query = [
        'status'     => 'approve',
        'type'       => 'review',
        'parent'     => 0,
        // Room for reviews without text, which are skipped below.
        'number'     => $limit * 3,
        'meta_query' => [
            'rating' => ['key' => 'rating', 'value' => 4, 'compare' => '>=', 'type' => 'NUMERIC'],
        ],
        'orderby'    => ['comment_date_gmt' => 'DESC'],
    ];

    if ($front) {
        $query['post_type']   = 'product';
        $query['post_status'] = 'publish';
        $query['orderby']     = ['rating' => 'DESC', 'comment_date_gmt' => 'DESC'];
    } elseif ($product !== null) {
        $query['post_id'] = $product->get_id();
    }

    $comments = get_comments($query);
    foreach (is_array($comments) ? $comments : [] as $comment) {
        if (!$comment instanceof WP_Comment) {
            continue;
        }

        $quote = trim(optimum_lift_plain_text(wp_strip_all_tags($comment->comment_content)));
        if ($quote === '') {
            continue;
        }

        $meta = [];
        if ($front) {
            $reviewed = wc_get_product((int) $comment->comment_post_ID);
            if ($reviewed instanceof WC_Product) {
                $meta[] = optimum_lift_plain_text($reviewed->get_name());
            }
        }
        if (wc_review_is_from_verified_owner((int) $comment->comment_ID)) {
            $meta[] = __('Verified purchase', 'optimum-lift');
        }

        $cards[] = [
            // The id WordPress links to after a review is posted.
            'id'     => 'comment-' . $comment->comment_ID,
            'quote'  => wp_trim_words($quote, 55, '…'),
            'name'   => optimum_lift_plain_text($comment->comment_author),
            'meta'   => implode(' · ', $meta),
            'rating' => max(1, min(5, (int) get_comment_meta((int) $comment->comment_ID, 'rating', true))),
        ];

        if (count($cards) >= $limit) {
            break;
        }
    }
}

if ($source !== 'native') {
    foreach (is_array($block['items'] ?? null) ? $block['items'] : [] as $row) {
        $quote = is_array($row) ? $text($row['quote'] ?? null) : '';
        if ($quote === '') {
            continue;
        }

        $cards[] = [
            'id'     => '',
            'quote'  => $quote,
            'name'   => $text($row['name'] ?? null),
            'meta'   => $text($row['meta'] ?? null),
            'rating' => is_numeric($row['rating'] ?? null) ? max(1, min(5, (int) $row['rating'])) : 5,
        ];
    }
}

$form = $own
    && $product !== null
    && is_user_logged_in()
    && wc_reviews_enabled()
    && comments_open($product->get_id())
    && wc_customer_bought_product('', get_current_user_id(), $product->get_id());

if ($cards === [] && !$form) {
    return;
}

$rating = null;
if (!empty($block['show_summary']) && $cards !== []) {
    $rating = $front ? optimum_lift_store_rating() : ($own && $product !== null ? optimum_lift_rating($product) : null);
}

$eyebrow = $text($block['eyebrow'] ?? null);
$heading = $text($block['heading'] ?? null);
$intro   = $text($block['intro'] ?? null);
$alt     = optimum_lift_block_tone_class($block) !== '';

$stars_percent = static fn (float $average): string => rtrim(rtrim(sprintf('%.1F', max(0, min(100, $average / 5 * 100))), '0'), '.');

// Two letters for the avatar: the first letters of the first and last words.
$initials = static function (string $name): string {
    $letters = [];
    foreach (preg_split('/\s+/u', $name) ?: [] as $word) {
        if (preg_match('/\p{L}/u', $word, $match) === 1) {
            $letters[] = $match[0];
        }
    }

    if (count($letters) > 2) {
        $letters = [$letters[0], $letters[count($letters) - 1]];
    }

    return mb_strtoupper(implode('', $letters));
};

$avatars = [
    'bg-linear-to-br from-accent to-accent-light',
    'bg-linear-to-br from-zinc-500 to-zinc-700',
    'bg-linear-to-br from-zinc-600 to-zinc-800',
];
?>
<section id="<?php echo esc_attr(optimum_lift_block_id($block, 'reviews')); ?>" class="py-20 md:py-28 <?php echo esc_attr(optimum_lift_block_tone_class($block)); ?>">
    <div class="<?php echo esc_attr($front ? 'mx-auto max-w-7xl px-4' : 'mx-auto max-w-6xl px-4'); ?>">
        <?php if ($rating !== null) : ?>
            <div class="reveal flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <?php if ($eyebrow !== '' || $heading !== '' || $intro !== '') : ?>
                    <div>
                        <?php if ($eyebrow !== '') : ?>
                            <span class="eyebrow"><?php echo esc_html($eyebrow); ?></span>
                        <?php endif; ?>
                        <?php if ($heading !== '') : ?>
                            <h2 class="<?php echo esc_attr($front ? 'mt-5 h-display text-3xl text-white first:mt-0 sm:text-5xl' : 'mt-5 h-display text-3xl text-white first:mt-0 sm:text-4xl'); ?>"><?php echo optimum_lift_heading_html($heading); ?></h2>
                        <?php endif; ?>
                        <?php if ($intro !== '') : ?>
                            <p class="mt-4 max-w-2xl text-[15px] leading-relaxed text-zinc-400 first:mt-0"><?php echo esc_html($intro); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php $average = optimum_lift_format_rating($rating['average']); ?>
                <div class="<?php echo esc_attr($alt ? 'flex shrink-0 items-center gap-3 self-start rounded-2xl border border-white/10 bg-paper px-5 py-3.5 sm:self-auto' : 'flex shrink-0 items-center gap-3 self-start rounded-2xl border border-white/10 bg-surface px-5 py-3.5 sm:self-auto'); ?>">
                    <p class="h-display text-3xl text-white" aria-hidden="true"><?php echo esc_html($average); ?></p>
                    <div>
                        <span class="olstars text-[14px]" style="--pct:<?php echo esc_attr($stars_percent($rating['average'])); ?>%" role="img" aria-label="<?php
                            /* translators: %s: average rating, e.g. 4,7. */
                            echo esc_attr(sprintf(__('%s out of 5 stars', 'optimum-lift'), $average));
                        ?>">★★★★★</span>
                        <p class="mt-0.5 text-[11px] font-bold text-zinc-500"><?php
                            $count = optimum_lift_format_number($rating['count']);
                            echo esc_html($front
                                /* translators: %s: number of reviews across the store. */
                                ? sprintf(_n('%s review', '%s reviews', $rating['count'], 'optimum-lift'), $count)
                                /* translators: %s: number of reviews of the Product. */
                                : sprintf(_n('%s review for this product', '%s reviews for this product', $rating['count'], 'optimum-lift'), $count));
                        ?></p>
                    </div>
                </div>
            </div>
        <?php elseif ($eyebrow !== '' || $heading !== '' || $intro !== '') : ?>
            <div class="reveal mx-auto max-w-2xl text-center">
                <?php if ($eyebrow !== '') : ?>
                    <span class="eyebrow"><?php echo esc_html($eyebrow); ?></span>
                <?php endif; ?>
                <?php if ($heading !== '') : ?>
                    <h2 class="<?php echo esc_attr($front ? 'mt-5 h-display text-3xl text-white first:mt-0 sm:text-5xl' : 'mt-5 h-display text-3xl text-white first:mt-0 sm:text-4xl'); ?>"><?php echo optimum_lift_heading_html($heading); ?></h2>
                <?php endif; ?>
                <?php if ($intro !== '') : ?>
                    <p class="mt-4 text-[15px] leading-relaxed text-zinc-400 first:mt-0"><?php echo esc_html($intro); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($cards !== []) : ?>
            <div class="<?php echo esc_attr(count($cards) > 3 ? 'mt-10 grid gap-5 first:mt-0 md:grid-cols-2 lg:grid-cols-3' : 'mt-10 grid gap-5 first:mt-0 md:grid-cols-3'); ?>">
                <?php foreach ($cards as $i => $card) : ?>
                    <?php $score = optimum_lift_format_rating((float) $card['rating']); ?>
                    <figure<?php echo $card['id'] !== '' ? ' id="' . esc_attr($card['id']) . '"' : ''; ?> class="<?php echo esc_attr($alt ? 'reveal flex flex-col rounded-3xl border border-white/[.08] bg-paper p-6' : 'reveal flex flex-col rounded-3xl border border-white/[.08] bg-surface p-6'); ?>">
                        <span class="olstars self-start text-[15px]" style="--pct:<?php echo esc_attr((string) ($card['rating'] * 20)); ?>%" role="img" aria-label="<?php
                            /* translators: %s: average rating, e.g. 4,7. */
                            echo esc_attr(sprintf(__('%s out of 5 stars', 'optimum-lift'), $score));
                        ?>">★★★★★</span>
                        <blockquote class="mt-3.5 flex-1 text-[13.5px] leading-relaxed text-zinc-300">
                            <p><?php
                                /* translators: %s: a customer's words, shown in quotation marks. */
                                echo esc_html(sprintf(_x('“%s”', 'quotation', 'optimum-lift'), $card['quote']));
                            ?></p>
                        </blockquote>
                        <?php if ($card['name'] !== '' || $card['meta'] !== '') : ?>
                            <figcaption class="mt-5 flex items-center gap-3 border-t border-white/[.07] pt-4">
                                <?php $letters = $initials($card['name']); ?>
                                <?php if ($letters !== '') : ?>
                                    <span class="<?php echo esc_attr('grid h-9 w-9 shrink-0 place-items-center rounded-full text-[12px] font-extrabold text-white ' . $avatars[$i % count($avatars)]); ?>" aria-hidden="true"><?php echo esc_html($letters); ?></span>
                                <?php endif; ?>
                                <span class="min-w-0">
                                    <?php if ($card['name'] !== '') : ?>
                                        <span class="block text-[13px] font-extrabold text-white"><?php echo esc_html($card['name']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($card['meta'] !== '') : ?>
                                        <span class="block text-[11px] text-zinc-500"><?php echo esc_html($card['meta']); ?></span>
                                    <?php endif; ?>
                                </span>
                            </figcaption>
                        <?php endif; ?>
                    </figure>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($form && $product !== null) : ?>
            <?php
            $field_class = 'mt-2 block w-full rounded-xl border border-white/10 bg-paper px-4 py-3 text-[14px] text-white placeholder:text-zinc-500';
            $label_class = 'block text-[11px] font-extrabold uppercase tracking-wider text-zinc-400';

            $fields = '';
            if (wc_review_ratings_enabled()) {
                $options = [
                    ''  => __('Rate…', 'optimum-lift'),
                    '5' => __('Perfect', 'optimum-lift'),
                    '4' => __('Good', 'optimum-lift'),
                    '3' => __('Average', 'optimum-lift'),
                    '2' => __('Not that bad', 'optimum-lift'),
                    '1' => __('Very poor', 'optimum-lift'),
                ];

                $fields .= '<p><label for="rating" class="' . esc_attr($label_class) . '">' . esc_html__('Your rating', 'optimum-lift') . '</label>';
                $fields .= '<select name="rating" id="rating" class="' . esc_attr($field_class) . '"' . (wc_review_ratings_required() ? ' required' : '') . '>';
                foreach ($options as $value => $label) {
                    $fields .= '<option value="' . esc_attr((string) $value) . '">' . esc_html($label) . '</option>';
                }
                $fields .= '</select></p>';
            }
            $fields .= '<p><label for="comment" class="' . esc_attr($label_class) . '">' . esc_html__('Your review', 'optimum-lift') . '</label>';
            $fields .= '<textarea id="comment" name="comment" rows="5" required class="' . esc_attr($field_class) . '"></textarea></p>';
            ?>
            <?php if (wp_get_unapproved_comment_author_email() !== '') : ?>
                <p class="mt-8 rounded-2xl border border-acid/25 bg-acid/[.06] px-5 py-4 text-[13.5px] text-zinc-300" role="status"><?php esc_html_e('Thank you. Your review will appear here once it is approved.', 'optimum-lift'); ?></p>
            <?php endif; ?>
            <details class="<?php echo esc_attr($alt ? 'reveal group mt-8 rounded-3xl border border-white/[.08] bg-paper' : 'reveal group mt-8 rounded-3xl border border-white/[.08] bg-surface'); ?>">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 text-[14.5px] font-extrabold text-white [&::-webkit-details-marker]:hidden">
                    <?php esc_html_e('Write a review', 'optimum-lift'); ?>
                    <?php echo optimum_lift_icon('plus', 'w-5 h-5 shrink-0 text-accent-light transition group-open:rotate-45'); ?>
                </summary>
                <div class="px-5 pb-5">
                    <?php
                    comment_form([
                        'title_reply'          => '',
                        'title_reply_before'   => '',
                        'title_reply_after'    => '',
                        'logged_in_as'         => '',
                        'comment_notes_before' => '',
                        'comment_notes_after'  => '',
                        'class_form'           => 'grid gap-4',
                        'comment_field'        => $fields,
                        'submit_field'         => '<p>%1$s %2$s</p>',
                        'submit_button'        => '<button type="submit" name="%1$s" id="%2$s" class="%3$s">%4$s</button>',
                        'class_submit'         => 'btn btn-light btn-md',
                        'label_submit'         => __('Submit review', 'optimum-lift'),
                    ], $product->get_id());
                    ?>
                </div>
            </details>
        <?php endif; ?>
    </div>
</section>
