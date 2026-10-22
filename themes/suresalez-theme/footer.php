<?php
/**
 * The footer template for SureSalez Hybrid Theme
 *
 * Displays the visual footer via parts/footers/footer-main.php and closes
 * the #page container, calling wp_footer() before closing tags.
 *
 * @package SureSalez_Theme
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
  <?php get_template_part('parts/footers/footer-main'); ?>
</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>
