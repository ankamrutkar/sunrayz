<?php
    /**
    * Toggle Switch Custom Control
    
    * @package cryptozfree
    * @since 1.0.0
    */
    if ( class_exists( 'WP_Customize_Control' ) ) :

        if ( ! class_exists( 'cryptozfree_Toggle_Switch_Custom_control' ) ) :
	class cryptozfree_Toggle_Switch_Custom_control extends WP_Customize_Control {
		/**
		 * The type of control being rendered
		 */
		public $type = 'toggle_switch';
	
		/**
		 * Render the control in the customizer
		 */
		public function render_content(){
		?>
			<div class="toggle-switch-control">
				<div class="toggle-switch">
					<input type="checkbox" id="<?php echo esc_attr($this->id); ?>" name="<?php echo esc_attr($this->id); ?>" class="toggle-switch-checkbox" value="<?php echo esc_attr( $this->value() ); ?>" <?php $this->link(); checked( $this->value() ); ?>>
					<label class="toggle-switch-label" for="<?php echo esc_attr( $this->id ); ?>">
						<span class="toggle-switch-inner"></span>
						<span class="toggle-switch-switch"></span>
					</label>
				</div>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
				<?php if( !empty( $this->description ) ) { ?>
					<span class="customize-control-description"><?php echo wp_kses_post( $this->description ); ?></span>
				<?php } ?>
			</div>
		<?php
		}
    }
    
endif;
endif;