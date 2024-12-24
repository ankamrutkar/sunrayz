<?php
/**
 * The template for displaying search results pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#search-result
 *
 * @package cryptozfree
 */

get_header();
?>
<main id="primary" class="site-main">
		<section class="post-content_section">
				<div class="container">
					<div class="row">
									<?php if ( have_posts() ) : ?>
										<?php
										/* Start the Loop */
										while ( have_posts() ) :
											the_post();

											/**
											 * Run the loop for the search to output the results.
											 * If you want to overload this in a child theme then include a file
											 * called content-search.php and that will be used instead.
											 */
											get_template_part( 'template-parts/content', 'search' );

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
				</div><!-- Row -->
			</div> <!-- Container -->
		</section>
	</main><!-- #main -->
<?php
get_footer();
