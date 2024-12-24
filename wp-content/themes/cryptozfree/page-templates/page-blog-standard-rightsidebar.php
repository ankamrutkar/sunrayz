<?php
/**
 * Template Name: Standard Right SideBar
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package cryptozfree
 */
get_header();
?>

<!-- Banner Background Image -->
<?php if ( have_posts() ) : ?>
<?php $thumb = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'full' );?>
<?php endif ;?>

    <div id="primary" class="content-area ">
		<main id="main" class="site-main">

            <?php 
            // The Query
            $current_page = get_query_var( 'paged' );
            $limit_post = get_option('posts_per_page');
                $the_query_article = new WP_Query( array(
                'post_type' => 'post',    
                'posts_per_page' => $limit_post,  
                'paged' => $current_page,

            ) ); ?>

            <section>
                <div class="post-section-standardview">
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-8 col-md-7 col-sm-12 order-lg-0 order-0 standard-title">
                               
                                    <?php if ( $the_query_article->have_posts() ) : ?>
                                    <?php while ( $the_query_article->have_posts() ) : ?>
                                        <?php $the_query_article->the_post(); ?>
                                        <?php get_template_part( 'template-parts/standard/content', 'standard'  ); ?>
                                        <?php endwhile; // end of the loop.?> 
                                        <?php else : 
                                                    get_template_part( 'template-parts/content', 'none' ); ?>
                                        <?php endif; ?>
                                        <?php wp_reset_postdata(); ?>
                                  
                                </div>
                                <div class="col-xl-4 col-lg-4 col-md-5 col-sm-12 order-lg-1 order-1 pt-5 pt-md-4 pt-lg-0">
                                    <?php get_sidebar(); ?>
                                </div> 
                        </div><!--//row  -->

                             <!-- pagination here -->                     
                             <div class="post-nav">
                                <?php echo paginate_links( array (
                                    'total'              => $the_query_article->max_num_pages,
                                    'prev_text'          => __('<i class="fa fa-angle-double-left"></i>', 'cryptozfree'),
                                    'next_text'          => __('<i class="fa fa-angle-double-right"></i>', 'cryptozfree'),
                                )) ; ?>
                            </div>

                    </div><!--//container  -->
                </div><!--//container  -->
            </section>


        </main><!-- #main -->
    </div><!-- #primary -->

 
<?php
get_footer();
