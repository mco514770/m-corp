<div id="wc_payment_gateway_<?php echo esc_attr( $plugin_id ); ?>_user_settings" class="sv_wc_payment_gateway_user_settings woocommerce">

	<h3><?php echo esc_html( $section_title ); ?></h3>

	<?php if ( ! empty( $section_description ) ) : ?>
		<p><?php echo wp_kses_post( $section_description ); ?></p>
	<?php endif; ?>

	<table class="form-table">

		<tbody>

			<?php
			/** Fire inside the payment gateway user settings section.
			 *
			 * @since 4.3.0
			 * @param \WP_User $user the current user object
			 */
			do_action( 'wc_payment_gateway_' . $plugin_id . '_user_profile', $user ); ?>

		</tbody>

	</table>

</div>
