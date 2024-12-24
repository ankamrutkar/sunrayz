<?php
/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package cryptozfree
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class('post-standard mb-5'); ?>>

	<div class="post-thumbnail-image single-post-thumb">
		
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
		 
	</div>

	<div class="post-standard-body-content">

		
			<div class="category_item category_items">
				<?php cryptozfree_post_category(); ?>
			</div>
				
				
			  <div class="meta entry-meta gridview_meta">
				<span class="author mr-1">
					<?php cryptozfree_posted_by(); ?>
					<span> | </span>
				</span>

				<span class="entry-date">
					<?php cryptozfree_posted_on() ;?>
				</span>
			</div>
		
		
		<header class="entry-header">
			<?php
			 the_title( '<h3 class="entry-title title-truncate"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h3>' );

			if ( 'post' === get_post_type() ) :
				?>
			<?php endif; ?>
		</header>

		<?php
		if ( 'post' === get_post_type() ) {
			?>
			<?php the_excerpt() ;?>

		<?php }else { ?>
			<div class="entry-content">
			<?php
			the_content( sprintf(
				wp_kses(
					__( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'cryptozfree' ),
					array(
						'span' => array(
							'class' => array(),
						),
					)
				),
				get_the_title()
			) );

			wp_link_pages( array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'cryptozfree' ),
				'after'  => '</div>',
			) );
			?>
		</div>
		<?php } ;?>
				
		<footer class="entry-footer">
			<?php cryptozfree_entry_footer(); ?>
		</footer>
	</div>
</article>
