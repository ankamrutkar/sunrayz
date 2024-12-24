<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package cryptozfree
 */

get_header();
?>

	<div id="page" class="site">
		<div id="primary" class="content-area">
			<main id="main" class="site-main">

			<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

				<?php the_content(); ?>

			<?php endwhile; endif; ?>

			</main><!-- #main -->
		</div><!-- #primary -->
			
	</div><!-- #page -->

<?php
get_footer();
