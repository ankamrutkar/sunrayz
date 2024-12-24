<?php
/**
 * The Radio Icon customize control extends the WP_Customize_Control class.
 *
 * @package customizer-controls
 */

if ( ! class_exists( 'WP_Customize_Control' ) ) {
	return;
}


/**
 * Class cryptozfree_Control_Import_Export
 *
 * @access public
 */
class cryptozfree_Control_Import_Export extends WP_Customize_Control {
	/**
	 * Control type
	 *
	 * @var string
	 */
	public $type = 'cryptozfree_import_export_control';
	/**
	 * Empty Render Function to prevent errors.
	 */
	public function render_content() {
		?>
			<span class="customize-control-title">
				<?php esc_html_e( 'Export', 'cryptozfree' ); ?>
			</span>
			<span class="description customize-control-description">
				<?php esc_html_e( 'Click the button below to export the customization settings for this theme.', 'cryptozfree' ); ?>
			</span>
			<input type="button" class="button cryptozfree-theme-export cryptozfree-theme-button" name="cryptozfree-theme-export-button" value="<?php esc_attr_e( 'Export', 'cryptozfree' ); ?>" />

			<hr class="kt-theme-hr" />

			<span class="customize-control-title">
				<?php esc_html_e( 'Import', 'cryptozfree' ); ?>
			</span>
			<span class="description customize-control-description">
				<?php esc_html_e( 'Upload a file to import customization settings for this theme.', 'cryptozfree' ); ?>
			</span>
			<div class="cryptozfree-theme-import-controls">
				<input type="file" name="cryptozfree-theme-import-file" class="cryptozfree-theme-import-file" />
				<?php wp_nonce_field( 'cryptozfree-theme-importing', 'cryptozfree-theme-import' ); ?>
			</div>
			<div class="cryptozfree-theme-uploading"><?php esc_html_e( 'Uploading...', 'cryptozfree' ); ?></div>
			<input type="button" class="button cryptozfree-theme-import cryptozfree-theme-button" name="cryptozfree-theme-import-button" value="<?php esc_attr_e( 'Import', 'cryptozfree' ); ?>" />

			<hr class="kt-theme-hr" />
			<span class="customize-control-title">
				<?php esc_html_e( 'Reset', 'cryptozfree' ); ?>
			</span>
			<span class="description customize-control-description">
				<?php esc_html_e( 'Click the button to reset all theme settings.', 'cryptozfree' ); ?>
			</span>
			<input type="button" class="components-button is-destructive cryptozfree-theme-reset cryptozfree-theme-button" name="cryptozfree-theme-reset-button" value="<?php esc_attr_e( 'Reset', 'cryptozfree' ); ?>" />
			<?php
	}
}