<?php

defined( 'ABSPATH' ) or die();

/**
 * @group ms-required
 */
class Years_Ago_Today_Multisite_Test extends WP_UnitTestCase {

	protected static $second_blog_id;

	public static function setUpBeforeClass(): void {
		if ( ! is_multisite() ) {
			return;
		}

		// Create a second blog in the network.
		self::$second_blog_id = wpmu_create_blog(
			'test.example.com',
			'/testblog/',
			'Test Blog',
			[
				'public'  => 1,
			],
			get_current_user_id()
		);
	}

	public static function tearDownAfterClass(): void {
		if ( ! is_multisite() ) {
			return;
		}

		wp_delete_site( self::$second_blog_id );
	}

	public function setUp(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'This test is only relevant when WP is loaded as multisite.' );
		}

		parent::setUp();

		global $wp_object_cache;
		$wp_object_cache->global_groups = array();
	}

	public function test_adds_cache_group_in_multisite() {
		global $wp_object_cache;

		$this->assertArrayNotHasKey( c2c_YearsAgoToday::$cache_group, $wp_object_cache->global_groups );

		$this->assertCount( 2, get_sites() );

		c2c_YearsAgoToday::init();

		$this->assertArrayHasKey( c2c_YearsAgoToday::$cache_group, $wp_object_cache->global_groups );
	}

	public function test_get_post_ids_cache_key__format() {
		switch_to_blog( self::$second_blog_id );
		$date = wp_date( 'Ymd' );
		$this->assertMatchesRegularExpression( '/^yat_' . self::$second_blog_id . '_20[0-9]{6}$/', c2c_YearsAgoToday::get_post_ids_cache_key() );
		$this->assertEquals( 'yat_2_' . $date, c2c_YearsAgoToday::get_post_ids_cache_key() );
	}

}
