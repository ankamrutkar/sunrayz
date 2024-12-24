<?php
/**
 * The template for displaying all single portfolio
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-portfolio
 *
 * @package cryptozfree
 */

get_header();
?>
<?php 
	$sidebar_position = cryptozfree_get_sidebar();
	
	if ( 'sidebar_right' === $sidebar_position || 'sidebar_left' === $sidebar_position ) :
		$center_column_width = 'col-lg-12 col-md-12 col-sm-12 order-lg-0 order-0';
	else :
		$center_column_width = 'col-lg-12 order-lg-0 order-0 full__width';
	endif;
?>

	<main id="primary" class="site-main">
		<section class="post-content_section">
				<div class="container">
					<div class="row">

						<div class="<?php echo esc_attr( $center_column_width ); ?>">
							
									<?php
									while ( have_posts() ) :
										the_post();

										get_template_part( 'template-parts/content', get_post_type() );

									endwhile; // End of the loop.
									?> 
   							
						</div>

				</div><!-- Row -->
			</div> <!-- Container -->
		</section>
	</main><!-- #main -->
<?php
get_footer();
