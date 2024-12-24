<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package cryptozfree
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">

    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<!-- ============= Preloader Setting  ================== -->
<?php if ( 1 === get_theme_mod( 'enable_site_preloader', '0' ) ) : 
    get_template_part( 'template-parts/preloader/preloader-section' ); 
endif; ?>

<?php wp_body_open(); ?>
<div id="page" class="site">
    <main><a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'cryptozfree' ); ?></a></main>

    <header id="masthead" class="site-header">
    <?php
    if ( did_action( 'elementor/loaded' ) ) {
        if ( get_theme_mod( 'header' ) == null ) {
            // "No settings in customizer";
            $templates = Elementor\Plugin::$instance->templates_manager->get_source( 'local' )->get_items();

            $has_default_header = false;
            $default_header_id = null;
            foreach ( $templates as $template ) {
                $options[ $template['template_id'] ] = $template['title'] . ' (' . $template['type'] . ')';
                if ( $template['title'] == "header" ) {
                    $has_default_header = true;
                    $default_header_id = $template['template_id'] ;
                }
            }

            if ( $has_default_header == true ) {
                echo Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $default_header_id );
            } else {
                ?>
                <div class="warning-message">
                    <div class="container">
                        <div class="row">
                            <?php
                            echo '<div class="before-import-warning-header"><p>No templates found for header! Create a template from <a class ="before-import-library" href="wp-admin/edit.php?post_type=elementor_library&tabs_group=library" target="_blank">Elementor library</a>. Then select from Customizer Header sections</p></div>';
                            ?>
                        </div>
                    </div>
                </div>
                <?php
            }

        } else {
            // echo "Setting Found in customizer";
            echo Elementor\Plugin::$instance->frontend->get_builder_content_for_display( get_theme_mod( 'header' ) );
        }

    } else {
        ?>
        <div class="warning-message">
            <div class="container">
                <div class="row">
                    <div class="before-import-warning-header" style="width: 100%; text-align: center;">
                        <h1><?php bloginfo('name'); ?></h1>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    ?>
    </header><!-- #masthead -->
    
    
    <!-- ============= breadcrumb Setting  ================== -->
    
    <?php if( !is_front_page() ):  ?>
        <?php if ( 1 === get_theme_mod( 'enable_site_page_title', '0' ) ) : 
            get_template_part( 'template-parts/pagetitle/page-title-section' );
        endif; ?>
    <?php endif ;?>


