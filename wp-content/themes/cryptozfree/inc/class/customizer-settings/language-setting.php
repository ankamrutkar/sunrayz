<?php

    $wp_customize->add_setting( 
        'cryptozfree_language_readmore_setting', 
        array(
            'sanitize_callback' => 'wp_filter_nohtml_kses',
            'default'           => esc_html__( '...Read More', 'cryptozfree' ),
        )
    );
        
    $wp_customize->add_control( 
        'cryptozfree_language_readmore_setting', 
        array(
            'label' => esc_html__( 'Read More', 'cryptozfree' ),
            'section' => 'cryptozfree_language_section',
            'type' => 'text'
        )
    );     

    $wp_customize->add_setting( 
        'cryptozfree_language_comment_setting', 
        array(
            'sanitize_callback' => 'wp_filter_nohtml_kses',
            'default'           => esc_html__( 'comment', 'cryptozfree' ),
        )
    );
        
    $wp_customize->add_control( 
        'cryptozfree_language_comment_setting', 
        array(
            'label' => esc_html__( 'Comment', 'cryptozfree' ),
            'section' => 'cryptozfree_language_section',
            'type' => 'text'
        )
    );   
    
    $wp_customize->add_setting( 
        'cryptozfree_language_comments_setting', 
        array(
            'sanitize_callback' => 'wp_filter_nohtml_kses',
            'default'           => esc_html__( 'comments', 'cryptozfree' ),
        )
    );
        
    $wp_customize->add_control( 
        'cryptozfree_language_comments_setting', 
        array(
            'label' => esc_html__( 'Comments', 'cryptozfree' ),
            'section' => 'cryptozfree_language_section',
            'type' => 'text'
        )
    );    

