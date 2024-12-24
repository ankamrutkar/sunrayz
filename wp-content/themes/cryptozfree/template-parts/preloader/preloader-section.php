<?php
/**
 * Template part for displaying Preloader
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package cryptozfree
 */

?>

<?php $preloader = get_theme_mod( 'enable_site_preloader_for_home_page' ); ?>
	<?php if ( 'all' === $preloader ) { ?> 
			<div id="preloader" class="smooth-loader-wrapper">
				<div class="loader">
					<img src="<?php echo esc_url( get_theme_mod( 'cryptozfree_preloader_image_setting', get_template_directory_uri() . '/assets/images/preloader.gif' ) ) ; ?>" alt="<?php echo 'Loading...' ?>">
				</div>
			</div>

		<?php } else{ ?>
			<?php if ( is_front_page() ) :?>
			<div id="preloader" class="smooth-loader-wrapper">
				<div class="loader">
					<img src="<?php echo esc_url( get_theme_mod( 'cryptozfree_preloader_image_setting', get_template_directory_uri() . '/assets/images/preloader.gif' ) ) ; ?>" alt="<?php echo 'Loading...' ?>">
				</div>
			</div>
			<?php endif; ?>
	<?php } ;?>