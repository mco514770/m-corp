<?php
/**
 * Mix and Match Item Thumbnail
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/mnm/mnm-product-thumbnail.php.
 *
 * HOWEVER, on occasion WooCommerce Mix and Match will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @author  Kathy Darling
 * @package WooCommerce Mix and Match/Templates
 * @since   1.0.0
 * @version 1.3.3
 */
if ( ! defined( 'ABSPATH' ) ){
	exit; // Exit if accessed directly
}
?>
<div class="mnm_image">
	<?php				
	/**
	 * Child item thumbnail size.
	 *
	 * @param $size
	 */
	$image_size = apply_filters( 'woocommerce_mnm_product_thumbnail_size', WC_MNM_Core_Compatibility::is_wc_version_gte( '3.3' ) ? 'woocommerce_thumbnail' : 'shop_thumbnail' );
	?>
	<?php echo $mnm_item->get_image( $image_size ); ?>
</div>
