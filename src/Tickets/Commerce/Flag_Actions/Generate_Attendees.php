<?php

namespace TEC\Tickets\Commerce\Flag_Actions;

use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Settings;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Status_Abstract;
use TEC\Tickets\Commerce\Status\Status_Handler;
use TEC\Tickets\Commerce\Status\Status_Interface;
use TEC\Tickets\Commerce\Ticket;
use Tribe__Utils__Array as Arr;

/**
 * Class Attendee_Generation
 *
 * @since   5.1.9
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
class Generate_Attendees extends Flag_Action_Abstract {
	/**
	 * {@inheritDoc}
	 */
	protected $flags = [
		'generate_attendees',
	];

	/**
	 * {@inheritDoc}
	 */
	protected $post_types = [
		Order::POSTTYPE
	];

	/**
	 * Hooks any WordPress filters related to this Flag Action.
	 *
	 * @since TBD
	 */
	public function hook() {
		parent::hook();

		$status = $this->get_status_when_to_trigger();
		add_filter( "tec_tickets_commerce_order_status_{$status->get_slug()}_get_flags", [ $this, 'modify_status_with_attendee_generation_flag' ], 10, 3 );
	}

	/**
	 * Returns the instance of the status we trigger attendee generation.
	 *
	 * @since TBD
	 *
	 * @return Status_Abstract
	 */
	public function get_status_when_to_trigger() {
		return tribe( Status_Handler::class )->get_inventory_decrease_status();
	}

	/**
	 * Include generate_attendee flag to either Completed or Pending
	 *
	 * @since TBD
	 *
	 * @param string[]        $flags  Which flags will trigger this action.
	 * @param \WP_Post        $post   Post object.
	 * @param Status_Abstract $status Instance of action flag we are triggering.
	 *
	 * @return string[]
	 */
	public function modify_status_with_attendee_generation_flag( $flags, $post, $status ) {
		$flags[] = 'generate_attendees';

		return $flags;
	}

	/**
	 * {@inheritDoc}
	 */
	public function handle( Status_Interface $new_status, $old_status, \WP_Post $post ) {
		// @todo we need an error handling piece here.
		if ( empty( $post->cart_items ) ) {
			return;
		}

		$default_currency = tribe_get_option( Settings::$option_currency_code, 'USD' );

		foreach ( $post->cart_items as $ticket_id => $item ) {
			$ticket = \Tribe__Tickets__Tickets::load_ticket_object( $item['ticket_id'] );
			if ( null === $ticket ) {
				continue;
			}

			$extra    = Arr::get( $item, 'extra', [] );
			$quantity = Arr::get( $item, 'quantity', 1 );

			// Skip generating for zero-ed items.
			if ( 0 >= $quantity ) {
				continue;
			}

			for ( $i = 0; $i < $quantity; $i ++ ) {
				$args = [
					'opt_out'       => Arr::get( $extra, 'optout' ),
					'price_paid'    => Arr::get( $item, 'price' ),
					'currency'      => Arr::get( $item, 'currency', $default_currency ),
					'security_code' => tribe( Module::class )->generate_security_code( time() . '-' . $i )
				];

				$attendee = tribe( Attendee::class )->create( $post, $ticket, $args );
			}
		}
	}
}