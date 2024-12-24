<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package cryptozfree
 */

get_header();
?>
<?php 
	$sidebar_position = cryptozfree_get_sidebar();

	if ( 'sidebar_right' === $sidebar_position || 'sidebar_left' === $sidebar_position ) :
		$center_column_width = 'col-lg-8 col-md-12 col-sm-12 order-lg-0 order-0 sidebarleft';
	else :
		$center_column_width = 'col-lg-12 order-lg-0 order-0 full__width';
	endif;
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
						<div class="layout-full">
							<div class="row">

								<?php					
								if ( have_posts() ) :

									/* Start the Loop */
									while ( have_posts() ) :
										the_post();

										/*
										* Include the Post-Type-specific template for the content.
										* If you want to override this in a child theme, then include a file
										* called content-___.php (where ___ is the Post Type name) and that will be used instead.
										*/
										get_template_part( 'template-parts/content', get_post_type() );

									endwhile;

									the_posts_pagination( array(
										'prev_text'          => __( 'Previous page', 'cm' ),
										'next_text'          => __( 'Next page', 'cm' ),
										'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'cm' ) . ' </span>',
									) );
	
								else :

									get_template_part( 'template-parts/content', 'none' );

								endif;
								?>
							</div>
						</div>
					</div>

					<?php if ( 'sidebar_right' === $sidebar_position ) : ?>
						<div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 order-lg-1 order-1 pt-5 pt-md-4 pt-lg-0 sidebarright">
							<?php get_sidebar(); ?>	
						</div> 
					<?php endif; ?>
				
				</div><!-- Row -->
			</div> <!-- Container -->
		</section>
	</main><!-- #main -->

<?php
get_footer();
