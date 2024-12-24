<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package cryptozfree
 */

get_header();
?>

	<div id="primary" class="">
		<main id="main" class="site-main">

			<section class="error-404 not-found">
				<div class="container">
					<div class="row justify-content-center align-items-center vh-100">
						<div class="col-md-10">
							<header class="page-header error-404-main" >
								
								<h2 class="page-title"><?php esc_html_e( '404', 'cryptozfree' ); ?></h2>
								<h3 class=""><?php esc_html_e( 'Oops!', 'cryptozfree' ); ?></h3>
								<h4 class="page-title"><?php esc_html_e( ' That page can&rsquo;t be found.', 'cryptozfree' ); ?></h4>
								<a href="<?php echo esc_url( home_url() ) ?>" class="error-btn"><i class="fa fa-angle-left"></i><?php esc_html_e( 'Go Home', 'cryptozfree' ); ?></a>
							</header><!-- .page-header -->
							
							<div class="page-content">
							</div><!-- .page-content -->

						</div>
					</div>
				</div>
			</section><!-- .error-404 -->

		</main><!-- #main -->
	</div><!-- #primary -->


<?php
get_footer();
