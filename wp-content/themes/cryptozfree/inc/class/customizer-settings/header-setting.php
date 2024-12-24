<?php 

    //==================== Header ===========================

    // Retrieve Template
    $templates = Elementor\Plugin::$instance->templates_manager->get_source( 'local' )->get_items();

    $options = [
    ];

    $option_header = [];
    $header_default = null;

    foreach ( $templates as $template ) {
            $options[ $template['template_id'] ] = $template['title'] . ' (' . $template['type'] . ')';

            if($template['title'] == "header"){
                $header_default = $template['template_id'];	
            }
    }

    $wp_customize->add_setting( 'header',
    array(
    'default' => $header_default,
    'transport' => 'refresh',
    'sanitize_callback' => 'cryptozfree_theme_sanitize_select',
    )
    );

    $wp_customize->add_control( 'header',
    array(
    'label' => __( 'Header', 'cryptozfree' ),
    'description' => __( 'Select Header', 'cryptozfree' ),
    'section' => 'cryptozfree_header_section',
    'type' => 'select',
    'choices' => $options
    )
    );
