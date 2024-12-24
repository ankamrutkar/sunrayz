<?php
/**
 * Displays the footer widget area.
 *
 * @package WordPress
 * @subpackage natura
 */
?>


<div class="container">
	<div class="row">
		<div class="col-lg-12 col-md-12 col-sm-12 order-lg-0 order-0">

			<div class="footer-credits">

						<p class="footer-copyright">&copy;
							<?php
							echo date_i18n(
								/* translators: Copyright date format, see https://www.php.net/date */
								_x( 'Y', 'copyright date format', 'natura' )
							);
							?>
							<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
						</p> <!-- .footer-copyright -->

						

					<p class="powered-by-wordpress">
						<a href="<?php echo esc_url( __( 'https://wordpress.org/', 'natura' ) ); ?>">
							<?php _e( 'Powered by WordPress', 'natura' ); ?>
						</a>
					</p> <!-- .powered-by-wordpress -->

					</div> <!-- .footer-credits -->
				</div>
			</div>
</div>	