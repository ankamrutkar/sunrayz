<?php
/**
 * Template part for displaying a post in a grid view
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package cryptozfree
 */

?>

    <div class="col-md-6 col-lg-4 pb-5">    
        <article id="post-<?php the_ID(); ?>" <?php post_class('post-grid post-grid-widthout-sidebar'); ?> >

            <div class="gridview-post-img">
                <a class="post-thumbnail" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
                    <?php
                    if (has_post_thumbnail() ) {
                        the_post_thumbnail('grid-size', array(
                            'alt' => the_title_attribute( array(
                                'echo' => false,
                            ) ),
                        ) );
                    } else {
                        echo '<img src="' . get_template_directory_uri() . '/assets/images/placeholder.png" />';
                    }
                ?>
                </a>
                </div>

            <div class="article-inner-content">
				
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
				
				


                <?php the_title( '<h3 class="article-title title-truncate"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h3>' ); ?>
                   
                    <?php the_excerpt() ;?>
            </div>
            
            <div class="article-footer d-flex justify-content-between align-items-center">     
            <?php $read_more = get_theme_mod( 'cryptozfree_language_readmore_setting', 'Read More' );?>                                                                            
                <a href="<?php the_permalink(); ?>" class="read-more"><?php echo esc_html( $read_more ); ?><span class="icon-align-right">
                <i class="fa fa-angle-double-right" aria-hidden="true"></i></span>
                </a>
                <footer class="entry-footer gridview_edited">
			        <?php cryptozfree_post_edit(); ?>
		        </footer><!-- .entry-footer -->
            </div>
            
        </article>
    </div><!--//column  -->
