<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

\Elementor\Plugin::$instance->frontend->add_body_class( 'elementor-template-canvas' );

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<?php if ( ! current_theme_supports( 'title-tag' ) ) : ?>
	<?php endif; ?>
	<?php wp_head(); ?>
	<?php
	// Keep the following line after `wp_head()` call, to ensure it's not overridden by another templates.
	?>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
</head>
<body <?php body_class(); ?>>
	<?php
	/**
	 * Before canvas page template content.
	 *
	 * Fires before the content of Elementor canvas page template.
	 *
	 * @since 1.0.0
	 */
	do_action( 'elementor/page_templates/canvas/before_content' );

	\Elementor\Plugin::$instance->modules_manager->get_modules( 'page-templates' )->print_content();

	/**
	 * After canvas page template content.
	 *
	 * Fires after the content of Elementor canvas page template.
	 *
	 * @since 1.0.0
	 */
	do_action( 'elementor/page_templates/canvas/after_content' );

	wp_footer();
	?>
	</body>
</html>
