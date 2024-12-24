<?php
/**
 * Template part for displaying results in search pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package cryptozfree
 */

?>

<div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 col-xs-12 col-xs-12">
	<article id="post-<?php the_ID(); ?>" <?php post_class('search-article'); ?>>
		<a class="post-thumbnail" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
			<?php 
			if (has_post_thumbnail() ) {
				the_post_thumbnail('standard-size', array(
					'alt' => the_title_attribute( array(
						'echo' => false,
					) ),
				) );
				
			} else {
				echo '<img src="' . get_template_directory_uri() . '/assets/images/placeholder.png" />';
			} ?>
			</a>
		<header class="entry-header">
			<?php the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>

			<?php if ( 'post' === get_post_type() ) : ?>
			<div class="entry-meta">
				<?php
				cryptozfree_posted_on();
				cryptozfree_posted_by();
				?>
			</div><!-- .entry-meta -->
			<?php endif; ?>
		</header><!-- .entry-header -->
		<div class="entry-summary">
			<?php the_excerpt(); ?>
		</div><!-- .entry-summary -->

		<footer class="entry-footer">
			<?php cryptozfree_entry_footer(); ?>
		</footer><!-- .entry-footer -->
	</article>
</div>
