<?php
/**
 * Header Builder Options
 *
 * @package cryptozfree
 */

namespace cryptozfree;

use cryptozfree\Theme_Customizer;
use function cryptozfree\cryptozfree;

Theme_Customizer::add_settings(
	array(
		'base_font' => array(
			'control_type' => 'cryptozfree_typography_control',
			'section'      => 'typography_section',
			'label'        => esc_html__( 'Base Font', 'cryptozfree' ),
			'default'      => cryptozfree()->default( 'base_font' ),
			'live_method'     => array(
				array(
					'type'     => 'css_typography',
					'selector' => 'body',
					'property' => 'font',
					'key'      => 'typography',
				),
			),
			'input_attrs'  => array(
				'id'         => 'base_font',
				'canInherit' => false,
			),
		),
		'load_base_italic' => array(
			'control_type' => 'cryptozfree_switch_control',
			'sanitize'     => 'cryptozfree_sanitize_toggle',
			'section'      => 'typography_section',
			'default'      => cryptozfree()->default( 'load_base_italic' ),
			'label'        => esc_html__( 'Load Italics Font Styles', 'cryptozfree' ),
			'context'      => array(
				array(
					'setting' => 'base_font',
					'operator'   => 'load_italic',
					'value'   => 'true',
				),
			),
		),
		'info_heading' => array(
			'control_type' => 'cryptozfree_title_control',
			'section'      => 'typography_section',
			'label'        => esc_html__( 'Headings', 'cryptozfree' ),
			'settings'     => false,
		),
		'heading_font' => array(
			'control_type' => 'cryptozfree_typography_control',
			'section'      => 'typography_section',
			'label'        => esc_html__( 'Heading Font Family', 'cryptozfree' ),
			'default'      => cryptozfree()->default( 'heading_font' ),
			'live_method'     => array(
				array(
					'type'     => 'css_typography',
					'selector' => 'h1,h2,h3,h4,h5,h6',
					'property' => 'font',
					'key'      => 'family',
				),
			),
			'input_attrs'  => array(
				'id'      => 'heading_font',
				'options' => 'family',
			),
		),
		'h1_font' => array(
			'control_type' => 'cryptozfree_typography_control',
			'section'      => 'typography_section',
			'label'        => esc_html__( 'H1 Font', 'cryptozfree' ),
			'default'      => cryptozfree()->default( 'h1_font' ),
			'live_method'     => array(
				array(
					'type'     => 'css_typography',
					'selector' => 'h1',
					'property' => 'font',
					'key'      => 'typography',
				),
			),
			'input_attrs'  => array(
				'id'             => 'h1_font',
				'headingInherit' => true,
			),
		),
		'h2_font' => array(
			'control_type' => 'cryptozfree_typography_control',
			'section'      => 'typography_section',
			'label'        => esc_html__( 'H2 Font', 'cryptozfree' ),
			'default'      => cryptozfree()->default( 'h2_font' ),
			'live_method'     => array(
				array(
					'type'     => 'css_typography',
					'selector' => 'h2',
					'property' => 'font',
					'key'      => 'typography',
				),
			),
			'input_attrs'  => array(
				'id'             => 'h2_font',
				'headingInherit' => true,
			),
		),
		'h3_font' => array(
			'control_type' => 'cryptozfree_typography_control',
			'section'      => 'typography_section',
			'label'        => esc_html__( 'H3 Font', 'cryptozfree' ),
			'default'      => cryptozfree()->default( 'h3_font' ),
			'live_method'     => array(
				array(
					'type'     => 'css_typography',
					'selector' => 'h3',
					'property' => 'font',
					'key'      => 'typography',
				),
			),
			'input_attrs'  => array(
				'id'             => 'h3_font',
				'headingInherit' => true,
			),
		),
		'h4_font' => array(
			'control_type' => 'cryptozfree_typography_control',
			'section'      => 'typography_section',
			'label'        => esc_html__( 'H4 Font', 'cryptozfree' ),
			'default'      => cryptozfree()->default( 'h4_font' ),
			'live_method'     => array(
				array(
					'type'     => 'css_typography',
					'selector' => 'h4',
					'property' => 'font',
					'key'      => 'typography',
				),
			),
			'input_attrs'  => array(
				'id'             => 'h4_font',
				'headingInherit' => true,
			),
		),
		'h5_font' => array(
			'control_type' => 'cryptozfree_typography_control',
			'section'      => 'typography_section',
			'label'        => esc_html__( 'H5 Font', 'cryptozfree' ),
			'default'      => cryptozfree()->default( 'h5_font' ),
			'live_method'     => array(
				array(
					'type'     => 'css_typography',
					'selector' => 'h5',
					'property' => 'font',
					'key'      => 'typography',
				),
			),
			'input_attrs'  => array(
				'id'             => 'h5_font',
				'headingInherit' => true,
			),
		),
		'h6_font' => array(
			'control_type' => 'cryptozfree_typography_control',
			'section'      => 'typography_section',
			'label'        => esc_html__( 'H6 Font', 'cryptozfree' ),
			'default'      => cryptozfree()->default( 'h6_font' ),
			'live_method'     => array(
				array(
					'type'     => 'css_typography',
					'selector' => 'h6',
					'property' => 'font',
					'key'      => 'typography',
				),
			),
			'input_attrs'  => array(
				'id'             => 'h6_font',
				'headingInherit' => true,
			),
		),
		'info_above_title_heading' => array(
			'control_type' => 'cryptozfree_title_control',
			'section'      => 'typography_section',
			'label'        => esc_html__( 'Title Above Content', 'cryptozfree' ),
			'settings'     => false,
		),
		'title_above_font' => array(
			'control_type' => 'cryptozfree_typography_control',
			'section'      => 'typography_section',
			'label'        => esc_html__( 'H1 Title', 'cryptozfree' ),
			'default'      => cryptozfree()->default( 'title_above_font' ),
			'live_method'     => array(
				array(
					'type'     => 'css_typography',
					'selector' => '.entry-hero h1',
					'property' => 'font',
					'key'      => 'typography',
				),
			),
			'input_attrs'  => array(
				'id'             => 'title_above_font',
				'headingInherit' => true,
			),
		),
		'title_above_breadcrumb_font' => array(
			'control_type' => 'cryptozfree_typography_control',
			'section'      => 'typography_section',
			'label'        => esc_html__( 'Breadcrumbs', 'cryptozfree' ),
			'default'      => cryptozfree()->default( 'title_above_breadcrumb_font' ),
			'live_method'     => array(
				array(
					'type'     => 'css_typography',
					'selector' => '.entry-hero .cryptozfree-breadcrumbs',
					'property' => 'font',
					'key'      => 'typography',
				),
			),
			'input_attrs'  => array(
				'id'      => 'title_above_breadcrumb_font',
			),
		),
		'google_subsets' => array(
			'control_type' => 'cryptozfree_check_icon_control',
			'section'      => 'typography_section',
			'sanitize'     => 'cryptozfree_sanitize_google_subsets',
			'priority'     => 20,
			'default'      => array(),
			'label'        => esc_html__( 'Google Font Subsets', 'cryptozfree' ),
			'input_attrs'  => array(
				'options' => array(
					'latin-ext' => array(
						'name' => __( 'Latin Extended', 'cryptozfree' ),
					),
					'cyrillic' => array(
						'name' => __( 'Cyrillic', 'cryptozfree' ),
					),
					'cyrillic-ext' => array(
						'name' => __( 'Cyrillic Extended', 'cryptozfree' ),
					),
					'greek' => array(
						'name' => __( 'Greek', 'cryptozfree' ),
					),
					'greek-ext' => array(
						'name' => __( 'Greek Extended', 'cryptozfree' ),
					),
					'vietnamese' => array(
						'name' => __( 'Vietnamese', 'cryptozfree' ),
					),
					'arabic' => array(
						'name' => __( 'Arabic', 'cryptozfree' ),
					),
					'khmer' => array(
						'name' => __( 'Khmer', 'cryptozfree' ),
					),
					'chinese' => array(
						'name' => __( 'Chinese', 'cryptozfree' ),
					),
					'chinese-simplified' => array(
						'name' => __( 'Chinese Simplified', 'cryptozfree' ),
					),
					'tamil' => array(
						'name' => __( 'Tamil', 'cryptozfree' ),
					),
					'bengali' => array(
						'name' => __( 'Bengali', 'cryptozfree' ),
					),
					'devanagari' => array(
						'name' => __( 'Devanagari', 'cryptozfree' ),
					),
					'hebrew' => array(
						'name' => __( 'Hebrew', 'cryptozfree' ),
					),
					'korean' => array(
						'name' => __( 'Korean', 'cryptozfree' ),
					),
					'thai' => array(
						'name' => __( 'Thai', 'cryptozfree' ),
					),
					'telugu' => array(
						'name' => __( 'Telugu', 'cryptozfree' ),
					),
				),
			),
		),
	)
);
