<?php
/**
 * This file represents an example of the code that themes would use to register
 * the required plugins.
 *
 * It is expected that theme authors would copy and paste this code into their
 * functions.php file, and amend to suit.
 *
 * @see http://tgmpluginactivation.com/configuration/ for detailed documentation.
 *
 * @package    TGM-Plugin-Activation
 * @subpackage Example
 * @version    2.6.1 for parent theme cryptozfree for publication on ThemeForest
 * @author     Thomas Griffin, Gary Jones, Juliette Reinders Folmer
 * @copyright  Copyright (c) 2011, Thomas Griffin
 * @license    http://opensource.org/licenses/gpl-2.0.php GPL v2 or later
 * @link       https://github.com/TGMPA/TGM-Plugin-Activation
 */

/**
 * Include the TGM_Plugin_Activation class.
 */
require_once get_template_directory() . '/inc/tgmpa/class-tgm-plugin-activation.php';

add_action( 'tgmpa_register', 'cryptozfree_required_plugins' );

/**
 * Register the required plugins for this theme.
 */
function cryptozfree_required_plugins() {
	/*
	 * Array of plugin arrays. Required keys are name and slug.
	 * If the source is NOT from the .org repo, then source is also required.
	 */
	$plugins = array(

		// Elementor
		array(
			'name'      => esc_html__( 'Elementor Page Builder', 'cryptozfree' ),
			'slug'      => 'elementor',
			'required'  => true,
			'version'   => '2.9.13',
			'force_activation'   => true,
		),
		
		// Demo Import
		array(
			'name'      => esc_html__( 'One Click Demo Import', 'cryptozfree' ),
			'slug'      => 'one-click-demo-import',
			'required'  => true,
			'force_activation'   => true,
		),

		// Contact Form 7
		array(
			'name'      => esc_html__( 'Contact Form 7', 'cryptozfree' ),
			'slug'      => 'contact-form-7',
			'required'  => true,
			'force_activation'   => true,
		),

		// Safe SVG
		array(
			'name'      => esc_html__( 'Safe SVG', 'cryptozfree' ),
			'slug'      => 'safe-svg',
			'required'  => true,
			'force_activation'   => true,
		),

	);

	/*
	 * Array of configuration settings. Amend each line as needed.
	 *
	 * TGMPA will start providing localized text strings soon. If you already have translations of our standard
	 * strings available, please help us make TGMPA even better by giving us access to these translations or by
	 * sending in a pull-request with .po file(s) with the translations.
	 *
	 * Only uncomment the strings in the config array if you want to customize the strings.
	 */
	$config = array(
		'id'           => 'cryptozfree',
		'default_path' => '',                      
		'menu'         => 'tgmpa-install-plugins', 
		'has_notices'  => true,                    
		'dismissable'  => true,                    
		'dismiss_msg'  => '',                    
		'is_automatic' => false,                
		'message'      => '',                    
		
		'strings'      => array(
			'page_title'                      => __( 'Install Required Plugins', 'cryptozfree' ),
			'menu_title'                      => __( 'Install Plugins', 'cryptozfree' ),
			
			'installing'                      => __( 'Installing Plugin: %s', 'cryptozfree' ),
			
			'updating'                        => __( 'Updating Plugin: %s', 'cryptozfree' ),
			'oops'                            => __( 'Something went wrong with the plugin API.', 'cryptozfree' ),
			'notice_can_install_required'     => _n_noop(
				
				'This theme requires the following plugin: %1$s.',
				'This theme requires the following plugins: %1$s.',
				'cryptozfree'
			),
			'notice_can_install_recommended'  => _n_noop(
			
				'This theme recommends the following plugin: %1$s.',
				'This theme recommends the following plugins: %1$s.',
				'cryptozfree'
			),
			'notice_ask_to_update'            => _n_noop(
			
				'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.',
				'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.',
				'cryptozfree'
			),
			'notice_ask_to_update_maybe'      => _n_noop(
			
				'There is an update available for: %1$s.',
				'There are updates available for the following plugins: %1$s.',
				'cryptozfree'
			),
			'notice_can_activate_required'    => _n_noop(
				
				'The following required plugin is currently inactive: %1$s.',
				'The following required plugins are currently inactive: %1$s.',
				'cryptozfree'
			),
			'notice_can_activate_recommended' => _n_noop(
				
				'The following recommended plugin is currently inactive: %1$s.',
				'The following recommended plugins are currently inactive: %1$s.',
				'cryptozfree'
			),
			'install_link'                    => _n_noop(
				'Begin installing plugin',
				'Begin installing plugins',
				'cryptozfree'
			),
			'update_link' 					  => _n_noop(
				'Begin updating plugin',
				'Begin updating plugins',
				'cryptozfree'
			),
			'activate_link'                   => _n_noop(
				'Begin activating plugin',
				'Begin activating plugins',
				'cryptozfree'
			),
			'return'                          => __( 'Return to Required Plugins Installer', 'cryptozfree' ),
			'plugin_activated'                => __( 'Plugin activated successfully.', 'cryptozfree' ),
			'activated_successfully'          => __( 'The following plugin was activated successfully:', 'cryptozfree' ),
			
			'plugin_already_active'           => __( 'No action taken. Plugin %1$s was already active.', 'cryptozfree' ),
			
			'plugin_needs_higher_version'     => __( 'Plugin not activated. A higher version of %s is needed for this theme. Please update the plugin.', 'cryptozfree' ),
			
			'complete'                        => __( 'All plugins installed and activated successfully. %1$s', 'cryptozfree' ),
			'dismiss'                         => __( 'Dismiss this notice', 'cryptozfree' ),
			'notice_cannot_install_activate'  => __( 'There are one or more required or recommended plugins to install, update or activate.', 'cryptozfree' ),
			'contact_admin'                   => __( 'Please contact the administrator of this site for help.', 'cryptozfree' ),

			'nag_type'                        => '',
		),
	);

	tgmpa( $plugins, $config );
}
