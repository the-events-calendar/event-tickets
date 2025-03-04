<?php
/** @noinspection PhpDocMissingThrowsInspection */

namespace Tribe\Tickets\Test\Commerce;

use Faker\Factory;
use ReflectionClass;
use RuntimeException;
use Tribe__Tickets__Commerce__Currency;
use Tribe__Tickets__Commerce__PayPal__Main;
use Tribe__Tickets__RSVP;
use Tribe__Tickets__Tickets;
use Tribe__Tickets_Plus__Commerce__WooCommerce__Main;
use WP_Error;
use WP_Post;
use WP_User;

trait Attendee_Maker {

	protected static $generated = 0;

	/**
	 * Generates a number of attendees for a ticket.
	 *
	 * @param int   $count
	 * @param int   $ticket_id
	 * @param int   $post_id
	 * @param array $overrides See single generation method code for overrides possibilities.
	 *
	 * @return array An array of generated attendees post IDs.
	 */
	protected function create_many_attendees_for_ticket( int $count, int $ticket_id, int $post_id, array $overrides = [] ): array {
		// If not set let's make sure to set an order ID to ensure all attendees will be part of the same order.
		if ( ! isset( $overrides['order_id'] ) ) {
			$overrides['order_id'] = md5( uniqid() );
		}

		$attendees = [];

		for ( $i = 0; $i < $count; $i ++ ) {
			$attendees[] = $this->create_attendee_for_ticket( $ticket_id, $post_id, $overrides );
		}

		return $attendees;
	}

	/**
	 * Generates an attendee for a ticket.
	 *
	 * @todo Adding RSVP tickets should update 'total_sales' count without having to hard-code it here and in other situations
	 *
	 * @param int   $ticket_id
	 * @param int   $post_id
	 * @param array $overrides See code for overrides possibilities.
	 *
	 * @return int The generated attendee
	 */
	protected function create_attendee_for_ticket( int $ticket_id, int $post_id, array $overrides = [] ): int {
		/** @var Tribe__Tickets__Tickets $provider */
		$provider            = tribe_tickets_get_ticket_provider( $ticket_id );
		$provider_reflection = new ReflectionClass( $provider );

		$post_key = $provider_reflection->getConstant( 'ATTENDEE_EVENT_KEY' );

		$product_key     = ! empty( $provider->attendee_product_key )
			? $provider->attendee_product_key
			: $provider_reflection->getConstant( 'ATTENDEE_PRODUCT_KEY' );
		$optout_key      = ! empty( $provider->attendee_optout_key )
			? $provider->attendee_optout_key
			: $provider_reflection->getConstant( 'ATTENDEE_OPTOUT_KEY' );
		$user_id_key     = ! empty( $provider->attendee_user_id )
			? $provider->attendee_user_id
			: $provider_reflection->getConstant( 'ATTENDEE_USER_ID' );
		$ticket_sent_key = ! empty( $provider->attendee_ticket_sent )
			? $provider->attendee_ticket_sent
			: $provider_reflection->getConstant( 'ATTENDEE_TICKET_SENT' );

		$default_sku = $provider instanceof Tribe__Tickets__RSVP ? '' : 'test-attnd' . self::$generated;

		$user_id = absint( $overrides['user_id'] ?? 0 );

		$user_info = $this->get_data_for_name_props( $user_id, $overrides );

		$meta = [
			$provider->checkin_key             => (bool) ( $overrides['checkin'] ?? false ),
			"{$provider->checkin_key}_details" => $overrides['checkin_details'] ?? false,
			$provider->security_code           => $overrides['security_code'] ?? md5( uniqid() ),
			$post_key                          => $post_id,
			$product_key                       => $ticket_id,
			$optout_key                        => $overrides['optout'] ?? false,
			$user_id_key                       => $user_id,
			$ticket_sent_key                   => $overrides['ticket_sent'] ?? true,
			'_sku'                             => $overrides['sku'] ?? $default_sku,
		];

		foreach ( $user_info as $key => $value ) {
			if ( property_exists( $provider, $key ) ) {
				$meta[ $provider->{$key} ] = $value;
			}
		}

		if ( $provider instanceof Tribe__Tickets__RSVP ) {
			$meta[ Tribe__Tickets__RSVP::ATTENDEE_RSVP_KEY ] = $overrides['rsvp_status'] ?? 'yes';
		}

		if ( $provider instanceof Tribe__Tickets__Commerce__PayPal__Main ) {
			$meta['_tribe_tpp_status'] = $overrides['order_status'] ?? 'completed';
		}

		if ( $provider instanceof Tribe__Tickets_Plus__Commerce__WooCommerce__Main ) {
			if ( ! isset( $overrides['order_id'] ) ) {
				throw new RuntimeException(
					'WooCommerce tickets attendees require an `order_id` parameter in the `overrides` array.'
					. "\nYou can generate orders using the Order_Maker trait."
					. "\nKeep in mind that generating Orders will create the Attendees too."
				);
			}

			$meta[ $provider->attendee_order_key ] = $overrides['order_id'];
			$meta['_billing_first_name']           = $user_info['first_name'];
			$meta['_billing_last_name']            = $user_info['last_name'];
			$meta['_billing_email']                = $user_info['email'];
		}

		if ( ! isset( $meta['_paid_price'] ) ) {
			$meta['_paid_price'] = (int) get_post_meta( $ticket_id, '_price', true );
		}
		if ( ! isset( $meta['_price_currency_symbol'] ) ) {
			/** @var Tribe__Tickets__Commerce__Currency $currency */
			$currency                       = tribe( 'tickets.commerce.currency' );
			$meta['_price_currency_symbol'] = $currency->get_currency_symbol( $ticket_id, true );
		}

		$explicit_keys = [
			'checkin',
			'checkin_details',
			'security_code',
			'optout',
			'user_id',
			'ticket_sent',
			'full_name',
			'email',
			'rsvp_status',
			'order_id',
			'sku',
		];

		$meta_input_overrides = array_diff_key( $overrides, array_combine( $explicit_keys, $explicit_keys ) );

		$postarr = [
			'post_title'  => $overrides['post_title'] ?? 'Generated Attendee ' . self::$generated,
			'post_type'   => $provider_reflection->getConstant( 'ATTENDEE_OBJECT' ),
			'post_status' => $overrides['post_status'] ?? 'publish',
			'meta_input'  => array_merge( $meta, $meta_input_overrides ),
		];

		$attendee_id = wp_insert_post( $postarr );

		self::$generated ++;

		if ( empty( $attendee_id ) || $attendee_id instanceof WP_Error ) {
			throw new RuntimeException( 'There was an error while generating the attendee, data: ' . json_encode( $postarr, JSON_PRETTY_PRINT ) );
		}

		$order_key = ! empty( $provider->order_key )
			? $provider->order_key
			: '';

		if (
			empty( $order_key )
			&& ! empty( $provider->attendee_order_key )
		) {
			$order_key = $provider->attendee_order_key;
		}

		if (
			empty( $order_key )
			&& ! empty( $provider_reflection->getConstant( 'ATTENDEE_ORDER_KEY' ) )
		) {
			$order_key = $provider_reflection->getConstant( 'ATTENDEE_ORDER_KEY' );
		}

		if ( empty( $order_key ) ) {
			throw new RuntimeException( 'There was an error while generating the attendee, lacking an Order Key, data: ' . json_encode( $postarr, JSON_PRETTY_PRINT ) );
		}

		$order = $provider instanceof Tribe__Tickets__RSVP
			? $attendee_id
			: $overrides['order_id'] ?? md5( uniqid() );

		update_post_meta( $attendee_id, $order_key, $order );

		return $attendee_id;
	}

	/**
	 * Sets the optout option on a group of attendees.
	 *
	 * @param array $attendees
	 * @param bool  $optout
	 */
	protected function optout_attendees( array $attendees, bool $optout = true ) {
		foreach ( $attendees as $attendee ) {
			if ( is_array( $attendee ) ) {
				if ( ! isset( $attendee['attendee_id'] ) ) {
					throw new RuntimeException( 'Attendee information does not contain the `attendee_id` entry' );
				}
				$attendee = $attendee['attendee_id'];
			}
			$this->optout_attendee( $attendee, $optout );
		}
	}

	/**
	 * Sets the optout option on an attendee.
	 *
	 * @param      int $attendee_id
	 * @param bool     $optout
	 */
	protected function optout_attendee( int $attendee_id, $optout = true ) {
		$attendee_post = get_post( $attendee_id );

		if ( ! $attendee_post instanceof WP_Post ) {
			throw new RuntimeException( "Attendee {$attendee_id} is not a valid Attendee post" );
		}

		$provider = tribe_tickets_get_ticket_provider( $attendee_id );

		if ( ! $provider instanceof Tribe__Tickets__Tickets ) {
			throw new RuntimeException( "Provider for attendee {$attendee_id} could not be found" );
		}

		$optout = filter_var( $optout, FILTER_VALIDATE_BOOLEAN );

		$optout_string = $optout ? 'yes' : 'no';

		update_post_meta( $attendee_post->ID, $provider->attendee_optout_key, $optout_string );
	}

	/**
	 * Given a User ID, get the user's data, possibly updating existing User.
	 *
	 * If user does not exist, generate faked info (allowed to be manually overridden).
	 * If user exists, get existing user data and fake (allowing overrides for) any data points that are missing,
	 * then update the existing user.
	 *
	 * @param int $user_id
	 *
	 * @return array The keys should match the Ticket Provider property.
	 *               All values are expected unless overridden for non-existing user to empty string.
	 */
	private function get_data_for_name_props( int $user_id = 0, array $overrides = [] ) {
		$faker = Factory::create();

		$result = [
			'first_name' => $overrides['first_name'] ?? $faker->firstName(),
			'last_name'  => $overrides['last_name'] ?? $faker->lastName(),
			'full_name'  => $overrides['full_name'] ?? $faker->name(),
			'email'      => $overrides['email'] ?? $faker->email(),
		];

		$user = get_userdata( $user_id );

		if ( $user instanceof WP_User ) {
			// First Name
			$first = get_user_meta( $user_id, 'first_name', true );
			if ( $first ) {
				$result['first_name'] = $first;
			} else {
				update_user_meta( $user_id, 'first_name', $result['first_name'] );
			}

			// Last Name
			$last = get_user_meta( $user_id, 'last_name', true );
			if ( $last ) {
				$result['last_name'] = $last;
			} else {
				update_user_meta( $user_id, 'last_name', $result['last_name'] );
			}

			// Full Name when user exists is just First Name + Last Name, since we know both exist
			$result['full_name'] = $result['first_name'] . ' ' . $result['last_name'];

			// Email
			$email = get_user_meta( $user_id, 'email', true );
			if ( $email ) {
				$result['email'] = $email;
			} else {
				update_user_meta( $user_id, 'email', $result['email'] );
			}
		}

		return $result;
	}
}
