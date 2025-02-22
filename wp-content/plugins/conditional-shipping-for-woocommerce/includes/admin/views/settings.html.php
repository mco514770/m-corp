<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2 class="woo-conditional-shipping-heading">
	<?php _e( 'Conditions', 'woo-conditional-shipping' ); ?>
	<a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=shipping&section=woo_conditional_shipping&ruleset_id=new' ); ?>" class="page-title-action"><?php esc_html_e( 'Add ruleset', 'woo-conditional-shipping' ); ?></a>
</h2>

<p>
  <?php _e( 'Conditions are used to modify shipping method availability. Each ruleset contains conditions and actions which are applied to shipping method(s).', 'woo-conditional-shipping' ); ?>
</p>
<table class="woo-conditional-shipping-rulesets widefat">
	<thead>
		<tr>
			<th class="woo-conditional-shipping-ruleset-status"></th>
			<th class="woo-conditional-shipping-ruleset-name"><?php esc_html_e( 'Ruleset', 'woo-conditional-shipping' ); ?></th>
		</tr>
	</thead>
	<tbody class="woo-conditional-shipping-ruleset-rows">
		<?php foreach ( $rulesets as $ruleset ) { ?>
			<tr>
				<td class="woo-conditional-shipping-ruleset-status">
					<?php $class = $ruleset->get_enabled() ? 'enabled' : 'disabled'; ?>
					<span class="woocommerce-input-toggle woocommerce-input-toggle--<?php echo $class; ?>" data-id="<?php echo $ruleset->get_id(); ?>"></span>
				</td>
				<td class="woo-conditional-shipping-ruleset-name">
					<a href="<?php echo $ruleset->get_admin_edit_url(); ?>">
						<?php echo $ruleset->get_title(); ?>
					</a>
					<div class="row-actions">
						<a href="<?php echo $ruleset->get_admin_edit_url(); ?>"><?php _e( 'Edit', 'woo-conditional-shipping' ); ?></a> | <a href="<?php echo $ruleset->get_admin_delete_url(); ?>" class="woo-conditional-shipping-ruleset-delete"><?php _e( 'Delete', 'woo-conditional-shipping' ); ?></a>
					</div>
				</td>
			</tr>
		<?php } ?>
		<?php if ( empty( $rulesets ) ) { ?>
			<tr>
				<td colspan="2" class="woo-conditional-shipping-ruleset-name">
					<?php _e( 'No rulesets defined yet.', 'woo-conditional-shipping' ); ?>
				</td>
			</tr>
		<?php } ?>
  </tbody>
</table>

<?php if ( ! empty( $health['enables'] ) || ! empty( $health['disables'] ) ) { ?>
	<div class="woo-conditional-shipping-health-check">
		<h3><?php _e( 'Health check', 'woo-conditional-shipping' ); ?></h3>

		<?php foreach ( $health['enables'] as $instance_id => $ruleset_ids ) { ?>
			<div class="issue-container">
				<div class="title">
					<?php printf(
						__( '<code>Enable shipping methods - %1$s</code> in multiple rulesets', 'woo-conditional-shipping' ),
						woo_conditional_shipping_get_method_title( $instance_id )
					); ?>

					<span class="toggle-indicator"></span>
				</div>

				<div class="details">
					<div class="issue">
						<?php printf( __( 'You have <code>Enable shipping methods - %1$s</code> in multiple rulesets (%2$s). <code>Enable shipping methods</code> will disable the methods if conditions do not pass. It can cause unexpected behaviour when used in multiple rulesets for the same shipping method (<code>%1$s</code>).', 'woo-conditional-shipping' ), woo_conditional_shipping_get_method_title( $instance_id ), woo_conditional_shipping_format_ruleset_ids( $ruleset_ids ) ); ?>
					</div>

					<div class="fix">
						<div><strong><?php _e( 'How to fix', 'woo-conditional-shipping' ); ?></strong></div>
						<div>
							<ul>
								<li><?php _e( 'Check if you can use <code>Disable shipping methods</code> instead. It\'s usually easier to work with.', 'woo-conditional-shipping' ); ?></li>
								<li><?php printf( __( 'Remove <code>Enable shipping methods - %s</code> from all but one ruleset.', 'woo-conditional-shipping' ), woo_conditional_shipping_get_method_title( $instance_id ) ); ?></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		<?php } ?>

		<?php foreach ( $health['disables'] as $data ) { ?>
			<div class="issue-container">
				<div class="title">
					<?php printf(
						__( '<code>%s</code> disabled in the shipping zone', 'woo-conditional-shipping' ),
						woo_conditional_shipping_get_method_title( $data['instance_id'] )
					); ?>

					<span class="toggle-indicator"></span>
				</div>

				<div class="details">
					<div class="issue">
						<?php printf(
							__( 'You have <code>%1$s - %2$s</code> in %3$s but <code>%2$s</code> is disabled in <a href="%4$s" target="_blank">the shipping zone</a>. Conditional Shipping can only process shipping methods which are enabled.', 'woo-conditional-shipping' ),
							woo_conditional_shipping_action_title( $data['action']['type'] ),
							woo_conditional_shipping_get_method_title( $data['instance_id'] ),
							woo_conditional_shipping_format_ruleset_ids( array( $data['ruleset']->get_id() ) ),
							woo_conditional_shipping_get_zone_url( $data['zone_id'] )
						); ?>
					</div>

					<div class="fix">
						<div><strong><?php _e( 'How to fix', 'woo-conditional-shipping' ); ?></strong></div>
						<div>
							<ul>
								<li>
									<?php printf( __( 'Enable <code>%s</code> in <a href="%s" target="_blank">the shipping zone</a>' ),
										woo_conditional_shipping_get_method_title( $data['instance_id'] ),
										woo_conditional_shipping_get_zone_url( $data['zone_id'] )
									); ?>
								</li>
								<li>
									<?php printf(
										__( 'Remove <code>%1$s - %2$s</code> from %3$s.', 'woo-conditional-shipping' ),
										woo_conditional_shipping_action_title( $data['action']['type'] ),
										woo_conditional_shipping_get_method_title( $data['instance_id'] ),
										woo_conditional_shipping_format_ruleset_ids( array( $data['ruleset']->get_id() ) )
									); ?>
								</li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		<?php } ?>
	</div>
<?php } ?>
