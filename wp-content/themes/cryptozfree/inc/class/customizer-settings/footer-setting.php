<?php


    // Retrieve Template
    $templates = Elementor\Plugin::$instance->templates_manager->get_source( 'local' )->get_items();

    $options = [
    ];

    $footer_default = null;

    foreach ( $templates as $template ) {
            $options[ $template['template_id'] ] = $template['title'] . ' (' . $template['type'] . ')';

            if($template['title'] == "footer"){
                $footer_default = $template['template_id'];
            }
    }

    //==================== Footer ===========================
	$wp_customize->add_setting( 'footer',
	array(
		'default' => $footer_default,
			'transport' => 'refresh',
			'sanitize_callback' => 'cryptozfree_theme_sanitize_select',
		)
	);


	$wp_customize->add_control( 'footer',
	array(
		'label' => __( 'Footer', 'cryptozfree' ),
		'description' => __( 'Select Footer', 'cryptozfree' ),
		'section' => 'cryptozfree_footer_section',
		'type' => 'select',
		'choices' => $options
	)
	);