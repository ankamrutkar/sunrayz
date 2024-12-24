<?php 

$wp_customize->add_setting( 'cryptozfree_site_width', array(
    'default'    => '1140',
    'capability' => 'edit_theme_options',
    'sanitize_callback' => 'sanitize_text_field',
    'transport'  => 'refresh'
) );

$wp_customize->add_control(
    new cryptozfree_Range_Custom_Control(
        $wp_customize,
        'cryptozfree_site_width',
        array(
            'label'       => __( 'Site Width', 'cryptozfree' ),
            'section'     => 'cryptozfree_layout_section',
            'settings'    => 'cryptozfree_site_width',
            'description' => __( 'Measurement is in pixel. Default Width: 1140px', 'cryptozfree' ),
            'input_attrs' => array(
                'min'  => 1,
                'max'  => 3000,
                'step' => 1
            )
        )
    )
);

    $wp_customize->add_setting( 'cryptozfree_sidebar_position',
        array(
            'default'    => 'sidebar_right',
            'transport'  => 'refresh',
            'sanitize_callback' => 'sanitize_text_field',
            'capability' => 'edit_theme_options'
        )
    );

    $wp_customize->add_control( new cryptozfree_Radio_Img_Control( $wp_customize, 'cryptozfree_sidebar_position',
        array(
            'label'   => __( 'Select Sidebar layout', 'cryptozfree' ),
            'section' => 'cryptozfree_layout_section',
            'choices' => array(
                'sidebar_left'  => array(
                    'image'     => get_template_directory_uri() . '/assets/images/sidebar-left.svg',
                    'name'      => __( 'Left Sidebar', 'cryptozfree' )
                ),
                'sidebar_right' => array(
                    'image'     => get_template_directory_uri() . '/assets/images/sidebar-right.svg',
                    'name'      => __( 'Right Sidebar', 'cryptozfree' )
                ),
                'sidebar_none' => array(
                    'image'    => get_template_directory_uri() . '/assets/images/sidebar-none.svg',
                    'name'     => __( 'Sidebar None', 'cryptozfree' )
                )
            )
        )
    ) );