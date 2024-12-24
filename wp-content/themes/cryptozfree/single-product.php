<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package cryptozfree
 */

get_header();
?>

	<main id="primary" class="site-main">
		<section class="post-content_section">
				<div class="container">
					<div class="row">
						<?php 	if ( 'sidebar_left' === $sidebar_position ) : ?>
							<div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 order-lg-0 order-1 pt-5 pt-md-4 pt-lg-0">
								<?php get_sidebar(); ?>	
							</div> 
						<?php endif; ?>
						<div class="<?php echo esc_attr( $center_column_width ); ?>">
							
									<?php
									while ( have_posts() ) :
										the_post();

										get_template_part( 'template-parts/content', get_post_type() );


									endwhile; // End of the loop.
									?>
   							
						</div>

						<?php if ( 'sidebar_right' === $sidebar_position ) : ?>
							<div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 order-lg-1 order-1 pt-5 pt-md-4 pt-lg-0">
								<?php get_sidebar(); ?>	
							</div> 
						<?php endif; ?>
				</div><!-- Row -->
			</div> <!-- Container -->
		</section>
	</main><!-- #main -->
<?php
get_footer();
