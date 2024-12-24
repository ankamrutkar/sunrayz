<?php 


    // Setting  breadcrumb Enable.
    $wp_customize->add_setting('enable_site_page_title',
    array(
      'default'           => '0',
      'capability'        => 'edit_theme_options',
      'transport' => 'refresh',
      'sanitize_callback' => 'cryptozfree_switch_sanitization',
    )
    );

    

    $wp_customize->add_control( new cryptozfree_Toggle_Switch_Custom_control( $wp_customize,'enable_site_page_title',
    array(
      'label'    => esc_html__('Enable Page Title', 'cryptozfree'),
      'section'  => 'page_title_section',
      'priority' => 21,
    )
    ) );

    // Setting  breadcrumb Enable for Home Page.
    $wp_customize->add_setting('enable_site_page_title_for_home_page',
    array(
      'default'           => 'all',
      'capability'        => 'edit_theme_options',
      'transport'         => 'postMessage',
      'sanitize_callback' => 'cryptozfree_sanitize_radio_btn',
    )
    );

    $wp_customize->add_control('enable_site_page_title_for_home_page',
    array(
      'label'    => esc_html__('Display Breadcrumb', 'cryptozfree'),
      'section'  => 'page_title_section',
      'type'     => 'checkbox',
      'priority' => 22,
      'choices'     => [
        'all'   => esc_html__( 'All Pages', 'cryptozfree' ),
      ],
    )
    );


    //Upload Option for breadcrumb image
    $wp_customize->add_setting('cryptozfree_breadcrumb_image_setting', array(
    'default' => '',
    'type' => 'theme_mod',
    'sanitize_callback' => 'sanitize_url',
    ));

    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'cryptozfree_breadcrumb_image_setting', array(
    'label' => esc_html__( 'Upload Background Image', 'cryptozfree' ),
    'section' => 'page_title_section',
    'settings' => 'cryptozfree_breadcrumb_image_setting',
    'priority' => 23,
    ))
    );

    
    //add setting to your section
    $wp_customize->add_setting( 'breadcrumb_background_color', 
    array(
      'default' => '#f8f8f8',
      'sanitize_callback' => 'sanitize_hex_color' //validates 3 or 6 digit HTML hex color code
    )
    );

    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'breadcrumb_background_color', 
    array(              
      'label'      => __( 'Background Color: ', 'cryptozfree' ),
      'section'    => 'page_title_section',
      'settings' => 'breadcrumb_background_color',
      'priority' => 27,
    ))
    );   
