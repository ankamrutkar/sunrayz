<?php
/**
 * Template part for displaying Banner Section
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package cryptozfree
 */

?>
<?php
// Banner Background Image 
if ( have_posts() ) :
    $thumb = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'full' );
 endif ;
 ?>
 
<section id="" class="cryptozfree-banner-breadcrumb" style="background: url('<?php echo esc_url ( $thumb['0']) ;?>');">

    <div class="container px-lg-0">
        <div class="row text-left">
            <div class="col-md-10">
            <?php if(function_exists( 'is_woocommerce' ) && is_woocommerce()){ ?>

                <h2 class="page-title"><?php woocommerce_page_title(); ?></h2>

                <?php }  elseif(is_page() || is_single()){ ?>

                <h2 class="page-title"><?php echo esc_html( get_the_title() ); ?></h2>

                <?php } elseif( is_search() ){ ?>

                <h2 class="page-title"><?php printf( __( 'Search Results for: %s', 'cryptozfree' ), '<span>' . get_search_query() . '</span>' ); ?></h2>

                <?php }elseif( is_404() ){ ?>

                <h2 class="page-title"><?php echo esc_html( 'Page Not Found: 404', 'cryptozfree'); ?></h2>

                <?php }elseif( is_home() ){ ?>

                <h2 class="page-title"><?php single_post_title(); ?></h2>

                <?php } 

                else {
                if(is_archive()) {
                    the_archive_title( '<h2 class="page-title">', '</h2>' ); 
                }
                ?>

                <?php if ( is_single() ) { ?>
                    <h2 class="page-title"><?php single_post_title(); ?></h2>
                <?php  }

                }
                ?>
              
              <?php if( function_exists( 'cryptozfree_breadcrumb_trail' ) ){ ?>
                    <div class="cryptozfree-breadcrumb">
                        <?php cryptozfree_breadcrumb_trail(); ?>
                    </div>
                <?php } ;?>
            </div>
        </div>
    </div>
</section>