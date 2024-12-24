<?php 
/**
 * cryptozfree Theme Customizer Color
 *
 * @package cryptozfree
 */


  // Elementor Style Overwrite
  $wp_customize->add_setting( 'elementor_style_overwrite',
  array(
      'default' => '0',
      'transport' => 'refresh',
      'sanitize_callback' => 'cryptozfree_switch_sanitization'
  )
 );
 $wp_customize->add_control( new cryptozfree_Toggle_Switch_Custom_control( $wp_customize, 'elementor_style_overwrite',
  array(
      'label' => esc_html__( 'Elementor Style Overwrite', 'cryptozfree' ),
      'section' => 'colors'
  )
 ) );

 
// Primary Color
$wp_customize->add_setting(
    'cryptozfree_primary_color',
    array(
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'sanitize_hex_color' ,
        'default'           => '#1545CB'
    )
);

$wp_customize->add_control(
    new WP_Customize_Color_Control(
        $wp_customize,
        'cryptozfree_primary_color',
        array(
            'label'   => __( 'Primary Color', 'cryptozfree' ),
            'section' => 'colors'
        )
    )
);

// Secondary Color
$wp_customize->add_setting(
    'cryptozfree_secondary_color',
    array(
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'sanitize_hex_color' ,
        'default'           => '#FE79A2'
    )
);

$wp_customize->add_control(
    new WP_Customize_Color_Control(
        $wp_customize,
        'cryptozfree_secondary_color',
        array(
            'label'   => __( 'Secondary Color', 'cryptozfree' ),
            'section' => 'colors'
        )
    )
);


//  Gradient Color

   // First Gradient Color
$wp_customize->add_setting(
    'cryptozfree_gradient_first_color',
    array(
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'sanitize_hex_color' ,
        'default'           => '#A253D8'
    )
);

$wp_customize->add_control(
    new WP_Customize_Color_Control(
        $wp_customize,
        'cryptozfree_gradient_first_color',
        array(
            'label'   => __( 'Gradient First Color', 'cryptozfree' ),
            'section' => 'colors'
        )
    )
);


$wp_customize->add_setting( 'cryptozfree_first_color_location', array(
    'default'    => '0',
    'capability' => 'edit_theme_options',
    'sanitize_callback' => 'sanitize_text_field',
    'transport'  => 'refresh'
) );

$wp_customize->add_control(
    new cryptozfree_Range_Custom_Control(
        $wp_customize,
        'cryptozfree_first_color_location',
        array(
            'label'       => __( '1st Location', 'cryptozfree' ),
            'section'     => 'colors',
            'settings'    => 'cryptozfree_first_color_location',
            'description' => __( 'Measurement is in %.', 'cryptozfree' ),
            'input_attrs' => array(
                'min'  => 0,
                'max'  => 100,
                'step' => 10
            )
        )
    )
);

   // Secondary Gradient Color
   $wp_customize->add_setting(
    'cryptozfree_gradient_second_color',
    array(
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'sanitize_hex_color' ,
        'default'           => '#1545CB'
    )
);

$wp_customize->add_control(
    new WP_Customize_Color_Control(
        $wp_customize,
        'cryptozfree_gradient_second_color',
        array(
            'label'   => __( 'Gradient Second Color', 'cryptozfree' ),
            'section' => 'colors'
        )
    )
);

$wp_customize->add_setting( 'cryptozfree_second_color_location', array(
    'default'    => '100',
    'capability' => 'edit_theme_options',
    'sanitize_callback' => 'sanitize_text_field',
    'transport'  => 'refresh'
) );

$wp_customize->add_control(
    new cryptozfree_Range_Custom_Control(
        $wp_customize,
        'cryptozfree_second_color_location',
        array(
            'label'       => __( '2nd Location', 'cryptozfree' ),
            'section'     => 'colors',
            'settings'    => 'cryptozfree_second_color_location',
            'description' => __( 'Measurement is in %.', 'cryptozfree' ),
            'input_attrs' => array(
                'min'  => 0,
                'max'  => 100,
                'step' => 10
            )
        )
    )
);

// Angle Gradient Color
$wp_customize->add_setting( 'cryptozfree_angle_color', array(
    'default'    => '180',
    'capability' => 'edit_theme_options',
    'sanitize_callback' => 'sanitize_text_field',
    'transport'  => 'refresh'
) );

$wp_customize->add_control(
    new cryptozfree_Range_Custom_Control(
        $wp_customize,
        'cryptozfree_angle_color',
        array(
            'label'       => __( 'Angle', 'cryptozfree' ),
            'section'     => 'colors',
            'settings'    => 'cryptozfree_angle_color',
            'description' => __( 'Measurement is in Deg.', 'cryptozfree' ),
            'input_attrs' => array(
                'min'  => 0,
                'max'  => 360,
                'step' => 10
            )
        )
    )
);

$wp_customize->add_setting( 'cryptozfree_g_opacity_color', array(
    'default'    => '1',
    'capability' => 'edit_theme_options',
    'sanitize_callback' => 'sanitize_text_field',
    'transport'  => 'refresh'
) );

$wp_customize->add_control(
    new cryptozfree_Range_Custom_Control(
        $wp_customize,
        'cryptozfree_g_opacity_color',
        array(
            'label'       => __( 'Opacity', 'cryptozfree' ),
            'section'     => 'colors',
            'settings'    => 'cryptozfree_g_opacity_color',
            'input_attrs' => array(
                'min'  => 0.0,
                'max'  => 1,
                'step' => .1
            )
        )
    )
);
