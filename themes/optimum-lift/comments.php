<?php

/**
 * Comments on a post or page: the list, its pagination and the form.
 *
 * The list has no avatars: a Gravatar image sends a hash of each commenter's
 * email to a third party on every view, and the theme self-hosts everything
 * for the same reason. WordPress's comment markup is styled in pages.css.
 */

declare(strict_types=1);

if (post_password_required()) {
    return;
}

$count = (int) get_comments_number();
?>
<section id="comments" class="mt-14 border-t border-white/[.07] pt-10">
    <?php if (have_comments()) : ?>
        <h2 class="h-display text-2xl text-white sm:text-3xl">
            <?php
            /* translators: %s: number of comments. */
            echo esc_html(sprintf(_n('%s comment', '%s comments', $count, 'optimum-lift'), number_format_i18n($count)));
            ?>
        </h2>

        <ol class="ol-comments mt-6 grid gap-3">
            <?php
            wp_list_comments([
                'style'       => 'ol',
                'format'      => 'html5',
                'short_ping'  => true,
                'avatar_size' => 0,
            ]);
            ?>
        </ol>

        <?php
        the_comments_navigation([
            'prev_text' => __('Older comments', 'optimum-lift'),
            'next_text' => __('Newer comments', 'optimum-lift'),
            'class'     => 'ol-comments-nav',
        ]);
        ?>

        <?php if (!comments_open()) : ?>
            <p class="mt-6 text-[13px] font-semibold text-zinc-500"><?php esc_html_e('Comments are closed.', 'optimum-lift'); ?></p>
        <?php endif; ?>
    <?php endif; ?>

    <?php
    comment_form([
        'format'             => 'html5',
        'class_container'    => 'ol-comment-respond',
        'class_form'         => 'ol-comment-form mt-5 grid gap-4',
        'title_reply_before' => '<h2 id="reply-title" class="comment-reply-title h-display text-2xl text-white sm:text-3xl">',
        'title_reply_after'  => '</h2>',
        'class_submit'       => 'btn btn-primary btn-md min-h-12',
        'submit_button'      => '<button name="%1$s" type="submit" id="%2$s" class="%3$s">%4$s</button>',
        'submit_field'       => '<p class="form-submit">%1$s %2$s</p>',
    ]);
    ?>
</section>
