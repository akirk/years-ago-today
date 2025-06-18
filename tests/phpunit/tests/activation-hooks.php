<?php

class Years_Ago_Today_Hooks_Test extends WP_UnitTestCase {
	protected $plugin_basename;

	/** @var string Memoize the server timezone. */
	protected $orig_tz;

	public function setUp(): void {
		parent::setUp();

		$this->plugin_basename = ltrim( YEARS_AGO_TODAY_PLUGIN_FILE, '/' );
		require_once YEARS_AGO_TODAY_PLUGIN_FILE;

		// Simulate a host whose PHP default TZ is UTC while the site runs in America/Denver.
		$this->orig_tz = date_default_timezone_get();
		date_default_timezone_set( 'UTC' );
		update_option( 'timezone_string', 'America/Denver' );

		// Keep the slate clean for every test.
		wp_clear_scheduled_hook( c2c_YearsAgoToday::$cron_name );
	}

	public function tearDown(): void {
		wp_clear_scheduled_hook( c2c_YearsAgoToday::$cron_name );
		date_default_timezone_set( $this->orig_tz );
		remove_all_filters( 'c2c_years_ago_today-email_cron_time' );

		parent::tearDown();
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

	/**
	 * The cron created by ::activate() must fire at 09:00 in the **site** TZ,
	 * regardless of the server’s default timezone.
	 */
	public function test_cron_is_scheduled_for_9am_site_time() {
		// Force the preferred send time so the test is deterministic.
		add_filter(
			'c2c_years_ago_today-email_cron_time',
			static fn () => '9:00 am'
		);

		c2c_YearsAgoToday::activate();

		$timestamp = wp_next_scheduled( c2c_YearsAgoToday::$cron_name );
		$this->assertNotFalse( $timestamp, 'Cron job was not scheduled' );

		$site_tz = wp_timezone(); // America/Denver
		$dt      = ( new DateTimeImmutable( "@{$timestamp}" ) )->setTimezone( $site_tz );

		// Should be exactly 09:00 in the site timezone.
		$this->assertSame( '09:00', $dt->format( 'H:i' ), 'Cron not set for 9 AM site time' );

		// And it should be in the future, never the past.
		$this->assertGreaterThan( time(), $timestamp, 'Cron scheduled in the past' );
	}

}
