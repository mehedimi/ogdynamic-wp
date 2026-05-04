<?php
/**
 * WooCommerce field helpers placeholder for v1.
 *
 * @package OGD
 */

namespace OGD;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WooCommerce {
	public static function product_fields(): array {
		return array(
			'product_title',
			'product_short_description',
			'product_image',
			'product_gallery_image',
			'product_price',
			'regular_price',
			'sale_price',
			'currency',
			'sku',
			'product_category',
			'product_tags',
			'stock_status',
			'rating',
			'review_count',
			'product_url',
		);
	}
}
