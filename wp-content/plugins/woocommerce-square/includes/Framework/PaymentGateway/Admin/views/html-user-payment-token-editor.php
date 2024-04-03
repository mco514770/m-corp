<tr>

	<th><?php echo esc_html( $title ); ?></th>

	<td class="forminp">

		<table class="sv_wc_payment_gateway_token_editor widefat" data-gateway-id="<?php echo esc_attr( $id ); ?>">

			<thead>
				<tr>

					<?php // Display a column for each token field
					foreach ( $columns as $column_id => $column_title ) : ?>
						<th class="token-<?php echo esc_attr( $column_id ); ?>"><?php echo esc_html( $column_title ); ?></th>
					<?php endforeach; ?>

				</tr>
			</thead>

			<tbody class="tokens">

				<?php
				/** Fire inside the payment gateway token editor.
				 *
				 * @since 4.3.0
				 * @param int $user_id the current user ID
				 */
				do_action( 'wc_payment_gateway_' . $id . '_token_editor_tokens', $user_id ); ?>

			</tbody>

			<tbody class="meta">
				<tr class="no-tokens">
					<td colspan="<?php echo count( $columns ); ?>"><?php esc_html_e( 'No saved payment tokens', 'woocommerce-square' ); ?></td>
				</tr>
			</tbody>

			<?php // Editor actions
			if ( ! empty( $actions ) ) : ?>

				<tfoot>
					<tr>
						<th class="actions" colspan="<?php echo count( $columns ); ?>">

							<?php foreach ( $actions as $action => $label ) : ?>

									<?php $button_class = 'save' === $action ? 'button-primary' : 'button'; ?>

									<button class="sv-wc-payment-gateway-token-editor-action-button <?php echo sanitize_html_class( $button_class ); ?>" data-action="<?php echo esc_attr( $action ); ?>" data-user-id="<?php echo esc_attr( $user_id ); ?>">
										<?php echo esc_attr( $label ); ?>
									</button>

							<?php endforeach; ?>

						</th>
					</tr>
				</tfoot>

			<?php endif; ?>

		</table>

	</td>

</tr>
