<?php 


    // Setting  preloader Enable.
    $wp_customize->add_setting('enable_site_preloader',
    array(
      'default'           => '0',
      'capability'        => 'edit_theme_options',
      'transport' => 'refresh',
      'sanitize_callback' => 'cryptozfree_switch_sanitization',
    )
    );

    $description = '<span>' . __( 'Download loading image here:', 'cryptozfree' ) .'<a target="_blank" style="color:#1545cb" href="https://loading.io/"><strong>'.__( ' loading.oi.','cryptozfree' ) . '</strong></a></span>';

    $wp_customize->add_control( new cryptozfree_Toggle_Switch_Custom_control( $wp_customize,'enable_site_preloader',
    array(
      'label'    => esc_html__('Enable preloader', 'cryptozfree'),
      'description' => $description,
      'section'  => 'preloader_section',
      'priority' => 10,
    )
    ) );

    // Setting  preloader Enable for Home Page.
    $wp_customize->add_setting('enable_site_preloader_for_home_page',
    array(
      'default'           => 'all',
      'capability'        => 'edit_theme_options',
      'transport'         => 'postMessage',
      'sanitize_callback' => 'cryptozfree_sanitize_radio_btn',
    )
    );

    $wp_customize->add_control('enable_site_preloader_for_home_page',
    array(
      'label'    => esc_html__('Select Option', 'cryptozfree'),
      'section'  => 'preloader_section',
      'type'     => 'radio',
      'priority' => 11,
      'choices'     => [
        'all'   => esc_html__( 'All Pages', 'cryptozfree' ),
        'home' => esc_html__( 'Only Home Page', 'cryptozfree' ),
      ],
    )
    );

    //Upload Option for preloader image
    $wp_customize->add_setting('cryptozfree_preloader_image_setting', array(
    'default' => '',
    'type' => 'theme_mod',
    'sanitize_callback' => 'sanitize_url',
    ));

    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'cryptozfree_preloader_image_setting', array(
    'label' => esc_html__( 'Upload Preloader Image', 'cryptozfree' ),
    'section' => 'preloader_section',
    'settings' => 'cryptozfree_preloader_image_setting',
    'priority' => 12,
    ))
    );

        
    //add preloader image size
    $wp_customize->add_setting( 'cryptozfree_slug_customizer_number', 
    array(
      'default'     => 100,
      'transport' => 'refresh',
      'sanitize_callback' => 'sanitize_text_field',
      'capability' => 'edit_theme_options',
    )
    );
    
    $wp_customize->add_control( new cryptozfree_Range_Custom_Control( $wp_customize,'cryptozfree_slug_customizer_number', 
    array(
      'label' => esc_html__( 'Add Preloader Size in: %', 'cryptozfree' ),
      'description' => __( 'Please Add Valid Number', 'cryptozfree' ),
      'section' => 'preloader_section',
      'settings' => 'cryptozfree_slug_customizer_number',
      'priority' => 15,
      'input_attrs' => array(
        'min'  => 10,
        'max'  => 100,
        'step' => 1
    )
    )
    ) );   

    //add setting to your section
    $wp_customize->add_setting( 'preloader_background_color', 
    array(
      'default' => '#f8f8f8',
      'sanitize_callback' => 'sanitize_hex_color' //validates 3 or 6 digit HTML hex color code
    )
    );

    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'preloader_background_color', 
    array(              
      'label'      => __( 'Preloader Background Color: ', 'cryptozfree' ),
      'section'    => 'preloader_section',
      'settings' => 'preloader_background_color',
      'priority' => 20,
    ))
    );   

