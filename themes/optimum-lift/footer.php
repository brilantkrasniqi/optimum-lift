<footer class="site-footer">
    <div class="site-footer__inner">
        <?php
        if (is_active_sidebar('footer')) {
            echo '<div class="site-footer__widgets">';
            dynamic_sidebar('footer');
            echo '</div>';
        }

        if (has_nav_menu('footer')) {
            wp_nav_menu([
                'theme_location'  => 'footer',
                'container'       => 'nav',
                'container_class' => 'site-footer__nav',
                'depth'           => 1,
            ]);
        }
        ?>

        <p class="site-footer__legal">
            <?php
            printf(
                /* translators: 1: year, 2: site name. */
                esc_html__('&copy; %1$s %2$s', 'optimum-lift'),
                esc_html(date_i18n('Y')),
                esc_html(get_bloginfo('name'))
            );
            ?>
        </p>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
