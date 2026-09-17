<?php
if (optimum_lift_is_checkout_chrome()) {
    get_template_part('template-parts/footer/checkout-footer');
} else {
    get_template_part('template-parts/footer/site-footer');
}

wp_footer();
?>
</body>
</html>
