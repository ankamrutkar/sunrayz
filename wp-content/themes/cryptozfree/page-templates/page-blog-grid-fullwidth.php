<?php
/**
 * Template Name: Grid FullWidth
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package cryptozfree
 */
get_header();
?>

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


            <section class="sdhj">
                <div class="post-section-gridview">
                    <div class="container">
                        <div class="row justify-content-center">

                            <?php if ( $the_query_article->have_posts() ) : ?>
                            <?php while ( $the_query_article->have_posts() ) : ?>
                                <?php $the_query_article->the_post(); ?>
                                <?php get_template_part( 'template-parts/grid/content', 'gridview' ); ?>
                                <?php endwhile; // end of the loop.?> 
                                <?php else : 
                                            get_template_part( 'template-parts/content', 'none' ); ?>
                                <?php endif; ?>
                                <?php wp_reset_postdata(); ?>
                        </div><!--//row  -->

                             <!-- pagination here -->                     
                            <div class="post-nav">
                                <?php echo paginate_links( array (
                                    'total'              => $the_query_article->max_num_pages,
                                    'prev_text'          => __('<i class="fa fa-long-arrow-left" aria-hidden="true"></i>', 'cryptozfree'),
                                    'next_text'          => __('<i class="fa fa-long-arrow-right" aria-hidden="true"></i>', 'cryptozfree'),
                                )) ; ?>
                            </div>

                    </div><!--//container  -->
                </div><!--//container  -->
            </section>

        </main><!-- #main -->
</div><!-- #primary -->

 
<?php
get_footer();
