<?php
/**
 * cryptozfree\Styles\Component class
 *
 * @package cryptozfree
 */

namespace cryptozfree\Styles;

use cryptozfree_Blocks_Frontend;
use cryptozfree\Component_Interface;
use cryptozfree\Templating_Component_Interface;
use cryptozfree\cryptozfree_CSS;
use LearnDash_Settings_Section;
use function cryptozfree\cryptozfree;
use function cryptozfree\get_webfont_url;
use function cryptozfree\print_webfont_preload;
use function add_action;
use function add_filter;
use function wp_enqueue_style;
use function wp_register_style;
use function wp_style_add_data;
use function get_theme_file_uri;
use function get_theme_file_path;
use function wp_styles;
use function esc_attr;
use function esc_url;
use function wp_style_is;
use function _doing_it_wrong;
use function wp_print_styles;
use function post_password_required;
use function is_singular;
use function comments_open;
use function get_comments_number;
use function apply_filters;
use function add_query_arg;
use function wp_add_inline_style;

/**
 * Class for managing stylesheets.
 *
 * Exposes template tags:
 * * `cryptozfree()->print_styles()`
 */
class Component implements Component_Interface, Templating_Component_Interface {

	/**
	 * Associative array of CSS files, as $handle => $data pairs.
	 * $data must be an array with keys 'file' (file path relative to 'assets/css' directory), and optionally 'global'
	 * (whether the file should immediately be enqueued instead of just being registered) and 'preload_callback'
	 * (callback function determining whether the file should be preloaded for the current request).
	 *
	 * Do not access this property directly, instead use the `get_css_files()` method.
	 *
	 * @var array
	 */
	protected $css_files;

	/**
	 * Associative array of Google Fonts to load.
	 *
	 * Do not access this property directly, instead use the `get_google_fonts()` method.
	 *
	 * @var array
	 */
	protected static $google_fonts = array();

	/**
	 * String of css based on options.
	 *
	 * @var string
	 */
	protected $dynamic_css = null;

	/**
	 * Gets the unique identifier for the theme component.
	 *
	 * @return string Component slug.
	 */
	public function get_slug() : string {
		return 'styles';
	}

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 */
	public function initialize() {
		add_action( 'wp_enqueue_scripts', array( $this, 'action_enqueue_styles' ) );
		//add_action( 'wp_enqueue_scripts', array( $this, 'action_enqueue_fonts' ), 90 );
		add_action( 'wp_head', array( $this, 'action_preload_styles' ) );
		add_action( 'after_setup_theme', array( $this, 'action_add_editor_styles' ) );
		add_filter( 'wp_resource_hints', array( $this, 'filter_resource_hints' ), 10, 2 );
		add_filter( 'cryptozfree_dynamic_css', array( $this, 'dynamic_css' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'action_add_gutenberg_styles' ), 90 );
		add_filter( 'tiny_mce_before_init', array( $this, 'filter_add_tinymce_editor_styles' ) );
		add_filter( 'cryptozfree_editor_dynamic_css', array( $this, 'editor_dynamic_css' ) );
		add_action( 'wp_head', array( $this, 'frontend_gfonts' ), 89 );
	}

	/**
	 * Gets template tags to expose as methods on the Template_Tags class instance, accessible through `cryptozfree()`.
	 *
	 * @return array Associative array of $method_name => $callback_info pairs. Each $callback_info must either be
	 *               a callable or an array with key 'callable'. This approach is used to reserve the possibility of
	 *               adding support for further arguments in the future.
	 */
	public function template_tags() : array {
		return array(
			'print_styles' => array( $this, 'print_styles' ),
		);
	}
	/**
	 * Registers or enqueues stylesheets.
	 *
	 * Stylesheets that are global are enqueued. All other stylesheets are only registered, to be enqueued later.
	 */
	public function action_enqueue_styles() {
		$css_uri = get_theme_file_uri( '/assets/css/' );
		$css_dir = get_theme_file_path( '/assets/css/' );

		$preloading_styles_enabled = $this->preloading_styles_enabled();

		$css_files = $this->get_css_files();
		foreach ( $css_files as $handle => $data ) {
			$src     = $css_uri . $data['file'];
			$version = cryptozfree()->get_asset_version( $css_dir . $data['file'] );

			/*
			 * Enqueue global stylesheets immediately and register the other ones for later use
			 * (unless preloading stylesheets is disabled, in which case stylesheets should be immediately
			 * enqueued based on whether they are necessary for the page content).
			 */
			if ( $data['global'] || ! $preloading_styles_enabled && is_callable( $data['preload_callback'] ) && call_user_func( $data['preload_callback'] ) ) {
				wp_enqueue_style( $handle, $src, array(), $version, $data['media'] );
			} else {
				wp_register_style( $handle, $src, array(), $version, $data['media'] );
			}

			wp_style_add_data( $handle, 'precache', true );
		}

		// Inline Dynamic CSS.
		wp_add_inline_style( 'cryptozfree-global', trim( apply_filters( 'cryptozfree_dynamic_css', '' ) ) );

		// // Enqueue Google Fonts.
		// $google_fonts_url = $this->get_google_fonts_url();
		// if ( ! empty( $google_fonts_url ) ) {
		// 	wp_enqueue_style( 'cryptozfree-fonts', $google_fonts_url, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		// }
	}
	/**
	 * Enqueue Frontend Fonts
	 */
	public function frontend_gfonts() {
		if ( empty( self::$google_fonts ) ) {
			return;
		}
		if ( class_exists( 'cryptozfree_Blocks_Frontend' ) ) {
			$ktblocks_instance = cryptozfree_Blocks_Frontend::get_instance();
			foreach ( $ktblocks_instance::$gfonts as $key => $font ) {
				if ( ! array_key_exists( $key, self::$google_fonts ) ) {
					$add_font = array(
						'fontfamily'   => $font['fontfamily'],
						'fontvariants' => ( isset( $font['fontvariants'] ) && ! empty( $font['fontvariants'] ) && is_array( $font['fontvariants'] ) ? $font['fontvariants'] : array() ),
						'fontsubsets'  => ( isset( $font['fontsubsets'] ) && ! empty( $font['fontsubsets'] ) && is_array( $font['fontsubsets'] ) ? $font['fontsubsets'] : array() ),
					);
					self::$google_fonts[ $key ] = $add_font;
				} else {
					foreach ( $font['fontvariants'] as $variant ) {
						if ( ! in_array( $variant, self::$google_fonts[ $key ]['fontvariants'], true ) ) {
							array_push( self::$google_fonts[ $key ]['fontvariants'], $variant );
						}
					}
					foreach ( $font['fontsubsets'] as $variant ) {
						if ( ! in_array( $variant, self::$google_fonts[ $key ]['fontsubsets'], true ) ) {
							array_push( self::$google_fonts[ $key ]['fontsubsets'], $variant );
						}
					}
				}
			}
			add_filter( 'cryptozfree_blocks_print_google_fonts', '__return_false' );
			// foreach ( self::$google_fonts as $key => $font ) {
			// 	if ( ! array_key_exists( $key, $ktblocks_instance::$gfonts ) ) {
			// 		$add_font = array(
			// 			'fontfamily'   => $font['fontfamily'],
			// 			'fontvariants' => ( isset( $font['fontvariants'] ) && ! empty( $font['fontvariants'] ) && is_array( $font['fontvariants'] ) ? $font['fontvariants'] : array() ),
			// 			'fontsubsets'  => ( isset( $font['fontsubsets'] ) && ! empty( $font['fontsubsets'] ) && is_array( $font['fontsubsets'] ) ? $font['fontsubsets'] : array() ),
			// 		);
			// 		$ktblocks_instance::$gfonts[ $key ] = $add_font;
			// 	} else {
			// 		foreach ( $font['fontvariants'] as $variant ) {
			// 			if ( ! in_array( $variant, $ktblocks_instance::$gfonts[ $key ]['fontvariants'], true ) ) {
			// 				array_push( $ktblocks_instance::$gfonts[ $key ]['fontvariants'], $variant );
			// 			}
			// 		}
			// 	}
			// }
			$this->action_enqueue_fonts();
		} else {
			$this->action_enqueue_fonts();
		}
	}

	/**
	 * Registers or enqueues google fonts.
	 */
	public function action_enqueue_fonts() {
		// Enqueue Google Fonts.
		$google_fonts_url = $this->get_google_fonts_url();
		if ( ! empty( $google_fonts_url ) ) {
			if ( cryptozfree()->option( 'load_fonts_local' ) ) {
				if ( cryptozfree()->option( 'preload_fonts_local' ) && apply_filters( 'cryptozfree_local_fonts_preload', true ) ) {
					print_webfont_preload( $google_fonts_url );
				}
				wp_register_style(
					'cryptozfree-fonts',
					get_webfont_url( $google_fonts_url ),
					array(),
					VERSION
				);
				wp_print_styles( 'cryptozfree-fonts' );
			} else {
				wp_register_style( 'cryptozfree-fonts', $google_fonts_url, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
				wp_print_styles( 'cryptozfree-fonts' );
			}
		}
	}

	/**
	 * Generates the dynamic css based on customizer options.
	 *
	 * @param string $css any custom css.
	 * @return string
	 */
	public function dynamic_css( $css ) {
		$generated_css = $this->generate_base_css();
		if ( ! empty( $generated_css ) ) {
			$css .= "\n/* cryptozfree Base CSS */\n" . $generated_css;
		}
		$generated_dynamic_css = $this->generate_dynamic_css();
		if ( ! empty( $generated_dynamic_css ) ) {
			$css .= "\n/* cryptozfree Dynamic CSS */\n" . $generated_dynamic_css;
		}
		return $css;
	}
	/**
	 * Generates the dynamic css based on page options.
	 *
	 * @return string
	 */
	public function generate_dynamic_css() {
		$css                    = new cryptozfree_CSS();
		$media_query            = array();
		$media_query['mobile']  = apply_filters( 'cryptozfree_mobile_media_query', '(max-width: 767px)' );
		$media_query['tablet']  = apply_filters( 'cryptozfree_tablet_media_query', '(max-width: 1024px)' );
		$media_query['desktop'] = apply_filters( 'cryptozfree_tablet_media_query', '(min-width: 1025px)' );
		// Above Page Title Featured Image.
		if ( is_singular() && has_post_thumbnail() ) {
			$post_type = get_post_type();
			if ( cryptozfree()->option( $post_type . '_title_featured_image' ) ) {
				$image = wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' );
				$css->set_selector( '.content-title-style-above .' . $post_type . '-hero-section .entry-hero-container-inner' );
				$css->add_property( 'background-image', $image[0] );
				$bg_settings = cryptozfree()->sub_option( $post_type . '_title_background', 'desktop' );
				if ( $bg_settings && isset( $bg_settings['image'] ) ) {
					$repeat      = ( isset( $bg_settings['image']['repeat'] ) && ! empty( $bg_settings['image']['repeat'] ) ? $bg_settings['image']['repeat'] : 'no-repeat' );
					$size        = ( isset( $bg_settings['image']['size'] ) && ! empty( $bg_settings['image']['size'] ) ? $bg_settings['image']['size'] : 'cover' );
					$position    = ( isset( $bg_settings['image']['position'] ) && is_array( $bg_settings['image']['position'] ) && isset( $bg_settings['image']['position']['x'] ) && ! empty( $bg_settings['image']['position']['x'] ) && isset( $bg_settings['image']['position']['y'] ) && ! empty( $bg_settings['image']['position']['y'] ) ? ( $bg_settings['image']['position']['x'] * 100 ) . '% ' . ( $bg_settings['image']['position']['y'] * 100 ) . '%' : 'center' );
					$attachement = ( isset( $bg_settings['image']['attachment'] ) && ! empty( $bg_settings['image']['attachment'] ) ? $bg_settings['image']['attachment'] : 'scroll' );
					$css->add_property( 'background-repeat', $repeat );
					$css->add_property( 'background-position', $position );
					$css->add_property( 'background-size', $size );
					$css->add_property( 'background-attachment', $attachement );
				} else {
					$css->add_property( 'background-repeat', 'no-repeat' );
					$css->add_property( 'background-position', 'center center' );
					$css->add_property( 'background-size', 'cover' );
					$css->add_property( 'background-attachment', 'scroll' );
				}
			}
		} elseif ( is_post_type_archive( 'product' ) && class_exists( 'woocommerce' ) && cryptozfree()->option( 'page_title_featured_image' ) ) {
			$post_id = wc_get_page_id( 'shop' );
			if ( has_post_thumbnail( $post_id ) ) {
				$post_type = get_post_type();
				$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'full' );
				$css->set_selector( '.' . $post_type . '-archive-hero-section .entry-hero-container-inner' );
				$css->add_property( 'background-image', $image[0] );
				$bg_settings = cryptozfree()->sub_option( $post_type . '_title_background', 'desktop' );
				if ( $bg_settings && isset( $bg_settings['image'] ) ) {
					$repeat      = ( isset( $bg_settings['image']['repeat'] ) && ! empty( $bg_settings['image']['repeat'] ) ? $bg_settings['image']['repeat'] : 'no-repeat' );
					$size        = ( isset( $bg_settings['image']['size'] ) && ! empty( $bg_settings['image']['size'] ) ? $bg_settings['image']['size'] : 'cover' );
					$position    = ( isset( $bg_settings['image']['position'] ) && is_array( $bg_settings['image']['position'] ) && isset( $bg_settings['image']['position']['x'] ) && ! empty( $bg_settings['image']['position']['x'] ) && isset( $bg_settings['image']['position']['y'] ) && ! empty( $bg_settings['image']['position']['y'] ) ? ( $bg_settings['image']['position']['x'] * 100 ) . '% ' . ( $bg_settings['image']['position']['y'] * 100 ) . '%' : 'center' );
					$attachement = ( isset( $bg_settings['image']['attachment'] ) && ! empty( $bg_settings['image']['attachment'] ) ? $bg_settings['image']['attachment'] : 'scroll' );
					$css->add_property( 'background-repeat', $repeat );
					$css->add_property( 'background-position', $position );
					$css->add_property( 'background-size', $size );
					$css->add_property( 'background-attachment', $attachement );
				} else {
					$css->add_property( 'background-repeat', 'no-repeat' );
					$css->add_property( 'background-position', 'cover' );
					$css->add_property( 'background-size', 'center' );
					$css->add_property( 'background-attachment', 'scroll' );
				}
			}
		} elseif ( is_singular( 'product' ) && 'title' === cryptozfree()->option( 'product_above_layout' ) && has_post_thumbnail() ) {
			$post_type = get_post_type();
			if ( cryptozfree()->option( $post_type . '_title_featured_image' ) ) {
				$image = wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' );
				$css->set_selector( '.' . $post_type . '-hero-section .entry-hero-container-inner' );
				$css->add_property( 'background-image', $image[0] );
				$bg_settings = cryptozfree()->sub_option( $post_type . '_title_background', 'desktop' );
				if ( $bg_settings && isset( $bg_settings['image'] ) ) {
					$repeat      = ( isset( $bg_settings['image']['repeat'] ) && ! empty( $bg_settings['image']['repeat'] ) ? $bg_settings['image']['repeat'] : 'no-repeat' );
					$size        = ( isset( $bg_settings['image']['size'] ) && ! empty( $bg_settings['image']['size'] ) ? $bg_settings['image']['size'] : 'cover' );
					$position    = ( isset( $bg_settings['image']['position'] ) && is_array( $bg_settings['image']['position'] ) && isset( $bg_settings['image']['position']['x'] ) && ! empty( $bg_settings['image']['position']['x'] ) && isset( $bg_settings['image']['position']['y'] ) && ! empty( $bg_settings['image']['position']['y'] ) ? ( $bg_settings['image']['position']['x'] * 100 ) . '% ' . ( $bg_settings['image']['position']['y'] * 100 ) . '%' : 'center' );
					$attachement = ( isset( $bg_settings['image']['attachment'] ) && ! empty( $bg_settings['image']['attachment'] ) ? $bg_settings['image']['attachment'] : 'scroll' );
					$css->add_property( 'background-repeat', $repeat );
					$css->add_property( 'background-position', $position );
					$css->add_property( 'background-size', $size );
					$css->add_property( 'background-attachment', $attachement );
				} else {
					$css->add_property( 'background-repeat', 'no-repeat' );
					$css->add_property( 'background-position', 'center' );
					$css->add_property( 'background-size', 'cover' );
					$css->add_property( 'background-attachment', 'scroll' );
				}
			}
		}
		return $css->css_output();
	}
	/**
	 * Generates the dynamic css based on customizer options.
	 *
	 * @return string
	 */
	public function generate_base_css() {
		$css                    = new cryptozfree_CSS();
		$media_query            = array();
		$media_query['mobile']  = apply_filters( 'cryptozfree_mobile_media_query', '(max-width: 767px)' );
		$media_query['tablet']  = apply_filters( 'cryptozfree_tablet_media_query', '(max-width: 1024px)' );
		$media_query['desktop'] = apply_filters( 'cryptozfree_tablet_media_query', '(min-width: 1025px)' );
		$wide_width_add         = apply_filters(
			'cryptozfree_align_wide_array',
			array(
				'px'  => '230',
				'em'  => '10',
				'rem' => '10',
				'vw'  => '10',
			)
		);
		$n_wide_width_add       = apply_filters(
			'cryptozfree_narrow_width_align_wide_array',
			array(
				'px'  => '260',
				'em'  => '10',
				'rem' => '10',
				'vw'  => '10',
			)
		);

		$max_width_unit        = cryptozfree()->sub_option( 'content_width', 'unit' );
		$max_width             = cryptozfree()->sub_option( 'content_width', 'size' );
		$alignwide_media_query = $max_width + $wide_width_add[ $max_width_unit ];

		$n_max_width_unit        = cryptozfree()->sub_option( 'content_narrow_width', 'unit' );
		$n_max_width             = cryptozfree()->sub_option( 'content_narrow_width', 'size' );
		$n_alignwide_media_query = $n_max_width + $n_wide_width_add[ $n_max_width_unit ];

		$media_query['alignwide']        = '(min-width: ' . $alignwide_media_query . $max_width_unit . ')';
		$media_query['alignwide_narrow'] = '(min-width: ' . $n_alignwide_media_query . $n_max_width_unit . ')';
		// Globals.
		$css->set_selector( ':root' );
		$css->add_property( '--global-palette1', cryptozfree()->palette_option( 'palette1' ) );
		$css->add_property( '--global-palette2', cryptozfree()->palette_option( 'palette2' ) );
		$css->add_property( '--global-palette3', cryptozfree()->palette_option( 'palette3' ) );
		$css->add_property( '--global-palette4', cryptozfree()->palette_option( 'palette4' ) );
		$css->add_property( '--global-palette5', cryptozfree()->palette_option( 'palette5' ) );
		$css->add_property( '--global-palette6', cryptozfree()->palette_option( 'palette6' ) );
		$css->add_property( '--global-palette7', cryptozfree()->palette_option( 'palette7' ) );
		$css->add_property( '--global-palette8', cryptozfree()->palette_option( 'palette8' ) );
		$css->add_property( '--global-palette9', cryptozfree()->palette_option( 'palette9' ) );
		$css->add_property( '--global-palette-highlight', $this->render_color( cryptozfree()->sub_option( 'link_color', 'highlight' ) ) );
		$css->add_property( '--global-palette-highlight-alt', $this->render_color( cryptozfree()->sub_option( 'link_color', 'highlight-alt' ) ) );
		$css->add_property( '--global-palette-highlight-alt2', $this->render_color( cryptozfree()->sub_option( 'link_color', 'highlight-alt2' ) ) );

		$css->add_property( '--global-palette-btn-bg', $this->render_color( cryptozfree()->sub_option( 'buttons_background', 'color' ) ) );
		$css->add_property( '--global-palette-btn-bg-hover', $this->render_color( cryptozfree()->sub_option( 'buttons_background', 'hover' ) ) );

		$css->add_property( '--global-palette-btn', $this->render_color( cryptozfree()->sub_option( 'buttons_color', 'color' ) ) );
		$css->add_property( '--global-palette-btn-hover', $this->render_color( cryptozfree()->sub_option( 'buttons_color', 'hover' ) ) );

		$css->add_property( '--global-body-font-family', $css->render_font_family( cryptozfree()->option( 'base_font' ), '' ) );
		$css->add_property( '--global-heading-font-family', $css->render_font_family( cryptozfree()->option( 'heading_font' ) ) );
		//$css->add_property( '--global-h1-font-family', $css->render_font_family( cryptozfree()->option( 'h1_font' ) ) );
		//$css->add_property( '--global-h2-font-family', $css->render_font_family( cryptozfree()->option( 'h2_font' ) ) );
		//$css->add_property( '--global-h3-font-family', $css->render_font_family( cryptozfree()->option( 'h3_font' ) ) );
		//$css->add_property( '--global-h4-font-family', $css->render_font_family( cryptozfree()->option( 'h4_font' ) ) );
		//$css->add_property( '--global-h5-font-family', $css->render_font_family( cryptozfree()->option( 'h5_font' ) ) );
		$css->add_property( '--global-primary-nav-font-family', $css->render_font_family( cryptozfree()->option( 'primary_navigation_typography' ), '' ) );
		//$css->add_property( '--global-secondary-nav-font-family', $css->render_font_family( cryptozfree()->option( 'secondary_navigation_typography' ) ) );
		//$css->add_property( '--global-site-title-font-family', $css->render_font_family( cryptozfree()->option( 'brand_typography' ) ) );
		//$css->add_property( '--global-site-tag-font-family', $css->render_font_family( cryptozfree()->option( 'brand_tag_typography' ) ) );
		//$css->add_property( '--global-button-font-family', $css->render_font_family( cryptozfree()->option( 'buttons_typography' ) ) );
		$css->add_property( '--global-content-width', cryptozfree()->sub_option( 'content_width', 'size' ) . cryptozfree()->sub_option( 'content_width', 'unit' ) );
		if ( class_exists( '\Elementor\Plugin' ) ) {
			$css->set_selector( ':root body.cryptozfree-elementor-colors' );
			$css->add_property( '--e-global-color-cryptozfree1', 'var(--global-palette1)' );
			$css->add_property( '--e-global-color-cryptozfree2', 'var(--global-palette2)' );
			$css->add_property( '--e-global-color-cryptozfree3', 'var(--global-palette3)' );
			$css->add_property( '--e-global-color-cryptozfree4', 'var(--global-palette4)' );
			$css->add_property( '--e-global-color-cryptozfree5', 'var(--global-palette5)' );
			$css->add_property( '--e-global-color-cryptozfree6', 'var(--global-palette6)' );
			$css->add_property( '--e-global-color-cryptozfree7', 'var(--global-palette7)' );
			$css->add_property( '--e-global-color-cryptozfree8', 'var(--global-palette8)' );
			$css->add_property( '--e-global-color-cryptozfree9', 'var(--global-palette9)' );
		}
		// Divi Editor.
		if ( class_exists( 'ET_Builder_Plugin' ) ) {
			$css->set_selector( 'body.et_divi_builder.et_fb_thin_admin_bar #wrapper' );
			$css->add_property( 'overflow', 'visible' );
			$css->set_selector( '#wrapper.et-fb-iframe-ancestor' );
			$css->add_property( 'overflow', 'visible' );
		}
		// Editor Colors.
		$css->set_selector( ':root .has-theme-palette-1-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette1)' );
		$css->set_selector( ':root .has-theme-palette-1-color' );
		$css->add_property( 'color', 'var(--global-palette1)' );
		$css->set_selector( ':root .has-theme-palette-2-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette2)' );
		$css->set_selector( ':root .has-theme-palette-2-color' );
		$css->add_property( 'color', 'var(--global-palette2)' );

		$css->set_selector( ':root .has-theme-palette-3-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette3)' );
		$css->set_selector( ':root .has-theme-palette-3-color' );
		$css->add_property( 'color', 'var(--global-palette3)' );

		$css->set_selector( ':root .has-theme-palette-4-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette4)' );
		$css->set_selector( ':root .has-theme-palette-4-color' );
		$css->add_property( 'color', 'var(--global-palette4)' );

		$css->set_selector( ':root .has-theme-palette-5-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette5)' );
		$css->set_selector( ':root .has-theme-palette-5-color' );
		$css->add_property( 'color', 'var(--global-palette5)' );

		$css->set_selector( ':root .has-theme-palette-6-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette6)' );
		$css->set_selector( ':root .has-theme-palette-6-color' );
		$css->add_property( 'color', 'var(--global-palette6)' );

		$css->set_selector( ':root .has-theme-palette-7-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette7)' );
		$css->set_selector( ':root .has-theme-palette-7-color' );
		$css->add_property( 'color', 'var(--global-palette7)' );

		$css->set_selector( ':root .has-theme-palette-8-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette8)' );
		$css->set_selector( ':root .has-theme-palette-8-color' );
		$css->add_property( 'color', 'var(--global-palette8)' );

		$css->set_selector( ':root .has-theme-palette-9-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette9)' );
		$css->set_selector( ':root .has-theme-palette-9-color' );
		$css->add_property( 'color', 'var(--global-palette9)' );

		$css->set_selector( ':root .has-theme-palette1-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette1)' );
		$css->set_selector( ':root .has-theme-palette1-color' );
		$css->add_property( 'color', 'var(--global-palette1)' );
		$css->set_selector( ':root .has-theme-palette2-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette2)' );
		$css->set_selector( ':root .has-theme-palette2-color' );
		$css->add_property( 'color', 'var(--global-palette2)' );

		$css->set_selector( ':root .has-theme-palette3-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette3)' );
		$css->set_selector( ':root .has-theme-palette3-color' );
		$css->add_property( 'color', 'var(--global-palette3)' );

		$css->set_selector( ':root .has-theme-palette4-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette4)' );
		$css->set_selector( ':root .has-theme-palette4-color' );
		$css->add_property( 'color', 'var(--global-palette4)' );

		$css->set_selector( ':root .has-theme-palette5-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette5)' );
		$css->set_selector( ':root .has-theme-palette5-color' );
		$css->add_property( 'color', 'var(--global-palette5)' );

		$css->set_selector( ':root .has-theme-palette6-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette6)' );
		$css->set_selector( ':root .has-theme-palette6-color' );
		$css->add_property( 'color', 'var(--global-palette6)' );

		$css->set_selector( ':root .has-theme-palette7-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette7)' );
		$css->set_selector( ':root .has-theme-palette7-color' );
		$css->add_property( 'color', 'var(--global-palette7)' );

		$css->set_selector( ':root .has-theme-palette8-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette8)' );
		$css->set_selector( ':root .has-theme-palette8-color' );
		$css->add_property( 'color', 'var(--global-palette8)' );

		$css->set_selector( ':root .has-theme-palette9-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette9)' );
		$css->set_selector( ':root .has-theme-palette9-color' );
		$css->add_property( 'color', 'var(--global-palette9)' );
		// if ( cryptozfree()->option( 'enable_footer_on_bottom' ) ) {
		// 	$css->set_selector( 'html' );
		// 	$css->add_property( 'min-height', '100%' );
		// }
		$css->set_selector( 'body' );
		$css->render_background( cryptozfree()->sub_option( 'site_background', 'desktop' ), $css );
		$css->set_selector( 'body, input, select, optgroup, textarea' );
		$css->render_font( cryptozfree()->option( 'base_font' ), $css, 'body' );
		$css->set_selector( '.content-bg, body.content-style-unboxed .site' );
		$css->render_background( cryptozfree()->sub_option( 'content_background', 'desktop' ), $css );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( 'body' );
		$css->render_background( cryptozfree()->sub_option( 'site_background', 'tablet' ), $css );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'base_font' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'base_font' ), 'tablet' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'base_font' ), 'tablet' ) );
		$css->set_selector( '.content-bg, body.content-style-unboxed .site' );
		$css->render_background( cryptozfree()->sub_option( 'content_background', 'tablet' ), $css );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( 'body' );
		$css->render_background( cryptozfree()->sub_option( 'site_background', 'mobile' ), $css );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'base_font' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'base_font' ), 'mobile' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'base_font' ), 'mobile' ) );
		$css->set_selector( '.content-bg, body.content-style-unboxed .site' );
		$css->render_background( cryptozfree()->sub_option( 'content_background', 'mobile' ), $css );
		$css->stop_media_query();
		// Header to Mobile Switch.
		if ( cryptozfree()->sub_option( 'header_mobile_switch', 'size' ) ) {
			$css->set_selector( '.site #mobile-header' );
			$css->add_property( 'display', 'block' );
			$css->set_selector( '.site #main-header' );
			$css->add_property( 'display', 'none' );
			$css->start_media_query( '(min-width: ' . cryptozfree()->sub_option( 'header_mobile_switch', 'size' ) . 'px)' );
			$css->set_selector( '.site #mobile-header' );
			$css->add_property( 'display', 'none' );
			$css->set_selector( '.site #main-header' );
			$css->add_property( 'display', 'block' );
			$css->stop_media_query();
		}
		// Heading Fonts.
		$css->set_selector( 'h1,h2,h3,h4,h5,h6' );
		$css->add_property( 'font-family', 'var(--global-heading-font-family)' );
		$css->set_selector( 'h1' );
		$css->render_font( cryptozfree()->option( 'h1_font' ), $css );
		$css->set_selector( 'h2' );
		$css->render_font( cryptozfree()->option( 'h2_font' ), $css );
		$css->set_selector( 'h3' );
		$css->render_font( cryptozfree()->option( 'h3_font' ), $css );
		$css->set_selector( 'h4' );
		$css->render_font( cryptozfree()->option( 'h4_font' ), $css );
		$css->set_selector( 'h5' );
		$css->render_font( cryptozfree()->option( 'h5_font' ), $css );
		$css->set_selector( 'h6' );
		$css->render_font( cryptozfree()->option( 'h6_font' ), $css );
		$css->set_selector( '.site .entry-hero h1' );
		$css->render_font( cryptozfree()->option( 'title_above_font' ), $css );
		$css->set_selector( '.entry-hero .cryptozfree-breadcrumbs, .entry-hero .search-form' );
		$css->render_font( cryptozfree()->option( 'title_above_breadcrumb_font' ), $css );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( 'h1' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'h1_font' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'h1_font' ), 'tablet' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'h1_font' ), 'tablet' ) );
		$css->set_selector( 'h2' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'h2_font' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'h2_font' ), 'tablet' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'h2_font' ), 'tablet' ) );
		$css->set_selector( 'h3' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'h3_font' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'h3_font' ), 'tablet' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'h3_font' ), 'tablet' ) );
		$css->set_selector( 'h4' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'h4_font' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'h4_font' ), 'tablet' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'h4_font' ), 'tablet' ) );
		$css->set_selector( 'h5' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'h5_font' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'h5_font' ), 'tablet' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'h5_font' ), 'tablet' ) );
		$css->set_selector( 'h6' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'h6_font' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'h6_font' ), 'tablet' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'h6_font' ), 'tablet' ) );
		$css->set_selector( '.site .entry-hero h1' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'title_above_font' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'title_above_font' ), 'tablet' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'title_above_font' ), 'tablet' ) );
		$css->set_selector( '.entry-hero .cryptozfree-breadcrumbs' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'title_above_breadcrumb_font' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'title_above_breadcrumb_font' ), 'tablet' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'title_above_breadcrumb_font' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( 'h1' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'h1_font' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'h1_font' ), 'mobile' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'h1_font' ), 'mobile' ) );
		$css->set_selector( 'h2' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'h2_font' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'h2_font' ), 'mobile' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'h2_font' ), 'mobile' ) );
		$css->set_selector( 'h3' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'h3_font' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'h3_font' ), 'mobile' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'h3_font' ), 'mobile' ) );
		$css->set_selector( 'h4' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'h4_font' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'h4_font' ), 'mobile' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'h4_font' ), 'mobile' ) );
		$css->set_selector( 'h5' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'h5_font' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'h5_font' ), 'mobile' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'h5_font' ), 'mobile' ) );
		$css->set_selector( 'h6' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'h6_font' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'h6_font' ), 'mobile' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'h6_font' ), 'mobile' ) );
		$css->set_selector( '.site .entry-hero h1' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'title_above_font' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'title_above_font' ), 'mobile' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'title_above_font' ), 'mobile' ) );
		$css->set_selector( '.entry-hero .cryptozfree-breadcrumbs' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'title_above_breadcrumb_font' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'title_above_breadcrumb_font' ), 'mobile' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'title_above_breadcrumb_font' ), 'mobile' ) );
		$css->stop_media_query();
		// Layout.
		$css->set_selector( '.site-container, .site-header-row-layout-contained, .site-footer-row-layout-contained, .entry-hero-layout-contained, .comments-area' );
		$css->add_property( 'max-width', cryptozfree()->sub_option( 'content_width', 'size' ) . cryptozfree()->sub_option( 'content_width', 'unit' ) );
		$css->set_selector( '.content-width-narrow .content-container.site-container, .content-width-narrow .hero-container.site-container' );
		$css->add_property( 'max-width', cryptozfree()->sub_option( 'content_narrow_width', 'size' ) . cryptozfree()->sub_option( 'content_narrow_width', 'unit' ) );
		$css->start_media_query( $media_query['alignwide'] );
		$css->set_selector( '.content-container  .alignwide' );
		$css->add_property( 'margin-left', '-' . ( $wide_width_add[ $max_width_unit ] / 2 ) . $max_width_unit );
		$css->add_property( 'margin-right', '-' . ( $wide_width_add[ $max_width_unit ] / 2 ) . $max_width_unit );
		$css->add_property( 'width', 'unset' );
		$css->add_property( 'max-width', 'unset' );
		$css->stop_media_query();
		$css->start_media_query( $media_query['alignwide_narrow'] );
		$css->set_selector( '.content-width-narrow .content-container .alignwide' );
		$css->add_property( 'margin-left', '-' . ( $n_wide_width_add[ $n_max_width_unit ] / 2 ) . $n_max_width_unit );
		$css->add_property( 'margin-right', '-' . ( $n_wide_width_add[ $n_max_width_unit ] / 2 ) . $n_max_width_unit );
		$css->add_property( 'width', 'unset' );
		$css->add_property( 'max-width', 'unset' );
		$css->stop_media_query();
		// Wide layout when boxed.
		$css->set_selector( '.content-style-boxed .site .entry-content .alignwide' );
		$css->add_property( 'margin-left', '-' . $this->render_range( cryptozfree()->option( 'boxed_spacing' ), 'desktop' ) );
		$css->add_property( 'margin-right', '-' . $this->render_range( cryptozfree()->option( 'boxed_spacing' ), 'desktop' ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.content-style-boxed .site .entry-content .alignwide' );
		$css->add_property( 'margin-left', '-' . $this->render_range( cryptozfree()->option( 'boxed_spacing' ), 'tablet' ) );
		$css->add_property( 'margin-right', '-' . $this->render_range( cryptozfree()->option( 'boxed_spacing' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.content-style-boxed .site .entry-content .alignwide' );
		$css->add_property( 'margin-left', '-' . $this->render_range( cryptozfree()->option( 'boxed_spacing' ), 'mobile' ) );
		$css->add_property( 'margin-right', '-' . $this->render_range( cryptozfree()->option( 'boxed_spacing' ), 'mobile' ) );
		$css->stop_media_query();
		// Content Spacing.
		$css->set_selector( '.content-area' );
		$css->add_property( 'margin-top', $css->render_range( cryptozfree()->option( 'content_spacing' ), 'desktop' ) );
		$css->add_property( 'margin-bottom', $css->render_range( cryptozfree()->option( 'content_spacing' ), 'desktop' ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.content-area' );
		$css->add_property( 'margin-top', $css->render_range( cryptozfree()->option( 'content_spacing' ), 'tablet' ) );
		$css->add_property( 'margin-bottom', $css->render_range( cryptozfree()->option( 'content_spacing' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.content-area' );
		$css->add_property( 'margin-top', $css->render_range( cryptozfree()->option( 'content_spacing' ), 'mobile' ) );
		$css->add_property( 'margin-bottom', $css->render_range( cryptozfree()->option( 'content_spacing' ), 'mobile' ) );
		$css->stop_media_query();
		// Boxed Spacing.
		$css->set_selector( '.entry-content-wrap' );
		$css->add_property( 'padding', $this->render_range( cryptozfree()->option( 'boxed_spacing' ), 'desktop' ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.entry-content-wrap' );
		$css->add_property( 'padding', $this->render_range( cryptozfree()->option( 'boxed_spacing' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.entry-content-wrap' );
		$css->add_property( 'padding', $this->render_range( cryptozfree()->option( 'boxed_spacing' ), 'mobile' ) );
		$css->stop_media_query();
		// Single Boxed Shadow.
		$css->set_selector( '.entry.single-entry' );
		$css->add_property( 'box-shadow', $css->render_shadow( cryptozfree()->option( 'boxed_shadow' ), cryptozfree()->default( 'boxed_shadow' ) ) );
		$css->add_property( 'border-radius', $css->render_measure( cryptozfree()->option( 'boxed_border_radius' ) ) );
		// Loop Boxed Shadow.
		$css->set_selector( '.entry.loop-entry' );
		$css->add_property( 'box-shadow', $css->render_shadow( cryptozfree()->option( 'boxed_grid_shadow' ), cryptozfree()->default( 'boxed_grid_shadow' ) ) );
		$css->add_property( 'border-radius', $css->render_measure( cryptozfree()->option( 'boxed_grid_border_radius' ) ) );
		// Boxed Grid Spacing.
		$css->set_selector( '.loop-entry .entry-content-wrap' );
		$css->add_property( 'padding', $css->render_range( cryptozfree()->option( 'boxed_grid_spacing' ), 'desktop' ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.loop-entry .entry-content-wrap' );
		$css->add_property( 'padding', $css->render_range( cryptozfree()->option( 'boxed_grid_spacing' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.loop-entry .entry-content-wrap' );
		$css->add_property( 'padding', $css->render_range( cryptozfree()->option( 'boxed_grid_spacing' ), 'mobile' ) );
		$css->stop_media_query();
		// Sidebar Width.
		if ( ! empty( cryptozfree()->sub_option( 'sidebar_width', 'size' ) ) && is_numeric( cryptozfree()->sub_option( 'sidebar_width', 'size' ) ) ) {
			$css->set_selector( '.has-sidebar:not(.has-left-sidebar) .content-container' );
			$css->add_property( 'grid-template-columns', '1fr ' . $this->render_size( cryptozfree()->option( 'sidebar_width' ) ) );
			$css->set_selector( '.has-sidebar.has-left-sidebar .content-container' );
			$css->add_property( 'grid-template-columns', $this->render_size( cryptozfree()->option( 'sidebar_width' ) ) . ' 1fr' );
		}
		// Sidebar.
		$css->set_selector( '.primary-sidebar.widget-area .widget' );
		$css->add_property( 'margin-bottom', $this->render_range( cryptozfree()->option( 'sidebar_widget_spacing' ), 'desktop' ) );
		$css->render_font( cryptozfree()->option( 'sidebar_widget_content' ), $css );
		$css->set_selector( '.primary-sidebar.widget-area .widget-title' );
		$css->render_font( cryptozfree()->option( 'sidebar_widget_title' ), $css );
		$css->set_selector( '.primary-sidebar.widget-area .widget a:not(.button)' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'sidebar_link_colors', 'color' ) ) );
		$css->set_selector( '.primary-sidebar.widget-area .widget a:not(.button):hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'sidebar_link_colors', 'hover' ) ) );
		$css->set_selector( '.primary-sidebar.widget-area' );
		$css->render_background( cryptozfree()->sub_option( 'sidebar_background', 'desktop' ), $css );
		$css->add_property( 'padding', $this->render_responsive_measure( cryptozfree()->option( 'sidebar_padding' ), 'desktop' ) );
		$css->set_selector( '.has-sidebar.has-left-sidebar .primary-sidebar.widget-area' );
		$css->add_property( 'border-right', $css->render_border( cryptozfree()->sub_option( 'sidebar_divider_border', 'desktop' ) ) );
		$css->set_selector( '.has-sidebar:not(.has-left-sidebar) .primary-sidebar.widget-area' );
		$css->add_property( 'border-left', $css->render_border( cryptozfree()->sub_option( 'sidebar_divider_border', 'desktop' ) ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.primary-sidebar.widget-area .widget' );
		$css->add_property( 'margin-bottom', $this->render_range( cryptozfree()->option( 'sidebar_widget_spacing' ), 'tablet' ) );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sidebar_widget_content' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sidebar_widget_content' ), 'tablet' ) );
		$css->set_selector( '.primary-sidebar.widget-area .widget-title' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sidebar_widget_title' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sidebar_widget_title' ), 'tablet' ) );
		$css->set_selector( '.primary-sidebar.widget-area' );
		$css->render_background( cryptozfree()->sub_option( 'sidebar_background', 'tablet' ), $css );
		$css->add_property( 'padding', $this->render_responsive_measure( cryptozfree()->option( 'sidebar_padding' ), 'tablet' ) );
		$css->set_selector( '.has-sidebar.has-left-sidebar .primary-sidebar.widget-area' );
		$css->add_property( 'border-right', $css->render_border( cryptozfree()->sub_option( 'sidebar_divider_border', 'tablet' ) ) );
		$css->set_selector( '.has-sidebar:not(.has-left-sidebar) .primary-sidebar.widget-area' );
		$css->add_property( 'border-left', $css->render_border( cryptozfree()->sub_option( 'sidebar_divider_border', 'tablet' ) ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.primary-sidebar.widget-area .widget' );
		$css->add_property( 'margin-bottom', $this->render_range( cryptozfree()->option( 'sidebar_widget_spacing' ), 'mobile' ) );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sidebar_widget_content' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sidebar_widget_content' ), 'mobile' ) );
		$css->set_selector( '.primary-sidebar.widget-area .widget-title' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sidebar_widget_title' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sidebar_widget_title' ), 'mobile' ) );
		$css->set_selector( '.primary-sidebar.widget-area' );
		$css->render_background( cryptozfree()->sub_option( 'sidebar_background', 'mobile' ), $css );
		$css->add_property( 'padding', $this->render_responsive_measure( cryptozfree()->option( 'sidebar_padding' ), 'mobile' ) );
		$css->set_selector( '.has-sidebar.has-left-sidebar .primary-sidebar.widget-area' );
		$css->add_property( 'border-right', $css->render_border( cryptozfree()->sub_option( 'sidebar_divider_border', 'mobile' ) ) );
		$css->set_selector( '.has-sidebar:not(.has-left-sidebar) .primary-sidebar.widget-area' );
		$css->add_property( 'border-left', $css->render_border( cryptozfree()->sub_option( 'sidebar_divider_border', 'mobile' ) ) );
		$css->stop_media_query();

		// Button.
		$css->set_selector( 'button, .button, .wp-block-button__link, input[type="button"], input[type="reset"], input[type="submit"], .fl-button, .elementor-button-wrapper .elementor-button' );
		$css->render_font( cryptozfree()->option( 'buttons_typography' ), $css );
		$css->add_property( 'border-radius', $this->render_range( cryptozfree()->option( 'buttons_border_radius' ), 'desktop' ) );
		$css->add_property( 'padding', $this->render_responsive_measure( cryptozfree()->option( 'buttons_padding' ), 'desktop' ) );
		$css->add_property( 'border', $css->render_responsive_border( cryptozfree()->option( 'buttons_border' ), 'desktop' ) );
		$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'buttons_border_colors', 'color' ) ) );
		$css->add_property( 'box-shadow', $css->render_shadow( cryptozfree()->option( 'buttons_shadow' ), cryptozfree()->default( 'buttons_shadow' ) ) );
		$css->set_selector( 'button:hover, button:focus, button:active, .button:hover, .button:focus, .button:active, .wp-block-button__link:hover, .wp-block-button__link:focus, .wp-block-button__link:active, input[type="button"]:hover, input[type="button"]:focus, input[type="button"]:active, input[type="reset"]:hover, input[type="reset"]:focus, input[type="reset"]:active, input[type="submit"]:hover, input[type="submit"]:focus, input[type="submit"]:active, .elementor-button-wrapper .elementor-button:hover, .elementor-button-wrapper .elementor-button:focus, .elementor-button-wrapper .elementor-button:active' );
		$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'buttons_border_colors', 'hover' ) ) );
		$css->add_property( 'box-shadow', $css->render_shadow( cryptozfree()->option( 'buttons_shadow_hover' ), cryptozfree()->default( 'buttons_shadow_hover' ) ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( 'button, .button, .wp-block-button__link, input[type="button"], input[type="reset"], input[type="submit"], .fl-button, .elementor-button-wrapper .elementor-button' );
		$css->add_property( 'border', $css->render_responsive_border( cryptozfree()->option( 'buttons_border' ), 'tablet' ) );
		$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'buttons_border_colors', 'color' ) ) );
		$css->add_property( 'border-radius', $this->render_range( cryptozfree()->option( 'buttons_border_radius' ), 'tablet' ) );
		$css->add_property( 'padding', $this->render_responsive_measure( cryptozfree()->option( 'buttons_padding' ), 'tablet' ) );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'buttons_typography' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'buttons_typography' ), 'tablet' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'buttons_typography' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( 'button, .button, .wp-block-button__link, input[type="button"], input[type="reset"], input[type="submit"], .fl-button, .elementor-button-wrapper .elementor-button' );
		$css->add_property( 'padding', $this->render_responsive_measure( cryptozfree()->option( 'buttons_padding' ), 'mobile' ) );
		$css->add_property( 'border-radius', $this->render_range( cryptozfree()->option( 'buttons_border_radius' ), 'mobile' ) );
		$css->add_property( 'border', $css->render_responsive_border( cryptozfree()->option( 'buttons_border' ), 'mobile' ) );
		$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'buttons_border_colors', 'color' ) ) );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'buttons_typography' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'buttons_typography' ), 'mobile' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'buttons_typography' ), 'mobile' ) );
		$css->stop_media_query();
		// Logo area.
		if ( cryptozfree()->option( 'custom_logo' ) || is_customize_preview() ) {
			$logo_width = cryptozfree()->option( 'logo_width' );
			foreach ( array( 'desktop', 'tablet', 'mobile' ) as $device ) {
				if ( isset( $logo_width['size'] ) && isset( $logo_width['size'][ $device ] ) && ! empty( $logo_width['size'][ $device ] ) ) {
					$unit = ( isset( $logo_width['unit'] ) && isset( $logo_width['unit'][ $device ] ) && ! empty( $logo_width['unit'][ $device ] ) ? $logo_width['unit'][ $device ] : 'px' );
					if ( 'desktop' !== $device ) {
						$css->start_media_query( $media_query[ $device ] );
					}
					$css->set_selector( '.site-branding a.brand img' );
					$css->add_property( 'max-width', $logo_width['size'][ $device ] . $unit );
					$css->set_selector( '.site-branding a.brand img.svg-logo-image' );
					$css->add_property( 'width', $logo_width['size'][ $device ] . $unit );
					if ( 'desktop' !== $device ) {
						$css->stop_media_query();
					}
				}
			}
		}
		$css->set_selector( '.site-branding' );
		$css->add_property( 'padding', $this->render_responsive_measure( cryptozfree()->option( 'header_logo_padding' ), 'desktop' ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.site-branding' );
		$css->add_property( 'padding', $this->render_responsive_measure( cryptozfree()->option( 'header_logo_padding' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.site-branding' );
		$css->add_property( 'padding', $this->render_responsive_measure( cryptozfree()->option( 'header_logo_padding' ), 'mobile' ) );
		$css->stop_media_query();
		$css->set_selector( '.site-branding .site-title' );
		$css->render_font( cryptozfree()->option( 'brand_typography' ), $css );
		$css->set_selector( '.site-branding .site-title:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'brand_typography_color', 'hover' ) ) );
		$css->set_selector( 'body.home .site-branding .site-title' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'brand_typography_color', 'active' ) ) );
		$css->set_selector( '.site-branding .site-description' );
		$css->render_font( cryptozfree()->option( 'brand_tag_typography' ), $css );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.site-branding .site-title' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'brand_typography' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'brand_typography' ), 'tablet' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'brand_typography' ), 'tablet' ) );
		$css->set_selector( '.site-branding .site-description' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'brand_tag_typography' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'brand_tag_typography' ), 'tablet' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'brand_tag_typography' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.site-branding .site-title' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'brand_typography' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'brand_typography' ), 'mobile' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'brand_typography' ), 'mobile' ) );
		$css->set_selector( '.site-branding .site-description' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'brand_tag_typography' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'brand_tag_typography' ), 'mobile' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'brand_tag_typography' ), 'mobile' ) );
		$css->stop_media_query();
		// Header.
		$css->set_selector( '#masthead, #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start):not(.site-header-row-container), #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) > .site-header-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_wrap_background', 'desktop' ), $css );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '#masthead, #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start):not(.site-header-row-container), #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) > .site-header-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_wrap_background', 'tablet' ), $css );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '#masthead, #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start):not(.site-header-row-container), #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) > .site-header-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_wrap_background', 'mobile' ), $css );
		$css->stop_media_query();
		// Header Main.
		$css->set_selector( '.site-main-header-wrap .site-header-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_main_background', 'desktop' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'header_main_top_border', 'desktop' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'header_main_bottom_border', 'desktop' ) ) );
		$css->set_selector( '.site-main-header-inner-wrap' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'header_main_height' ), 'desktop' ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.site-main-header-wrap .site-header-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_main_background', 'tablet' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'header_main_top_border', 'tablet' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'header_main_bottom_border', 'tablet' ) ) );
		$css->set_selector( '.site-main-header-inner-wrap' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'header_main_height' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.site-main-header-wrap .site-header-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_main_background', 'mobile' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'header_main_top_border', 'mobile' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'header_main_bottom_border', 'mobile' ) ) );
		$css->set_selector( '.site-main-header-inner-wrap' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'header_main_height' ), 'mobile' ) );
		$css->stop_media_query();
		// Header Main Transparent.
		$css->set_selector( '.transparent-header #masthead .site-main-header-wrap .site-header-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_main_trans_background', 'desktop' ), $css );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.transparent-header #masthead .site-main-header-wrap .site-header-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_main_trans_background', 'tablet' ), $css );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.transparent-header #masthead .site-main-header-wrap .site-header-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_main_trans_background', 'mobile' ), $css );
		$css->stop_media_query();

		// Header Top.
		$css->set_selector( '.site-top-header-wrap .site-header-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_top_background', 'desktop' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'header_top_top_border', 'desktop' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'header_top_bottom_border', 'desktop' ) ) );
		$css->set_selector( '.site-top-header-inner-wrap' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'header_top_height' ), 'desktop' ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.site-top-header-wrap .site-header-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_top_background', 'tablet' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'header_top_top_border', 'tablet' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'header_top_bottom_border', 'tablet' ) ) );
		$css->set_selector( '.site-top-header-inner-wrap' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'header_top_height' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.site-top-header-wrap .site-header-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_top_background', 'mobile' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'header_top_top_border', 'mobile' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'header_top_bottom_border', 'mobile' ) ) );
		$css->set_selector( '.site-top-header-inner-wrap' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'header_top_height' ), 'mobile' ) );
		$css->stop_media_query();
		// Header Top Transparent.
		$css->set_selector( '.transparent-header #masthead .site-top-header-wrap .site-header-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_top_trans_background', 'desktop' ), $css );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.transparent-header #masthead .site-top-header-wrap .site-header-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_top_trans_background', 'tablet' ), $css );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.transparent-header #masthead .site-top-header-wrap .site-header-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_top_trans_background', 'mobile' ), $css );
		$css->stop_media_query();

		// Header Bottom.
		$css->set_selector( '.site-bottom-header-wrap .site-header-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_bottom_background', 'desktop' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'header_bottom_top_border', 'desktop' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'header_bottom_bottom_border', 'desktop' ) ) );
		$css->set_selector( '.site-bottom-header-inner-wrap' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'header_bottom_height' ), 'desktop' ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.site-bottom-header-wrap .site-header-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_bottom_background', 'tablet' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'header_bottom_top_border', 'tablet' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'header_bottom_bottom_border', 'tablet' ) ) );
		$css->set_selector( '.site-bottom-header-inner-wrap' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'header_bottom_height' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.site-bottom-header-wrap .site-header-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_bottom_background', 'mobile' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'header_bottom_top_border', 'mobile' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'header_bottom_bottom_border', 'mobile' ) ) );
		$css->set_selector( '.site-bottom-header-inner-wrap' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'header_bottom_height' ), 'mobile' ) );
		$css->stop_media_query();
		// Header Bottom Transparent.
		$css->set_selector( '.transparent-header #masthead .site-bottom-header-wrap .site-header-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_bottom_trans_background', 'desktop' ), $css );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.transparent-header #masthead .site-bottom-header-wrap .site-header-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_bottom_trans_background', 'tablet' ), $css );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.transparent-header #masthead .site-bottom-header-wrap .site-header-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_bottom_trans_background', 'mobile' ), $css );
		$css->stop_media_query();
		
		// Sticky Header.
		$css->set_selector( '#masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start):not(.site-header-row-container):not(.item-hidden-above), #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start):not(.item-hidden-above) > .site-header-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_sticky_background', 'desktop' ), $css );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'header_sticky_bottom_border', 'desktop' ) ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '#masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start):not(.site-header-row-container):not(.item-hidden-above), #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start):not(.item-hidden-above) > .site-header-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_sticky_background', 'tablet' ), $css );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'header_sticky_bottom_border', 'tablet' ) ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '#masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start):not(.site-header-row-container):not(.item-hidden-above), #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start):not(.item-hidden-above) > .site-header-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_sticky_background', 'mobile' ), $css );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'header_sticky_bottom_border', 'mobile' ) ) );
		$css->stop_media_query();
		$css->set_selector( '#masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .site-branding .site-title, #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .site-branding .site-description' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_sticky_site_title_color', 'color' ) ) );
		$css->set_selector( '#masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .header-menu-container > ul > li > a' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_sticky_navigation_color', 'color' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_sticky_navigation_background', 'color' ) ) );
		$css->set_selector( '#masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .mobile-toggle-open-container .menu-toggle-open, #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .search-toggle-open-container .search-toggle-open' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_sticky_navigation_color', 'color' ) ) );
		$css->set_selector( '#masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .header-menu-container > ul > li > a:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_sticky_navigation_color', 'hover' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_sticky_navigation_background', 'hover' ) ) );
		$css->set_selector( '#masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .mobile-toggle-open-container .menu-toggle-open:hover, #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .mobile-toggle-open-container .menu-toggle-open:focus, #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .search-toggle-open-container .search-toggle-open:hover, #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .search-toggle-open-container .search-toggle-open:focus' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_sticky_navigation_color', 'hover' ) ) );
		$css->set_selector( '#masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .header-menu-container > ul > li.current-menu-item > a, #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .header-menu-container > ul > li.current_page_item > a' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_sticky_navigation_color', 'active' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_sticky_navigation_background', 'active' ) ) );
		// Sticky Button.
		$css->set_selector( '#masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .header-button, #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .mobile-header-button-wrap .mobile-header-button' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_sticky_button_color', 'color' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_sticky_button_color', 'background' ) ) );
		$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'header_sticky_button_color', 'border' ) ) );
		$css->set_selector( '#masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .header-button:hover, #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .mobile-header-button-wrap .mobile-header-button:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_sticky_button_color', 'hover' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_sticky_button_color', 'backgroundHover' ) ) );
		$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'header_sticky_button_color', 'borderHover' ) ) );
		// Sticky Social.
		$css->set_selector( '#masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .header-social-wrap a.social-button, #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .header-mobile-social-wrap a.social-button' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_sticky_social_color', 'color' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_sticky_social_color', 'background' ) ) );
		$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'header_sticky_social_color', 'border' ) ) );
		$css->set_selector( '#masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .header-social-wrap a.social-button:hover, #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .header-mobile-social-wrap a.social-button:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_sticky_social_color', 'hover' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_sticky_social_color', 'backgroundHover' ) ) );
		$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'header_sticky_social_color', 'borderHover' ) ) );
		// Sticky cart.
		$css->set_selector( '#masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .header-cart-wrap .header-cart-button, #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .header-mobile-cart-wrap .header-cart-button' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_sticky_cart_color', 'color' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_sticky_cart_color', 'background' ) ) );
		$css->set_selector( '#masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .header-cart-wrap .header-cart-button:hover, #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .header-mobile-cart-wrap .header-cart-button .header-cart-total:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_sticky_cart_color', 'hover' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_sticky_cart_color', 'backgroundHover' ) ) );
		$css->set_selector( '#masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .header-cart-wrap .header-cart-button .header-cart-total, #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .header-mobile-cart-wrap .header-cart-button .header-cart-total' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_sticky_cart_total_color', 'color' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_sticky_cart_total_color', 'background' ) ) );
		$css->set_selector( '#masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .header-cart-wrap .header-cart-button:hover .header-cart-total, #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .header-mobile-cart-wrap .header-cart-button:hover .header-cart-total' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_sticky_cart_total_color', 'hover' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_sticky_cart_total_color', 'backgroundHover' ) ) );
		// Sticky HTML.
		$css->set_selector( '#masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .header-html, #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .mobile-html' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_sticky_html_color', 'color' ) ) );
		$css->set_selector( '#masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .header-html a, #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .mobile-html a' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_sticky_html_color', 'link' ) ) );
		$css->set_selector( '#masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .header-html a:hover, #masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .mobile-html a:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_sticky_html_color', 'hover' ) ) );
		// Sticky Header Logo.
		if ( cryptozfree()->option( 'header_sticky_custom_logo' ) ) {
			$css->set_selector( '#masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .site-branding img' );
			$css->add_property( 'max-width', $this->render_range( cryptozfree()->option( 'header_sticky_logo_width' ), 'desktop' ) );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '#masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .site-branding img' );
			$css->add_property( 'max-width', $this->render_range( cryptozfree()->option( 'header_sticky_logo_width' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '#masthead .cryptozfree-sticky-header.item-is-fixed:not(.item-at-start) .site-branding img' );
			$css->add_property( 'max-width', $this->render_range( cryptozfree()->option( 'header_sticky_logo_width' ), 'mobile' ) );
			$css->stop_media_query();
		}
		// Transparent Header Logo.
		if ( cryptozfree()->option( 'transparent_header_custom_logo' ) ) {
			$css->set_selector( '.transparent-header #main-header .site-branding img' );
			$css->add_property( 'max-width', $this->render_range( cryptozfree()->option( 'transparent_header_logo_width' ), 'desktop' ) );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.transparent-header #main-header .site-branding img, .mobile-transparent-header #mobile-header .site-branding img' );
			$css->add_property( 'max-width', $this->render_range( cryptozfree()->option( 'transparent_header_logo_width' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.transparent-header #main-header .site-branding img, .mobile-transparent-header #mobile-header .site-branding img' );
			$css->add_property( 'max-width', $this->render_range( cryptozfree()->option( 'transparent_header_logo_width' ), 'mobile' ) );
			$css->stop_media_query();
		}
		// Transparent Header.
		$css->set_selector( '.transparent-header #wrapper #masthead' );
		$css->render_background( cryptozfree()->sub_option( 'transparent_header_background', 'desktop' ), $css );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'transparent_header_bottom_border', 'desktop' ) ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.transparent-header #wrapper #masthead' );
		$css->render_background( cryptozfree()->sub_option( 'transparent_header_background', 'tablet' ), $css );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'transparent_header_bottom_border', 'tablet' ) ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.transparent-header #wrapper #masthead' );
		$css->render_background( cryptozfree()->sub_option( 'transparent_header_background', 'mobile' ), $css );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'transparent_header_bottom_border', 'mobile' ) ) );
		$css->stop_media_query();
		$css->set_selector( '.transparent-header #main-header .site-title, .transparent-header #main-header .site-branding .site-description, .mobile-transparent-header #mobile-header .site-branding .site-title, .mobile-transparent-header #mobile-header .site-branding .site-description' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'transparent_header_site_title_color', 'color' ) ) );
		$css->set_selector( '.transparent-header .header-navigation .header-menu-container > ul > li > a' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'transparent_header_navigation_color', 'color' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'transparent_header_navigation_background', 'color' ) ) );
		$css->set_selector( '.mobile-transparent-header .mobile-toggle-open-container .menu-toggle-open, .transparent-header .search-toggle-open-container .search-toggle-open' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'transparent_header_navigation_color', 'color' ) ) );
		$css->set_selector( '.transparent-header .header-navigation .header-menu-container > ul > li > a:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'transparent_header_navigation_color', 'hover' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'transparent_header_navigation_background', 'hover' ) ) );
		$css->set_selector( '.mobile-transparent-header .mobile-toggle-open-container .menu-toggle-open:hover, .transparent-header .mobile-toggle-open-container .menu-toggle-open:focus, .transparent-header .search-toggle-open-container .search-toggle-open:hover, .transparent-header .search-toggle-open-container .search-toggle-open:focus' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'transparent_header_navigation_color', 'hover' ) ) );
		$css->set_selector( '.transparent-header .header-navigation .header-menu-container > ul > li.current-menu-item > a, .transparent-header .header-menu-container > ul > li.current_page_item > a' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'transparent_header_navigation_color', 'active' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'transparent_header_navigation_background', 'active' ) ) );
		// Transparent Button.
		$css->set_selector( '.transparent-header #main-header .header-button, .mobile-transparent-header .mobile-header-button-wrap .mobile-header-button' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'transparent_header_button_color', 'color' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'transparent_header_button_color', 'background' ) ) );
		$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'transparent_header_button_color', 'border' ) ) );
		$css->set_selector( '.transparent-header #main-header .header-button:hover, .mobile-transparent-header .mobile-header-button-wrap .mobile-header-button:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'transparent_header_button_color', 'hover' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'transparent_header_button_color', 'backgroundHover' ) ) );
		$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'transparent_header_button_color', 'borderHover' ) ) );
		// Transparent Social.
		$css->set_selector( '.transparent-header .header-social-wrap a.social-button, .mobile-transparent-header #mobile-header .header-mobile-social-wrap a.social-button' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'transparent_header_social_color', 'color' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'transparent_header_social_color', 'background' ) ) );
		$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'transparent_header_social_color', 'border' ) ) );
		$css->set_selector( '.transparent-header .header-social-wrap a.social-button:hover, .mobile-transparent-header #mobile-header .header-mobile-social-wrap a.social-button:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'transparent_header_social_color', 'hover' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'transparent_header_social_color', 'backgroundHover' ) ) );
		$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'transparent_header_social_color', 'borderHover' ) ) );
		// Transparent cart.
		$css->set_selector( '.transparent-header #main-header .header-cart-wrap .header-cart-button, .mobile-transparent-header #mobile-header .header-mobile-cart-wrap .header-cart-button' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'transparent_header_cart_color', 'color' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'transparent_header_cart_color', 'background' ) ) );
		$css->set_selector( '.transparent-header #main-header .header-cart-wrap .header-cart-button:hover, .mobile-transparent-header #mobile-header .header-mobile-cart-wrap .header-cart-button:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'transparent_header_cart_color', 'hover' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'transparent_header_cart_color', 'backgroundHover' ) ) );
		$css->set_selector( '.transparent-header #main-header .header-cart-wrap .header-cart-button .header-cart-total, .mobile-transparent-header #mobile-header .header-mobile-cart-wrap .header-cart-button .header-cart-total' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'transparent_header_cart_total_color', 'color' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'transparent_header_cart_total_color', 'background' ) ) );
		$css->set_selector( '.transparent-header #main-header .header-cart-wrap .header-cart-button:hover .header-cart-total, .mobile-transparent-header #mobile-header .header-mobile-cart-wrap .header-cart-button:hover .header-cart-total' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'transparent_header_cart_total_color', 'hover' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'transparent_header_cart_total_color', 'backgroundHover' ) ) );
		// Transparent HTML.
		$css->set_selector( '.transparent-header #main-header .header-html, .mobile-transparent-header .mobile-html' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'transparent_header_html_color', 'color' ) ) );
		$css->set_selector( '.transparent-header #main-header .header-html a, .mobile-transparent-header .mobile-html a' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'transparent_header_html_color', 'link' ) ) );
		$css->set_selector( '.transparent-header #main-header .header-html a:hover, .mobile-transparent-header .mobile-html a:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'transparent_header_html_color', 'hover' ) ) );
		// Padding for transparent header.
		// if ( cryptozfree()->has_header() ) {
		// 	$css->start_media_query( $media_query['desktop'] );
		// 	$css->set_selector( '.transparent-header .entry-hero .entry-hero-container-inner' );
		// 	$css->add_property( 'padding-top', $this->render_hero_padding( 'desktop' ) );
		// 	$css->stop_media_query();
		// 	$css->start_media_query( $media_query['tablet'] );
		// 	$css->set_selector( '.mobile-transparent-header .entry-hero .entry-hero-container-inner' );
		// 	$css->add_property( 'padding-top', $this->render_hero_padding( 'tablet' ) );
		// 	$css->stop_media_query();
		// 	$css->start_media_query( $media_query['mobile'] );
		// 	$css->set_selector( '.mobile-transparent-header .entry-hero .entry-hero-container-inner' );
		// 	$css->add_property( 'padding-top', $this->render_hero_padding( 'mobile' ) );
		// 	$css->stop_media_query();
		// }
		// Above Title Area.
		$css->set_selector( '.site .entry-hero-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'above_title_background', 'desktop' ), $css );
		$css->set_selector( '.site .hero-section-overlay' );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'above_title_overlay_color', 'color' ) ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.site .entry-hero-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'above_title_background', 'tablet' ), $css );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.site .entry-hero-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'above_title_background', 'mobile' ), $css );
		$css->stop_media_query();
		// Navigation.
		$css->set_selector( '.header-navigation[class*="header-navigation-style-underline"] .header-menu-container.primary-menu-container>ul>li>a:after' );
		$css->add_property( 'width', 'calc( 100% - ' . $this->render_size( cryptozfree()->option( 'primary_navigation_spacing' ) ) . ')' );
		$css->set_selector( '.main-navigation .primary-menu-container > ul > li > a' );
		$css->add_property( 'padding-left', $this->render_half_size( cryptozfree()->option( 'primary_navigation_spacing' ) ) );
		$css->add_property( 'padding-right', $this->render_half_size( cryptozfree()->option( 'primary_navigation_spacing' ) ) );
		if ( cryptozfree()->option( 'primary_navigation_style' ) === 'standard' || cryptozfree()->option( 'primary_navigation_style' ) === 'underline' ) {
			$css->add_property( 'padding-top', cryptozfree()->sub_option( 'primary_navigation_vertical_spacing', 'size' ) . cryptozfree()->sub_option( 'primary_navigation_vertical_spacing', 'unit' ) );
			$css->add_property( 'padding-bottom', cryptozfree()->sub_option( 'primary_navigation_vertical_spacing', 'size' ) . cryptozfree()->sub_option( 'primary_navigation_vertical_spacing', 'unit' ) );
		}
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'primary_navigation_color', 'color' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'primary_navigation_background', 'color' ) ) );
		$css->set_selector( '.main-navigation .primary-menu-container > ul li a' );
		$css->render_font( cryptozfree()->option( 'primary_navigation_typography' ), $css, 'primary_nav' );
		$css->set_selector( '.main-navigation .primary-menu-container > ul > li > a:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'primary_navigation_color', 'hover' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'primary_navigation_background', 'hover' ) ) );
		$css->set_selector( '.main-navigation .primary-menu-container > ul > li.current-menu-item > a' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'primary_navigation_color', 'active' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'primary_navigation_background', 'active' ) ) );
		// Second Nav.
		$css->set_selector( '.header-navigation[class*="header-navigation-style-underline"] .header-menu-container.secondary-menu-container>ul>li>a:after' );
		$css->add_property( 'width', 'calc( 100% - ' . $this->render_size( cryptozfree()->option( 'secondary_navigation_spacing' ) ) . ')' );
		$css->set_selector( '.secondary-navigation .secondary-menu-container > ul > li > a' );
		$css->add_property( 'padding-left', $this->render_half_size( cryptozfree()->option( 'secondary_navigation_spacing' ) ) );
		$css->add_property( 'padding-right', $this->render_half_size( cryptozfree()->option( 'secondary_navigation_spacing' ) ) );
		if ( cryptozfree()->option( 'secondary_navigation_style' ) === 'standard' || cryptozfree()->option( 'secondary_navigation_style' ) === 'underline' ) {
			$css->add_property( 'padding-top', cryptozfree()->sub_option( 'secondary_navigation_vertical_spacing', 'size' ) . cryptozfree()->sub_option( 'secondary_navigation_vertical_spacing', 'unit' ) );
			$css->add_property( 'padding-bottom', cryptozfree()->sub_option( 'secondary_navigation_vertical_spacing', 'size' ) . cryptozfree()->sub_option( 'secondary_navigation_vertical_spacing', 'unit' ) );
		}
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'secondary_navigation_color', 'color' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'secondary_navigation_background', 'color' ) ) );
		$css->set_selector( '.secondary-navigation .secondary-menu-container > ul li a' );
		$css->render_font( cryptozfree()->option( 'secondary_navigation_typography' ), $css );
		$css->set_selector( '.secondary-navigation .secondary-menu-container > ul > li > a:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'secondary_navigation_color', 'hover' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'secondary_navigation_background', 'hover' ) ) );
		$css->set_selector( '.secondary-navigation .secondary-menu-container > ul > li.current-menu-item > a' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'secondary_navigation_color', 'active' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'secondary_navigation_background', 'active' ) ) );
		// Dropdown.
		$css->set_selector( '.header-navigation .header-menu-container ul ul' );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'dropdown_navigation_background', 'color' ) ) );
		$css->add_property( 'box-shadow', $css->render_shadow( cryptozfree()->option( 'dropdown_navigation_shadow' ), cryptozfree()->default( 'dropdown_navigation_shadow' ) ) );
		$css->set_selector( '.header-navigation .header-menu-container ul ul li, .header-menu-container ul.menu > li.cryptozfree-menu-mega-enabled > ul > li > a' );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->option( 'dropdown_navigation_divider' ) ) );
		$css->set_selector( '.header-navigation .header-menu-container ul ul li a' );
		$css->add_property( 'width', cryptozfree()->sub_option( 'dropdown_navigation_width', 'size' ) . cryptozfree()->sub_option( 'dropdown_navigation_width', 'unit' ) );
		$css->add_property( 'padding-top', $css->render_size( cryptozfree()->option( 'dropdown_navigation_vertical_spacing' ) ) );
		$css->add_property( 'padding-bottom', $css->render_size( cryptozfree()->option( 'dropdown_navigation_vertical_spacing' ) ) );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'dropdown_navigation_color', 'color' ) ) );
		$css->render_font( cryptozfree()->option( 'dropdown_navigation_typography' ), $css );
		$css->set_selector( '.header-navigation .header-menu-container ul ul li a:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'dropdown_navigation_color', 'hover' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'dropdown_navigation_background', 'hover' ) ) );
		$css->set_selector( '.header-navigation .header-menu-container ul ul li.current-menu-item > a' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'dropdown_navigation_color', 'active' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'dropdown_navigation_background', 'active' ) ) );
		// Mobile Toggle.
		$css->set_selector( '.mobile-toggle-open-container .menu-toggle-open' );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'mobile_trigger_background', 'color' ) ) );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'mobile_trigger_color', 'color' ) ) );
		$css->add_property( 'padding', $this->render_measure( cryptozfree()->option( 'mobile_trigger_padding' ) ) );
		$css->render_font( cryptozfree()->option( 'mobile_trigger_typography' ), $css );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.mobile-toggle-open-container .menu-toggle-open' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'mobile_trigger_typography' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'mobile_trigger_typography' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.mobile-toggle-open-container .menu-toggle-open' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'mobile_trigger_typography' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'mobile_trigger_typography' ), 'mobile' ) );
		$css->stop_media_query();
		$css->set_selector( '.mobile-toggle-open-container .menu-toggle-open.menu-toggle-style-bordered' );
		$css->add_property( 'border', $css->render_border( cryptozfree()->option( 'mobile_trigger_border' ) ) );
		$css->set_selector( '.mobile-toggle-open-container .menu-toggle-open .menu-toggle-icon' );
		$css->add_property( 'font-size', cryptozfree()->sub_option( 'mobile_trigger_icon_size', 'size' ) . cryptozfree()->sub_option( 'mobile_trigger_icon_size', 'unit' ) );
		$css->set_selector( '.mobile-toggle-open-container .menu-toggle-open:hover, .mobile-toggle-open-container .menu-toggle-open:focus' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'mobile_trigger_color', 'hover' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'mobile_trigger_background', 'hover' ) ) );
		// Mobile Menu.
		$css->set_selector( '.mobile-navigation ul li' );
		$css->render_font( cryptozfree()->option( 'mobile_navigation_typography' ), $css );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.mobile-navigation ul li' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'mobile_navigation_typography' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'mobile_navigation_typography' ), 'tablet' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'mobile_navigation_typography' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.mobile-navigation ul li' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'mobile_navigation_typography' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'mobile_navigation_typography' ), 'mobile' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'mobile_navigation_typography' ), 'mobile' ) );
		$css->stop_media_query();
		$css->set_selector( '.mobile-navigation ul li a' );
		$css->add_property( 'padding-top', cryptozfree()->sub_option( 'mobile_navigation_vertical_spacing', 'size' ) . cryptozfree()->sub_option( 'mobile_navigation_vertical_spacing', 'unit' ) );
		$css->add_property( 'padding-bottom', cryptozfree()->sub_option( 'mobile_navigation_vertical_spacing', 'size' ) . cryptozfree()->sub_option( 'mobile_navigation_vertical_spacing', 'unit' ) );
		$css->set_selector( '.mobile-navigation ul li:not(.menu-item-has-children) > a, .mobile-navigation ul li.menu-item-has-children > .drawer-nav-drop-wrap' );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'mobile_navigation_background', 'color' ) ) );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'mobile_navigation_color', 'color' ) ) );
		$css->set_selector( '.mobile-navigation ul li:not(.menu-item-has-children) > a:hover, .mobile-navigation ul li.menu-item-has-children > .drawer-nav-drop-wrap:hover' );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'mobile_navigation_background', 'hover' ) ) );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'mobile_navigation_color', 'hover' ) ) );
		$css->set_selector( '.mobile-navigation ul li.current-menu-item:not(.menu-item-has-children) > a, .mobile-navigation ul li.current-menu-item.menu-item-has-children > .drawer-nav-drop-wrap' );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'mobile_navigation_background', 'active' ) ) );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'mobile_navigation_color', 'active' ) ) );
		$css->set_selector( '.mobile-navigation ul li.menu-item-has-children .drawer-nav-drop-wrap, .mobile-navigation ul li:not(.menu-item-has-children) a' );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->option( 'mobile_navigation_divider' ) ) );
		$css->set_selector( '.mobile-navigation ul li.menu-item-has-children .drawer-nav-drop-wrap button' );
		$css->add_property( 'border-left', $css->render_border( cryptozfree()->option( 'mobile_navigation_divider' ) ) );
		// Mobile Popout.
		$css->set_selector( '#mobile-drawer .drawer-inner, #mobile-drawer.popup-drawer-layout-fullwidth.popup-drawer-animation-slice .pop-portion-bg' );
		$css->render_background( cryptozfree()->sub_option( 'header_popup_background', 'desktop' ), $css );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '#mobile-drawer .drawer-inner, #mobile-drawer.popup-drawer-layout-fullwidth.popup-drawer-animation-slice .pop-portion-bg' );
		$css->render_background( cryptozfree()->sub_option( 'header_popup_background', 'tablet' ), $css );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '#mobile-drawer .drawer-inner, #mobile-drawer.popup-drawer-layout-fullwidth.popup-drawer-animation-slice .pop-portion-bg' );
		$css->render_background( cryptozfree()->sub_option( 'header_popup_background', 'mobile' ), $css );
		$css->stop_media_query();
		$css->set_selector( '#mobile-drawer .drawer-header .drawer-toggle, #mobile-drawer .drawer-header .drawer-toggle:focus' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_popup_close_color', 'color' ) ) );
		$css->set_selector( '#mobile-drawer .drawer-header .drawer-toggle:hover, #mobile-drawer .drawer-header .drawer-toggle:focus:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_popup_close_color', 'hover' ) ) );
		// Header Button.
		$css->set_selector( '#main-header .header-button' );
		$css->render_font( cryptozfree()->option( 'header_button_typography' ), $css );
		$css->add_property( 'margin', $this->render_measure( cryptozfree()->option( 'header_button_margin' ) ) );
		$css->add_property( 'border-radius', $this->render_measure( cryptozfree()->option( 'header_button_radius' ) ) );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_button_color', 'color' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_button_background', 'color' ) ) );
		$css->add_property( 'border', $css->render_border( cryptozfree()->option( 'header_button_border' ) ) );
		$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'header_button_border_colors', 'color' ) ) );
		$css->add_property( 'box-shadow', $css->render_shadow( cryptozfree()->option( 'header_button_shadow' ), cryptozfree()->default( 'header_button_shadow' ) ) );
		$css->set_selector( '#main-header .header-button.button-size-custom' );
		$css->add_property( 'padding', $this->render_measure( cryptozfree()->option( 'header_button_padding' ) ) );
		$css->set_selector( '#main-header .header-button:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_button_color', 'hover' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_button_background', 'hover' ) ) );
		$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'header_button_border_colors', 'hover' ) ) );
		$css->add_property( 'box-shadow', $css->render_shadow( cryptozfree()->option( 'header_button_shadow_hover' ), cryptozfree()->default( 'header_button_shadow_hover' ) ) );
		// Header HTML.
		$css->set_selector( '.header-html' );
		$css->render_font( cryptozfree()->option( 'header_html_typography' ), $css );
		$css->add_property( 'margin', $this->render_measure( cryptozfree()->option( 'header_html_margin' ) ) );
		$css->set_selector( '.header-html a' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_html_link_color', 'color' ) ) );
		$css->set_selector( '.header-html a:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_html_link_color', 'hover' ) ) );
		// Woo Header.
		if ( class_exists( 'woocommerce' ) ) {
			// Header Cart.
			$css->set_selector( '.site-header-item .header-cart-wrap .header-cart-inner-wrap .header-cart-button' );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_cart_background', 'color' ) ) );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_cart_color', 'color' ) ) );
			$css->add_property( 'padding', $this->render_measure( cryptozfree()->option( 'header_cart_padding' ) ) );
			$css->set_selector( '.header-cart-wrap .header-cart-button .header-cart-total' );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_cart_total_background', 'color' ) ) );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_cart_total_color', 'color' ) ) );
			$css->set_selector( '.site-header-item .header-cart-wrap .header-cart-inner-wrap .header-cart-button:hover' );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_cart_background', 'hover' ) ) );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_cart_color', 'hover' ) ) );
			$css->set_selector( '.header-cart-wrap .header-cart-button:hover .header-cart-total' );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_cart_total_background', 'hover' ) ) );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_cart_total_color', 'hover' ) ) );
			$css->set_selector( '.header-cart-wrap .header-cart-button .header-cart-label' );
			$css->render_font( cryptozfree()->option( 'header_cart_typography' ), $css );
			if ( ! empty( cryptozfree()->sub_option( 'header_cart_icon_size', 'size' ) ) ) {
				$css->set_selector( '.header-cart-wrap .header-cart-button .cryptozfree-svg-iconset' );
				$css->add_property( 'font-size', cryptozfree()->sub_option( 'header_cart_icon_size', 'size' ) . cryptozfree()->sub_option( 'header_cart_icon_size', 'unit' ) );
			}

			// Mobile Cart.
			$css->set_selector( '.header-mobile-cart-wrap .header-cart-inner-wrap .header-cart-button' );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_mobile_cart_background', 'color' ) ) );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_mobile_cart_color', 'color' ) ) );
			$css->add_property( 'padding', $this->render_measure( cryptozfree()->option( 'header_mobile_cart_padding' ) ) );
			$css->set_selector( '.header-mobile-cart-wrap .header-cart-button .header-cart-total' );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_mobile_cart_total_background', 'color' ) ) );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_mobile_cart_total_color', 'color' ) ) );
			$css->set_selector( '.header-mobile-cart-wrap .header-cart-inner-wrap .header-cart-button:hover' );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_mobile_cart_background', 'hover' ) ) );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_mobile_cart_color', 'hover' ) ) );
			$css->set_selector( '.header-mobile-cart-wrap .header-cart-button:hover .header-cart-total' );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_mobile_cart_total_background', 'hover' ) ) );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_mobile_cart_total_color', 'hover' ) ) );
			$css->set_selector( '.header-mobile-cart-wrap .header-cart-button .header-cart-label' );
			$css->render_font( cryptozfree()->option( 'header_mobile_cart_typography' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.header-mobile-cart-wrap .header-cart-button .header-cart-label' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'header_mobile_cart_typography' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'header_mobile_cart_typography' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.header-mobile-cart-wrap .header-cart-button .header-cart-label' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'header_mobile_cart_typography' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'header_mobile_cart_typography' ), 'mobile' ) );
			$css->stop_media_query();
			if ( ! empty( cryptozfree()->sub_option( 'header_mobile_cart_icon_size', 'size' ) ) ) {
				$css->set_selector( '.header-mobile-cart-wrap .header-cart-button .cryptozfree-svg-iconset' );
				$css->add_property( 'font-size', cryptozfree()->sub_option( 'header_mobile_cart_icon_size', 'size' ) . cryptozfree()->sub_option( 'header_mobile_cart_icon_size', 'unit' ) );
			}
		}
		// Header Social.
		$css->set_selector( '.header-social-wrap' );
		$css->add_property( 'margin', $this->render_measure( cryptozfree()->option( 'header_social_margin' ) ) );
		$css->set_selector( '.header-social-inner-wrap' );
		$css->add_property( 'font-size', $this->render_size( cryptozfree()->option( 'header_social_icon_size' ) ) );
		$css->add_property( 'margin-top', $this->render_negative_size( cryptozfree()->option( 'header_social_item_spacing' ) ) );
		$css->add_property( 'margin-left', $this->render_negative_half_size( cryptozfree()->option( 'header_social_item_spacing' ) ) );
		$css->add_property( 'margin-right', $this->render_negative_half_size( cryptozfree()->option( 'header_social_item_spacing' ) ) );
		$css->set_selector( '.header-social-wrap .header-social-inner-wrap .social-button' );
		$css->add_property( 'margin-top', $this->render_size( cryptozfree()->option( 'header_social_item_spacing' ) ) );
		$css->add_property( 'margin-left', $this->render_half_size( cryptozfree()->option( 'header_social_item_spacing' ) ) );
		$css->add_property( 'margin-right', $this->render_half_size( cryptozfree()->option( 'header_social_item_spacing' ) ) );
		if ( ! in_array( cryptozfree()->option( 'header_social_brand' ), array( 'always', 'untilhover' ), true ) ) {
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_social_color', 'color' ) ) );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_social_background', 'color' ) ) );
		}
		$css->add_property( 'border', $css->render_border( cryptozfree()->option( 'header_social_border' ) ) );
		$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'header_social_border_colors', 'color' ) ) );
		$css->add_property( 'border-radius', $this->render_size( cryptozfree()->sub_option( 'header_social_border_radius' ) ) );
		$css->set_selector( '.header-social-wrap .header-social-inner-wrap .social-button:hover' );
		if ( ! in_array( cryptozfree()->option( 'header_social_brand' ), array( 'always', 'onhover' ), true ) ) {
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_social_color', 'hover' ) ) );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_social_background', 'hover' ) ) );
		}
		$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'header_social_border_colors', 'hover' ) ) );
		$css->set_selector( '.header-social-wrap .social-button .social-label' );
		$css->render_font( cryptozfree()->option( 'header_social_typography' ), $css );

		// Mobile Header Social.
		$css->set_selector( '.header-mobile-social-wrap' );
		$css->add_property( 'margin', $this->render_measure( cryptozfree()->option( 'header_mobile_social_margin' ) ) );
		$css->set_selector( '.header-mobile-social-inner-wrap' );
		$css->add_property( 'font-size', $this->render_size( cryptozfree()->option( 'header_mobile_social_icon_size' ) ) );
		$css->add_property( 'margin-top', $this->render_negative_size( cryptozfree()->option( 'header_mobile_social_item_spacing' ) ) );
		$css->add_property( 'margin-left', $this->render_negative_half_size( cryptozfree()->option( 'header_mobile_social_item_spacing' ) ) );
		$css->add_property( 'margin-right', $this->render_negative_half_size( cryptozfree()->option( 'header_mobile_social_item_spacing' ) ) );
		$css->set_selector( '.header-mobile-social-wrap .header-mobile-social-inner-wrap .social-button' );
		$css->add_property( 'margin-top', $this->render_size( cryptozfree()->option( 'header_mobile_social_item_spacing' ) ) );
		$css->add_property( 'margin-left', $this->render_half_size( cryptozfree()->option( 'header_mobile_social_item_spacing' ) ) );
		$css->add_property( 'margin-right', $this->render_half_size( cryptozfree()->option( 'header_mobile_social_item_spacing' ) ) );
		if ( ! in_array( cryptozfree()->option( 'header_mobile_social_brand' ), array( 'always', 'untilhover' ), true ) ) {
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_mobile_social_color', 'color' ) ) );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_mobile_social_background', 'color' ) ) );
		}
		$css->add_property( 'border', $css->render_border( cryptozfree()->option( 'header_mobile_social_border' ) ) );
		$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'header_mobile_social_border_colors', 'color' ) ) );
		$css->add_property( 'border-radius', $this->render_size( cryptozfree()->sub_option( 'header_mobile_social_border_radius' ) ) );
		$css->set_selector( '.header-mobile-social-wrap .header-mobile-social-inner-wrap .social-button:hover' );
		if ( ! in_array( cryptozfree()->option( 'header_mobile_social_brand' ), array( 'always', 'onhover' ), true ) ) {
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_mobile_social_color', 'hover' ) ) );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_mobile_social_background', 'hover' ) ) );
		}
		$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'header_mobile_social_border_colors', 'hover' ) ) );
		$css->set_selector( '.header-mobile-social-wrap .social-button .social-label' );
		$css->render_font( cryptozfree()->option( 'header_mobile_social_typography' ), $css );

		// Search Toggle.
		$css->set_selector( '.search-toggle-open-container .search-toggle-open' );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_search_background', 'color' ) ) );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_search_color', 'color' ) ) );
		$css->add_property( 'padding', $this->render_measure( cryptozfree()->option( 'header_search_padding' ) ) );
		$css->add_property( 'margin', $this->render_measure( cryptozfree()->option( 'header_search_margin' ) ) );
		$css->render_font( cryptozfree()->option( 'header_search_typography' ), $css );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.search-toggle-open-container .search-toggle-open' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'header_search_typography' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'header_search_typography' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.search-toggle-open-container .search-toggle-open' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'header_search_typography' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'header_search_typography' ), 'mobile' ) );
		$css->stop_media_query();
		$css->set_selector( '.search-toggle-open-container .search-toggle-open.search-toggle-style-bordered' );
		$css->add_property( 'border', $css->render_border( cryptozfree()->option( 'header_search_border' ) ) );
		if ( cryptozfree()->option( 'custom_logo' ) ) {
			$search_size = cryptozfree()->option( 'header_search_icon_size' );
			foreach ( array( 'desktop', 'tablet', 'mobile' ) as $device ) {
				if ( isset( $search_size['size'] ) && isset( $search_size['size'][ $device ] ) && ! empty( $search_size['size'][ $device ] ) ) {
					$unit = ( isset( $search_size['unit'] ) && isset( $search_size['unit'][ $device ] ) && ! empty( $search_size['unit'][ $device ] ) ? $search_size['unit'][ $device ] : 'px' );
					if ( 'desktop' !== $device ) {
						$css->start_media_query( $media_query[ $device ] );
					}
					$css->set_selector( '.search-toggle-open-container .search-toggle-open .search-toggle-icon' );
					$css->add_property( 'font-size', $search_size['size'][ $device ] . $unit );
					if ( 'desktop' !== $device ) {
						$css->stop_media_query();
					}
				}
			}
		}
		$css->set_selector( '.search-toggle-open-container .search-toggle-open:hover, .search-toggle-open-container .search-toggle-open:focus' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_search_color', 'hover' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'header_search_background', 'hover' ) ) );
		// Search Modal.
		$css->set_selector( '#search-drawer .drawer-inner .drawer-content form input.search-field, #search-drawer .drawer-inner .drawer-content form .cryptozfree-search-icon-wrap, #search-drawer .drawer-header' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_search_modal_color', 'color' ) ) );
		$css->set_selector( '#search-drawer .drawer-inner .drawer-content form input.search-field:focus, #search-drawer .drawer-inner .drawer-content form input.search-submit:hover ~ .cryptozfree-search-icon-wrap, #search-drawer .drawer-inner .drawer-content form button[type="submit"]:hover ~ .cryptozfree-search-icon-wrap' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'header_search_modal_color', 'hover' ) ) );
		$css->set_selector( '#search-drawer .drawer-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_search_modal_background', 'desktop' ), $css );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '#search-drawer .drawer-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_search_modal_background', 'tablet' ), $css );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '#search-drawer .drawer-inner' );
		$css->render_background( cryptozfree()->sub_option( 'header_search_modal_background', 'mobile' ), $css );
		$css->stop_media_query();
		// Header Button.
		$css->set_selector( '.mobile-header-button-wrap .mobile-header-button' );
		$css->render_font( cryptozfree()->option( 'mobile_button_typography' ), $css );
		$css->add_property( 'margin', $this->render_measure( cryptozfree()->option( 'mobile_button_margin' ) ) );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'mobile_button_color', 'color' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'mobile_button_background', 'color' ) ) );
		$css->add_property( 'border', $css->render_border( cryptozfree()->option( 'mobile_button_border' ) ) );
		$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'mobile_button_border_colors', 'color' ) ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.mobile-header-button-wrap .mobile-header-button' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'mobile_button_typography' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'mobile_button_typography' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.mobile-header-button-wrap .mobile-header-button' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'mobile_button_typography' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'mobile_button_typography' ), 'mobile' ) );
		$css->stop_media_query();
		$css->set_selector( '.mobile-header-button-wrap .mobile-header-button:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'mobile_button_color', 'hover' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'mobile_button_background', 'hover' ) ) );
		$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'mobile_button_border_colors', 'hover' ) ) );
		// Header HTML.
		$css->set_selector( '.mobile-html' );
		$css->render_font( cryptozfree()->option( 'mobile_html_typography' ), $css );
		$css->add_property( 'margin', $this->render_measure( cryptozfree()->option( 'mobile_html_margin' ) ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.mobile-html' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'mobile_html_typography' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'mobile_html_typography' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.mobile-html' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'mobile_html_typography' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'mobile_html_typography' ), 'mobile' ) );
		$css->stop_media_query();
		$css->set_selector( '.mobile-html a' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'mobile_html_link_color', 'color' ) ) );
		$css->set_selector( '.mobile-html a:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'mobile_html_link_color', 'hover' ) ) );
		// Footer.
		$css->set_selector( '#colophon' );
		$css->render_background( cryptozfree()->sub_option( 'footer_wrap_background', 'desktop' ), $css );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '#colophon' );
		$css->render_background( cryptozfree()->sub_option( 'footer_wrap_background', 'tablet' ), $css );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '#colophon' );
		$css->render_background( cryptozfree()->sub_option( 'footer_wrap_background', 'mobile' ), $css );
		$css->stop_media_query();

		// Footer Middle.
		$css->set_selector( '.site-middle-footer-wrap .site-footer-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'footer_middle_background', 'desktop' ), $css );
		$css->render_font( cryptozfree()->option( 'footer_middle_widget_content' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'footer_middle_top_border', 'desktop' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'footer_middle_bottom_border', 'desktop' ) ) );
		$css->set_selector( '#colophon .site-middle-footer-wrap .site-footer-row-container-inner a' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'footer_middle_link_colors', 'color' ) ) );
		$css->set_selector( '#colophon .site-middle-footer-wrap .site-footer-row-container-inner a:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'footer_middle_link_colors', 'hover' ) ) );
		$css->set_selector( '.site-middle-footer-inner-wrap' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'footer_middle_height' ), 'desktop' ) );
		$css->add_property( 'padding-top', $this->render_range( cryptozfree()->option( 'footer_middle_top_spacing' ), 'desktop' ) );
		$css->add_property( 'padding-bottom', $this->render_range( cryptozfree()->option( 'footer_middle_bottom_spacing' ), 'desktop' ) );
		$css->add_property( 'grid-column-gap', $this->render_range( cryptozfree()->option( 'footer_middle_column_spacing' ), 'desktop' ) );
		$css->add_property( 'grid-row-gap', $this->render_range( cryptozfree()->option( 'footer_middle_column_spacing' ), 'desktop' ) );
		$css->set_selector( '.site-middle-footer-inner-wrap .widget' );
		$css->add_property( 'margin-bottom', $this->render_range( cryptozfree()->option( 'footer_middle_column_spacing' ), 'desktop' ) );
		$css->set_selector( '.site-middle-footer-inner-wrap .widget-area .widget-title' );
		$css->render_font( cryptozfree()->option( 'footer_middle_widget_title' ), $css );
		$css->set_selector( '.site-middle-footer-inner-wrap .site-footer-section:not(:last-child):after' );
		$css->add_property( 'border-right', $css->render_border( cryptozfree()->sub_option( 'footer_middle_column_border', 'desktop' ) ) );
		if ( $this->render_range( cryptozfree()->option( 'footer_middle_column_spacing' ), 'desktop' ) ) {
			$css->add_property( 'right', 'calc(-' . $this->render_range( cryptozfree()->option( 'footer_middle_column_spacing' ), 'desktop' ) . ' / 2)' );
		}
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.site-middle-footer-wrap .site-footer-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'footer_middle_background', 'tablet' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'footer_middle_top_border', 'tablet' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'footer_middle_bottom_border', 'tablet' ) ) );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'footer_middle_widget_content' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'footer_middle_widget_content' ), 'tablet' ) );
		$css->set_selector( '.site-middle-footer-inner-wrap .widget-area .widget-title' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'footer_middle_widget_title' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'footer_middle_widget_title' ), 'tablet' ) );
		$css->set_selector( '.site-middle-footer-inner-wrap' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'footer_middle_height' ), 'tablet' ) );
		$css->add_property( 'padding-top', $this->render_range( cryptozfree()->option( 'footer_middle_top_spacing' ), 'tablet' ) );
		$css->add_property( 'padding-bottom', $this->render_range( cryptozfree()->option( 'footer_middle_bottom_spacing' ), 'tablet' ) );
		$css->add_property( 'grid-column-gap', $this->render_range( cryptozfree()->option( 'footer_middle_column_spacing' ), 'tablet' ) );
		$css->add_property( 'grid-row-gap', $this->render_range( cryptozfree()->option( 'footer_middle_column_spacing' ), 'tablet' ) );
		$css->set_selector( '.site-middle-footer-inner-wrap .widget' );
		$css->add_property( 'margin-bottom', $this->render_range( cryptozfree()->option( 'footer_middle_column_spacing' ), 'tablet' ) );
		$css->set_selector( '.site-middle-footer-inner-wrap .site-footer-section:not(:last-child):after' );
		$css->add_property( 'border-right', $css->render_border( cryptozfree()->sub_option( 'footer_middle_column_border', 'tablet' ) ) );
		if ( $this->render_range( cryptozfree()->option( 'footer_middle_column_spacing' ), 'tablet' ) ) {
			$css->add_property( 'right', 'calc(-' . $this->render_range( cryptozfree()->option( 'footer_middle_column_spacing' ), 'tablet' ) . ' / 2)' );
		}
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.site-middle-footer-wrap .site-footer-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'footer_middle_background', 'mobile' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'footer_middle_top_border', 'mobile' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'footer_middle_bottom_border', 'mobile' ) ) );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'footer_middle_widget_content' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'footer_middle_widget_content' ), 'mobile' ) );
		$css->set_selector( '.site-middle-footer-inner-wrap .widget-area .widget-title' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'footer_middle_widget_title' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'footer_middle_widget_title' ), 'mobile' ) );
		$css->set_selector( '.site-middle-footer-inner-wrap' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'footer_middle_height' ), 'mobile' ) );
		$css->add_property( 'padding-top', $this->render_range( cryptozfree()->option( 'footer_middle_top_spacing' ), 'mobile' ) );
		$css->add_property( 'padding-bottom', $this->render_range( cryptozfree()->option( 'footer_middle_bottom_spacing' ), 'mobile' ) );
		$css->add_property( 'grid-column-gap', $this->render_range( cryptozfree()->option( 'footer_middle_column_spacing' ), 'mobile' ) );
		$css->add_property( 'grid-row-gap', $this->render_range( cryptozfree()->option( 'footer_middle_column_spacing' ), 'mobile' ) );
		$css->set_selector( '.site-middle-footer-inner-wrap .widget' );
		$css->add_property( 'margin-bottom', $this->render_range( cryptozfree()->option( 'footer_middle_column_spacing' ), 'mobile' ) );
		$css->set_selector( '.site-middle-footer-inner-wrap .site-footer-section:not(:last-child):after' );
		$css->add_property( 'border-right', $css->render_border( cryptozfree()->sub_option( 'footer_middle_column_border', 'mobile' ) ) );
		if ( $this->render_range( cryptozfree()->option( 'footer_middle_column_spacing' ), 'mobile' ) ) {
			$css->add_property( 'right', 'calc(-' . $this->render_range( cryptozfree()->option( 'footer_middle_column_spacing' ), 'mobile' ) . ' / 2)' );
		}
		$css->stop_media_query();

		// Footer top.
		$css->set_selector( '.site-top-footer-wrap .site-footer-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'footer_top_background', 'desktop' ), $css );
		$css->render_font( cryptozfree()->option( 'footer_top_widget_content' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'footer_top_top_border', 'desktop' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'footer_top_bottom_border', 'desktop' ) ) );
		$css->set_selector( '#colophon .site-top-footer-wrap .site-footer-row-container-inner a' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'footer_top_link_colors', 'color' ) ) );
		$css->set_selector( '#colophon .site-top-footer-wrap .site-footer-row-container-inner a:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'footer_top_link_colors', 'hover' ) ) );
		$css->set_selector( '.site-top-footer-inner-wrap' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'footer_top_height' ), 'desktop' ) );
		$css->add_property( 'padding-top', $this->render_range( cryptozfree()->option( 'footer_top_top_spacing' ), 'desktop' ) );
		$css->add_property( 'padding-bottom', $this->render_range( cryptozfree()->option( 'footer_top_bottom_spacing' ), 'desktop' ) );
		$css->add_property( 'grid-column-gap', $this->render_range( cryptozfree()->option( 'footer_top_column_spacing' ), 'desktop' ) );
		$css->add_property( 'grid-row-gap', $this->render_range( cryptozfree()->option( 'footer_top_column_spacing' ), 'desktop' ) );
		$css->set_selector( '.site-top-footer-inner-wrap .widget' );
		$css->add_property( 'margin-bottom', $this->render_range( cryptozfree()->option( 'footer_top_column_spacing' ), 'desktop' ) );
		$css->set_selector( '.site-top-footer-inner-wrap .widget-area .widget-title' );
		$css->render_font( cryptozfree()->option( 'footer_top_widget_title' ), $css );
		$css->set_selector( '.site-top-footer-inner-wrap .site-footer-section:not(:last-child):after' );
		$css->add_property( 'border-right', $css->render_border( cryptozfree()->sub_option( 'footer_top_column_border', 'desktop' ) ) );
		if ( $this->render_range( cryptozfree()->option( 'footer_top_column_spacing' ), 'desktop' ) ) {
			$css->add_property( 'right', 'calc(-' . $this->render_range( cryptozfree()->option( 'footer_top_column_spacing' ), 'desktop' ) . ' / 2)' );
		}
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.site-top-footer-wrap .site-footer-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'footer_top_background', 'tablet' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'footer_top_top_border', 'tablet' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'footer_top_bottom_border', 'tablet' ) ) );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'footer_top_widget_content' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'footer_top_widget_content' ), 'tablet' ) );
		$css->set_selector( '.site-top-footer-inner-wrap .widget-area .widget-title' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'footer_top_widget_title' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'footer_top_widget_title' ), 'tablet' ) );
		$css->set_selector( '.site-top-footer-inner-wrap' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'footer_top_height' ), 'tablet' ) );
		$css->add_property( 'padding-top', $this->render_range( cryptozfree()->option( 'footer_top_top_spacing' ), 'tablet' ) );
		$css->add_property( 'padding-bottom', $this->render_range( cryptozfree()->option( 'footer_top_bottom_spacing' ), 'tablet' ) );
		$css->add_property( 'grid-column-gap', $this->render_range( cryptozfree()->option( 'footer_top_column_spacing' ), 'tablet' ) );
		$css->add_property( 'grid-row-gap', $this->render_range( cryptozfree()->option( 'footer_top_column_spacing' ), 'tablet' ) );
		$css->set_selector( '.site-top-footer-inner-wrap .widget' );
		$css->add_property( 'margin-bottom', $this->render_range( cryptozfree()->option( 'footer_top_column_spacing' ), 'tablet' ) );
		$css->set_selector( '.site-top-footer-inner-wrap .site-footer-section:not(:last-child):after' );
		$css->add_property( 'border-right', $css->render_border( cryptozfree()->sub_option( 'footer_top_column_border', 'tablet' ) ) );
		if ( $this->render_range( cryptozfree()->option( 'footer_top_column_spacing' ), 'tablet' ) ) {
			$css->add_property( 'right', 'calc(-' . $this->render_range( cryptozfree()->option( 'footer_top_column_spacing' ), 'tablet' ) . ' / 2)' );
		}
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.site-top-footer-wrap .site-footer-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'footer_top_background', 'mobile' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'footer_top_top_border', 'mobile' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'footer_top_bottom_border', 'mobile' ) ) );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'footer_top_widget_content' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'footer_top_widget_content' ), 'mobile' ) );
		$css->set_selector( '.site-top-footer-inner-wrap .widget-area .widget-title' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'footer_top_widget_title' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'footer_top_widget_title' ), 'mobile' ) );
		$css->set_selector( '.site-top-footer-inner-wrap' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'footer_top_height' ), 'mobile' ) );
		$css->add_property( 'padding-top', $this->render_range( cryptozfree()->option( 'footer_top_top_spacing' ), 'mobile' ) );
		$css->add_property( 'padding-bottom', $this->render_range( cryptozfree()->option( 'footer_top_bottom_spacing' ), 'mobile' ) );
		$css->add_property( 'grid-column-gap', $this->render_range( cryptozfree()->option( 'footer_top_column_spacing' ), 'mobile' ) );
		$css->add_property( 'grid-row-gap', $this->render_range( cryptozfree()->option( 'footer_top_column_spacing' ), 'mobile' ) );
		$css->set_selector( '.site-top-footer-inner-wrap .widget' );
		$css->add_property( 'margin-bottom', $this->render_range( cryptozfree()->option( 'footer_top_column_spacing' ), 'mobile' ) );
		$css->set_selector( '.site-top-footer-inner-wrap .site-footer-section:not(:last-child):after' );
		$css->add_property( 'border-right', $css->render_border( cryptozfree()->sub_option( 'footer_top_column_border', 'mobile' ) ) );
		if ( $this->render_range( cryptozfree()->option( 'footer_top_column_spacing' ), 'mobile' ) ) {
			$css->add_property( 'right', 'calc(-' . $this->render_range( cryptozfree()->option( 'footer_top_column_spacing' ), 'mobile' ) . ' / 2)' );
		}
		$css->stop_media_query();

		// Footer bottom.
		$css->set_selector( '.site-bottom-footer-wrap .site-footer-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'footer_bottom_background', 'desktop' ), $css );
		$css->render_font( cryptozfree()->option( 'footer_bottom_widget_content' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'footer_bottom_top_border', 'desktop' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'footer_bottom_bottom_border', 'desktop' ) ) );
		$css->set_selector( '#colophon .site-bottom-footer-wrap .site-footer-row-container-inner a' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'footer_bottom_link_colors', 'color' ) ) );
		$css->set_selector( '#colophon .site-bottom-footer-wrap .site-footer-row-container-inner a:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'footer_bottom_link_colors', 'hover' ) ) );
		$css->set_selector( '.site-bottom-footer-inner-wrap' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'footer_bottom_height' ), 'desktop' ) );
		$css->add_property( 'padding-top', $this->render_range( cryptozfree()->option( 'footer_bottom_top_spacing' ), 'desktop' ) );
		$css->add_property( 'padding-bottom', $this->render_range( cryptozfree()->option( 'footer_bottom_bottom_spacing' ), 'desktop' ) );
		$css->add_property( 'grid-column-gap', $this->render_range( cryptozfree()->option( 'footer_bottom_column_spacing' ), 'desktop' ) );
		$css->set_selector( '.site-bottom-footer-inner-wrap .widget' );
		$css->add_property( 'margin-bottom', $this->render_range( cryptozfree()->option( 'footer_bottom_column_spacing' ), 'desktop' ) );
		$css->set_selector( '.site-bottom-footer-inner-wrap .widget-area .widget-title' );
		$css->render_font( cryptozfree()->option( 'footer_bottom_widget_title' ), $css );
		$css->set_selector( '.site-bottom-footer-inner-wrap .site-footer-section:not(:last-child):after' );
		$css->add_property( 'border-right', $css->render_border( cryptozfree()->sub_option( 'footer_bottom_column_border', 'desktop' ) ) );
		if ( $this->render_range( cryptozfree()->option( 'footer_bottom_column_spacing' ), 'desktop' ) ) {
			$css->add_property( 'right', 'calc(-' . $this->render_range( cryptozfree()->option( 'footer_bottom_column_spacing' ), 'desktop' ) . ' / 2)' );
		}
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.site-bottom-footer-wrap .site-footer-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'footer_bottom_background', 'tablet' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'footer_bottom_top_border', 'tablet' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'footer_bottom_bottom_border', 'tablet' ) ) );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'footer_bottom_widget_content' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'footer_bottom_widget_content' ), 'tablet' ) );
		$css->set_selector( '.site-bottom-footer-inner-wrap .widget-area .widget-title' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'footer_bottom_widget_title' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'footer_bottom_widget_title' ), 'tablet' ) );
		$css->set_selector( '.site-bottom-footer-inner-wrap' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'footer_bottom_height' ), 'tablet' ) );
		$css->add_property( 'padding-top', $this->render_range( cryptozfree()->option( 'footer_bottom_top_spacing' ), 'tablet' ) );
		$css->add_property( 'padding-bottom', $this->render_range( cryptozfree()->option( 'footer_bottom_bottom_spacing' ), 'tablet' ) );
		$css->add_property( 'grid-column-gap', $this->render_range( cryptozfree()->option( 'footer_bottom_column_spacing' ), 'tablet' ) );
		$css->set_selector( '.site-bottom-footer-inner-wrap .widget' );
		$css->add_property( 'margin-bottom', $this->render_range( cryptozfree()->option( 'footer_bottom_column_spacing' ), 'tablet' ) );
		$css->set_selector( '.site-bottom-footer-inner-wrap .site-footer-section:not(:last-child):after' );
		$css->add_property( 'border-right', $css->render_border( cryptozfree()->sub_option( 'footer_bottom_column_border', 'tablet' ) ) );
		if ( $this->render_range( cryptozfree()->option( 'footer_bottom_column_spacing' ), 'tablet' ) ) {
			$css->add_property( 'right', 'calc(-' . $this->render_range( cryptozfree()->option( 'footer_bottom_column_spacing' ), 'tablet' ) . ' / 2)' );
		}
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.site-bottom-footer-wrap .site-footer-row-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'footer_bottom_background', 'mobile' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'footer_bottom_top_border', 'mobile' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'footer_bottom_bottom_border', 'mobile' ) ) );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'footer_bottom_widget_content' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'footer_bottom_widget_content' ), 'mobile' ) );
		$css->set_selector( '.site-bottom-footer-inner-wrap .widget-area .widget-title' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'footer_bottom_widget_title' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'footer_bottom_widget_title' ), 'mobile' ) );
		$css->set_selector( '.site-bottom-footer-inner-wrap' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'footer_bottom_height' ), 'mobile' ) );
		$css->add_property( 'padding-top', $this->render_range( cryptozfree()->option( 'footer_bottom_top_spacing' ), 'mobile' ) );
		$css->add_property( 'padding-bottom', $this->render_range( cryptozfree()->option( 'footer_bottom_bottom_spacing' ), 'mobile' ) );
		$css->add_property( 'grid-column-gap', $this->render_range( cryptozfree()->option( 'footer_bottom_column_spacing' ), 'mobile' ) );
		$css->set_selector( '.site-bottom-footer-inner-wrap .widget' );
		$css->add_property( 'margin-bottom', $this->render_range( cryptozfree()->option( 'footer_bottom_column_spacing' ), 'mobile' ) );
		$css->set_selector( '.site-bottom-footer-inner-wrap .site-footer-section:not(:last-child):after' );
		$css->add_property( 'border-right', $css->render_border( cryptozfree()->sub_option( 'footer_bottom_column_border', 'mobile' ) ) );
		if ( $this->render_range( cryptozfree()->option( 'footer_bottom_column_spacing' ), 'mobile' ) ) {
			$css->add_property( 'right', 'calc(-' . $this->render_range( cryptozfree()->option( 'footer_bottom_column_spacing' ), 'mobile' ) . ' / 2)' );
		}
		$css->stop_media_query();

		// Footer Social.
		$css->set_selector( '.footer-social-wrap' );
		$css->add_property( 'margin', $this->render_measure( cryptozfree()->option( 'footer_social_margin' ) ) );
		$css->set_selector( '.footer-social-inner-wrap' );
		$css->add_property( 'font-size', $this->render_size( cryptozfree()->option( 'footer_social_icon_size' ) ) );
		$css->add_property( 'margin-top', $this->render_negative_size( cryptozfree()->option( 'footer_social_item_spacing' ) ) );
		$css->add_property( 'margin-left', $this->render_negative_half_size( cryptozfree()->option( 'footer_social_item_spacing' ) ) );
		$css->add_property( 'margin-right', $this->render_negative_half_size( cryptozfree()->option( 'footer_social_item_spacing' ) ) );
		$css->set_selector( '#colophon .site-footer-wrap .footer-social-wrap .social-button' );
		$css->add_property( 'margin-top', $this->render_size( cryptozfree()->option( 'footer_social_item_spacing' ) ) );
		$css->add_property( 'margin-left', $this->render_half_size( cryptozfree()->option( 'footer_social_item_spacing' ) ) );
		$css->add_property( 'margin-right', $this->render_half_size( cryptozfree()->option( 'footer_social_item_spacing' ) ) );
		if ( ! in_array( cryptozfree()->option( 'footer_social_brand' ), array( 'always', 'untilhover' ), true ) ) {
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'footer_social_color', 'color' ) ) );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'footer_social_background', 'color' ) ) );
		}
		$css->add_property( 'border', $css->render_border( cryptozfree()->option( 'footer_social_border' ) ) );
		$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'footer_social_border_colors', 'color' ) ) );
		$css->add_property( 'border-radius', $this->render_size( cryptozfree()->sub_option( 'footer_social_border_radius' ) ) );
		$css->set_selector( '#colophon .site-footer-wrap .footer-social-wrap .footer-social-inner-wrap .social-button:hover' );
		if ( ! in_array( cryptozfree()->option( 'footer_social_brand' ), array( 'always', 'onhover' ), true ) ) {
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'footer_social_color', 'hover' ) ) );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'footer_social_background', 'hover' ) ) );
		}
		$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'footer_social_border_colors', 'hover' ) ) );
		$css->set_selector( '.footer-social-wrap .social-button .social-label' );
		$css->render_font( cryptozfree()->option( 'footer_social_typography' ), $css );

		// Footer HTML.
		$css->set_selector( '#colophon .footer-html' );
		$css->render_font( cryptozfree()->option( 'footer_html_typography' ), $css );
		$css->add_property( 'margin', $this->render_measure( cryptozfree()->option( 'footer_html_margin' ) ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '#colophon .footer-html' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'footer_html_typography' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'footer_html_typography' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '#colophon .footer-html' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'footer_html_typography' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'footer_html_typography' ), 'mobile' ) );
		$css->stop_media_query();
		$css->set_selector( '#colophon .site-footer-row-container .site-footer-row .footer-html a' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'footer_html_link_color', 'color' ) ) );
		$css->set_selector( '#colophon .site-footer-row-container .site-footer-row .footer-html a:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'footer_html_link_color', 'hover' ) ) );
		// Scroll To Top.
		if ( cryptozfree()->option( 'scroll_up' ) ) {
			$css->set_selector( '#kt-scroll-up' );
			$css->add_property( 'border', $css->render_border( cryptozfree()->option( 'scroll_up_border' ) ) );
			$css->add_property( 'border-radius', $this->render_measure( cryptozfree()->option( 'scroll_up_radius' ) ) );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'scroll_up_color', 'color' ) ) );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'scroll_up_background', 'color' ) ) );
			$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'scroll_up_border_colors', 'color' ) ) );
			$css->add_property( 'bottom', $this->render_range( cryptozfree()->option( 'scroll_up_bottom_offset' ), 'desktop' ) );
			$css->add_property( 'font-size', $this->render_range( cryptozfree()->option( 'scroll_up_icon_size' ), 'desktop' ) );
			$css->add_property( 'padding', $this->render_responsive_measure( cryptozfree()->option( 'scroll_up_padding' ), 'desktop' ) );
			$css->set_selector( '#kt-scroll-up.scroll-up-side-right' );
			$css->add_property( 'right', $this->render_range( cryptozfree()->option( 'scroll_up_side_offset' ), 'desktop' ) );
			$css->set_selector( '#kt-scroll-up.scroll-up-side-left' );
			$css->add_property( 'left', $this->render_range( cryptozfree()->option( 'scroll_up_side_offset' ), 'desktop' ) );
			$css->set_selector( '#kt-scroll-up:hover' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'scroll_up_color', 'hover' ) ) );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'scroll_up_background', 'hover' ) ) );
			$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'scroll_up_border_colors', 'hover' ) ) );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '#kt-scroll-up' );
			$css->add_property( 'bottom', $this->render_range( cryptozfree()->option( 'scroll_up_bottom_offset' ), 'tablet' ) );
			$css->add_property( 'font-size', $this->render_range( cryptozfree()->option( 'scroll_up_icon_size' ), 'tablet' ) );
			$css->add_property( 'padding', $this->render_responsive_measure( cryptozfree()->option( 'scroll_up_padding' ), 'tablet' ) );
			$css->set_selector( '#kt-scroll-up.scroll-up-side-right' );
			$css->add_property( 'right', $this->render_range( cryptozfree()->option( 'scroll_up_side_offset' ), 'tablet' ) );
			$css->set_selector( '#kt-scroll-up.scroll-up-side-left' );
			$css->add_property( 'left', $this->render_range( cryptozfree()->option( 'scroll_up_side_offset' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '#kt-scroll-up' );
			$css->add_property( 'bottom', $this->render_range( cryptozfree()->option( 'scroll_up_bottom_offset' ), 'mobile' ) );
			$css->add_property( 'font-size', $this->render_range( cryptozfree()->option( 'scroll_up_icon_size' ), 'mobile' ) );
			$css->add_property( 'padding', $this->render_responsive_measure( cryptozfree()->option( 'scroll_up_padding' ), 'mobile' ) );
			$css->set_selector( '#kt-scroll-up.scroll-up-side-right' );
			$css->add_property( 'right', $this->render_range( cryptozfree()->option( 'scroll_up_side_offset' ), 'mobile' ) );
			$css->set_selector( '#kt-scroll-up.scroll-up-side-left' );
			$css->add_property( 'left', $this->render_range( cryptozfree()->option( 'scroll_up_side_offset' ), 'mobile' ) );
			$css->stop_media_query();
		}
		// Navigation.
		$css->set_selector( '#colophon .footer-navigation .footer-menu-container > ul > li > a' );
		$css->add_property( 'padding-left', $this->render_half_size( cryptozfree()->option( 'footer_navigation_spacing' ) ) );
		$css->add_property( 'padding-right', $this->render_half_size( cryptozfree()->option( 'footer_navigation_spacing' ) ) );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'footer_navigation_color', 'color' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'footer_navigation_background', 'color' ) ) );
		$css->set_selector( '#colophon .footer-navigation .footer-menu-container > ul li a' );
		$css->render_font( cryptozfree()->option( 'footer_navigation_typography' ), $css );
		$css->set_selector( '#colophon .footer-navigation .footer-menu-container > ul li a:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'footer_navigation_color', 'hover' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'footer_navigation_background', 'hover' ) ) );
		$css->set_selector( '#colophon .footer-navigation .footer-menu-container > ul li.current-menu-item > a' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'footer_navigation_color', 'active' ) ) );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'footer_navigation_background', 'active' ) ) );

		// Page Backgrounds.
		$css->set_selector( 'body.page' );
		$css->render_background( cryptozfree()->sub_option( 'page_background', 'desktop' ), $css );
		$css->set_selector( 'body.page .content-bg, body.content-style-unboxed.page .site' );
		$css->render_background( cryptozfree()->sub_option( 'page_content_background', 'desktop' ), $css );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( 'body.page' );
		$css->render_background( cryptozfree()->sub_option( 'page_background', 'tablet' ), $css );
		$css->set_selector( 'body.page .content-bg, body.content-style-unboxed.page .site' );
		$css->render_background( cryptozfree()->sub_option( 'page_content_background', 'tablet' ), $css );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( 'body.page' );
		$css->render_background( cryptozfree()->sub_option( 'page_background', 'mobile' ), $css );
		$css->set_selector( 'body.page .content-bg, body.content-style-unboxed.page .site' );
		$css->render_background( cryptozfree()->sub_option( 'page_content_background', 'mobile' ), $css );
		$css->stop_media_query();

		// Page Title.
		$css->set_selector( '.site .page-title h1' );
		$css->render_font( cryptozfree()->option( 'page_title_font' ), $css );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.site .page-title h1' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'page_title_font' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'page_title_font' ), 'tablet' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'page_title_font' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.site .page-title h1' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'page_title_font' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'page_title_font' ), 'mobile' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'page_title_font' ), 'mobile' ) );
		$css->stop_media_query();
		$css->set_selector( '.page-title .entry-meta' );
		$css->render_font( cryptozfree()->option( 'page_title_meta_font' ), $css );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'page_title_meta_color', 'color' ) ) );
		$css->set_selector( '.page-title .entry-meta a:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'page_title_meta_color', 'hover' ) ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.page-title .entry-meta' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'page_title_meta_font' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'page_title_meta_font' ), 'tablet' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'page_title_meta_font' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.page-title .entry-meta' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'page_title_meta_font' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'page_title_meta_font' ), 'mobile' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'page_title_meta_font' ), 'mobile' ) );
		$css->stop_media_query();
		$css->set_selector( '.page-title .cryptozfree-breadcrumbs' );
		$css->render_font( cryptozfree()->option( 'page_title_breadcrumb_font' ), $css );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'page_title_breadcrumb_color', 'color' ) ) );
		$css->set_selector( '.page-title .cryptozfree-breadcrumbs a:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'page_title_breadcrumb_color', 'hover' ) ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.page-title .cryptozfree-breadcrumbs' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'page_title_breadcrumb_font' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'page_title_breadcrumb_font' ), 'tablet' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'page_title_breadcrumb_font' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.page-title .cryptozfree-breadcrumbs' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'page_title_breadcrumb_font' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'page_title_breadcrumb_font' ), 'mobile' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'page_title_breadcrumb_font' ), 'mobile' ) );
		$css->stop_media_query();
		// Above Page Title.
		$css->set_selector( '.page-hero-section .entry-hero-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'page_title_background', 'desktop' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'page_title_top_border', 'desktop' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'page_title_bottom_border', 'desktop' ) ) );
		$css->set_selector( '.entry-hero.page-hero-section .entry-header' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'page_title_height' ), 'desktop' ) );
		$css->set_selector( '.page-hero-section .hero-section-overlay' );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'page_title_overlay_color', 'color' ) ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.page-hero-section .entry-hero-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'page_title_background', 'tablet' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'page_title_top_border', 'tablet' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'page_title_bottom_border', 'tablet' ) ) );
		$css->set_selector( '.page-hero-section .entry-header' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'page_title_height' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.page-hero-section .entry-hero-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'page_title_background', 'mobile' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'page_title_top_border', 'mobile' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'page_title_bottom_border', 'mobile' ) ) );
		$css->set_selector( '.entry-hero.page-hero-section .entry-header' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'page_title_height' ), 'mobile' ) );
		$css->stop_media_query();
		if ( ! get_option( 'show_avatars' ) ) {
			$css->set_selector( '.entry-author-style-center' );
			$css->add_property( 'padding-top', 'var(--global-md-spacing)' );
			$css->add_property( 'border-top', '1px solid var(--global-gray-500)' );
			$css->set_selector( '.entry-author-style-center .entry-author-avatar, .entry-meta .author-avatar' );
			$css->add_property( 'display', 'none' );
			$css->set_selector( '.entry-author-style-normal .entry-author-profile' );
			$css->add_property( 'padding-left', '0px' );
			$css->set_selector( '#comments .comment-meta' );
			$css->add_property( 'margin-left', '0px' );
		}
		if ( ! cryptozfree()->option( 'post_comments_date' ) ) {
			$css->set_selector( '.comment-metadata a:not(.comment-edit-link), .comment-body .edit-link:before' );
			$css->add_property( 'display', 'none' );
		}
		// 404 Backgrounds.
		$css->set_selector( 'body.error404' );
		$css->render_background( cryptozfree()->sub_option( '404_background', 'desktop' ), $css );
		$css->set_selector( 'body.error404 .content-bg, body.content-style-unboxed.error404 .site' );
		$css->render_background( cryptozfree()->sub_option( '404_content_background', 'desktop' ), $css );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( 'body.error404' );
		$css->render_background( cryptozfree()->sub_option( '404_background', 'tablet' ), $css );
		$css->set_selector( 'body.error404 .content-bg, body.content-style-unboxed.error404 .site' );
		$css->render_background( cryptozfree()->sub_option( '404_content_background', 'tablet' ), $css );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( 'body.error404' );
		$css->render_background( cryptozfree()->sub_option( '404_background', 'mobile' ), $css );
		$css->set_selector( 'body.error404 .content-bg, body.content-style-unboxed.error404 .site' );
		$css->render_background( cryptozfree()->sub_option( '404_content_background', 'mobile' ), $css );
		$css->stop_media_query();
		if ( is_singular( 'post' ) ) {
			// Post Backgrounds.
			$css->set_selector( 'body.single' );
			$css->render_background( cryptozfree()->sub_option( 'post_background', 'desktop' ), $css );
			$css->set_selector( 'body.single .content-bg, body.content-style-unboxed.single .site' );
			$css->render_background( cryptozfree()->sub_option( 'post_content_background', 'desktop' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( 'body.single' );
			$css->render_background( cryptozfree()->sub_option( 'post_background', 'tablet' ), $css );
			$css->set_selector( 'body.single .content-bg, body.content-style-unboxed.single .site' );
			$css->render_background( cryptozfree()->sub_option( 'post_content_background', 'tablet' ), $css );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( 'body.single' );
			$css->render_background( cryptozfree()->sub_option( 'post_background', 'mobile' ), $css );
			$css->set_selector( 'body.single .content-bg, body.content-style-unboxed.single .site' );
			$css->render_background( cryptozfree()->sub_option( 'post_content_background', 'mobile' ), $css );
			$css->stop_media_query();
			// Post Related Backgrounds.
			$css->set_selector( 'body.single .entry-related' );
			$css->render_background( cryptozfree()->sub_option( 'post_related_background', 'desktop' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( 'body.single .entry-related' );
			$css->render_background( cryptozfree()->sub_option( 'post_related_background', 'tablet' ), $css );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( 'body.single .entry-related' );
			$css->render_background( cryptozfree()->sub_option( 'post_related_background', 'mobile' ), $css );
			$css->stop_media_query();
			// Post Related Title.
			$css->set_selector( '.site .entry-related h2.entry-related-title' );
			$css->render_font( cryptozfree()->option( 'post_related_title_font' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.site .entry-related h2.entry-related-title' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'post_related_title_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'post_related_title_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'post_related_title_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.site .entry-related h2.entry-related-title' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'post_related_title_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'post_related_title_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'post_related_title_font' ), 'mobile' ) );
			$css->stop_media_query();
			// Post Title.
			$css->set_selector( '.site .post-title h1' );
			$css->render_font( cryptozfree()->option( 'post_title_font' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.site .post-title h1' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'post_title_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'post_title_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'post_title_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.site .post-title h1' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'post_title_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'post_title_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'post_title_font' ), 'mobile' ) );
			$css->stop_media_query();
			// Post Title Categories.
			$css->set_selector( '.post-title .entry-taxonomies, .post-title .entry-taxonomies a' );
			$css->render_font( cryptozfree()->option( 'post_title_category_font' ), $css );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'post_title_category_color', 'color' ) ) );
			$css->set_selector( '.post-title .entry-taxonomies a:hover' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'post_title_category_color', 'hover' ) ) );
			$css->set_selector( '.post-title .entry-taxonomies .category-style-pill a' );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'post_title_category_color', 'color' ) ) );
			$css->set_selector( '.post-title .entry-taxonomies .category-style-pill a:hover' );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'post_title_category_color', 'hover' ) ) );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.post-title .entry-taxonomies' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'post_title_category_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'post_title_category_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'post_title_category_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.post-title .entry-taxonomies' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'post_title_category_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'post_title_category_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'post_title_category_font' ), 'mobile' ) );
			$css->stop_media_query();
			// Post Title meta.
			$css->set_selector( '.post-title .entry-meta' );
			$css->render_font( cryptozfree()->option( 'post_title_meta_font' ), $css );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'post_title_meta_color', 'color' ) ) );
			$css->set_selector( '.post-title .entry-meta a:hover' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'post_title_meta_color', 'hover' ) ) );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.post-title .entry-meta' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'post_title_meta_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'post_title_meta_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'post_title_meta_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.post-title .entry-meta' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'post_title_meta_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'post_title_meta_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'post_title_meta_font' ), 'mobile' ) );
			$css->stop_media_query();
			// Post Title Breadcrumbs.
			$css->set_selector( '.post-title .cryptozfree-breadcrumbs' );
			$css->render_font( cryptozfree()->option( 'post_title_breadcrumb_font' ), $css );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'post_title_breadcrumb_color', 'color' ) ) );
			$css->set_selector( '.post-title .cryptozfree-breadcrumbs a:hover' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'post_title_breadcrumb_color', 'hover' ) ) );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.post-title .cryptozfree-breadcrumbs' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'post_title_breadcrumb_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'post_title_breadcrumb_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'post_title_breadcrumb_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.post-title .cryptozfree-breadcrumbs' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'post_title_breadcrumb_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'post_title_breadcrumb_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'post_title_breadcrumb_font' ), 'mobile' ) );
			$css->stop_media_query();
			// Post Title Excerpt.
			$css->set_selector( '.post-title .title-entry-excerpt' );
			$css->render_font( cryptozfree()->option( 'post_title_excerpt_font' ), $css );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'post_title_excerpt_color', 'color' ) ) );
			$css->set_selector( '.post-title .title-entry-excerpt a:hover' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'post_title_excerpt_color', 'hover' ) ) );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.post-title .title-entry-excerpt' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'post_title_excerpt_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'post_title_excerpt_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'post_title_excerpt_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.post-title .title-entry-excerpt' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'post_title_excerpt_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'post_title_excerpt_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'post_title_excerpt_font' ), 'mobile' ) );
			$css->stop_media_query();
			// Above Post Title.
			$css->set_selector( '.post-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'post_title_background', 'desktop' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'post_title_top_border', 'desktop' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'post_title_bottom_border', 'desktop' ) ) );
			$css->set_selector( '.entry-hero.post-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'post_title_height' ), 'desktop' ) );
			$css->set_selector( '.post-hero-section .hero-section-overlay' );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'post_title_overlay_color', 'color' ) ) );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.post-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'post_title_background', 'tablet' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'post_title_top_border', 'tablet' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'post_title_bottom_border', 'tablet' ) ) );
			$css->set_selector( '.post-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'post_title_height' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.post-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'post_title_background', 'mobile' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'post_title_top_border', 'mobile' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'post_title_bottom_border', 'mobile' ) ) );
			$css->set_selector( '.entry-hero.post-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'post_title_height' ), 'mobile' ) );
			$css->stop_media_query();
		}
		// Above Archive Post Title.
		$css->set_selector( '.post-archive-hero-section .entry-hero-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'post_archive_title_background', 'desktop' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'post_archive_title_top_border', 'desktop' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'post_archive_title_bottom_border', 'desktop' ) ) );
		$css->set_selector( '.entry-hero.post-archive-hero-section .entry-header' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'post_archive_title_height' ), 'desktop' ) );
		$css->set_selector( '.post-archive-hero-section .hero-section-overlay' );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'post_archive_title_overlay_color', 'color' ) ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.post-archive-hero-section .entry-hero-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'post_archive_title_background', 'tablet' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'post_archive_title_top_border', 'tablet' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'post_archive_title_bottom_border', 'tablet' ) ) );
		$css->set_selector( '.post-archive-hero-section .entry-header' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'post_archive_title_height' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.post-archive-hero-section .entry-hero-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'post_archive_title_background', 'mobile' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'post_archive_title_top_border', 'mobile' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'post_archive_title_bottom_border', 'mobile' ) ) );
		$css->set_selector( '.entry-hero.post-archive-hero-section .entry-header' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'post_archive_title_height' ), 'mobile' ) );
		$css->stop_media_query();
		$css->set_selector( '.site .post-archive-title h1' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'post_archive_title_color', 'color' ) ) );
		$css->set_selector( '.post-archive-title .cryptozfree-breadcrumbs' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'post_archive_title_breadcrumb_color', 'color' ) ) );
		$css->set_selector( '.post-archive-title .cryptozfree-breadcrumbs a:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'post_archive_title_breadcrumb_color', 'hover' ) ) );
		$css->set_selector( '.post-archive-title .archive-description' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'post_archive_title_description_color', 'color' ) ) );
		$css->set_selector( '.post-archive-title .archive-description a:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'post_archive_title_description_color', 'hover' ) ) );
		// Archive Backgrounds.
		$css->set_selector( 'body.archive, body.blog' );
		$css->render_background( cryptozfree()->sub_option( 'post_archive_background', 'desktop' ), $css );
		$css->set_selector( 'body.archive .content-bg, body.content-style-unboxed.archive .site, body.blog .content-bg, body.content-style-unboxed.blog .site' );
		$css->render_background( cryptozfree()->sub_option( 'post_archive_content_background', 'desktop' ), $css );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( 'body.archive, body.blog' );
		$css->render_background( cryptozfree()->sub_option( 'post_archive_background', 'tablet' ), $css );
		$css->set_selector( 'body.archive .content-bg, body.content-style-unboxed.archive .site, body.blog .content-bg, body.content-style-unboxed.blog .site' );
		$css->render_background( cryptozfree()->sub_option( 'post_archive_content_background', 'tablet' ), $css );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( 'body.archive, body.blog' );
		$css->render_background( cryptozfree()->sub_option( 'post_archive_background', 'mobile' ), $css );
		$css->set_selector( 'body.archive .content-bg, body.content-style-unboxed.archive .site, body.blog .content-bg, body.content-style-unboxed.blog .site' );
		$css->render_background( cryptozfree()->sub_option( 'post_archive_content_background', 'mobile' ), $css );
		$css->stop_media_query();
		// Post archive item title.
		$css->set_selector( '.loop-entry.type-post h2.entry-title' );
		$css->render_font( cryptozfree()->option( 'post_archive_item_title_font' ), $css );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.loop-entry.type-post h2.entry-title' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'post_archive_item_title_font' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'post_archive_item_title_font' ), 'tablet' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'post_archive_item_title_font' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.loop-entry.type-post h2.entry-title' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'post_archive_item_title_font' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'post_archive_item_title_font' ), 'mobile' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'post_archive_item_title_font' ), 'mobile' ) );
		$css->stop_media_query();
		// Post archive item category.
		$css->set_selector( '.loop-entry.type-post .entry-taxonomies' );
		$css->render_font( cryptozfree()->option( 'post_archive_item_category_font' ), $css );
		$css->set_selector( '.loop-entry.type-post .entry-taxonomies, .loop-entry.type-post .entry-taxonomies a' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'post_archive_item_category_color', 'color' ) ) );
		$css->set_selector( '.loop-entry.type-post .entry-taxonomies .category-style-pill a' );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'post_archive_item_category_color', 'color' ) ) );
		$css->set_selector( '.loop-entry.type-post .entry-taxonomies a:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'post_archive_item_category_color', 'hover' ) ) );
		$css->set_selector( '.loop-entry.type-post .entry-taxonomies .category-style-pill a:hover' );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'post_archive_item_category_color', 'hover' ) ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.loop-entry.type-post .entry-taxonomies' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'post_archive_item_category_font' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'post_archive_item_category_font' ), 'tablet' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'post_archive_item_category_font' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.loop-entry.type-post .entry-taxonomies' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'post_archive_item_category_font' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'post_archive_item_category_font' ), 'mobile' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'post_archive_item_category_font' ), 'mobile' ) );
		$css->stop_media_query();
		// Post archive item meta.
		$css->set_selector( '.loop-entry.type-post .entry-meta' );
		$css->render_font( cryptozfree()->option( 'post_archive_item_meta_font' ), $css );
		$css->set_selector( '.loop-entry.type-post .entry-meta' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'post_archive_item_meta_color', 'color' ) ) );
		$css->set_selector( '.loop-entry.type-post .entry-meta a:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'post_archive_item_meta_color', 'hover' ) ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.loop-entry.type-post .entry-meta' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'post_archive_item_meta_font' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'post_archive_item_meta_font' ), 'tablet' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'post_archive_item_meta_font' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.loop-entry.type-post .entry-meta' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'post_archive_item_meta_font' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'post_archive_item_meta_font' ), 'mobile' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'post_archive_item_meta_font' ), 'mobile' ) );
		$css->stop_media_query();
		// Search results Title.
		$css->set_selector( '.search-archive-hero-section .entry-hero-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'search_archive_title_background', 'desktop' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'search_archive_title_top_border', 'desktop' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'search_archive_title_bottom_border', 'desktop' ) ) );
		$css->set_selector( '.entry-hero.search-archive-hero-section .entry-header' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'search_archive_title_height' ), 'desktop' ) );
		$css->set_selector( '.search-archive-hero-section .hero-section-overlay' );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'search_archive_title_overlay_color', 'color' ) ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.search-archive-hero-section .entry-hero-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'search_archive_title_background', 'tablet' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'search_archive_title_top_border', 'tablet' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'search_archive_title_bottom_border', 'tablet' ) ) );
		$css->set_selector( '.search-archive-hero-section .entry-header' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'search_archive_title_height' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.search-archive-hero-section .entry-hero-container-inner' );
		$css->render_background( cryptozfree()->sub_option( 'search_archive_title_background', 'mobile' ), $css );
		$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'search_archive_title_top_border', 'mobile' ) ) );
		$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'search_archive_title_bottom_border', 'mobile' ) ) );
		$css->set_selector( '.entry-hero.search-archive-hero-section .entry-header' );
		$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'search_archive_title_height' ), 'mobile' ) );
		$css->stop_media_query();
		$css->set_selector( '.search-archive-title h1' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'search_archive_title_color', 'color' ) ) );
		// Search Results Backgrounds.
		$css->set_selector( 'body.search-results' );
		$css->render_background( cryptozfree()->sub_option( 'search_archive_background', 'desktop' ), $css );
		$css->set_selector( 'body.search-results .content-bg, body.content-style-unboxed.search-results .site' );
		$css->render_background( cryptozfree()->sub_option( 'search_archive_content_background', 'desktop' ), $css );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( 'body.search-results' );
		$css->render_background( cryptozfree()->sub_option( 'search_archive_background', 'tablet' ), $css );
		$css->set_selector( 'body.search-results .content-bg, body.content-style-unboxed.search-results .site' );
		$css->render_background( cryptozfree()->sub_option( 'search_archive_content_background', 'tablet' ), $css );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( 'body.search-results' );
		$css->render_background( cryptozfree()->sub_option( 'search_archive_background', 'mobile' ), $css );
		$css->set_selector( 'body.search-results .content-bg, body.content-style-unboxed.search-results .site' );
		$css->render_background( cryptozfree()->sub_option( 'search_archive_content_background', 'mobile' ), $css );
		$css->stop_media_query();
		// Search Results item title.
		$css->set_selector( '.search-results .loop-entry h2.entry-title' );
		$css->render_font( cryptozfree()->option( 'search_archive_item_title_font' ), $css );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.search-results .loop-entry h2.entry-title' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'search_archive_item_title_font' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'search_archive_item_title_font' ), 'tablet' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'search_archive_item_title_font' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.search-results .loop-entry h2.entry-title' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'search_archive_item_title_font' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'search_archive_item_title_font' ), 'mobile' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'search_archive_item_title_font' ), 'mobile' ) );
		$css->stop_media_query();
		// Search Results item category.
		$css->set_selector( '.search-results .loop-entry .entry-taxonomies' );
		$css->render_font( cryptozfree()->option( 'search_archive_item_category_font' ), $css );
		$css->set_selector( '.search-results .loop-entry .entry-taxonomies, .search-results .loop-entry .entry-taxonomies a' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'search_archive_item_category_color', 'color' ) ) );
		$css->set_selector( '.loop-entry .entry-taxonomies .category-style-pill a' );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'search_archive_item_category_color', 'color' ) ) );
		$css->set_selector( '.search-results .loop-entry .entry-taxonomies a:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'search_archive_item_category_color', 'hover' ) ) );
		$css->set_selector( '.loop-entry .entry-taxonomies .category-style-pill a:hover' );
		$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'search_archive_item_category_color', 'hover' ) ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.search-results .loop-entry .entry-taxonomies' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'search_archive_item_category_font' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'search_archive_item_category_font' ), 'tablet' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'search_archive_item_category_font' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.search-results .loop-entry .entry-taxonomies' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'search_archive_item_category_font' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'search_archive_item_category_font' ), 'mobile' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'search_archive_item_category_font' ), 'mobile' ) );
		$css->stop_media_query();
		// Search Results item meta.
		$css->set_selector( '.search-results .loop-entry .entry-meta' );
		$css->render_font( cryptozfree()->option( 'search_archive_item_meta_font' ), $css );
		$css->set_selector( '.search-results .loop-entry .entry-meta' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'search_archive_item_meta_color', 'color' ) ) );
		$css->set_selector( '.search-results .loop-entry .entry-meta a:hover' );
		$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'search_archive_item_meta_color', 'hover' ) ) );
		$css->start_media_query( $media_query['tablet'] );
		$css->set_selector( '.search-results .loop-entry .entry-meta' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'search_archive_item_meta_font' ), 'tablet' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'search_archive_item_meta_font' ), 'tablet' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'search_archive_item_meta_font' ), 'tablet' ) );
		$css->stop_media_query();
		$css->start_media_query( $media_query['mobile'] );
		$css->set_selector( '.search-results .loop-entry .entry-meta' );
		$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'search_archive_item_meta_font' ), 'mobile' ) );
		$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'search_archive_item_meta_font' ), 'mobile' ) );
		$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'search_archive_item_meta_font' ), 'mobile' ) );
		$css->stop_media_query();
		if ( class_exists( 'woocommerce' ) ) {
			if ( cryptozfree()->option( 'custom_quantity' ) ) {
				$css->set_selector( '.woocommerce table.shop_table td.product-quantity' );
				$css->add_property( 'min-width', '130px' );
			}
			// Shop Notice.
			$css->set_selector( '.woocommerce-demo-store .woocommerce-store-notice' );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'woo_store_notice_background', 'color' ) ) );
			$css->set_selector( '.woocommerce-demo-store .woocommerce-store-notice a, .woocommerce-demo-store .woocommerce-store-notice' );
			$css->render_font( cryptozfree()->option( 'woo_store_notice_font' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.woocommerce-demo-store .woocommerce-store-notice a, .woocommerce-demo-store .woocommerce-store-notice' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'product_title_breadcrumb_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'product_title_breadcrumb_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'product_title_breadcrumb_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.woocommerce-demo-store .woocommerce-store-notice a, .woocommerce-demo-store .woocommerce-store-notice' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'product_title_breadcrumb_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'product_title_breadcrumb_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'product_title_breadcrumb_font' ), 'mobile' ) );
			$css->stop_media_query();
			// Above Product Title.
			$css->set_selector( '.product-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'product_title_background', 'desktop' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'product_title_top_border', 'desktop' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'product_title_bottom_border', 'desktop' ) ) );
			$css->set_selector( '.entry-hero.product-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'product_title_height' ), 'desktop' ) );
			$css->set_selector( '.product-hero-section .hero-section-overlay' );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'product_title_overlay_color', 'color' ) ) );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.product-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'product_title_background', 'tablet' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'product_title_top_border', 'tablet' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'product_title_bottom_border', 'tablet' ) ) );
			$css->set_selector( '.product-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'product_title_height' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.product-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'product_title_background', 'mobile' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'product_title_top_border', 'mobile' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'product_title_bottom_border', 'mobile' ) ) );
			$css->set_selector( '.entry-hero.product-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'product_title_height' ), 'mobile' ) );
			$css->stop_media_query();
			// Product Breadcrumbs.
			$css->set_selector( '.product-title .cryptozfree-breadcrumbs' );
			$css->render_font( cryptozfree()->option( 'product_title_breadcrumb_font' ), $css );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'product_title_breadcrumb_color', 'color' ) ) );
			$css->set_selector( '.product-title .cryptozfree-breadcrumbs a:hover' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'product_title_breadcrumb_color', 'hover' ) ) );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.product-title .cryptozfree-breadcrumbs' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'product_title_breadcrumb_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'product_title_breadcrumb_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'product_title_breadcrumb_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.product-title .cryptozfree-breadcrumbs' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'product_title_breadcrumb_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'product_title_breadcrumb_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'product_title_breadcrumb_font' ), 'mobile' ) );
			$css->stop_media_query();
			// Product Title Category.
			$css->set_selector( '.product-title .single-category' );
			$css->render_font( cryptozfree()->option( 'product_above_category_font' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.product-title .single-category' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'product_above_category_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'product_above_category_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'product_above_category_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.product-title .single-category' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'product_above_category_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'product_above_category_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'product_above_category_font' ), 'mobile' ) );
			$css->stop_media_query();
			// Product Above Extra Title.
			$css->set_selector( '.site .product-hero-section .extra-title' );
			$css->render_font( cryptozfree()->option( 'product_above_title_font' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.site .product-hero-section .extra-title' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'product_above_title_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'product_above_title_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'product_above_title_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.site .product-hero-section .extra-title' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'product_above_title_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'product_above_title_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'product_above_title_font' ), 'mobile' ) );
			$css->stop_media_query();
			// Product Title.
			$css->set_selector( '.woocommerce div.product .product_title' );
			$css->render_font( cryptozfree()->option( 'product_title_font' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.woocommerce div.product .product_title' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'product_title_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'product_title_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'product_title_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.woocommerce div.product .product_title' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'product_title_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'product_title_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'product_title_font' ), 'mobile' ) );
			$css->stop_media_query();
			$css->set_selector( '.woocommerce div.product .product-single-category' );
			$css->render_font( cryptozfree()->option( 'product_single_category_font' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.woocommerce div.product .product-single-category' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'product_single_category_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'product_single_category_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'product_single_category_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.woocommerce div.product .product-single-category' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'product_single_category_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'product_single_category_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'product_single_category_font' ), 'mobile' ) );
			$css->stop_media_query();
			// Product Single Backgrounds.
			$css->set_selector( 'body.single-product' );
			$css->render_background( cryptozfree()->sub_option( 'product_background', 'desktop' ), $css );
			$css->set_selector( 'body.single-product .content-bg, body.content-style-unboxed.single-product .site' );
			$css->render_background( cryptozfree()->sub_option( 'product_content_background', 'desktop' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( 'body.single-product' );
			$css->render_background( cryptozfree()->sub_option( 'product_background', 'tablet' ), $css );
			$css->set_selector( 'body.single-product .content-bg, body.content-style-unboxed.single-product .site' );
			$css->render_background( cryptozfree()->sub_option( 'product_content_background', 'tablet' ), $css );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( 'body.single-product' );
			$css->render_background( cryptozfree()->sub_option( 'product_background', 'mobile' ), $css );
			$css->set_selector( 'body.single-product .content-bg, body.content-style-unboxed.single-product .site' );
			$css->render_background( cryptozfree()->sub_option( 'product_content_background', 'mobile' ), $css );
			$css->stop_media_query();
			// Product Archive Backgrounds.
			$css->set_selector( 'body.archive.tax-woo-product, body.post-type-archive-product' );
			$css->render_background( cryptozfree()->sub_option( 'product_archive_background', 'desktop' ), $css );
			$css->set_selector( 'body.archive.tax-woo-product .content-bg, body.content-style-unboxed.archive.tax-woo-product .site, body.post-type-archive-product .content-bg, body.content-style-unboxed.archive.post-type-archive-product .site, body.content-style-unboxed.archive.tax-woo-product .content-bg.loop-entry .content-bg:not(.loop-entry), body.content-style-unboxed.post-type-archive-product .content-bg.loop-entry .content-bg:not(.loop-entry)' );
			$css->render_background( cryptozfree()->sub_option( 'product_archive_content_background', 'desktop' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( 'body.archive.tax-woo-product, body.post-type-archive-product' );
			$css->render_background( cryptozfree()->sub_option( 'product_archive_background', 'tablet' ), $css );
			$css->set_selector( 'body.archive.tax-woo-product .content-bg, body.content-style-unboxed.archive.tax-woo-product .site, body.post-type-archive-product .content-bg, body.content-style-unboxed.archive.post-type-archive-product .site, body.content-style-unboxed.archive.tax-woo-product .content-bg.loop-entry .content-bg:not(.loop-entry), body.content-style-unboxed.post-type-archive-product .content-bg.loop-entry .content-bg:not(.loop-entry)' );
			$css->render_background( cryptozfree()->sub_option( 'product_archive_content_background', 'tablet' ), $css );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( 'body.archive.tax-woo-product, body.post-type-archive-product' );
			$css->render_background( cryptozfree()->sub_option( 'product_archive_background', 'mobile' ), $css );
			$css->set_selector( 'body.archive.tax-woo-product .content-bg, body.content-style-unboxed.archive.tax-woo-product .site, body.post-type-archive-product .content-bg, body.content-style-unboxed.archive.post-type-archive-product .site, body.content-style-unboxed.archive.tax-woo-product .content-bg.loop-entry .content-bg:not(.loop-entry), body.content-style-unboxed.post-type-archive-product .content-bg.loop-entry .content-bg:not(.loop-entry)' );
			$css->render_background( cryptozfree()->sub_option( 'product_archive_content_background', 'mobile' ), $css );
			$css->stop_media_query();
			// Product Archive Columns Mobile.
			if ( 'twocolumn' === cryptozfree()->option( 'product_archive_mobile_columns' ) ) {
				$css->start_media_query( $media_query['mobile'] );
				$css->set_selector( '.woocommerce ul.products:not(.products-list-view)' );
				$css->add_property( 'grid-template-columns', 'repeat(2, minmax(0, 1fr))' );
				$css->add_property( 'column-gap', '0.5rem' );
				$css->add_property( 'grid-row-gap', '0.5rem' );
				$css->stop_media_query();
			}
			// Product Archive Title.
			$css->set_selector( '.product-archive-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'product_archive_title_background', 'desktop' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'product_archive_title_top_border', 'desktop' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'product_archive_title_bottom_border', 'desktop' ) ) );
			$css->set_selector( '.entry-hero.product-archive-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'product_archive_title_height' ), 'desktop' ) );
			$css->set_selector( '.product-archive-hero-section .hero-section-overlay' );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'product_archive_title_overlay_color', 'color' ) ) );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.product-archive-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'product_archive_title_background', 'tablet' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'product_archive_title_top_border', 'tablet' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'product_archive_title_bottom_border', 'tablet' ) ) );
			$css->set_selector( '.product-archive-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'product_archive_title_height' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.product-archive-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'product_archive_title_background', 'mobile' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'product_archive_title_top_border', 'mobile' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'product_archive_title_bottom_border', 'mobile' ) ) );
			$css->set_selector( '.entry-hero.product-archive-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'product_archive_title_height' ), 'mobile' ) );
			$css->stop_media_query();
			$css->set_selector( '.product-archive-title h1' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'product_archive_title_color', 'color' ) ) );
			$css->set_selector( '.product-archive-title .cryptozfree-breadcrumbs' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'product_archive_title_breadcrumb_color', 'color' ) ) );
			$css->set_selector( '.product-archive-title .cryptozfree-breadcrumbs a:hover' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'product_archive_title_breadcrumb_color', 'hover' ) ) );
			$css->set_selector( '.product-archive-title .archive-description' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'product_archive_title_description_color', 'color' ) ) );
			$css->set_selector( '.product-archive-title .archive-description a:hover' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'product_archive_title_description_color', 'hover' ) ) );
			// Product Archive Title Font.
			$css->set_selector( '.woocommerce ul.products li.product h3, .woocommerce ul.products li.product .product-details .woocommerce-loop-product__title, .woocommerce ul.products li.product .product-details .woocommerce-loop-category__title, .wc-block-grid__products .wc-block-grid__product .wc-block-grid__product-title' );
			$css->render_font( cryptozfree()->option( 'product_archive_title_font' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.woocommerce ul.products li.product h3, .woocommerce ul.products li.product .product-details .woocommerce-loop-product__title, .woocommerce ul.products li.product .product-details .woocommerce-loop-category__title, .wc-block-grid__products .wc-block-grid__product .wc-block-grid__product-title' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'product_archive_title_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'product_archive_title_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'product_archive_title_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.woocommerce ul.products li.product h3, .woocommerce ul.products li.product .product-details .woocommerce-loop-product__title, .woocommerce ul.products li.product .product-details .woocommerce-loop-category__title, .wc-block-grid__products .wc-block-grid__product .wc-block-grid__product-title' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'product_archive_title_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'product_archive_title_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'product_archive_title_font' ), 'mobile' ) );
			$css->stop_media_query();
			// Product Archive Price Font.
			$css->set_selector( '.woocommerce ul.products li.product .product-details .price, .wc-block-grid__products .wc-block-grid__product .wc-block-grid__product-price' );
			$css->render_font( cryptozfree()->option( 'product_archive_price_font' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.woocommerce ul.products li.product .product-details .price, .wc-block-grid__products .wc-block-grid__product .wc-block-grid__product-price' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'product_archive_price_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'product_archive_price_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'product_archive_price_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.woocommerce ul.products li.product .product-details .price, .wc-block-grid__products .wc-block-grid__product .wc-block-grid__product-price' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'product_archive_price_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'product_archive_price_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'product_archive_price_font' ), 'mobile' ) );
			$css->stop_media_query();
		}
		// Learndash.
		if ( class_exists( 'SFWD_LMS' ) ) {
			// Course Title.
			$css->set_selector( '.sfwd-courses-title h1' );
			$css->render_font( cryptozfree()->option( 'sfwd-courses_title_font' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.sfwd-courses-title h1' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sfwd-courses_title_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sfwd-courses_title_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'sfwd-courses_title_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.sfwd-courses-title h1' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sfwd-courses_title_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sfwd-courses_title_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'sfwd-courses_title_font' ), 'mobile' ) );
			$css->stop_media_query();
			// Course Title Breadcrumbs.
			$css->set_selector( '.sfwd-courses-title .cryptozfree-breadcrumbs' );
			$css->render_font( cryptozfree()->option( 'sfwd-courses_title_breadcrumb_font' ), $css );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'sfwd-courses_title_breadcrumb_color', 'color' ) ) );
			$css->set_selector( '.sfwd-courses-title .cryptozfree-breadcrumbs a:hover' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'sfwd-courses_title_breadcrumb_color', 'hover' ) ) );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.sfwd-courses-title .cryptozfree-breadcrumbs' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sfwd-courses_title_breadcrumb_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sfwd-courses_title_breadcrumb_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'sfwd-courses_title_breadcrumb_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.sfwd-courses-title .cryptozfree-breadcrumbs' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sfwd-courses_title_breadcrumb_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sfwd-courses_title_breadcrumb_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'sfwd-courses_title_breadcrumb_font' ), 'mobile' ) );
			$css->stop_media_query();
			// Above Course Title.
			$css->set_selector( '.sfwd-courses-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'sfwd-courses_title_background', 'desktop' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'sfwd-courses_title_top_border', 'desktop' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'sfwd-courses_title_bottom_border', 'desktop' ) ) );
			$css->set_selector( '.entry-hero.sfwd-courses-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'sfwd-courses_title_height' ), 'desktop' ) );
			$css->set_selector( '.sfwd-courses-hero-section .hero-section-overlay' );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'sfwd-courses_title_overlay_color', 'color' ) ) );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.sfwd-courses-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'sfwd-courses_title_background', 'tablet' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'sfwd-courses_title_top_border', 'tablet' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'sfwd-courses_title_bottom_border', 'tablet' ) ) );
			$css->set_selector( '.sfwd-courses-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'sfwd-courses_title_height' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.sfwd-courses-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'sfwd-courses_title_background', 'mobile' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'sfwd-courses_title_top_border', 'mobile' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'sfwd-courses_title_bottom_border', 'mobile' ) ) );
			$css->set_selector( '.entry-hero.sfwd-courses-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'sfwd-courses_title_height' ), 'mobile' ) );
			$css->stop_media_query();
			// Course Backgrounds.
			$css->set_selector( 'body.single-sfwd-courses' );
			$css->render_background( cryptozfree()->sub_option( 'sfwd-courses_background', 'desktop' ), $css );
			$css->set_selector( 'body.single-sfwd-courses .content-bg, body.content-style-unboxed.single-sfwd-courses .site' );
			$css->render_background( cryptozfree()->sub_option( 'sfwd-courses_content_background', 'desktop' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( 'body.single-sfwd-courses' );
			$css->render_background( cryptozfree()->sub_option( 'sfwd-courses_background', 'tablet' ), $css );
			$css->set_selector( 'body.single-sfwd-courses .content-bg, body.content-style-unboxed.single-sfwd-courses .site' );
			$css->render_background( cryptozfree()->sub_option( 'sfwd-courses_content_background', 'tablet' ), $css );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( 'body.single-sfwd-courses' );
			$css->render_background( cryptozfree()->sub_option( 'sfwd-courses_background', 'mobile' ), $css );
			$css->set_selector( 'body.single-sfwd-courses .content-bg, body.content-style-unboxed.single-sfwd-courses .site' );
			$css->render_background( cryptozfree()->sub_option( 'sfwd-courses_content_background', 'mobile' ), $css );
			$css->stop_media_query();
			if ( class_exists( 'LearnDash_Settings_Section' ) ) {
				$in_focus_mode = \LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'focus_mode_enabled' );
				if ( ! $in_focus_mode ) {
					// Lesson Title.
					$css->set_selector( '.sfwd-lessons-title h1' );
					$css->render_font( cryptozfree()->option( 'sfwd-lessons_title_font' ), $css );
					$css->start_media_query( $media_query['tablet'] );
					$css->set_selector( '.sfwd-lessons-title h1' );
					$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sfwd-lessons_title_font' ), 'tablet' ) );
					$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sfwd-lessons_title_font' ), 'tablet' ) );
					$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'sfwd-lessons_title_font' ), 'tablet' ) );
					$css->stop_media_query();
					$css->start_media_query( $media_query['mobile'] );
					$css->set_selector( '.sfwd-lessons-title h1' );
					$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sfwd-lessons_title_font' ), 'mobile' ) );
					$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sfwd-lessons_title_font' ), 'mobile' ) );
					$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'sfwd-lessons_title_font' ), 'mobile' ) );
					$css->stop_media_query();
					// Lesson Title Breadcrumbs.
					$css->set_selector( '.sfwd-lessons-title .cryptozfree-breadcrumbs' );
					$css->render_font( cryptozfree()->option( 'sfwd-lessons_title_breadcrumb_font' ), $css );
					$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'sfwd-lessons_title_breadcrumb_color', 'color' ) ) );
					$css->set_selector( '.sfwd-lessons-title .cryptozfree-breadcrumbs a:hover' );
					$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'sfwd-lessons_title_breadcrumb_color', 'hover' ) ) );
					$css->start_media_query( $media_query['tablet'] );
					$css->set_selector( '.sfwd-lessons-title .cryptozfree-breadcrumbs' );
					$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sfwd-lessons_title_breadcrumb_font' ), 'tablet' ) );
					$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sfwd-lessons_title_breadcrumb_font' ), 'tablet' ) );
					$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'sfwd-lessons_title_breadcrumb_font' ), 'tablet' ) );
					$css->stop_media_query();
					$css->start_media_query( $media_query['mobile'] );
					$css->set_selector( '.sfwd-lessons-title .cryptozfree-breadcrumbs' );
					$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sfwd-lessons_title_breadcrumb_font' ), 'mobile' ) );
					$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sfwd-lessons_title_breadcrumb_font' ), 'mobile' ) );
					$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'sfwd-lessons_title_breadcrumb_font' ), 'mobile' ) );
					$css->stop_media_query();
					// Above Lesson Title.
					$css->set_selector( '.sfwd-lessons-hero-section .entry-hero-container-inner' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-lessons_title_background', 'desktop' ), $css );
					$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'sfwd-lessons_title_top_border', 'desktop' ) ) );
					$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'sfwd-lessons_title_bottom_border', 'desktop' ) ) );
					$css->set_selector( '.entry-hero.sfwd-lessons-hero-section .entry-header' );
					$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'sfwd-lessons_title_height' ), 'desktop' ) );
					$css->set_selector( '.sfwd-lessons-hero-section .hero-section-overlay' );
					$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'sfwd-lessons_title_overlay_color', 'color' ) ) );
					$css->start_media_query( $media_query['tablet'] );
					$css->set_selector( '.sfwd-lessons-hero-section .entry-hero-container-inner' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-lessons_title_background', 'tablet' ), $css );
					$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'sfwd-lessons_title_top_border', 'tablet' ) ) );
					$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'sfwd-lessons_title_bottom_border', 'tablet' ) ) );
					$css->set_selector( '.sfwd-lessons-hero-section .entry-header' );
					$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'sfwd-lessons_title_height' ), 'tablet' ) );
					$css->stop_media_query();
					$css->start_media_query( $media_query['mobile'] );
					$css->set_selector( '.sfwd-lessons-hero-section .entry-hero-container-inner' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-lessons_title_background', 'mobile' ), $css );
					$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'sfwd-lessons_title_top_border', 'mobile' ) ) );
					$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'sfwd-lessons_title_bottom_border', 'mobile' ) ) );
					$css->set_selector( '.entry-hero.sfwd-lessons-hero-section .entry-header' );
					$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'sfwd-lessons_title_height' ), 'mobile' ) );
					$css->stop_media_query();
					// Lesson Backgrounds.
					$css->set_selector( 'body.single-sfwd-lessons' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-lessons_background', 'desktop' ), $css );
					$css->set_selector( 'body.single-sfwd-lessons .content-bg, body.content-style-unboxed.single-sfwd-lessons .site' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-lessons_content_background', 'desktop' ), $css );
					$css->start_media_query( $media_query['tablet'] );
					$css->set_selector( 'body.single-sfwd-lessons' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-lessons_background', 'tablet' ), $css );
					$css->set_selector( 'body.single-sfwd-lessons .content-bg, body.content-style-unboxed.single-sfwd-lessons .site' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-lessons_content_background', 'tablet' ), $css );
					$css->stop_media_query();
					$css->start_media_query( $media_query['mobile'] );
					$css->set_selector( 'body.single-sfwd-lessons' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-lessons_background', 'mobile' ), $css );
					$css->set_selector( 'body.single-sfwd-lessons .content-bg, body.content-style-unboxed.single-sfwd-lessons .site' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-lessons_content_background', 'mobile' ), $css );
					$css->stop_media_query();
					// Quiz Title.
					$css->set_selector( '.sfwd-quiz-title h1' );
					$css->render_font( cryptozfree()->option( 'sfwd-quiz_title_font' ), $css );
					$css->start_media_query( $media_query['tablet'] );
					$css->set_selector( '.sfwd-quiz-title h1' );
					$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sfwd-quiz_title_font' ), 'tablet' ) );
					$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sfwd-quiz_title_font' ), 'tablet' ) );
					$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'sfwd-quiz_title_font' ), 'tablet' ) );
					$css->stop_media_query();
					$css->start_media_query( $media_query['mobile'] );
					$css->set_selector( '.sfwd-quiz-title h1' );
					$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sfwd-quiz_title_font' ), 'mobile' ) );
					$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sfwd-quiz_title_font' ), 'mobile' ) );
					$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'sfwd-quiz_title_font' ), 'mobile' ) );
					$css->stop_media_query();
					// Quiz Title Breadcrumbs.
					$css->set_selector( '.sfwd-quiz-title .cryptozfree-breadcrumbs' );
					$css->render_font( cryptozfree()->option( 'sfwd-quiz_title_breadcrumb_font' ), $css );
					$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'sfwd-quiz_title_breadcrumb_color', 'color' ) ) );
					$css->set_selector( '.sfwd-quiz-title .cryptozfree-breadcrumbs a:hover' );
					$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'sfwd-quiz_title_breadcrumb_color', 'hover' ) ) );
					$css->start_media_query( $media_query['tablet'] );
					$css->set_selector( '.sfwd-quiz-title .cryptozfree-breadcrumbs' );
					$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sfwd-quiz_title_breadcrumb_font' ), 'tablet' ) );
					$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sfwd-quiz_title_breadcrumb_font' ), 'tablet' ) );
					$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'sfwd-quiz_title_breadcrumb_font' ), 'tablet' ) );
					$css->stop_media_query();
					$css->start_media_query( $media_query['mobile'] );
					$css->set_selector( '.sfwd-quiz-title .cryptozfree-breadcrumbs' );
					$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sfwd-quiz_title_breadcrumb_font' ), 'mobile' ) );
					$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sfwd-quiz_title_breadcrumb_font' ), 'mobile' ) );
					$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'sfwd-quiz_title_breadcrumb_font' ), 'mobile' ) );
					$css->stop_media_query();
					// Above Quiz Title.
					$css->set_selector( '.sfwd-quiz-hero-section .entry-hero-container-inner' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-quiz_title_background', 'desktop' ), $css );
					$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'sfwd-quiz_title_top_border', 'desktop' ) ) );
					$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'sfwd-quiz_title_bottom_border', 'desktop' ) ) );
					$css->set_selector( '.entry-hero.sfwd-quiz-hero-section .entry-header' );
					$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'sfwd-quiz_title_height' ), 'desktop' ) );
					$css->set_selector( '.sfwd-quiz-hero-section .hero-section-overlay' );
					$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'sfwd-quiz_title_overlay_color', 'color' ) ) );
					$css->start_media_query( $media_query['tablet'] );
					$css->set_selector( '.sfwd-quiz-hero-section .entry-hero-container-inner' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-quiz_title_background', 'tablet' ), $css );
					$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'sfwd-quiz_title_top_border', 'tablet' ) ) );
					$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'sfwd-quiz_title_bottom_border', 'tablet' ) ) );
					$css->set_selector( '.sfwd-quiz-hero-section .entry-header' );
					$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'sfwd-quiz_title_height' ), 'tablet' ) );
					$css->stop_media_query();
					$css->start_media_query( $media_query['mobile'] );
					$css->set_selector( '.sfwd-quiz-hero-section .entry-hero-container-inner' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-quiz_title_background', 'mobile' ), $css );
					$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'sfwd-quiz_title_top_border', 'mobile' ) ) );
					$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'sfwd-quiz_title_bottom_border', 'mobile' ) ) );
					$css->set_selector( '.entry-hero.sfwd-quiz-hero-section .entry-header' );
					$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'sfwd-quiz_title_height' ), 'mobile' ) );
					$css->stop_media_query();
					// Quiz Backgrounds.
					$css->set_selector( 'body.single-sfwd-quiz' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-quiz_background', 'desktop' ), $css );
					$css->set_selector( 'body.single-sfwd-quiz .content-bg, body.content-style-unboxed.single-sfwd-quiz .site' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-quiz_content_background', 'desktop' ), $css );
					$css->start_media_query( $media_query['tablet'] );
					$css->set_selector( 'body.single-sfwd-quiz' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-quiz_background', 'tablet' ), $css );
					$css->set_selector( 'body.single-sfwd-quiz .content-bg, body.content-style-unboxed.single-sfwd-quiz .site' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-quiz_content_background', 'tablet' ), $css );
					$css->stop_media_query();
					$css->start_media_query( $media_query['mobile'] );
					$css->set_selector( 'body.single-sfwd-quiz' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-quiz_background', 'mobile' ), $css );
					$css->set_selector( 'body.single-sfwd-quiz .content-bg, body.content-style-unboxed.single-sfwd-quiz .site' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-quiz_content_background', 'mobile' ), $css );
					$css->stop_media_query();
					// Topic Title.
					$css->set_selector( '.sfwd-topic-title h1' );
					$css->render_font( cryptozfree()->option( 'sfwd-topic_title_font' ), $css );
					$css->start_media_query( $media_query['tablet'] );
					$css->set_selector( '.sfwd-topic-title h1' );
					$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sfwd-topic_title_font' ), 'tablet' ) );
					$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sfwd-topic_title_font' ), 'tablet' ) );
					$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'sfwd-topic_title_font' ), 'tablet' ) );
					$css->stop_media_query();
					$css->start_media_query( $media_query['mobile'] );
					$css->set_selector( '.sfwd-topic-title h1' );
					$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sfwd-topic_title_font' ), 'mobile' ) );
					$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sfwd-topic_title_font' ), 'mobile' ) );
					$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'sfwd-topic_title_font' ), 'mobile' ) );
					$css->stop_media_query();
					// Topic Title Breadcrumbs.
					$css->set_selector( '.sfwd-topic-title .cryptozfree-breadcrumbs' );
					$css->render_font( cryptozfree()->option( 'sfwd-topic_title_breadcrumb_font' ), $css );
					$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'sfwd-topic_title_breadcrumb_color', 'color' ) ) );
					$css->set_selector( '.sfwd-topic-title .cryptozfree-breadcrumbs a:hover' );
					$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'sfwd-topic_title_breadcrumb_color', 'hover' ) ) );
					$css->start_media_query( $media_query['tablet'] );
					$css->set_selector( '.sfwd-topic-title .cryptozfree-breadcrumbs' );
					$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sfwd-topic_title_breadcrumb_font' ), 'tablet' ) );
					$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sfwd-topic_title_breadcrumb_font' ), 'tablet' ) );
					$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'sfwd-topic_title_breadcrumb_font' ), 'tablet' ) );
					$css->stop_media_query();
					$css->start_media_query( $media_query['mobile'] );
					$css->set_selector( '.sfwd-topic-title .cryptozfree-breadcrumbs' );
					$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sfwd-topic_title_breadcrumb_font' ), 'mobile' ) );
					$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sfwd-topic_title_breadcrumb_font' ), 'mobile' ) );
					$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'sfwd-topic_title_breadcrumb_font' ), 'mobile' ) );
					$css->stop_media_query();
					// Above Topic Title.
					$css->set_selector( '.sfwd-topic-hero-section .entry-hero-container-inner' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-topic_title_background', 'desktop' ), $css );
					$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'sfwd-topic_title_top_border', 'desktop' ) ) );
					$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'sfwd-topic_title_bottom_border', 'desktop' ) ) );
					$css->set_selector( '.entry-hero.sfwd-topic-hero-section .entry-header' );
					$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'sfwd-topic_title_height' ), 'desktop' ) );
					$css->set_selector( '.sfwd-topic-hero-section .hero-section-overlay' );
					$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'sfwd-topic_title_overlay_color', 'color' ) ) );
					$css->start_media_query( $media_query['tablet'] );
					$css->set_selector( '.sfwd-topic-hero-section .entry-hero-container-inner' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-topic_title_background', 'tablet' ), $css );
					$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'sfwd-topic_title_top_border', 'tablet' ) ) );
					$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'sfwd-topic_title_bottom_border', 'tablet' ) ) );
					$css->set_selector( '.sfwd-topic-hero-section .entry-header' );
					$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'sfwd-topic_title_height' ), 'tablet' ) );
					$css->stop_media_query();
					$css->start_media_query( $media_query['mobile'] );
					$css->set_selector( '.sfwd-topic-hero-section .entry-hero-container-inner' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-topic_title_background', 'mobile' ), $css );
					$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'sfwd-topic_title_top_border', 'mobile' ) ) );
					$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'sfwd-topic_title_bottom_border', 'mobile' ) ) );
					$css->set_selector( '.entry-hero.sfwd-topic-hero-section .entry-header' );
					$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'sfwd-topic_title_height' ), 'mobile' ) );
					$css->stop_media_query();
					// Topic Backgrounds.
					$css->set_selector( 'body.single-sfwd-topic' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-topic_background', 'desktop' ), $css );
					$css->set_selector( 'body.single-sfwd-topic .content-bg, body.content-style-unboxed.single-sfwd-topic .site' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-topic_content_background', 'desktop' ), $css );
					$css->start_media_query( $media_query['tablet'] );
					$css->set_selector( 'body.single-sfwd-topic' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-topic_background', 'tablet' ), $css );
					$css->set_selector( 'body.single-sfwd-topic .content-bg, body.content-style-unboxed.single-sfwd-topic .site' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-topic_content_background', 'tablet' ), $css );
					$css->stop_media_query();
					$css->start_media_query( $media_query['mobile'] );
					$css->set_selector( 'body.single-sfwd-topic' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-topic_background', 'mobile' ), $css );
					$css->set_selector( 'body.single-sfwd-topic .content-bg, body.content-style-unboxed.single-sfwd-topic .site' );
					$css->render_background( cryptozfree()->sub_option( 'sfwd-topic_content_background', 'mobile' ), $css );
					$css->stop_media_query();
				}
			}
			// Group Title.
			$css->set_selector( '.site .groupe-title h1' );
			$css->render_font( cryptozfree()->option( 'groupe_title_font' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.site .groupe-title h1' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'groupe_title_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'groupe_title_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'groupe_title_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.site .groupe-title h1' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'groupe_title_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'groupe_title_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'groupe_title_font' ), 'mobile' ) );
			$css->stop_media_query();
			// Essay Group Breadcrumbs.
			$css->set_selector( '.groupe-title .cryptozfree-breadcrumbs' );
			$css->render_font( cryptozfree()->option( 'groupe_title_breadcrumb_font' ), $css );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'groupe_title_breadcrumb_color', 'color' ) ) );
			$css->set_selector( '.groupe-title .cryptozfree-breadcrumbs a:hover' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'groupe_title_breadcrumb_color', 'hover' ) ) );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.groupe-title .cryptozfree-breadcrumbs' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'groupe_title_breadcrumb_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'groupe_title_breadcrumb_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'groupe_title_breadcrumb_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.groupe-title .cryptozfree-breadcrumbs' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'groupe_title_breadcrumb_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'groupe_title_breadcrumb_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'groupe_title_breadcrumb_font' ), 'mobile' ) );
			$css->stop_media_query();
			// Above Group Title.
			$css->set_selector( '.groupe-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'groupe_title_background', 'desktop' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'groupe_title_top_border', 'desktop' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'groupe_title_bottom_border', 'desktop' ) ) );
			$css->set_selector( '.entry-hero.groupe-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'groupe_title_height' ), 'desktop' ) );
			$css->set_selector( '.groupe-hero-section .hero-section-overlay' );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'groupe_title_overlay_color', 'color' ) ) );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.groupe-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'groupe_title_background', 'tablet' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'groupe_title_top_border', 'tablet' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'groupe_title_bottom_border', 'tablet' ) ) );
			$css->set_selector( '.groupe-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'groupe_title_height' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.groupe-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'groupe_title_background', 'mobile' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'groupe_title_top_border', 'mobile' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'groupe_title_bottom_border', 'mobile' ) ) );
			$css->set_selector( '.entry-hero.groupe-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'groupe_title_height' ), 'mobile' ) );
			$css->stop_media_query();
			// Essay Title.
			$css->set_selector( '.site .sfwd-essays-title h1' );
			$css->render_font( cryptozfree()->option( 'sfwd-essays_title_font' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.site .sfwd-essays-title h1' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sfwd-essays_title_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sfwd-essays_title_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'sfwd-essays_title_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.site .sfwd-essays-title h1' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sfwd-essays_title_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sfwd-essays_title_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'sfwd-essays_title_font' ), 'mobile' ) );
			$css->stop_media_query();
			// Essay Title Breadcrumbs.
			$css->set_selector( '.sfwd-essays-title .cryptozfree-breadcrumbs' );
			$css->render_font( cryptozfree()->option( 'sfwd-essays_title_breadcrumb_font' ), $css );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'sfwd-essays_title_breadcrumb_color', 'color' ) ) );
			$css->set_selector( '.sfwd-essays-title .cryptozfree-breadcrumbs a:hover' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'sfwd-essays_title_breadcrumb_color', 'hover' ) ) );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.sfwd-essays-title .cryptozfree-breadcrumbs' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sfwd-essays_title_breadcrumb_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sfwd-essays_title_breadcrumb_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'sfwd-essays_title_breadcrumb_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.sfwd-essays-title .cryptozfree-breadcrumbs' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sfwd-essays_title_breadcrumb_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sfwd-essays_title_breadcrumb_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'sfwd-essays_title_breadcrumb_font' ), 'mobile' ) );
			$css->stop_media_query();
			// Above Essay Title.
			$css->set_selector( '.sfwd-essays-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'sfwd-essays_title_background', 'desktop' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'sfwd-essays_title_top_border', 'desktop' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'sfwd-essays_title_bottom_border', 'desktop' ) ) );
			$css->set_selector( '.entry-hero.sfwd-essays-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'sfwd-essays_title_height' ), 'desktop' ) );
			$css->set_selector( '.sfwd-essays-hero-section .hero-section-overlay' );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'sfwd-essays_title_overlay_color', 'color' ) ) );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.sfwd-essays-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'sfwd-essays_title_background', 'tablet' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'sfwd-essays_title_top_border', 'tablet' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'sfwd-essays_title_bottom_border', 'tablet' ) ) );
			$css->set_selector( '.sfwd-essays-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'sfwd-essays_title_height' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.sfwd-essays-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'sfwd-essays_title_background', 'mobile' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'sfwd-essays_title_top_border', 'mobile' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'sfwd-essays_title_bottom_border', 'mobile' ) ) );
			$css->set_selector( '.entry-hero.sfwd-essays-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'sfwd-essays_title_height' ), 'mobile' ) );
			$css->stop_media_query();
			// LearnDash Grid Title.
			$css->set_selector( '.ld-course-list-items .ld_course_grid.entry .course .entry-title' );
			$css->render_font( cryptozfree()->option( 'sfwd-grid_title_font' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.ld-course-list-items .ld_course_grid.entry .course .entry-title' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sfwd-grid_title_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sfwd-grid_title_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'sfwd-grid_title_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.ld-course-list-items .ld_course_grid.entry .course .entry-title' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'sfwd-grid_title_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'sfwd-grid_title_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'sfwd-grid_title_font' ), 'mobile' ) );
			$css->stop_media_query();
		}
		// Lifter CSS.
		if ( class_exists( 'LifterLMS' ) ) {
			// Course Backgrounds.
			$css->set_selector( 'body.single-course' );
			$css->render_background( cryptozfree()->sub_option( 'course_background', 'desktop' ), $css );
			$css->set_selector( 'body.single-course .content-bg, body.content-style-unboxed.single-course .site' );
			$css->render_background( cryptozfree()->sub_option( 'course_content_background', 'desktop' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( 'body.single-course' );
			$css->render_background( cryptozfree()->sub_option( 'course_background', 'tablet' ), $css );
			$css->set_selector( 'body.single-course .content-bg, body.content-style-unboxed.single-course .site' );
			$css->render_background( cryptozfree()->sub_option( 'course_content_background', 'tablet' ), $css );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( 'body.single-course' );
			$css->render_background( cryptozfree()->sub_option( 'course_background', 'mobile' ), $css );
			$css->set_selector( 'body.single-course .content-bg, body.content-style-unboxed.single-course .site' );
			$css->render_background( cryptozfree()->sub_option( 'course_content_background', 'mobile' ), $css );
			$css->stop_media_query();
			// Lesson Backgrounds.
			$css->set_selector( 'body.single-lesson' );
			$css->render_background( cryptozfree()->sub_option( 'lesson_background', 'desktop' ), $css );
			$css->set_selector( 'body.single-lesson .content-bg, body.content-style-unboxed.single-lesson .site' );
			$css->render_background( cryptozfree()->sub_option( 'lesson_content_background', 'desktop' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( 'body.single-lesson' );
			$css->render_background( cryptozfree()->sub_option( 'lesson_background', 'tablet' ), $css );
			$css->set_selector( 'body.single-lesson .content-bg, body.content-style-unboxed.single-lesson .site' );
			$css->render_background( cryptozfree()->sub_option( 'lesson_content_background', 'tablet' ), $css );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( 'body.single-lesson' );
			$css->render_background( cryptozfree()->sub_option( 'lesson_background', 'mobile' ), $css );
			$css->set_selector( 'body.single-lesson .content-bg, body.content-style-unboxed.single-lesson .site' );
			$css->render_background( cryptozfree()->sub_option( 'lesson_content_background', 'mobile' ), $css );
			$css->stop_media_query();
			// Course Archive Backgrounds.
			$css->set_selector( 'body.archive.tax-course_cat, body.post-type-archive-course' );
			$css->render_background( cryptozfree()->sub_option( 'course_archive_background', 'desktop' ), $css );
			$css->set_selector( 'body.archive.tax-course_cat .content-bg, body.content-style-unboxed.archive.tax-course_cat .site, body.post-type-archive-course .content-bg, body.content-style-unboxed.archive.post-type-archive-course .site' );
			$css->render_background( cryptozfree()->sub_option( 'course_archive_content_background', 'desktop' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( 'body.archive.tax-course_cat, body.post-type-archive-course' );
			$css->render_background( cryptozfree()->sub_option( 'course_archive_background', 'tablet' ), $css );
			$css->set_selector( 'body.archive.tax-course_cat .content-bg, body.content-style-unboxed.archive.tax-course_cat .site, body.post-type-archive-course .content-bg, body.content-style-unboxed.archive.post-type-archive-course .site' );
			$css->render_background( cryptozfree()->sub_option( 'course_archive_content_background', 'tablet' ), $css );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( 'body.archive.tax-course_cat, body.post-type-archive-course' );
			$css->render_background( cryptozfree()->sub_option( 'course_archive_background', 'mobile' ), $css );
			$css->set_selector( 'body.archive.tax-course_cat .content-bg, body.content-style-unboxed.archive.tax-course_cat .site, body.post-type-archive-course .content-bg, body.content-style-unboxed.archive.post-type-archive-course .site' );
			$css->render_background( cryptozfree()->sub_option( 'course_archive_content_background', 'mobile' ), $css );
			$css->stop_media_query();
			// Membership Archive Backgrounds.
			$css->set_selector( 'body.archive.tax-membership_cat, body.post-type-archive-llms_membership' );
			$css->render_background( cryptozfree()->sub_option( 'llms_membership_archive_background', 'desktop' ), $css );
			$css->set_selector( 'body.archive.tax-membership_cat .content-bg, body.content-style-unboxed.archive.tax-membership_cat .site, body.post-type-archive-llms_membership .content-bg, body.content-style-unboxed.archive.post-type-archive-llms_membership .site' );
			$css->render_background( cryptozfree()->sub_option( 'llms_membership_archive_content_background', 'desktop' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( 'body.archive.tax-membership_cat, body.post-type-archive-llms_membership' );
			$css->render_background( cryptozfree()->sub_option( 'llms_membership_archive_background', 'tablet' ), $css );
			$css->set_selector( 'body.archive.tax-membership_cat .content-bg, body.content-style-unboxed.archive.tax-membership_cat .site, body.post-type-archive-llms_membership .content-bg, body.content-style-unboxed.archive.post-type-archive-llms_membership .site' );
			$css->render_background( cryptozfree()->sub_option( 'llms_membership_archive_content_background', 'tablet' ), $css );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( 'body.archive.tax-membership_cat, body.post-type-archive-llms_membership' );
			$css->render_background( cryptozfree()->sub_option( 'llms_membership_archive_background', 'mobile' ), $css );
			$css->set_selector( 'body.archive.tax-membership_cat .content-bg, body.content-style-unboxed.archive.tax-membership_cat .site, body.post-type-archive-llms_membership .content-bg, body.content-style-unboxed.archive.post-type-archive-llms_membership .site' );
			$css->render_background( cryptozfree()->sub_option( 'llms_membership_archive_content_background', 'mobile' ), $css );
			$css->stop_media_query();
			// Course Title.
			$css->set_selector( '.site .course-title h1' );
			$css->render_font( cryptozfree()->option( 'course_title_font' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.site .course-title h1' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'course_title_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'course_title_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'course_title_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.site .course-title h1' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'course_title_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'course_title_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'course_title_font' ), 'mobile' ) );
			$css->stop_media_query();
			// Course Title Breadcrumbs.
			$css->set_selector( '.course-title .cryptozfree-breadcrumbs' );
			$css->render_font( cryptozfree()->option( 'course_title_breadcrumb_font' ), $css );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'course_title_breadcrumb_color', 'color' ) ) );
			$css->set_selector( '.course-title .cryptozfree-breadcrumbs a:hover' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'course_title_breadcrumb_color', 'hover' ) ) );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.course-title .cryptozfree-breadcrumbs' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'course_title_breadcrumb_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'course_title_breadcrumb_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'course_title_breadcrumb_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.course-title .cryptozfree-breadcrumbs' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'course_title_breadcrumb_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'course_title_breadcrumb_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'course_title_breadcrumb_font' ), 'mobile' ) );
			$css->stop_media_query();
			// Above Course Title.
			$css->set_selector( '.course-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'course_title_background', 'desktop' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'course_title_top_border', 'desktop' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'course_title_bottom_border', 'desktop' ) ) );
			$css->set_selector( '.entry-hero.course-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'course_title_height' ), 'desktop' ) );
			$css->set_selector( '.course-hero-section .hero-section-overlay' );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'course_title_overlay_color', 'color' ) ) );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.course-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'course_title_background', 'tablet' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'course_title_top_border', 'tablet' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'course_title_bottom_border', 'tablet' ) ) );
			$css->set_selector( '.course-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'course_title_height' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.course-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'course_title_background', 'mobile' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'course_title_top_border', 'mobile' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'course_title_bottom_border', 'mobile' ) ) );
			$css->set_selector( '.entry-hero.course-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'course_title_height' ), 'mobile' ) );
			$css->stop_media_query();
			// Lesson Title.
			$css->set_selector( '.site .lesson-title h1' );
			$css->render_font( cryptozfree()->option( 'lesson_title_font' ), $css );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.site .lesson-title h1' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'lesson_title_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'lesson_title_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'lesson_title_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.site .lesson-title h1' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'lesson_title_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'lesson_title_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'lesson_title_font' ), 'mobile' ) );
			$css->stop_media_query();
			// Lesson Title Breadcrumbs.
			$css->set_selector( '.lesson-title .cryptozfree-breadcrumbs' );
			$css->render_font( cryptozfree()->option( 'lesson_title_breadcrumb_font' ), $css );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'lesson_title_breadcrumb_color', 'color' ) ) );
			$css->set_selector( '.lesson-title .cryptozfree-breadcrumbs a:hover' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'lesson_title_breadcrumb_color', 'hover' ) ) );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.lesson-title .cryptozfree-breadcrumbs' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'lesson_title_breadcrumb_font' ), 'tablet' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'lesson_title_breadcrumb_font' ), 'tablet' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'lesson_title_breadcrumb_font' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.lesson-title .cryptozfree-breadcrumbs' );
			$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( 'lesson_title_breadcrumb_font' ), 'mobile' ) );
			$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( 'lesson_title_breadcrumb_font' ), 'mobile' ) );
			$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( 'lesson_title_breadcrumb_font' ), 'mobile' ) );
			$css->stop_media_query();
			// Above Lesson Title.
			$css->set_selector( '.lesson-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'lesson_title_background', 'desktop' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'lesson_title_top_border', 'desktop' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'lesson_title_bottom_border', 'desktop' ) ) );
			$css->set_selector( '.entry-hero.lesson-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'lesson_title_height' ), 'desktop' ) );
			$css->set_selector( '.lesson-hero-section .hero-section-overlay' );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'lesson_title_overlay_color', 'color' ) ) );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.lesson-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'lesson_title_background', 'tablet' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'lesson_title_top_border', 'tablet' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'lesson_title_bottom_border', 'tablet' ) ) );
			$css->set_selector( '.lesson-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'lesson_title_height' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.lesson-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'lesson_title_background', 'mobile' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'lesson_title_top_border', 'mobile' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'lesson_title_bottom_border', 'mobile' ) ) );
			$css->set_selector( '.entry-hero.lesson-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'lesson_title_height' ), 'mobile' ) );
			$css->stop_media_query();
			// Course Archive Title.
			$css->set_selector( '.course-archive-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'course_archive_title_background', 'desktop' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'course_archive_title_top_border', 'desktop' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'course_archive_title_bottom_border', 'desktop' ) ) );
			$css->set_selector( '.entry-hero.course-archive-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'course_archive_title_height' ), 'desktop' ) );
			$css->set_selector( '.course-archive-hero-section .hero-section-overlay' );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'course_archive_title_overlay_color', 'color' ) ) );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.course-archive-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'course_archive_title_background', 'tablet' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'course_archive_title_top_border', 'tablet' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'course_archive_title_bottom_border', 'tablet' ) ) );
			$css->set_selector( '.course-archive-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'course_archive_title_height' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.course-archive-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'course_archive_title_background', 'mobile' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'course_archive_title_top_border', 'mobile' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'course_archive_title_bottom_border', 'mobile' ) ) );
			$css->set_selector( '.entry-hero.course-archive-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'course_archive_title_height' ), 'mobile' ) );
			$css->stop_media_query();
			$css->set_selector( '.site .course-archive-title h1' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'course_archive_title_color', 'color' ) ) );
			$css->set_selector( '.course-archive-title .cryptozfree-breadcrumbs' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'course_archive_title_breadcrumb_color', 'color' ) ) );
			$css->set_selector( '.course-archive-title .cryptozfree-breadcrumbs a:hover' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'course_archive_title_breadcrumb_color', 'hover' ) ) );
			$css->set_selector( '.course-archive-title .archive-description' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'course_archive_title_description_color', 'color' ) ) );
			$css->set_selector( '.course-archive-title .archive-description a:hover' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'course_archive_title_description_color', 'hover' ) ) );
			// Membership Archive Title.
			$css->set_selector( '.llms_membership-archive-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'llms_membership_archive_title_background', 'desktop' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'llms_membership_archive_title_top_border', 'desktop' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'llms_membership_archive_title_bottom_border', 'desktop' ) ) );
			$css->set_selector( '.entry-hero.llms_membership-archive-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'llms_membership_archive_title_height' ), 'desktop' ) );
			$css->set_selector( '.llms_membership-archive-hero-section .hero-section-overlay' );
			$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( 'llms_membership_archive_title_overlay_color', 'color' ) ) );
			$css->start_media_query( $media_query['tablet'] );
			$css->set_selector( '.llms_membership-archive-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'llms_membership_archive_title_background', 'tablet' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'llms_membership_archive_title_top_border', 'tablet' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'llms_membership_archive_title_bottom_border', 'tablet' ) ) );
			$css->set_selector( '.llms_membership-archive-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'llms_membership_archive_title_height' ), 'tablet' ) );
			$css->stop_media_query();
			$css->start_media_query( $media_query['mobile'] );
			$css->set_selector( '.llms_membership-archive-hero-section .entry-hero-container-inner' );
			$css->render_background( cryptozfree()->sub_option( 'llms_membership_archive_title_background', 'mobile' ), $css );
			$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( 'llms_membership_archive_title_top_border', 'mobile' ) ) );
			$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( 'llms_membership_archive_title_bottom_border', 'mobile' ) ) );
			$css->set_selector( '.entry-hero.llms_membership-archive-hero-section .entry-header' );
			$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( 'llms_membership_archive_title_height' ), 'mobile' ) );
			$css->stop_media_query();
			$css->set_selector( '.site .llms_membership-archive-title h1' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'llms_membership_archive_title_color', 'color' ) ) );
			$css->set_selector( '.llms_membership-archive-title .cryptozfree-breadcrumbs' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'llms_membership_archive_title_breadcrumb_color', 'color' ) ) );
			$css->set_selector( '.llms_membership-archive-title .cryptozfree-breadcrumbs a:hover' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'llms_membership_archive_title_breadcrumb_color', 'hover' ) ) );
			$css->set_selector( '.llms_membership-archive-title .archive-description' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'llms_membership_archive_title_description_color', 'color' ) ) );
			$css->set_selector( '.llms_membership-archive-title .archive-description a:hover' );
			$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( 'llms_membership_archive_title_description_color', 'hover' ) ) );
		}
		$all_post_types    = cryptozfree()->get_post_types_objects();
		$extras_post_types = array();
		$add_extras        = false;
		foreach ( $all_post_types as $post_type_item ) {
			$post_type_name  = $post_type_item->name;
			$post_type_label = $post_type_item->label;
			$ignore_type     = cryptozfree()->get_post_types_to_ignore();
			if ( ! in_array( $post_type_name, $ignore_type, true ) ) {			
				if ( is_singular( $post_type_name ) ) {
					// CPT Backgrounds.
					$css->set_selector( 'body.single-' . $post_type_name );
					$css->render_background( cryptozfree()->sub_option( $post_type_name . '_background', 'desktop' ), $css );
					$css->set_selector( 'body.single-' . $post_type_name . ' .content-bg, body.content-style-unboxed.single-' . $post_type_name . ' .site' );
					$css->render_background( cryptozfree()->sub_option( $post_type_name . '_content_background', 'desktop' ), $css );
					$css->start_media_query( $media_query['tablet'] );
					$css->set_selector( 'body.single-' . $post_type_name );
					$css->render_background( cryptozfree()->sub_option( $post_type_name . '_background', 'tablet' ), $css );
					$css->set_selector( 'body.single-' . $post_type_name . ' .content-bg, body.content-style-unboxed.single-' . $post_type_name . ' .site' );
					$css->render_background( cryptozfree()->sub_option( $post_type_name . '_content_background', 'tablet' ), $css );
					$css->stop_media_query();
					$css->start_media_query( $media_query['mobile'] );
					$css->set_selector( 'body.single-' . $post_type_name );
					$css->render_background( cryptozfree()->sub_option( $post_type_name . '_background', 'mobile' ), $css );
					$css->set_selector( 'body.single-' . $post_type_name . ' .content-bg, body.content-style-unboxed.single-' . $post_type_name . ' .site' );
					$css->render_background( cryptozfree()->sub_option( $post_type_name . '_content_background', 'mobile' ), $css );
					$css->stop_media_query();
					// CPT Title.
					$css->set_selector( '.site .' . $post_type_name . '-title h1' );
					$css->render_font( cryptozfree()->option( $post_type_name . '_title_font' ), $css );
					$css->start_media_query( $media_query['tablet'] );
					$css->set_selector( '.site .' . $post_type_name . '-title h1' );
					$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( $post_type_name . '_title_font' ), 'tablet' ) );
					$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( $post_type_name . '_title_font' ), 'tablet' ) );
					$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( $post_type_name . '_title_font' ), 'tablet' ) );
					$css->stop_media_query();
					$css->start_media_query( $media_query['mobile'] );
					$css->set_selector( '.site .' . $post_type_name . '-title h1' );
					$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( $post_type_name . '_title_font' ), 'mobile' ) );
					$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( $post_type_name . '_title_font' ), 'mobile' ) );
					$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( $post_type_name . '_title_font' ), 'mobile' ) );
					$css->stop_media_query();
					// CPT Title meta.
					$css->set_selector( '.' . $post_type_name . '-title .entry-meta' );
					$css->render_font( cryptozfree()->option( $post_type_name . '_title_meta_font' ), $css );
					$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( $post_type_name . '_title_meta_color', 'color' ) ) );
					$css->set_selector( '.' . $post_type_name . '-title .entry-meta a:hover' );
					$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( $post_type_name . '_title_meta_color', 'hover' ) ) );
					$css->start_media_query( $media_query['tablet'] );
					$css->set_selector( '.' . $post_type_name . '-title .entry-meta' );
					$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( $post_type_name . '_title_meta_font' ), 'tablet' ) );
					$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( $post_type_name . '_title_meta_font' ), 'tablet' ) );
					$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( $post_type_name . '_title_meta_font' ), 'tablet' ) );
					$css->stop_media_query();
					$css->start_media_query( $media_query['mobile'] );
					$css->set_selector( '.' . $post_type_name . '-title .entry-meta' );
					$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( $post_type_name . '_title_meta_font' ), 'mobile' ) );
					$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( $post_type_name . '_title_meta_font' ), 'mobile' ) );
					$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( $post_type_name . '_title_meta_font' ), 'mobile' ) );
					$css->stop_media_query();
					// CPT Title Breadcrumbs.
					$css->set_selector( '.' . $post_type_name . '-title .cryptozfree-breadcrumbs' );
					$css->render_font( cryptozfree()->option( $post_type_name . '_title_breadcrumb_font' ), $css );
					$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( $post_type_name . '_title_breadcrumb_color', 'color' ) ) );
					$css->set_selector( '.' . $post_type_name . '-title .cryptozfree-breadcrumbs a:hover' );
					$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( $post_type_name . '_title_breadcrumb_color', 'hover' ) ) );
					$css->start_media_query( $media_query['tablet'] );
					$css->set_selector( '.' . $post_type_name . '-title .cryptozfree-breadcrumbs' );
					$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( $post_type_name . '_title_breadcrumb_font' ), 'tablet' ) );
					$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( $post_type_name . '_title_breadcrumb_font' ), 'tablet' ) );
					$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( $post_type_name . '_title_breadcrumb_font' ), 'tablet' ) );
					$css->stop_media_query();
					$css->start_media_query( $media_query['mobile'] );
					$css->set_selector( '.' . $post_type_name . '-title .cryptozfree-breadcrumbs' );
					$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( $post_type_name . '_title_breadcrumb_font' ), 'mobile' ) );
					$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( $post_type_name . '_title_breadcrumb_font' ), 'mobile' ) );
					$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( $post_type_name . '_title_breadcrumb_font' ), 'mobile' ) );
					$css->stop_media_query();
					// CPT Title Excerpt.
					$css->set_selector( '.' . $post_type_name . '-title .title-entry-excerpt' );
					$css->render_font( cryptozfree()->option( $post_type_name . '_title_excerpt_font' ), $css );
					$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( $post_type_name . '_title_excerpt_color', 'color' ) ) );
					$css->set_selector( '.' . $post_type_name . '-title .title-entry-excerpt a:hover' );
					$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( $post_type_name . '_title_excerpt_color', 'hover' ) ) );
					$css->start_media_query( $media_query['tablet'] );
					$css->set_selector( '.' . $post_type_name . '-title .title-entry-excerpt' );
					$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( $post_type_name . '_title_excerpt_font' ), 'tablet' ) );
					$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( $post_type_name . '_title_excerpt_font' ), 'tablet' ) );
					$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( $post_type_name . '_title_excerpt_font' ), 'tablet' ) );
					$css->stop_media_query();
					$css->start_media_query( $media_query['mobile'] );
					$css->set_selector( '.' . $post_type_name . '-title .title-entry-excerpt' );
					$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( $post_type_name . '_title_excerpt_font' ), 'mobile' ) );
					$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( $post_type_name . '_title_excerpt_font' ), 'mobile' ) );
					$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( $post_type_name . '_title_excerpt_font' ), 'mobile' ) );
					$css->stop_media_query();
					// CPT Post Title.
					$css->set_selector( '.' . $post_type_name . '-hero-section .entry-hero-container-inner' );
					$css->render_background( cryptozfree()->sub_option( $post_type_name . '_title_background', 'desktop' ), $css );
					$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( $post_type_name . '_title_top_border', 'desktop' ) ) );
					$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( $post_type_name . '_title_bottom_border', 'desktop' ) ) );
					$css->set_selector( '.entry-hero.' . $post_type_name . '-hero-section .entry-header' );
					$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( $post_type_name . '_title_height' ), 'desktop' ) );
					$css->set_selector( '.' . $post_type_name . '-hero-section .hero-section-overlay' );
					$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( $post_type_name . '_title_overlay_color', 'color' ) ) );
					$css->start_media_query( $media_query['tablet'] );
					$css->set_selector( '.' . $post_type_name . '-hero-section .entry-hero-container-inner' );
					$css->render_background( cryptozfree()->sub_option( $post_type_name . '_title_background', 'tablet' ), $css );
					$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( $post_type_name . '_title_top_border', 'tablet' ) ) );
					$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( $post_type_name . '_title_bottom_border', 'tablet' ) ) );
					$css->set_selector( '.' . $post_type_name . '-hero-section .entry-header' );
					$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( $post_type_name . '_title_height' ), 'tablet' ) );
					$css->stop_media_query();
					$css->start_media_query( $media_query['mobile'] );
					$css->set_selector( '.' . $post_type_name . '-hero-section .entry-hero-container-inner' );
					$css->render_background( cryptozfree()->sub_option( $post_type_name . '_title_background', 'mobile' ), $css );
					$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( $post_type_name . '_title_top_border', 'mobile' ) ) );
					$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( $post_type_name . '_title_bottom_border', 'mobile' ) ) );
					$css->set_selector( '.entry-hero.' . $post_type_name . '-hero-section .entry-header' );
					$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( $post_type_name . '_title_height' ), 'mobile' ) );
					$css->stop_media_query();
				}
				if ( is_archive() ) {
					// Above Archive CPT Title.
					$css->set_selector( '.' . $post_type_name . '-archive-hero-section .entry-hero-container-inner' );
					$css->render_background( cryptozfree()->sub_option( $post_type_name . '_archive_title_background', 'desktop' ), $css );
					$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( $post_type_name . '_archive_title_top_border', 'desktop' ) ) );
					$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( $post_type_name . '_archive_title_bottom_border', 'desktop' ) ) );
					$css->set_selector( '.entry-hero.' . $post_type_name . '-archive-hero-section .entry-header' );
					$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( $post_type_name . '_archive_title_height' ), 'desktop' ) );
					$css->set_selector( '.' . $post_type_name . '-archive-hero-section .hero-section-overlay' );
					$css->add_property( 'background', $this->render_color( cryptozfree()->sub_option( $post_type_name . '_archive_title_overlay_color', 'color' ) ) );
					$css->start_media_query( $media_query['tablet'] );
					$css->set_selector( '.' . $post_type_name . '-archive-hero-section .entry-hero-container-inner' );
					$css->render_background( cryptozfree()->sub_option( $post_type_name . '_archive_title_background', 'tablet' ), $css );
					$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( $post_type_name . '_archive_title_top_border', 'tablet' ) ) );
					$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( $post_type_name . '_archive_title_bottom_border', 'tablet' ) ) );
					$css->set_selector( '.' . $post_type_name . '-archive-hero-section .entry-header' );
					$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( $post_type_name . '_archive_title_height' ), 'tablet' ) );
					$css->stop_media_query();
					$css->start_media_query( $media_query['mobile'] );
					$css->set_selector( '.' . $post_type_name . '-archive-hero-section .entry-hero-container-inner' );
					$css->render_background( cryptozfree()->sub_option( $post_type_name . '_archive_title_background', 'mobile' ), $css );
					$css->add_property( 'border-top', $css->render_border( cryptozfree()->sub_option( $post_type_name . '_archive_title_top_border', 'mobile' ) ) );
					$css->add_property( 'border-bottom', $css->render_border( cryptozfree()->sub_option( $post_type_name . '_archive_title_bottom_border', 'mobile' ) ) );
					$css->set_selector( '.entry-hero.' . $post_type_name . '-archive-hero-section .entry-header' );
					$css->add_property( 'min-height', $this->render_range( cryptozfree()->option( $post_type_name . '_archive_title_height' ), 'mobile' ) );
					$css->stop_media_query();
					$css->set_selector( '.site .' . $post_type_name . '-archive-title h1' );
					$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( $post_type_name . '_archive_title_color', 'color' ) ) );
					$css->set_selector( '.' . $post_type_name . '-archive-title .cryptozfree-breadcrumbs' );
					$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( $post_type_name . '_archive_title_breadcrumb_color', 'color' ) ) );
					$css->set_selector( '.' . $post_type_name . '-archive-title .cryptozfree-breadcrumbs a:hover' );
					$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( $post_type_name . '_archive_title_breadcrumb_color', 'hover' ) ) );
					$css->set_selector( '.' . $post_type_name . '-archive-title .archive-description' );
					$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( $post_type_name . '_archive_title_description_color', 'color' ) ) );
					$css->set_selector( '.' . $post_type_name . '-archive-title .archive-description a:hover' );
					$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( $post_type_name . '_archive_title_description_color', 'hover' ) ) );
					// CPT Archive Backgrounds.
					$css->set_selector( 'body.post-type-archive-' . $post_type_name );
					$css->render_background( cryptozfree()->sub_option( $post_type_name . '_archive_background', 'desktop' ), $css );
					$css->set_selector( 'body.post-type-archive-' . $post_type_name . ' .content-bg, body.content-style-unboxed.post-type-archive-' . $post_type_name . ' .site, body.blog .content-bg, body.content-style-unboxed.blog .site' );
					$css->render_background( cryptozfree()->sub_option( $post_type_name . '_archive_content_background', 'desktop' ), $css );
					$css->start_media_query( $media_query['tablet'] );
					$css->set_selector( 'body.post-type-archive-' . $post_type_name );
					$css->render_background( cryptozfree()->sub_option( $post_type_name . '_archive_background', 'tablet' ), $css );
					$css->set_selector( 'body.post-type-archive-' . $post_type_name . ' .content-bg, body.content-style-unboxed.post-type-archive-' . $post_type_name . ' .site, body.blog .content-bg, body.content-style-unboxed.blog .site' );
					$css->render_background( cryptozfree()->sub_option( $post_type_name . '_archive_content_background', 'tablet' ), $css );
					$css->stop_media_query();
					$css->start_media_query( $media_query['mobile'] );
					$css->set_selector( 'body.post-type-archive-' . $post_type_name );
					$css->render_background( cryptozfree()->sub_option( $post_type_name . '_archive_background', 'mobile' ), $css );
					$css->set_selector( 'body.post-type-archive-' . $post_type_name . ' .content-bg, body.content-style-unboxed.post-type-archive-' . $post_type_name . ' .site' );
					$css->render_background( cryptozfree()->sub_option( $post_type_name . '_archive_content_background', 'mobile' ), $css );
					$css->stop_media_query();
					// CTP archive item title.
					$css->set_selector( '.loop-entry.type-' . $post_type_name . ' h2.entry-title' );
					$css->render_font( cryptozfree()->option( $post_type_name . '_archive_item_title_font' ), $css );
					$css->start_media_query( $media_query['tablet'] );
					$css->set_selector( '.loop-entry.type-' . $post_type_name . ' h2.entry-title' );
					$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( $post_type_name . '_archive_item_title_font' ), 'tablet' ) );
					$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( $post_type_name . '_archive_item_title_font' ), 'tablet' ) );
					$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( $post_type_name . '_archive_item_title_font' ), 'tablet' ) );
					$css->stop_media_query();
					$css->start_media_query( $media_query['mobile'] );
					$css->set_selector( '.loop-entry.type-' . $post_type_name . ' h2.entry-title' );
					$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( $post_type_name . '_archive_item_title_font' ), 'mobile' ) );
					$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( $post_type_name . '_archive_item_title_font' ), 'mobile' ) );
					$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( $post_type_name . '_archive_item_title_font' ), 'mobile' ) );
					$css->stop_media_query();
					// CPT archive item meta.
					$css->set_selector( '.loop-entry.type-' . $post_type_name . ' .entry-meta' );
					$css->render_font( cryptozfree()->option( $post_type_name . '_archive_item_meta_font' ), $css );
					$css->set_selector( '.loop-entry.type-' . $post_type_name . ' .entry-meta' );
					$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( $post_type_name . '_archive_item_meta_color', 'color' ) ) );
					$css->set_selector( '.loop-entry.type-' . $post_type_name . ' .entry-meta a:hover' );
					$css->add_property( 'color', $this->render_color( cryptozfree()->sub_option( $post_type_name . '_archive_item_meta_color', 'hover' ) ) );
					$css->start_media_query( $media_query['tablet'] );
					$css->set_selector( '.loop-entry.type-' . $post_type_name . ' .entry-meta' );
					$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( $post_type_name . '_archive_item_meta_font' ), 'tablet' ) );
					$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( $post_type_name . '_archive_item_meta_font' ), 'tablet' ) );
					$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( $post_type_name . '_archive_item_meta_font' ), 'tablet' ) );
					$css->stop_media_query();
					$css->start_media_query( $media_query['mobile'] );
					$css->set_selector( '.loop-entry.type-' . $post_type_name . ' .entry-meta' );
					$css->add_property( 'font-size', $this->render_font_size( cryptozfree()->option( $post_type_name . '_archive_item_meta_font' ), 'mobile' ) );
					$css->add_property( 'line-height', $this->render_font_height( cryptozfree()->option( $post_type_name . '_archive_item_meta_font' ), 'mobile' ) );
					$css->add_property( 'letter-spacing', $this->render_font_spacing( cryptozfree()->option( $post_type_name . '_archive_item_meta_font' ), 'mobile' ) );
					$css->stop_media_query();
				}
			}
		}
		// Social brands.
		if ( '' !== cryptozfree()->option( 'header_social_brand' ) || '' !== cryptozfree()->option( 'header_mobile_social_brand' ) || '' !== cryptozfree()->option( 'footer_social_brand' ) ) {
			$socials = array(
				'facebook'=> '#3b5998',
				'instagram'=> '#517fa4',
				'twitter'=> '#1DA1F2',
				'youtube'=> '#FF3333',
				'facebook_group'=> '#3b5998',
				'vimeo'=> '#4EBBFF',
				'pinterest'=> '#C92228',
				'linkedin'=> '#4875B4',
				'medium'=> '#181818',
				'wordpress'=> '#00749C',
				'reddit'=> '#ff4500',
				'patreon'=> '#052D49',
				'github'=> '#4078c0',
				'dribbble'=> '#EA4C89',
				'behance'=> '#1769ff',
				'vk'=> '#45668e',
				'xing'=> '#006567',
				'rss'=> '#FF6200',
				'email'=> '#181818',
				'phone'=> '#181818',
				'whatsapp'=> '#28cf54',
				'google_reviews'=> '#DB4437',
				'telegram'=> '#0088cc',
				'yelp'=> '#c41200',
				'trip_advisor'=> '#00af87',
				'imdb'=> '#F5C518',
				'soundcloud'=> '#ff7700',
				'tumblr'=> '#32506d',
				'tiktok'=> '#69C9D0',
				'discord'=> '#7289DA',
			);
			foreach( $socials as $name => $color ) {
				$css->set_selector( 'body.social-brand-colors .social-show-brand-hover .social-link-' . $name . ':not(.ignore-brand):not(.skip):not(.ignore):hover, body.social-brand-colors .social-show-brand-until .social-link-' . $name . ':not(:hover):not(.skip):not(.ignore), body.social-brand-colors .social-show-brand-always .social-link-' . $name . ':not(.ignore-brand):not(.skip):not(.ignore)' );
				$css->add_property( 'background', $color );
			}
		}
		self::$google_fonts = $css->fonts_output();
		return $css->css_output();
	}
	/**
	 * Generates the dynamic css based on customizer options.
	 *
	 * @return string
	 */
	public function generate_editor_css() {
		$css = new cryptozfree_CSS();
		// Globals.
		$css->set_selector( ':root' );
		$css->add_property( '--global-palette1', cryptozfree()->palette_option( 'palette1' ) );
		$css->add_property( '--global-palette2', cryptozfree()->palette_option( 'palette2' ) );
		$css->add_property( '--global-palette3', cryptozfree()->palette_option( 'palette3' ) );
		$css->add_property( '--global-palette4', cryptozfree()->palette_option( 'palette4' ) );
		$css->add_property( '--global-palette5', cryptozfree()->palette_option( 'palette5' ) );
		$css->add_property( '--global-palette6', cryptozfree()->palette_option( 'palette6' ) );
		$css->add_property( '--global-palette7', cryptozfree()->palette_option( 'palette7' ) );
		$css->add_property( '--global-palette8', cryptozfree()->palette_option( 'palette8' ) );
		$css->add_property( '--global-palette9', cryptozfree()->palette_option( 'palette9' ) );
		$css->add_property( '--global-palette-highlight', $this->render_color( cryptozfree()->sub_option( 'link_color', 'highlight' ) ) );
		$css->add_property( '--global-palette-highlight-alt', $this->render_color( cryptozfree()->sub_option( 'link_color', 'highlight-alt' ) ) );
		$css->add_property( '--global-palette-highlight-alt2', $this->render_color( cryptozfree()->sub_option( 'link_color', 'highlight-alt2' ) ) );

		$css->add_property( '--global-palette-btn', $this->render_color( cryptozfree()->sub_option( 'buttons_color', 'color' ) ) );
		$css->add_property( '--global-palette-btn-hover', $this->render_color( cryptozfree()->sub_option( 'buttons_color', 'hover' ) ) );
		$css->add_property( '--global-palette-btn-bg', $this->render_color( cryptozfree()->sub_option( 'buttons_background', 'color' ) ) );
		$css->add_property( '--global-palette-btn-bg-hover', $this->render_color( cryptozfree()->sub_option( 'buttons_background', 'hover' ) ) );
		// Colors.
		$css->set_selector( ':root .has-theme-palette1-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette1)' );
		$css->set_selector( ':root .has-theme-palette1-color' );
		$css->add_property( 'color', 'var(--global-palette1)' );
		$css->set_selector( ':root .has-theme-palette2-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette2)' );
		$css->set_selector( ':root .has-theme-palette2-color' );
		$css->add_property( 'color', 'var(--global-palette2)' );

		$css->set_selector( ':root .has-theme-palette3-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette3)' );
		$css->set_selector( ':root .has-theme-palette3-color' );
		$css->add_property( 'color', 'var(--global-palette3)' );

		$css->set_selector( ':root .has-theme-palette4-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette4)' );
		$css->set_selector( ':root .has-theme-palette4-color' );
		$css->add_property( 'color', 'var(--global-palette4)' );

		$css->set_selector( ':root .has-theme-palette5-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette5)' );
		$css->set_selector( ':root .has-theme-palette5-color' );
		$css->add_property( 'color', 'var(--global-palette5)' );

		$css->set_selector( ':root .has-theme-palette6-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette6)' );
		$css->set_selector( ':root .has-theme-palette6-color' );
		$css->add_property( 'color', 'var(--global-palette6)' );

		$css->set_selector( ':root .has-theme-palette7-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette7)' );
		$css->set_selector( ':root .has-theme-palette7-color' );
		$css->add_property( 'color', 'var(--global-palette7)' );

		$css->set_selector( ':root .has-theme-palette8-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette8)' );
		$css->set_selector( ':root .has-theme-palette8-color' );
		$css->add_property( 'color', 'var(--global-palette8)' );

		$css->set_selector( ':root .has-theme-palette9-background-color' );
		$css->add_property( 'background-color', 'var(--global-palette9)' );
		$css->set_selector( ':root .has-theme-palette9-color' );
		$css->add_property( 'color', 'var(--global-palette9)' );
		// Buttons.
		$css->set_selector( '.editor-styles-wrapper .wp-block-button .wp-block-button__link, .editor-styles-wrapper .kt-button.kb-btn-global-inherit' );
		$css->render_font( cryptozfree()->option( 'buttons_typography' ), $css );
		$css->add_property( 'border-radius', $this->render_range( cryptozfree()->option( 'buttons_border_radius' ), 'desktop' ) );
		$css->add_property( 'padding', $this->render_responsive_measure( cryptozfree()->option( 'buttons_padding' ), 'desktop' ) );
		$css->set_selector( '.editor-styles-wrapper .wp-block-button .wp-block-button__link, .editor-styles-wrapper .kb-forms-submit, .editor-styles-wrapper .kt-button' );
		$css->add_property( 'box-shadow', $css->render_shadow( cryptozfree()->option( 'buttons_shadow' ), cryptozfree()->default( 'buttons_shadow' ) ) );
		$css->set_selector( '.editor-styles-wrapper .wp-block-button:not(.is-style-outline) .wp-block-button__link, .editor-styles-wrapper .kt-button.kb-btn-global-inherit' );
		$css->add_property( 'border', $css->render_responsive_border( cryptozfree()->option( 'buttons_border' ), 'desktop' ) );
		$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'buttons_border_colors', 'color' ) ) );
		$css->set_selector( '.editor-styles-wrapper .wp-block-button:not(.is-style-outline) .wp-block-button__link:hover, .editor-styles-wrapper .kt-button.kb-btn-global-inherit:hover' );
		$css->add_property( 'border-color', $this->render_color( cryptozfree()->sub_option( 'buttons_border_colors', 'hover' ) ) );
		$css->set_selector( '.editor-styles-wrapper .wp-block-button .wp-block-button__link:hover, .editor-styles-wrapper .kb-forms-submit:hover, .editor-styles-wrapper .kt-button:hover' );
		$css->add_property( 'box-shadow', $css->render_shadow( cryptozfree()->option( 'buttons_shadow_hover' ), cryptozfree()->default( 'buttons_shadow_hover' ) ) );

		$css->set_selector( '.editor-styles-wrapper .kt-button' );
		$css->add_property( 'font-family', cryptozfree()->sub_option( 'buttons_typography', 'family' ) );
		$css->add_property( 'font-weight', cryptozfree()->sub_option( 'buttons_typography', 'weight' ) );

		$css->set_selector( '.block-editor-page .editor-styles-wrapper' );
		$css->render_background( cryptozfree()->sub_option( 'site_background', 'desktop' ), $css );
		$css->render_font( cryptozfree()->option( 'base_font' ), $css );
		$css->set_selector( '.block-editor-page.post-content-style-unboxed .editor-styles-wrapper' );
		$css->render_background( cryptozfree()->sub_option( 'content_background', 'desktop' ), $css );
		// Page specific.
		$css->set_selector( '.block-editor-page.post-type-page .editor-styles-wrapper' );
		$css->render_background( cryptozfree()->sub_option( 'page_site_background', 'desktop' ), $css );
		$css->set_selector( '.block-editor-page.post-type-page.post-content-style-unboxed .editor-styles-wrapper' );
		$css->render_background( cryptozfree()->sub_option( 'page_content_background', 'desktop' ), $css );
		$css->set_selector( '.block-editor-page.post-type-page.post-content-style-boxed .editor-styles-wrapper:before' );
		$css->render_background( cryptozfree()->sub_option( 'page_content_background', 'desktop' ), $css );
		// Post specific.
		$css->set_selector( '.block-editor-page.post-type-post .editor-styles-wrapper' );
		$css->render_background( cryptozfree()->sub_option( 'post_site_background', 'desktop' ), $css );
		$css->set_selector( '.block-editor-page.post-type-post.post-content-style-unboxed .editor-styles-wrapper' );
		$css->render_background( cryptozfree()->sub_option( 'post_content_background', 'desktop' ), $css );
		$css->set_selector( '.block-editor-page.post-type-post.post-content-style-boxed .editor-styles-wrapper:before' );
		$css->render_background( cryptozfree()->sub_option( 'post_content_background', 'desktop' ), $css );
		// Boxed Editor Width.
		$css->set_selector( '.block-editor-page.post-content-style-boxed .editor-styles-wrapper:before' );
		$css->add_property( 'max-width', 'calc(' . cryptozfree()->sub_option( 'content_width', 'size' ) . cryptozfree()->sub_option( 'content_width', 'unit' ) . ' - 3rem )' );
		$css->render_background( cryptozfree()->sub_option( 'content_background', 'desktop' ), $css );
		// Narrow width.
		$css->set_selector( '.block-editor-page.post-content-style-boxed.post-content-width-narrow .editor-styles-wrapper:before' );
		$css->add_property( 'max-width', 'calc(' . cryptozfree()->sub_option( 'content_narrow_width', 'size' ) . cryptozfree()->sub_option( 'content_narrow_width', 'unit' ) . ' - 3rem )' );
		// Sidebar Width.
		$sidebar_size = cryptozfree()->sub_option( 'sidebar_width', 'size' );
		if ( empty( $sidebar_size ) ) {
			if ( 'px' !== cryptozfree()->sub_option( 'content_width', 'unit' ) ) {
				$content_width = floor( cryptozfree()->sub_option( 'content_width', 'size' ) * 17 ) - 48;
			} else {
				$content_width = cryptozfree()->sub_option( 'content_width', 'size' ) - 48;
			}
			$sidebar_neg = floor( ( 27 / 100 ) * $content_width ) . 'px';
		} elseif ( '%' === cryptozfree()->sub_option( 'sidebar_width', 'unit' ) ) {
			if ( 'px' !== cryptozfree()->sub_option( 'content_width', 'unit' ) ) {
				$content_width = floor( cryptozfree()->sub_option( 'content_width', 'size' ) * 17 ) - 48;
			} else {
				$content_width = cryptozfree()->sub_option( 'content_width', 'size' ) - 48;
			}
			$sidebar_neg = floor( ( $sidebar_size / 100 ) * $content_width ) . 'px';
		} else {
			$sidebar_neg = $sidebar_size . cryptozfree()->sub_option( 'sidebar_width', 'unit' );
		}
		$css->set_selector( '.block-editor-page.post-content-style-boxed.post-content-sidebar-right .editor-styles-wrapper:before, .block-editor-page.post-content-style-boxed.post-content-sidebar-left .editor-styles-wrapper:before' );
		$css->add_property( 'max-width', 'calc(' . cryptozfree()->sub_option( 'content_width', 'size' ) . cryptozfree()->sub_option( 'content_width', 'unit' ) . ' - ' . $sidebar_neg . ' - 3.5rem - 3rem )' );
		// Full Width.
		$css->set_selector( '.block-editor-page.post-content-style-boxed.post-content-width-fullwidth .editor-styles-wrapper:before' );
		$css->add_property( 'max-width', '100%' );
		// Content Editor Width.
		$css->set_selector( 'body.block-editor-page .editor-styles-wrapper .block-editor-block-list__layout .wp-block, body.block-editor-page .editor-post-title__block' );
		$css->add_property( 'max-width', 'calc(' . cryptozfree()->sub_option( 'content_width', 'size' ) . cryptozfree()->sub_option( 'content_width', 'unit' ) . ' - 3rem )' );
		// Boxed Content Editor Width.
		$css->set_selector( 'body.block-editor-page.post-content-style-boxed .block-editor-block-list__layout .wp-block, body.block-editor-page.post-content-style-boxed .editor-post-title__block' );
		$css->add_property( 'max-width', 'calc(' . cryptozfree()->sub_option( 'content_width', 'size' ) . cryptozfree()->sub_option( 'content_width', 'unit' ) . ' - 3rem - 4rem )' );
		// Narrow Content Editor Width.
		$css->set_selector( 'body.block-editor-page.post-content-width-narrow .block-editor-block-list__layout .wp-block, body.block-editor-page.post-content-width-narrow .editor-post-title__block' );
		$css->add_property( 'max-width', 'calc(' . cryptozfree()->sub_option( 'content_narrow_width', 'size' ) . cryptozfree()->sub_option( 'content_narrow_width', 'unit' ) . ' - 3rem )' );
		// Boxed Narrow Content Editor Width.
		$css->set_selector( 'body.block-editor-page.post-content-style-boxed.post-content-width-narrow .block-editor-block-list__layout .wp-block, body.block-editor-page.post-content-style-boxed.post-content-width-narrow .editor-post-title__block' );
		$css->add_property( 'max-width', 'calc(' . cryptozfree()->sub_option( 'content_narrow_width', 'size' ) . cryptozfree()->sub_option( 'content_narrow_width', 'unit' ) . ' - 3rem - 4rem)' );
		// Sidebar Content Editor Width.
		$css->set_selector( 'body.block-editor-page.post-content-sidebar-right .block-editor-block-list__layout .wp-block, body.block-editor-page.post-content-sidebar-left .block-editor-block-list__layout .wp-block, body.block-editor-page.post-content-sidebar-right .editor-post-title__block, body.block-editor-page.post-content-sidebar-left .editor-post-title__block' );
		$css->add_property( 'max-width', 'calc(' . cryptozfree()->sub_option( 'content_width', 'size' ) . cryptozfree()->sub_option( 'content_width', 'unit' ) . ' - ' . $sidebar_neg . ' - 3.5rem - 3rem )' );
		// Boxed Sidebar Content Editor Width.
		$css->set_selector( 'body.block-editor-page.post-content-style-boxed.post-content-sidebar-right .block-editor-block-list__layout .wp-block, body.block-editor-page.post-content-style-boxed.post-content-sidebar-left .block-editor-block-list__layout .wp-block, body.post-content-style-boxed.block-editor-page.post-content-sidebar-right .editor-post-title__block, body.post-content-style-boxed.block-editor-page.post-content-sidebar-left .editor-post-title__block' );
		$css->add_property( 'max-width', 'calc(' . cryptozfree()->sub_option( 'content_width', 'size' ) . cryptozfree()->sub_option( 'content_width', 'unit' ) . ' - ' . $sidebar_neg . ' - 3.5rem - 3rem - 4rem )' );
		// Fullwidth Content Editor Width.
		$css->set_selector( 'body.block-editor-page.post-content-width-fullwidth .block-editor-block-list__layout .wp-block, body.block-editor-page.post-content-width-fullwidth .editor-post-title__block' );
		$css->add_property( 'max-width', 'none' );
		$css->set_selector( 'body.block-editor-page.post-content-width-fullwidth.post-content-style-boxed .block-editor-block-list__layout .wp-block, body.block-editor-page.post-content-width-fullwidth.post-content-style-boxed .editor-post-title__block' );
		$css->add_property( 'max-width', 'none' );
		// cryptozfree row theme Width.
		$css->set_selector( 'body.block-editor-page .wp-block-cryptozfree-rowlayout > .innerblocks-wrap.kb-theme-content-width' );
		$css->add_property( 'padding-left', '1.5rem' );
		$css->add_property( 'padding-right', '1.5rem' );
		$css->set_selector( 'body.block-editor-page.post-content-style-boxed .wp-block-cryptozfree-rowlayout > .innerblocks-wrap.kb-theme-content-width' );
		$css->add_property( 'padding-left', 'calc(1.5rem + 2rem)' );
		$css->add_property( 'padding-right', 'calc(1.5rem + 2rem)' );
		// Align Wide.
		$css->set_selector( 'body.block-editor-page .block-editor-block-list__layout .wp-block[data-align=wide], body.block-editor-page .block-editor-block-list__layout .wp-block.alignwide' );
		$css->add_property( 'max-width', 'calc(' . cryptozfree()->sub_option( 'content_width', 'size' ) . cryptozfree()->sub_option( 'content_width', 'unit' ) . ' + 160px)' );
		// Align Wide Normal boxed.
		$css->set_selector( 'body.block-editor-page.post-content-style-boxed .block-editor-block-list__layout .wp-block[data-align=wide], body.block-editor-page.post-content-style-boxed .block-editor-block-list__layout .wp-block.alignwide' );
		$css->add_property( 'max-width', 'calc(' . cryptozfree()->sub_option( 'content_width', 'size' ) . cryptozfree()->sub_option( 'content_width', 'unit' ) . ' - 3rem )' );
		// Align Wide narrow boxed.
		$css->set_selector( 'body.block-editor-page.post-content-style-boxed.post-content-width-narrow .block-editor-block-list__layout .wp-block[data-align=wide], body.block-editor-page.post-content-style-boxed.post-content-width-narrow .block-editor-block-list__layout .wp-block.alignwide' );
		$css->add_property( 'max-width', 'calc(' . cryptozfree()->sub_option( 'content_narrow_width', 'size' ) . cryptozfree()->sub_option( 'content_narrow_width', 'unit' ) . ' - 3rem )' );
		// Align Wide Sidebar boxed.
		$css->set_selector( 'body.block-editor-page.post-content-style-boxed.post-content-sidebar-right .block-editor-block-list__layout .wp-block[data-align=wide], body.block-editor-page.post-content-style-boxed.post-content-sidebar-right .block-editor-block-list__layout .wp-block.alignwide, body.block-editor-page.post-content-style-boxed.post-content-sidebar-left .block-editor-block-list__layout .wp-block[data-align=wide], body.block-editor-page.post-content-style-boxed.post-content-sidebar-left .block-editor-block-list__layout .wp-block.alignwide' );
		$css->add_property( 'max-width', 'calc(' . cryptozfree()->sub_option( 'content_width', 'size' ) . cryptozfree()->sub_option( 'content_width', 'unit' ) . ' - ' . $sidebar_neg . ' - 3.5em - 3rem )' );
		$css->set_selector( '.editor-styles-wrapper .wp-block[data-align="full"], .editor-styles-wrapper .wp-block.alignfull' );
		$css->add_property( 'max-width', 'none !important' );
		// $css->set_selector( 'body.block-editor-page .interface-interface-skeleton__editor' );
		// $css->add_property( 'max-width', '100%' );
		// Heading Fonts.
		$css->set_selector( '.block-editor-page .editor-post-title__block .editor-post-title__input, .block-editor-page .editor-styles-wrapper h1, .block-editor-page .editor-styles-wrapper h2, .block-editor-page .editor-styles-wrapper h3, .block-editor-page .editor-styles-wrapper h4, .block-editor-page .editor-styles-wrapper h5, .block-editor-page .editor-styles-wrapper h6' );
		$css->add_property( 'font-family', $css->render_font_family( cryptozfree()->option( 'heading_font' ) ) );
		$css->set_selector( '.block-editor-page .editor-styles-wrapper h1, .block-editor-page .editor-post-title__block .editor-post-title__input' );
		$css->render_font( cryptozfree()->option( 'h1_font' ), $css );
		$css->set_selector( '.block-editor-page .editor-styles-wrapper h2' );
		$css->render_font( cryptozfree()->option( 'h2_font' ), $css );
		$css->set_selector( '.block-editor-page .editor-styles-wrapper h3' );
		$css->render_font( cryptozfree()->option( 'h3_font' ), $css );
		$css->set_selector( '.block-editor-page .editor-styles-wrapper h4' );
		$css->render_font( cryptozfree()->option( 'h4_font' ), $css );
		$css->set_selector( '.block-editor-page .editor-styles-wrapper h5' );
		$css->render_font( cryptozfree()->option( 'h5_font' ), $css );
		$css->set_selector( '.block-editor-page .editor-styles-wrapper h6' );
		$css->render_font( cryptozfree()->option( 'h6_font' ), $css );
		self::$google_fonts = $css->fonts_output();
		return $css->css_output();

	}
	/**
	 * Generates the dynamic css based on customizer options.
	 *
	 * @param array $mce_init tinymce settings.
	 * @return string
	 */
	public function filter_add_tinymce_editor_styles( $mce_init ) {
		$css = new cryptozfree_CSS();
		$css->set_selector( ':root' );
		$css->add_property( '--global-palette1', cryptozfree()->palette_option( 'palette1' ) );
		$css->add_property( '--global-palette2', cryptozfree()->palette_option( 'palette2' ) );
		$css->add_property( '--global-palette3', cryptozfree()->palette_option( 'palette3' ) );
		$css->add_property( '--global-palette4', cryptozfree()->palette_option( 'palette4' ) );
		$css->add_property( '--global-palette5', cryptozfree()->palette_option( 'palette5' ) );
		$css->add_property( '--global-palette6', cryptozfree()->palette_option( 'palette6' ) );
		$css->add_property( '--global-palette7', cryptozfree()->palette_option( 'palette7' ) );
		$css->add_property( '--global-palette8', cryptozfree()->palette_option( 'palette8' ) );
		$css->add_property( '--global-palette9', cryptozfree()->palette_option( 'palette9' ) );	
		// Body Fonts.
		$css->set_selector( 'body.mce-content-body' );
		$css->render_font( cryptozfree()->option( 'base_font' ), $css, 'body' );
		$css->set_selector( 'body.mce-content-body h1,body.mce-content-body h2,body.mce-content-body h3,body.mce-content-body h4,body.mce-content-body h5,body.mce-content-body h6' );
		$css->add_property( 'font-family', $css->render_font_family( cryptozfree()->option( 'heading_font' ) ) );
		$css->set_selector( 'body.mce-content-body h1' );
		$css->render_font( cryptozfree()->option( 'h1_font' ), $css );
		$css->set_selector( 'body.mce-content-body h2' );
		$css->render_font( cryptozfree()->option( 'h2_font' ), $css );
		$css->set_selector( 'body.mce-content-body h3' );
		$css->render_font( cryptozfree()->option( 'h3_font' ), $css );
		$css->set_selector( 'body.mce-content-body h4' );
		$css->render_font( cryptozfree()->option( 'h4_font' ), $css );
		$css->set_selector( 'body.mce-content-body h5' );
		$css->render_font( cryptozfree()->option( 'h5_font' ), $css );
		$css->set_selector( 'body.mce-content-body h6' );
		$css->render_font( cryptozfree()->option( 'h6_font' ), $css );

		if ( isset( $mce_init['content_style'] ) ) {
			$mce_init['content_style'] .= ' ' . str_replace( '"', "'", $css->css_output() ) . ' ';
		} else {
			$mce_init['content_style'] = str_replace( '"', "'", $css->css_output() ) . ' ';
		}
		return $mce_init;
	}
	/**
	 * Generates the Initial hero padding.
	 *
	 * @param string $device the target device.
	 * @return string
	 */
	public function render_hero_padding( $device ) {
		if ( 'desktop' === $device ) {
			 $top_height = false;
			// if ( cryptozfree()->display_header_row( 'top' ) && $this->render_range( cryptozfree()->sub_option( 'header_top_height' ), $device ) ) {
			// 	$top_height = $this->render_range( cryptozfree()->sub_option( 'header_top_height' ), $device );
			// }
			 $main_height = false;
			// if ( cryptozfree()->display_header_row( 'main' ) && $this->render_range( cryptozfree()->sub_option( 'header_main_height' ), $device ) ) {
			// 	$main_height = $this->render_range( cryptozfree()->sub_option( 'header_main_height' ), $device );
			// }
			 $bottom_height = false;
			// if ( cryptozfree()->display_header_row( 'bottom' ) && $this->render_range( cryptozfree()->sub_option( 'header_bottom_height' ), $device ) ) {
			// 	$bottom_height = $this->render_range( cryptozfree()->sub_option( 'header_bottom_height' ), $device );
			// }
			$size_string = '';
			if ( ( $top_height && $main_height ) || ( $top_height && $bottom_height ) || ( $bottom_height && $main_height ) ) {
				$size_string = 'calc(';
			}
			if ( $top_height ) {
				$size_string .= $top_height;
				if ( $bottom_height || $main_height ) {
					$size_string .= ' + ';
				}
			}
			if ( $main_height ) {
				$size_string .= $main_height;
				if ( $bottom_height ) {
					$size_string .= ' + ';
				}
			}
			if ( $bottom_height ) {
				$size_string .= $bottom_height;
			}
			if ( ( $top_height && $main_height ) || ( $top_height && $bottom_height ) || ( $bottom_height && $main_height ) ) {
				$size_string .= ')';
			}
			return $size_string;
		} else {
			$top_height = false;
			// if ( cryptozfree()->display_mobile_header_row( 'top' ) ) {
			// 	if ( $this->render_range( cryptozfree()->sub_option( 'header_top_height' ), $device ) ) {
			// 		$top_height = $this->render_range( cryptozfree()->sub_option( 'header_top_height' ), $device );
			// 	} elseif ( 'mobile' === $device && $this->render_range( cryptozfree()->sub_option( 'header_top_height' ), 'tablet' ) ) {
			// 		$top_height = $this->render_range( cryptozfree()->sub_option( 'header_top_height' ), 'tablet' );
			// 	} elseif ( $this->render_range( cryptozfree()->sub_option( 'header_top_height' ), 'desktop' ) ) {
			// 		$top_height = $this->render_range( cryptozfree()->sub_option( 'header_top_height' ), 'desktop' );
			// 	}
			// }
			$main_height = false;
			// if ( cryptozfree()->display_mobile_header_row( 'main' ) ) {
			// 	if ( $this->render_range( cryptozfree()->sub_option( 'header_main_height' ), $device ) ) {
			// 		$main_height = $this->render_range( cryptozfree()->sub_option( 'header_main_height' ), $device );
			// 	} elseif ( 'mobile' === $device && $this->render_range( cryptozfree()->sub_option( 'header_main_height' ), 'tablet' ) ) {
			// 		$main_height = $this->render_range( cryptozfree()->sub_option( 'header_main_height' ), 'tablet' );
			// 	} elseif ( $this->render_range( cryptozfree()->sub_option( 'header_main_height' ), 'desktop' ) ) {
			// 		$main_height = $this->render_range( cryptozfree()->sub_option( 'header_main_height' ), 'desktop' );
			// 	}
			// }
			$bottom_height = false;
			// if ( cryptozfree()->display_mobile_header_row( 'bottom' ) ) {
			// 	if ( $this->render_range( cryptozfree()->sub_option( 'header_bottom_height' ), $device ) ) {
			// 		$main_height = $this->render_range( cryptozfree()->sub_option( 'header_bottom_height' ), $device );
			// 	} elseif ( 'mobile' === $device && $this->render_range( cryptozfree()->sub_option( 'header_bottom_height' ), 'tablet' ) ) {
			// 		$main_height = $this->render_range( cryptozfree()->sub_option( 'header_bottom_height' ), 'tablet' );
			// 	} elseif ( $this->render_range( cryptozfree()->sub_option( 'header_bottom_height' ), 'desktop' ) ) {
			// 		$main_height = $this->render_range( cryptozfree()->sub_option( 'header_bottom_height' ), 'desktop' );
			// 	}
			// }
			$size_string = '';
			if ( ( $top_height && $main_height ) || ( $top_height && $bottom_height ) || ( $bottom_height && $main_height ) ) {
				$size_string = 'calc(';
			}
			if ( $top_height ) {
				$size_string .= $top_height;
				if ( $bottom_height || $main_height ) {
					$size_string .= ' + ';
				}
			}
			if ( $main_height ) {
				$size_string .= $main_height;
				if ( $bottom_height ) {
					$size_string .= ' + ';
				}
			}
			if ( $bottom_height ) {
				$size_string .= $bottom_height;
			}
			if ( ( $top_height && $main_height ) || ( $top_height && $bottom_height ) || ( $bottom_height && $main_height ) ) {
				$size_string .= ')';
			}
			return $size_string;
		}
	}
	/**
	 * Generates the size output.
	 *
	 * @param array $size an array of size settings.
	 * @return string
	 */
	public function render_half_size( $size ) {
		if ( empty( $size ) ) {
			return false;
		}
		if ( ! is_array( $size ) ) {
			return false;
		}
		$size_number = ( isset( $size['size'] ) && ! empty( $size['size'] ) ? $size['size'] : '0' );
		$size_unit   = ( isset( $size['unit'] ) && ! empty( $size['unit'] ) ? $size['unit'] : 'em' );

		$size_string = 'calc(' . $size_number . $size_unit . ' / 2)';
		return $size_string;
	}
	/**
	 * Generates the size output.
	 *
	 * @param array $size an array of size settings.
	 * @return string
	 */
	public function render_negative_half_size( $size ) {
		if ( empty( $size ) ) {
			return false;
		}
		if ( ! is_array( $size ) ) {
			return false;
		}
		$size_number = ( isset( $size['size'] ) && ! empty( $size['size'] ) ? $size['size'] : '0' );
		$size_unit   = ( isset( $size['unit'] ) && ! empty( $size['unit'] ) ? $size['unit'] : 'em' );

		$size_string = 'calc(-' . $size_number . $size_unit . ' / 2)';
		return $size_string;
	}

	/**
	 * Generates the size output.
	 *
	 * @param array $size an array of size settings.
	 * @return string
	 */
	public function render_size( $size ) {
		if ( empty( $size ) ) {
			return false;
		}
		if ( ! is_array( $size ) ) {
			return false;
		}
		$size_number = ( isset( $size['size'] ) && ! empty( $size['size'] ) ? $size['size'] : '0' );
		$size_unit   = ( isset( $size['unit'] ) && ! empty( $size['unit'] ) ? $size['unit'] : 'em' );

		$size_string = $size_number . $size_unit;
		return $size_string;
	}
	/**
	 * Generates the size output.
	 *
	 * @param array $size an array of size settings.
	 * @return string
	 */
	public function render_negative_size( $size ) {
		if ( empty( $size ) ) {
			return false;
		}
		if ( ! is_array( $size ) ) {
			return false;
		}
		$size_number = ( isset( $size['size'] ) && ! empty( $size['size'] ) ? $size['size'] : '0' );
		$size_unit   = ( isset( $size['unit'] ) && ! empty( $size['unit'] ) ? $size['unit'] : 'em' );

		$size_string = '-' . $size_number . $size_unit;
		return $size_string;
	}
	/**
	 * Generates the measure output.
	 *
	 * @param array $measure an array of font settings.
	 * @return string
	 */
	public function render_measure( $measure ) {
		if ( empty( $measure ) ) {
			return false;
		}
		if ( ! is_array( $measure ) ) {
			return false;
		}
		if ( ! isset( $measure['size'] ) ) {
			return false;
		}
		if ( ! is_array( $measure['size'] ) ) {
			return false;
		}
		if ( ! is_numeric( $measure['size'][0] ) && ! is_numeric( $measure['size'][0] ) && ! is_numeric( $measure['size'][0] ) && ! is_numeric( $measure['size'][0] ) ) {
			return false;
		}
		$size_unit   = ( isset( $measure['unit'] ) && ! empty( $measure['unit'] ) ? $measure['unit'] : 'px' );
		$size_string = ( is_numeric( $measure['size'][0] ) ? $measure['size'][0] : '0' ) . $size_unit . ' ' . ( is_numeric( $measure['size'][1] ) ? $measure['size'][1] : '0' ) . $size_unit . ' ' . ( is_numeric( $measure['size'][2] ) ? $measure['size'][2] : '0' ) . $size_unit . ' ' . ( is_numeric( $measure['size'][3] ) ? $measure['size'][3] : '0' ) . $size_unit;
		return $size_string;
	}
	/**
	 * Generates the measure output.
	 *
	 * @param array $measure an array of font settings.
	 * @return string
	 */
	public function render_responsive_measure( $measure, $device ) {
		if ( empty( $measure ) ) {
			return false;
		}
		if ( ! is_array( $measure ) ) {
			return false;
		}
		if ( ! isset( $measure['size'] ) ) {
			return false;
		}
		if ( ! is_array( $measure['size'] ) ) {
			return false;
		}
		if ( ! isset( $measure['size'][ $device ] ) ) {
			return false;
		}
		if ( ! is_array( $measure['size'][ $device ] ) ) {
			return false;
		}
		if ( ! is_numeric( $measure['size'][ $device ][0] ) && ! is_numeric( $measure['size'][ $device ][0] ) && ! is_numeric( $measure['size'][ $device ][0] ) && ! is_numeric( $measure['size'][ $device ][0] ) ) {
			return false;
		}
		$size_unit   = ( isset( $measure['unit'] ) && isset( $measure['unit'][ $device ] ) && ! empty( $measure['unit'][ $device ] ) ? $measure['unit'][ $device ] : 'px' );
		$size_string = ( is_numeric( $measure['size'][ $device ][0] ) ? $measure['size'][ $device ][0] : '0' ) . $size_unit . ' ' . ( is_numeric( $measure['size'][ $device ][1] ) ? $measure['size'][ $device ][1] : '0' ) . $size_unit . ' ' . ( is_numeric( $measure['size'][ $device ][2] ) ? $measure['size'][ $device ][2] : '0' ) . $size_unit . ' ' . ( is_numeric( $measure['size'][ $device ][3] ) ? $measure['size'][ $device ][3] : '0' ) . $size_unit;
		return $size_string;
	}
	/**
	 * Generates the font output.
	 *
	 * @param array  $font an array of font settings.
	 * @param object $css an object of css output.
	 * @param string $inherit an string to determine if the font should inherit.
	 * @return string
	 */
	public function render_font( $font, $css, $inherit = null ) {
		if ( empty( $font ) ) {
			return false;
		}
		if ( ! is_array( $font ) ) {
			return false;
		}
		if ( isset( $font['style'] ) && ! empty( $font['style'] ) ) {
			$css->add_property( 'font-style', $font['style'] );
		}
		if ( isset( $font['weight'] ) && ! empty( $font['weight'] ) ) {
			$css->add_property( 'font-weight', $font['weight'] );
		}
		$size_type = ( isset( $font['sizeType'] ) && ! empty( $font['sizeType'] ) ? $font['sizeType'] : 'px' );
		if ( isset( $font['size'] ) && isset( $font['size']['desktop'] ) && ! empty( $font['size']['desktop'] ) ) {
			$css->add_property( 'font-size', $font['size']['desktop'] . $size_type );
		}
		$line_type = ( isset( $font['lineType'] ) && ! empty( $font['lineType'] ) ? $font['lineType'] : '' );
		$line_type = ( '-' !== $line_type ? $line_type : '' );
		if ( isset( $font['lineHeight'] ) && isset( $font['lineHeight']['desktop'] ) && ! empty( $font['lineHeight']['desktop'] ) ) {
			$css->add_property( 'line-height', $font['lineHeight']['desktop'] . $line_type );
		}
		$letter_type = ( isset( $font['spacingType'] ) && ! empty( $font['spacingType'] ) ? $font['spacingType'] : 'em' );
		if ( isset( $font['letterSpacing'] ) && isset( $font['letterSpacing']['desktop'] ) && ! empty( $font['letterSpacing']['desktop'] ) ) {
			$css->add_property( 'letter-spacing', $font['letterSpacing']['desktop'] . $letter_type );
		}
		$family = ( isset( $font['family'] ) && ! empty( $font['family'] ) && 'inherit' !== $font['family'] ? $font['family'] : '' );
		if ( ! empty( $family ) ) {
			$css->add_property( 'font-family', $family );
			if ( isset( $font['google'] ) && true === $font['google'] ) {
				if ( ! empty( $inherit ) && 'body' === $inherit ) {
					$css->maybe_add_google_font( $font, $inherit );
				} else {
					$css->maybe_add_google_font( $font );
				}
			}
		}
		if ( isset( $font['transform'] ) && ! empty( $font['transform'] ) ) {
			$css->add_property( 'text-transform', $font['transform'] );
		}
		if ( isset( $font['color'] ) && ! empty( $font['color'] ) ) {
			$css->add_property( 'color', $this->render_color( $font['color'] ) );
		}
	}
	/**
	 * Generates the font family output.
	 *
	 * @param array $font an array of font settings.
	 * @return string
	 */
	public function render_font_family( $font, $area = 'headers' ) {
		if ( empty( $font ) ) {
			return false;
		}
		if ( ! is_array( $font ) ) {
			return false;
		}
		if ( ! isset( $font['family'] ) ) {
			return false;
		}
		if ( empty( $font['family'] ) ) {
			return false;
		}
		if ( 'inherit' === $font['family'] ) {
			$font_string = 'inherit';
		} else {
			$font_string = $font['family'];
		}
		if ( isset( $font['google'] ) && true === $font['google'] ) {
			$this->maybe_add_google_font( $font, $area );
		}

		return $font_string;
	}
	/**
	 * Generates the font size output.
	 *
	 * @param array  $font an array of font settings.
	 * @param string $device the device this is showing on.
	 * @return string
	 */
	public function render_font_size( $font, $device ) {
		if ( empty( $font ) ) {
			return false;
		}
		if ( ! is_array( $font ) ) {
			return false;
		}
		if ( ! isset( $font['size'] ) ) {
			return false;
		}
		if ( ! is_array( $font['size'] ) ) {
			return false;
		}
		if ( ! isset( $font['size'][ $device ] ) ) {
			return false;
		}
		if ( empty( $font['size'][ $device ] ) ) {
			return false;
		}
		$font_string = $font['size'][ $device ] . ( isset( $font['sizeType'] ) && ! empty( $font['sizeType'] ) ? $font['sizeType'] : 'px' );

		return $font_string;
	}
	/**
	 * Generates the font height output.
	 *
	 * @param array  $font an array of font settings.
	 * @param string $device the device this is showing on.
	 * @return string
	 */
	public function render_font_height( $font, $device ) {
		if ( empty( $font ) ) {
			return false;
		}
		if ( ! is_array( $font ) ) {
			return false;
		}
		if ( ! isset( $font['lineHeight'] ) ) {
			return false;
		}
		if ( ! is_array( $font['lineHeight'] ) ) {
			return false;
		}
		if ( ! isset( $font['lineHeight'][ $device ] ) ) {
			return false;
		}
		if ( empty( $font['lineHeight'][ $device ] ) ) {
			return false;
		}
		if ( isset( $font['lineType'] ) && ! empty( $font['lineType'] ) && '-' === $font['lineType'] ) {
			$font['lineType'] = '';
		}
		$font_string = $font['lineHeight'][ $device ] . ( isset( $font['lineType'] ) && ! empty( $font['lineType'] ) ? $font['lineType'] : '' );

		return $font_string;
	}
	/**
	 * Generates the font spacing output.
	 *
	 * @param array  $font an array of font settings.
	 * @param string $device the device this is showing on.
	 * @return string
	 */
	public function render_font_spacing( $font, $device ) {
		if ( empty( $font ) ) {
			return false;
		}
		if ( ! is_array( $font ) ) {
			return false;
		}
		if ( ! isset( $font['letterSpacing'] ) ) {
			return false;
		}
		if ( ! is_array( $font['letterSpacing'] ) ) {
			return false;
		}
		if ( ! isset( $font['letterSpacing'][ $device ] ) ) {
			return false;
		}
		if ( empty( $font['letterSpacing'][ $device ] ) ) {
			return false;
		}
		$font_string = $font['letterSpacing'][ $device ] . ( isset( $font['spacingType'] ) && ! empty( $font['spacingType'] ) ? $font['spacingType'] : 'em' );

		return $font_string;
	}

	/**
	 * Generates the color output.
	 *
	 * @param string $color any color attribute.
	 * @return string
	 */
	public function render_color( $color ) {
		if ( empty( $color ) ) {
			return false;
		}
		if ( ! is_array( $color ) && strpos( $color, 'palette' ) !== false ) {
			$color = 'var(--global-' . $color . ')';
		}
		return $color;
	}
	/**
	 * Generates the size output.
	 *
	 * @param array  $size an array of size settings.
	 * @param string $device the device this is showing on.
	 * @return string
	 */
	public function render_range( $size, $device ) {
		if ( empty( $size ) ) {
			return false;
		}
		if ( ! is_array( $size ) ) {
			return false;
		}
		if ( ! isset( $size['size'] ) ) {
			return false;
		}
		if ( ! is_array( $size['size'] ) ) {
			return false;
		}
		if ( ! isset( $size['size'][ $device ] ) ) {
			return false;
		}
		if ( ! is_numeric( $size['size'][ $device ] ) ) {
			return false;
		}
		$size_type   = ( isset( $size['unit'] ) && is_array( $size['unit'] ) && isset( $size['unit'][ $device ] ) && ! empty( $size['unit'][ $device ] ) ? $size['unit'][ $device ] : 'px' );
		$size_string = $size['size'][ $device ] . $size_type;

		return $size_string;
	}
	/**
	 * Preloads in-body stylesheets depending on what templates are being used.
	 *
	 * Only stylesheets that have a 'preload_callback' provided will be considered. If that callback evaluates to true
	 * for the current request, the stylesheet will be preloaded.
	 *
	 * Preloading is disabled when AMP is active, as AMP injects the stylesheets inline.
	 *
	 * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Preloading_content
	 */
	public function action_preload_styles() {

		// If preloading styles is disabled, return early.
		if ( ! $this->preloading_styles_enabled() ) {
			return;
		}

		$wp_styles = wp_styles();

		$css_files = $this->get_css_files();
		foreach ( $css_files as $handle => $data ) {

			// Skip if stylesheet not registered.
			if ( ! isset( $wp_styles->registered[ $handle ] ) ) {
				continue;
			}

			// Skip if no preload callback provided.
			if ( ! is_callable( $data['preload_callback'] ) ) {
				continue;
			}

			// Skip if preloading is not necessary for this request.
			if ( ! call_user_func( $data['preload_callback'] ) ) {
				continue;
			}

			$preload_uri = $wp_styles->registered[ $handle ]->src . '?ver=' . $wp_styles->registered[ $handle ]->ver;

			echo '<link rel="preload" id="' . esc_attr( $handle ) . '-preload" href="' . esc_url( $preload_uri ) . '" as="style">';
			echo "\n";
		}
	}

	/**
	 * Enqueues WordPress theme styles for the editor.
	 */
	public function action_add_editor_styles() {
		// Enqueue block editor stylesheet.
		add_editor_style( 'assets/css/editor/editor-styles.min.css' );
	}
	/**
	 * Generates the dynamic css based on customizer options.
	 *
	 * @param string $css any custom css.
	 * @return string
	 */
	public function editor_dynamic_css( $css ) {
		$generated_css = $this->generate_editor_css();
		if ( ! empty( $generated_css ) ) {
			$css .= "\n/* cryptozfree Dynamic CSS */\n" . $generated_css;
		}
		return $css;
	}

	/**
	 * Enqueues WordPress theme styles for the editor.
	 */
	public function action_add_gutenberg_styles() {
		wp_register_style( 'cryptozfree-editor-global', false );
		wp_enqueue_style( 'cryptozfree-editor-global' );
		wp_add_inline_style( 'cryptozfree-editor-global', trim( apply_filters( 'cryptozfree_editor_dynamic_css', '' ) ) );
		// Enqueue Google Fonts.
		$google_fonts_url = $this->get_google_fonts_url();
		if ( ! empty( $google_fonts_url ) ) {
			wp_enqueue_style( 'cryptozfree-google-fonts', $this->get_google_fonts_url() );
		}
	}
	/**
	 * Adds preconnect resource hint for Google Fonts.
	 *
	 * @param array  $urls          URLs to print for resource hints.
	 * @param string $relation_type The relation type the URLs are printed.
	 * @return array URLs to print for resource hints.
	 */
	public function filter_resource_hints( array $urls, string $relation_type ) : array {
		if ( 'preconnect' === $relation_type && wp_style_is( 'cryptozfree-fonts', 'queue' ) ) {
			$urls[] = array(
				'href' => 'https://fonts.gstatic.com',
				'crossorigin',
			);
		}

		return $urls;
	}

	/**
	 * Prints stylesheet link tags directly.
	 *
	 * This should be used for stylesheets that aren't global and thus should only be loaded if the HTML markup
	 * they are responsible for is actually present. Template parts should use this method when the related markup
	 * requires a specific stylesheet to be loaded. If preloading stylesheets is disabled, this method will not do
	 * anything.
	 *
	 * If the `<link>` tag for a given stylesheet has already been printed, it will be skipped.
	 *
	 * @param string ...$handles One or more stylesheet handles.
	 */
	public function print_styles( string ...$handles ) {

		// If preloading styles is disabled (and thus they have already been enqueued), return early.
		if ( ! $this->preloading_styles_enabled() ) {
			return;
		}

		$css_files = $this->get_css_files();
		$handles   = array_filter(
			$handles,
			function( $handle ) use ( $css_files ) {
				$is_valid = isset( $css_files[ $handle ] ) && ! $css_files[ $handle ]['global'];
				if ( ! $is_valid ) {
					/* translators: %s: stylesheet handle */
					_doing_it_wrong( __CLASS__ . '::print_styles()', esc_html( sprintf( __( 'Invalid theme stylesheet handle: %s', 'cryptozfree' ), $handle ) ), 'cryptozfree 1.0.0' );
				}
				return $is_valid;
			}
		);

		if ( empty( $handles ) ) {
			return;
		}

		wp_print_styles( $handles );
	}

	/**
	 * Determines whether to preload stylesheets and inject their link tags directly within the page content.
	 *
	 * Using this technique generally improves performance, however may not be preferred under certain circumstances.
	 * For example, since AMP will include all style rules directly in the head, it must not be used in that context.
	 * By default, this method returns true unless the page is being served in AMP. The
	 * {@see 'cryptozfree_preloading_styles_enabled'} filter can be used to tweak the return value.
	 *
	 * @return bool True if preloading stylesheets and injecting them is enabled, false otherwise.
	 */
	protected function preloading_styles_enabled() {
		$preloading_styles_enabled = ! cryptozfree()->is_amp();

		if ( $preloading_styles_enabled ) {
			$preloading_styles_enabled = cryptozfree()->option( 'enable_preload' );
		}

		/**
		 * Filters whether to preload stylesheets and inject their link tags within the page content.
		 *
		 * @param bool $preloading_styles_enabled Whether preloading stylesheets and injecting them is enabled.
		 */
		return apply_filters( 'cryptozfree_preloading_styles_enabled', $preloading_styles_enabled );
	}

	/**
	 * Gets all CSS files.
	 *
	 * @return array Associative array of $handle => $data pairs.
	 */
	protected function get_css_files() : array {
		if ( is_array( $this->css_files ) ) {
			return $this->css_files;
		}

		$css_files = array(
			'cryptozfree-global'     => array(
				'file'   => 'global.min.css',
				'global' => true,
			),
			// 'cryptozfree-rtl'   => array(
			// 	'file'   => 'rtl.min.css',
			// 	'global' => is_rtl(),
			// ),
			// 'cryptozfree-simplelightbox-css' => array(
			// 	'file'   => 'simplelightbox.min.css',
			// 	'global' => cryptozfree()->option( 'lightbox' ),
			// ),
			// 'cryptozfree-header'    => array(
			// 	'file'             => 'header.min.css',
			// 	'preload_callback' => function() {
			// 		return cryptozfree()->has_header();
			// 	},
			// ),
			// 'cryptozfree-content'    => array(
			// 	'file'             => 'content.min.css',
			// 	'preload_callback' => function() {
			// 		return cryptozfree()->has_content();
			// 	},
			// ),
			// 'cryptozfree-comments'   => array(
			// 	'file'             => 'comments.min.css',
			// 	'preload_callback' => function() {
			// 		return cryptozfree()->show_comments();
			// 	},
			// ),
			// 'cryptozfree-sidebar'    => array(
			// 	'file'             => 'sidebar.min.css',
			// 	'preload_callback' => function() {
			// 		return cryptozfree()->has_sidebar();
			// 	},
			// ),
			// 'cryptozfree-author-box'   => array(
			// 	'file'             => 'author-box.min.css',
			// 	'preload_callback' => function() {
			// 		return is_single() && cryptozfree()->option( 'post_author_box' );
			// 	},
			// ),
			// 'cryptozfree-related-posts'   => array(
			// 	'file'             => 'related-posts.min.css',
			// 	'preload_callback' => function() {
			// 		return is_single() && cryptozfree()->option( 'post_related' );
			// 	},
			// ),
			// 'cryptozfree-slide'   => array(
			// 	'file'             => 'slider.min.css',
			// 	'preload_callback' => function() {
			// 		return is_single() && cryptozfree()->option( 'post_related' );
			// 	},
			// ),
			'cryptozfree-woocommerce'    => array(
				'file'   => 'woocommerce.min.css',
				'global' => class_exists( 'woocommerce' ),
			),
			// 'cryptozfree-heroic'    => array(
			// 	'file'   => 'heroic-knowledge-base.min.css',
			// 	'global' => class_exists( 'HT_Knowledge_Base' ),
			// ),
			// 'cryptozfree-footer'    => array(
			// 	'file'             => 'footer.min.css',
			// 	'preload_callback' => function() {
			// 		return cryptozfree()->has_footer();
			// 	},
			// ),
		);

		/**
		 * Filters default CSS files.
		 *
		 * @param array $css_files Associative array of CSS files, as $handle => $data pairs.
		 *                         $data must be an array with keys 'file' (file path relative to 'assets/css'
		 *                         directory), and optionally 'global' (whether the file should immediately be
		 *                         enqueued instead of just being registered) and 'preload_callback' (callback)
		 *                         function determining whether the file should be preloaded for the current request).
		 */
		$css_files = apply_filters( 'cryptozfree_css_files', $css_files );

		$this->css_files = array();
		foreach ( $css_files as $handle => $data ) {
			if ( is_string( $data ) ) {
				$data = array( 'file' => $data );
			}

			if ( empty( $data['file'] ) ) {
				continue;
			}

			$this->css_files[ $handle ] = array_merge(
				array(
					'global'           => false,
					'preload_callback' => null,
					'media'            => 'all',
				),
				$data
			);
		}

		return $this->css_files;
	}

	/**
	 * Add google font to array.
	 *
	 * @param array  $font the font settings.
	 * @param string $full the font use case.
	 */
	public function maybe_add_google_font( $font, $full = null ) {
		if ( ! empty( $full ) && 'headers' === $full ) {
			$new_variant = array();
			if ( isset( $font['variant'] ) && ! empty( $font['variant'] ) && is_array( $font['variant'] ) ) {
				foreach ( array( 'h1_font', 'h2_font', 'h3_font', 'h4_font', 'h5_font', 'h6_font' ) as $option ) {
					$variant = cryptozfree()->sub_option( $option, 'variant' );
					if ( in_array( $variant, $font['variant'], true ) && ! in_array( $variant, $new_variant, true ) ) {
						array_push( $new_variant, $variant );
					}
				}
			}
			if ( empty( $new_variant ) ) {
				$new_variant = $font['variant'];
			}
		}
		if ( ! empty( $full ) && 'body' === $full && 'inherit' === cryptozfree()->sub_option( 'heading_font', 'family' ) ) {
			$new_variant = array( $font['variant'] );
			if ( isset( $font['variant'] ) && ! empty( $font['variant'] ) && ! is_array( $font['variant'] ) ) {
				$current_variant = array( $font['variant'] );
				foreach ( array( 'h1_font', 'h2_font', 'h3_font', 'h4_font', 'h5_font', 'h6_font' ) as $option ) {
					$variant = cryptozfree()->sub_option( $option, 'variant' );
					if ( ! in_array( $variant, $current_variant, true ) && ! in_array( $variant, $new_variant, true ) ) {
						array_push( $new_variant, $variant );
					}
				}
			}
			if ( empty( $new_variant ) ) {
				$new_variant = array( $font['variant'] );
			}
		} else if ( ! empty( $full ) && 'body' === $full && 'inherit' !== cryptozfree()->sub_option( 'heading_font', 'family' ) ) {
			$new_variant = array( $font['variant'], '700' );
		}
				// Check if the font has been added yet.
		if ( ! array_key_exists( $font['family'], self::$google_fonts ) ) {
			if ( ! empty( $full ) && 'headers' === $full ) {
				$add_font = array(
					'fontfamily'   => $font['family'],
					'fontvariants' => ( isset( $new_variant ) && ! empty( $new_variant ) && is_array( $new_variant ) ? $new_variant : array() ),
					'fontsubsets'  => ( isset( $font['subset'] ) && ! empty( $font['subset'] ) ? array( $font['subset'] ) : array() ),
				);
			} else if ( ! empty( $full ) && 'body' === $full && 'inherit' === cryptozfree()->sub_option( 'heading_font', 'family' ) ) {
				$add_font = array(
					'fontfamily'   => $font['family'],
					'fontvariants' => ( isset( $new_variant ) && ! empty( $new_variant ) && is_array( $new_variant ) ? $new_variant : array() ),
					'fontsubsets'  => ( isset( $font['subset'] ) && ! empty( $font['subset'] ) ? array( $font['subset'] ) : array() ),
				);
			} else if ( ! empty( $full ) && 'body' === $full && 'inherit' !== cryptozfree()->sub_option( 'heading_font', 'family' ) ) {
				$add_font = array(
					'fontfamily'   => $font['family'],
					'fontvariants' => ( isset( $new_variant ) && ! empty( $new_variant ) && is_array( $new_variant ) ? $new_variant : array() ),
					'fontsubsets'  => ( isset( $font['subset'] ) && ! empty( $font['subset'] ) ? array( $font['subset'] ) : array() ),
				);
			} else {
				$add_font = array(
					'fontfamily'   => $font['family'],
					'fontvariants' => ( isset( $font['variant'] ) && ! empty( $font['variant'] ) ? array( $font['variant'] ) : array() ),
					'fontsubsets'  => ( isset( $font['subset'] ) && ! empty( $font['subset'] ) ? array( $font['subset'] ) : array() ),
				);
			}
			self::$google_fonts[ $font['family'] ] = $add_font;
		} else {
			if ( ! empty( $full ) ) {
				foreach ( $new_variant as $variant ) {
					if ( ! in_array( $variant, self::$google_fonts[ $font['family'] ]['fontvariants'], true ) ) {
						array_push( self::$google_fonts[ $font['family'] ]['fontvariants'], $variant );
					}
				}
			} else {
				if ( ! in_array( $font['variant'], self::$google_fonts[ $font['family'] ]['fontvariants'], true ) ) {
					array_push( self::$google_fonts[ $font['family'] ]['fontvariants'], $font['variant'] );
				}
			}
		}
	}
	/**
	 * Load the front end Google Fonts
	 */
	public function get_google_fonts_url() {
		$google_fonts = apply_filters( 'cryptozfree_theme_google_fonts_array', self::$google_fonts );
		if ( empty( $google_fonts ) ) {
			return '';
		}
		if ( ! apply_filters( 'cryptozfree_print_google_fonts', true ) ) {
			return '';
		}
		$link    = '';
		$sub_add = array();
		$subsets = cryptozfree()->option( 'google_subsets' );
		foreach ( $google_fonts as $key => $gfont_values ) {
			if ( ! empty( $link ) ) {
				$link .= '%7C'; // Append a new font to the string.
			}
			$link .= $gfont_values['fontfamily'];
			if ( ! empty( $gfont_values['fontvariants'] ) ) {
				$link .= ':';
				$link .= implode( ',', $gfont_values['fontvariants'] );
			}
			if ( ! empty( $gfont_values['fontsubsets'] ) && is_array( $gfont_values['fontsubsets'] ) ) {
				foreach ( $gfont_values['fontsubsets'] as $subkey ) {
					if ( ! empty( $subkey ) && ! isset( $sub_add[ $subkey ] ) ) {
						$sub_add[] = $subkey;
					}
				}
			}
		}
		$args = array(
			'family' => $link,
		);
		if ( ! empty( $subsets ) ) {
			$available = array( 'latin-ext', 'cyrillic', 'cyrillic-ext', 'greek', 'greek-ext', 'vietnamese', 'arabic', 'khmer', 'chinese', 'chinese-simplified', 'tamil', 'bengali', 'devanagari', 'hebrew', 'korean', 'thai', 'telugu' );
			foreach ( $subsets as $key => $enabled ) {
				if ( $enabled && in_array( $key, $available, true ) ) {
					if ( 'chinese' === $key ) {
						$key = 'chinese-traditional';
					}
					if ( ! isset( $sub_add[ $key ] ) ) {
						$sub_add[] = $key;
					}
				}
			}
			if ( $sub_add ) {
				$args['subset'] = implode( ',', $sub_add );
			}
		}
		if ( apply_filters( 'cryptozfree_display_swap_google_fonts', true ) ) {
			$args['display'] = 'swap';
		}
		$font_url = add_query_arg( $args, 'https://fonts.googleapis.com/css' );
		return $font_url;
	}
}
