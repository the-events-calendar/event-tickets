<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Screen_Options
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Screen_Options {
	/**
	 * @var string The user option that will be used to store the number of orders per page to show.
	 */
	public static $per_page_user_option = 'event_tickets_paypal_orders_per_page';

	/**
	 * Filters the save operations of screen options to save the ones the class manages.
	 *
	 * @since TBD
	 *
	 * @param bool   $status Whether the option should be saved or not.
	 * @param string $option The user option slug.
	 * @param mixed  $value  The user option value.
	 *
	 * @return bool|mixed Either `false` if the user option is not one managed by the class or the user
	 *                    option value to save.
	 */
	public function filter_set_screen_options( $status, $option, $value ) {
		if ( $option === self::$per_page_user_option ) {
			return $value;
		}

		return $status;
	}
}
