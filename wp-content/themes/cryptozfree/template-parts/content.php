<?php
/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package cryptozfree
 */

?>
<?php if ( !is_single() ) { ?>
	<div class="col-sm-12 col-xs-12 col-xs-12">
<?php } ;?>

<article id="post-<?php the_ID(); ?>" <?php post_class('mb-5'); ?>>

	<div class="post-thumbnail-image <?php if ( is_single() ) : echo 'single-post-thumb'; endif; ?>">
		
		<?php 
		if (has_post_thumbnail() ) {
			the_post_thumbnail('standard-size', array(
				'alt' => the_title_attribute( array(
					'echo' => false,
				) ),
			) );
		} ?>
		
	</div>

	<div class="meta-items">
	<?php
		if ( 'post' === get_post_type() && !is_single() ) :
			?>

		<div class="category_item category_items">
			<?php cryptozfree_post_category(); ?>
		</div>


		<header class="entry-header">
			<?php
				if ( is_singular() ) : null;
				else : the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
				endif;
			?>
		</header>



			<div class="meta entry-meta gridview_meta">
				<span class="author mr-1">
					<?php cryptozfree_posted_by(); ?>
					<span> | </span>
				</span>

				<span class="entry-date">
					<?php cryptozfree_posted_on() ;?>
				</span>
			</div>



		<?php endif; ?>
	</div>




	<?php
		if ( !is_single() && 'post' === get_post_type()) {
			?>
			<?php the_excerpt() ;?>

		<?php }else { ?>


			<?php if ( is_single() ) { ?>

				<div class="meta mb-2">

					<div class="category_items">
						<?php cryptozfree_post_category(); ?>
					</div>

					<span class="author mr-1">
						<?php cryptozfree_posted_by(); ?>
						<span> | </span>
					</span>

					<span class="entry-date">
						<?php cryptozfree_posted_on() ;?>
					</span>
				</div>

				<header class="entry-single-header">
					<div class="entry-title">
						<?php the_title(); ?>
					</div>
				</header>

			<?php } ?>
		<div class="entry-content">
		<?php
		the_content(
			sprintf(
				wp_kses(
					__( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'cryptozfree' ),
					array(
						'span' => array(
							'class' => array(),
						),
					)
				),
				wp_kses_post( get_the_title() )
			)
		);

		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'cryptozfree' ),
				'after'  => '</div>',
			)
		);
		?>
	</div>
	<?php } ;?>

	<footer class="entry-footer d-flex justify-content-between">	
	 	<?php cryptozfree_entry_footer(); ?>

		<?php if ( is_single() ) { ?>
			<span class="entry-tag"><span class="tag">Tags: </span> <?php cryptozfree_entry_tag(); ?></span>
		<?php } ?>


	</footer>



</article>
<?php if ( !is_single() ) { ?>
	</div>
<?php } ;?>


