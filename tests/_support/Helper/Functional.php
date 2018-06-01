<?php

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Module\WPDb;

class Functional extends \Codeception\Module {
	/**
	 * Sets an option in the tribe option row.
	 *
	 * @param string $key
	 * @param string|array $value
	 */
	public function setTribeOption( $key, $value ) {
		$option_name = 'tribe_events_calendar_options';
		/** @var WPDb $db */
		$db      = $this->getModule( 'WPDb' );
		$options = $db->grabOptionFromDatabase( $option_name );
		if ( empty( $options ) ) {
			$db->haveOptionInDatabase( $option_name, [ $key => $value ] );
		} else {
			$db->haveOptionInDatabase( $option_name, array_merge( $options, [ $key => $value ] ) );
		}
	}
}
