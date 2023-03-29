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

	/**
	 * Define custom actions here
	 */
	public function assert_log( string $level = 'debug', string $message ): void {
		$debug_data = json_decode( $this->grabTextFrom( '#tec-debug-data' ), true );
		codecept_debug( $debug_data );
		$this->assertArrayHasKey( 'logs', $debug_data, 'No logs found in debug data' );
		$this->assertArrayHasKey( $level, $debug_data['logs'], "No $level logs found in debug data" );

		foreach ( $debug_data['logs'][ $level ] as $log ) {
			if ( $log['message'] === $message ) {
				return;
			}
		}

		$this->fail( "Could not find $level log message: $message" );
	}
}
