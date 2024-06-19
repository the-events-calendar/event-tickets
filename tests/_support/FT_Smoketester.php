<?php

use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;

/**
 * Inherited Methods
 * @method void wantToTest( $text )
 * @method void wantTo( $text )
 * @method void execute( $callable )
 * @method void expectTo( $prediction )
 * @method void expect( $prediction )
 * @method void amGoingTo( $argumentation )
 * @method void am( $role )
 * @method void lookForwardTo( $achieveValue )
 * @method void comment( $description )
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class FT_Smoketester extends \Codeception\Actor {
	use _generated\FT_SmoketesterActions;

	public const TEC_DEBUG_DATA = '#tec-debug-data';

	/**
	 * Asserts that the debug data contains the specified log.
	 *
	 * @since 5.9.1
	 *
	 * @param string $level   The log level to look for, e.g. 'debug', 'warning', 'error'.
	 * @param string $message The log message to look for.
	 *
	 * @return void
	 */
	public function assert_log( string $level = 'debug', string $message ): void {
		$logs = $this->grab_debug_data( 'logs' );

		$this->assertArrayHasKey( $level, $logs, "No $level logs found in debug data" );

		foreach ( $logs[ $level ] as $log ) {
			if ( $log['message'] === $message ) {
				return;
			}
		}

		$this->fail( "Could not find $level log message: $message" );
	}

	/**
	 * Reads the debug data from the page and returns it.
	 *
	 * @since 5.9.1
	 *
	 * @param string $key The key to look for in the debug data.
	 *
	 * @return mixed
	 */
	protected function grab_debug_data( string $key ) {
		$debug_data = json_decode( $this->grabTextFrom( self::TEC_DEBUG_DATA ), true );
		codecept_debug( $debug_data );
		$this->assertArrayHasKey( $key, $debug_data, "No '$key' key found in debug data" );

		return $debug_data[ $key ];
	}

	/**
	 * Updates the option to allow, or forbid, Series to have tickets.
	 *
	 * @since 5.9.1
	 *
	 * @param bool $series_are_ticketable Whether Series should be ticketable or not.
	 *
	 * @return void
	 */
	public function have_ticketable_series_in_database( bool $series_are_ticketable = true ): void {
		$enabled_post_types = tribe_get_option( 'ticket-enabled-post-types' );
		if ( $series_are_ticketable ) { // Ensure Tickets can be added to Series.
			$enabled_post_types[] = Series_Post_Type::POSTTYPE;
			$enabled_post_types = array_unique( $enabled_post_types );
		} else {
			$enabled_post_types = array_diff( $enabled_post_types, [ Series_Post_Type::POSTTYPE ] );
		}
		tribe_update_option( 'ticket-enabled-post-types', $enabled_post_types );
	}

	/**
	 * Inserts a post of type `tribe_event_series` in the database.
	 *
	 * @since 5.9.1
	 *
	 * @param array<string,mixed> $overrides The post overrides.
	 *
	 * @return int The post ID of the inserted Series post.
	 */
	public function have_series_in_database( array $overrides = [] ): int {
		$data = $overrides;
		$data['post_type'] = Series_Post_Type::POSTTYPE;

		return $this->havePostInDatabase( $data );
	}

	/**
	 * Asserts that a key is present in the debug data and that its value matches the expected one.
	 *
	 * @since 5.9.1
	 *
	 * @param string $key   The key to look for in the debug data.
	 * @param mixed  $value The expected value.
	 */
	public function assert_data_key( string $key, $value = null ): void {
		$expected = $this->grab_debug_data( $key );
		$this->assertEquals(
			$value,
			$expected,
			'Failed asserting that the debug data key ' . $key . ' has the expected value.'
		);
	}
}
