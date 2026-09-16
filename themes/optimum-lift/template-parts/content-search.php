<?php
/**
 * One search result. Results can be any post type, including a Product.
 */
?>
<article <?php post_class('entry entry--result'); ?>>
    <h2 class="entry__title">
        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
    </h2>

    <p class="entry__type"><?php echo esc_html(get_post_type_object(get_post_type())?->labels->singular_name ?? ''); ?></p>

    <div class="entry__excerpt"><?php the_excerpt(); ?></div>
</article>
