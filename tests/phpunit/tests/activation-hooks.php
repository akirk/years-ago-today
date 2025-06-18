<?php

class Years_Ago_Today_Hooks_Test extends WP_UnitTestCase {
	protected $plugin_basename;

	public function setUp(): void {
		parent::setUp();

		$this->plugin_basename = ltrim( YEARS_AGO_TODAY_PLUGIN_FILE, '/' );
		require_once YEARS_AGO_TODAY_PLUGIN_FILE;
	}

	public function test_activation_hook_is_registered() {
		$hook = 'activate_' . $this->plugin_basename;
		$priority = has_action( $hook, array( 'c2c_YearsAgoToday', 'activate' ) );

		$this->assertSame( 10, $priority, "Activation hook should be registered at priority 10." );
	}

	public function test_deactivation_hook_is_registered() {
		$hook = 'deactivate_' . $this->plugin_basename;
		$priority = has_action( $hook, array( 'c2c_YearsAgoToday', 'deactivate' ) );

		$this->assertSame( 10, $priority, "Deactivation hook should be registered at priority 10." );
	}

	public function test_activation_callback_performs_setup() {
		do_action( 'activate_' . $this->plugin_basename, false );

		$timestamp = wp_next_scheduled( c2c_YearsAgoToday::$cron_name );
		$this->assertNotFalse( $timestamp, 'Plugin activation should schedule ' . c2c_YearsAgoToday::$cron_name . '.' );
	}

	public function test_deactivation_callback_cleans_up() {
		wp_schedule_event( time() + 600, 'hourly', c2c_YearsAgoToday::$cron_name );
		$this->assertNotFalse( wp_next_scheduled( c2c_YearsAgoToday::$cron_name ) );

		do_action( 'deactivate_' . $this->plugin_basename, false );

		$this->assertFalse(
			wp_next_scheduled( c2c_YearsAgoToday::$cron_name ),
			'Plugin deactivation should clear ' . c2c_YearsAgoToday::$cron_name . '.'
		);
	}

}
