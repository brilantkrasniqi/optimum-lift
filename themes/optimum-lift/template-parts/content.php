<?php
/**
 * One entry in a list of posts.
 */
?>
<article <?php post_class('entry entry--card'); ?>>
    <?php if (has_post_thumbnail()) : ?>
        <a class="entry__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
            <?php the_post_thumbnail('medium_large'); ?>
        </a>
    <?php endif; ?>

    <h2 class="entry__title">
        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
    </h2>

    <?php optimum_lift_entry_meta(); ?>

    <div class="entry__excerpt"><?php the_excerpt(); ?></div>
</article>
