<?php
/**
 * Displays the site header.
 *
 * @package WordPress
 * @subpackage cryptozfree
 */

$wrapper_classes  = 'site-header';
$wrapper_classes .= has_custom_logo() ? ' has-logo' : '';
$wrapper_classes .= ( true === get_theme_mod( 'display_title_and_tagline', true ) ) ? ' has-title-and-tagline' : '';
$wrapper_classes .= has_nav_menu( 'primary' ) ? ' has-menu' : '';
?>

<header id="masthead" class="<?php echo esc_attr( $wrapper_classes ); ?>" role="banner">

	<?php get_template_part( 'template-parts/header/site-branding' ); ?>
	<?php get_template_part( 'template-parts/header/site-nav' ); ?>

</header><!-- #masthead -->
