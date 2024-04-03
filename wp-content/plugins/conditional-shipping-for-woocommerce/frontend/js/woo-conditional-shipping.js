jQuery(document).ready(function($) {
	$( document.body ).on( 'updated_checkout', function() {
		if ( typeof conditionalShippingNotices !== 'undefined' ) {
			var shippingRow = $( 'tr.woocommerce-shipping-totals td:eq(0)' );

			if ( shippingRow.length > 0 ) {
				$.each( conditionalShippingNotices, function( index, notice ) {
					shippingRow.append( notice );
				} );
			}
		}
	} );
});
