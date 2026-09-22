<?php

use PHPUnit\Framework\TestCase;

final class QueryBuilderTest extends TestCase {
	protected function setUp(): void {
		reset_mock_state();
	}

	public function test_build_query_args_sanitizes_and_applies_defaults(): void {
		$GLOBALS['mock_state']['post_meta'][10] = array(
			'orderby'    => 'not-valid',
			'order'      => 'up',
			'limit'      => 200,
			'categories' => array( ' Summer Sale ', 'new_arrivals' ),
		);

		$args = clevprca_build_query_args( 10 );

		$this->assertSame( 'date', $args['orderby'] );
		$this->assertSame( 'DESC', $args['order'] );
		$this->assertSame( 48, $args['limit'] );
		$this->assertSame( array( 'summer-sale', 'new_arrivals' ), $args['category'] );
	}

	public function test_manual_products_take_priority(): void {
		$GLOBALS['mock_state']['post_meta'][11] = array(
			'manual_products_enabled' => true,
			'manual_product_ids'      => array( '3', '3', '7' ),
			'orderby'                 => 'rating',
		);

		$args = clevprca_build_query_args( 11 );

		$this->assertSame( array( 3, 7 ), $args['include'] );
		$this->assertSame( 'include', $args['orderby'] );
	}

	public function test_union_strategy_merges_sale_and_featured_ids(): void {
		$GLOBALS['mock_state']['post_meta'][12] = array(
			'on_sale'     => true,
			'on_featured' => true,
		);
		$GLOBALS['mock_state']['product_ids_on_sale'] = array( 1, 2 );
		$GLOBALS['mock_state']['featured_product_ids'] = array( 2, 5 );

		add_filter(
			'cleverspr_carousel/include_strategy',
			static function () {
				return 'union';
			}
		);

		$args = clevprca_build_query_args( 12 );

		$this->assertSame( array( 1, 2, 5 ), $args['include'] );
	}
}
