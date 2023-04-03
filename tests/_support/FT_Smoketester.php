<?php


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
	 * @since TBD
	 *
	 * @param string $level The log level to look for, e.g. 'debug', 'warning', 'error'.
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
	 * @since TBD
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
}
