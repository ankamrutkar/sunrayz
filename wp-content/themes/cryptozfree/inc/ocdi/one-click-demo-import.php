<?php


  function ocdi_import_files()
  {
    return array(
      array(
        'import_file_name' => 'cryptozfree Import',
        'import_file_url' => 'https://ocdi.iamabdus.com/cryptozfree/cryptozfree-theme.xml',
        'import_widget_file_url' => 'https://ocdi.iamabdus.com/cryptozfree/cryptozfree-widgets.wie',
        'import_customizer_file_url' => 'https://ocdi.iamabdus.com/cryptozfree/cryptozfree-export.dat',
        'import_preview_image_url'   => 'https://ocdi.iamabdus.com/cryptozfree/preview.png',
        'preview_url' => 'https://cryptoz.iamabdus.com/free/',
        'import_notice'              => __( 'After you import this demo, you will have to setup Customizer.', 'cryptozfree' ),
      ),
    );
  }
  add_filter('ocdi/import_files', 'ocdi_import_files');
  
  
  function ocdi_after_import_setup() {
    // Assign menus to their locations.
    $main_menu = get_term_by( 'name', 'Main Menu', 'nav_menu' );
    $footer_menu = get_term_by( 'name', 'Footer Menu', 'nav_menu' );
  
    set_theme_mod( 'nav_menu_locations', [
            'main-1' => $main_menu->term_id,
            'menu-2' => $footer_menu->term_id,
        ]
    );
  
    // Assign front page and posts page (blog page).
    $front_page_id = get_page_by_title( 'Home' );
    $blog_page_id  = get_page_by_title( 'Blog Grid Without Sidebar' );
  
    update_option( 'show_on_front', 'page' );
    update_option( 'page_on_front', $front_page_id->ID );
    update_option( 'page_for_posts', $blog_page_id->ID );
  
  }
  add_action( 'ocdi/after_import', 'ocdi_after_import_setup' );
  
  
  /**
   * After import run elementor stuff.
   */
  function cryptozfree_elementor_after_import( $selected_palette ) {
    // If elementor make sure we set things up and clear cache.
      if ( class_exists( 'Elementor\Plugin' ) ) {
        if ( class_exists( 'cryptozfree\Theme' ) ) {
          $component = \cryptozfree\Theme::instance()->components['elementor'];
          if ( $component ) {
            $component->elementor_add_theme_colors();
          }
        }
        
        \Elementor\Plugin::instance()->files_manager->clear_cache();
      }
    }
    add_action( 'ocdi/after_import', 'cryptozfree_elementor_after_import' ); 
  

/**
 * Replace demo URLs
 */
function replace_demo_urls_after_import() {
	// Get the current site URL dynamically using home_url()
	$new_url = home_url();
	$old_url = 'https://cryptoz.iamabdus.com/free'; // ##### CHANGE URL #####

	// Use Elementor Utils class to update URLs
	if (class_exists('Elementor\Utils')) {
			Elementor\Utils::replace_urls($old_url, $new_url);
	}

	// Replace menu URLs
	$locations = get_nav_menu_locations(); // Get all menu locations

	foreach ($locations as $location => $menu_id) {
			$menu_items = wp_get_nav_menu_items($menu_id);

			if (is_array($menu_items)) {
					foreach ($menu_items as $key => $item) {
							// Replace the initial part of URL if it's a custom link type
							if ($item->type === 'custom') {
									if (strpos($item->url, $old_url) === 0) {
											// Update URL
											$new_menu_url = str_replace($old_url, $new_url, $item->url);
											// Update the menu item object
											update_post_meta($item->ID, '_menu_item_url', esc_url_raw($new_menu_url));
									}
							}
					}
			}
	}
}

add_action('ocdi/after_import', 'replace_demo_urls_after_import');