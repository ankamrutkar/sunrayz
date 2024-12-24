<?php
/**
 * Customizer Separator Control settings for this theme.
 *
 * @package cryptozfree
 * @since 1.0.0
 */

if ( class_exists( 'WP_Customize_Control' ) ) :

	if ( ! class_exists( 'cryptozfree_customizer_Custom_Control' ) ) :

		class cryptozfree_customizer_Custom_Control extends WP_Customize_Control {
            
            public function __construct()
            {
                // Register our sections
                add_action('customize_register', array($this, 'cryptozfree_add_customizer_sections'));
            }

            //==================== breadcrumb  ===========================

            public function cryptozfree_add_customizer_sections($wp_customize) {
                    // Move customizer default settings
                $wp_customize->add_section('title_tagline', array(
                    'title'    => esc_html__('Logo', 'cryptozfree'),
                    'priority' => 20,
                   
                ));

                $wp_customize->add_section('preloader_section', array(
                    'title'    => esc_html__('Preloader', 'cryptozfree'),
                    'priority' => 40,
                  
                ));

                $wp_customize->add_section('cryptozfree_colors', array(
                    'title'    => esc_html__('Colors', 'cryptozfree'),
                    'priority' => 50,
                   
                ));

                $wp_customize->add_section('cryptozfree_header_section', array(
                    'title'    => esc_html__('Header Template', 'cryptozfree'),
                    'priority' => 60,
                   
                ));

                $wp_customize->add_section('cryptozfree_footer_section', array(
                    'title'    => esc_html__('Footer Template', 'cryptozfree'),
                    'priority' => 70,
                    
                ));
                
                $wp_customize->add_section('cryptozfree_layout_section', array(
                    'title'    => esc_html__('Layout', 'cryptozfree'),
                    'priority' => 80,
                    
                ));

                $wp_customize->add_section('page_title_section', array(
                    'title'    => esc_html__('breadcrumb', 'cryptozfree'),
                    'priority' => 90,
                  
                ));

                $wp_customize->add_section('cryptozfree_language_section', array(
                    'title'    => esc_html__('Language', 'cryptozfree'),
                    'priority' => 110,
                    
                ));

                if (file_exists(get_template_directory() . '/inc/class/customizer-settings/preloader-setting.php')) {
                    require_once get_template_directory() . '/inc/class/customizer-settings/preloader-setting.php';
                }

                if (file_exists(get_template_directory() . '/inc/class/customizer-settings/pagetitle-setting.php')) {
                    require_once get_template_directory() . '/inc/class/customizer-settings/pagetitle-setting.php';
                }

                if (file_exists(get_template_directory() . '/inc/class/customizer-settings/header-setting.php')) {
                    require_once get_template_directory() . '/inc/class/customizer-settings/header-setting.php';
                }

                if (file_exists(get_template_directory() . '/inc/class/customizer-settings/footer-setting.php')) {
                    require_once get_template_directory() . '/inc/class/customizer-settings/footer-setting.php';
                }

                if (file_exists(get_template_directory() . '/inc/class/customizer-settings/layout-setting.php')) {
                    require_once get_template_directory() . '/inc/class/customizer-settings/layout-setting.php';
                }

                // if (file_exists(get_template_directory() . '/inc/class/customizer-settings/color-setting.php')) {
                //     require_once get_template_directory() . '/inc/class/customizer-settings/color-setting.php';
                // }

                if (file_exists(get_template_directory() . '/inc/class/customizer-settings/language-setting.php')) {
                    require_once get_template_directory() . '/inc/class/customizer-settings/language-setting.php';
                }

                // if (file_exists(get_template_directory() . '/inc/class/customizer-settings/class-control-color-palette.php')) {
                //     require_once get_template_directory() . '/inc/class/customizer-settings/class-control-color-palette.php';
                // }

            }
        }
    endif;



/**
* Initialise our Customizer settings
*/
$cryptozfree_customizer_settings = new cryptozfree_customizer_Custom_Control();
endif;

