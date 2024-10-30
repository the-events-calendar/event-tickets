<?php

namespace Tribe\Tickets\ORM\Tickets;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe__Tickets__Ticket_Repository as Ticket_Repository;
use Tribe__Tickets__Data_API as Data_API;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker as Commerce_Ticket_Maker;

class FetchTest extends \Codeception\TestCase\WPTestCase {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Commerce_Ticket_Maker;

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();

		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', function () {
			return [ 'post' ];
		} );

		// Enable Tribe Commerce.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = tribe( 'tickets.commerce.paypal' )->plugin_name;

			return $modules;
		} );

		// Reset Data_API object so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );
	}

	public function _before() {
		tribe_events()->per_page( - 1 )->delete();
		tribe_tickets()->per_page( - 1 )->delete();
	}

	/**
	 * It should allow fetching tickets.
	 *
	 * @test
	 */
	public function should_allow_fetching_tickets() {
		/** @var Ticket_Repository $tickets */
		$tickets = tribe_tickets();

		$post_id = $this->factory->post->create();

		$paypal_ticket_ids = $this->create_many_paypal_tickets_basic( 5, $post_id );
		$rsvp_ticket_ids   = $this->create_many_rsvp_tickets( 5, $post_id );

		$ticket_ids = $tickets->get_ids();

		$this->assertEqualSets( array_merge( $paypal_ticket_ids, $rsvp_ticket_ids ), $ticket_ids );
	}

	/**
	 * It should allow fetching tickets from the rsvp context.
	 *
	 * @test
	 */
	public function should_allow_fetching_tickets_from_rsvp_context() {
		/** @var Ticket_Repository $tickets */
		$tickets = tribe_tickets( 'rsvp' );

		$post_id = $this->factory->post->create();

		$rsvp_ticket_ids   = $this->create_many_rsvp_tickets( 5, $post_id );
		$paypal_ticket_ids = $this->create_many_paypal_tickets_basic( 5, $post_id );

		$ticket_ids = $tickets->get_ids();

		$this->assertEqualSets( $rsvp_ticket_ids, $ticket_ids );
	}

	/**
	 * It should allow fetching tickets from the tribe-commerce context.
	 *
	 * @test
	 */
	public function should_allow_fetching_tickets_from_tribe_commerce_context() {
		/** @var Ticket_Repository $tickets */
		$tickets = tribe_tickets( 'tribe-commerce' );

		$post_id = $this->factory->post->create();

		$paypal_ticket_ids = $this->create_many_paypal_tickets_basic( 5, $post_id );
		$rsvp_ticket_ids   = $this->create_many_rsvp_tickets( 5, $post_id );

		$ticket_ids = $tickets->get_ids();

		$this->assertEqualSets( $paypal_ticket_ids, $ticket_ids );
	}

	/**
	 * It should allow fetching tickets by their ticket type
	 *
	 * @test
	 */
	public function should_allow_fetching_tickets_by_their_ticket_type(): void {
		$post_id = $this->factory->post->create();

		$default_ticket_1 = $this->create_tc_ticket( $post_id );
		update_post_meta( $default_ticket_1, '_type', 'default' );
		$default_ticket_2 = $this->create_tc_ticket( $post_id );
		update_post_meta( $default_ticket_2, '_type', 'default' );
		$pass_ticket_1 = $this->create_tc_ticket( $post_id );
		update_post_meta( $pass_ticket_1, '_type', 'pass' );
		$pass_ticket_2 = $this->create_tc_ticket( $post_id );
		update_post_meta( $pass_ticket_2, '_type', 'pass' );
		$no_type_ticket_1 = $this->create_tc_ticket( $post_id );
		delete_post_meta( $no_type_ticket_1, '_type' );
		$no_type_ticket_2 = $this->create_tc_ticket( $post_id );
		delete_post_meta( $no_type_ticket_2, '_type' );
		$member_type_ticket_1 = $this->create_tc_ticket( $post_id );
		update_post_meta( $member_type_ticket_1, '_type', 'member' );
		$member_type_ticket_2 = $this->create_tc_ticket( $post_id );
		update_post_meta( $member_type_ticket_2, '_type', 'member' );

		// Try and fetch all tickets for the event.
		$this->assertEqualSets(
			[
				$default_ticket_1,
				$default_ticket_2,
				$no_type_ticket_1,
				$no_type_ticket_2,
				$pass_ticket_1,
				$pass_ticket_2,
				$member_type_ticket_1,
				$member_type_ticket_2,
			],
			tribe_tickets()->where( 'event', $post_id )->get_ids()
		);

		// Fetch only default tickets.
		$this->assertEqualSets( [
			$default_ticket_1,
			$default_ticket_2,
			$no_type_ticket_1,
			$no_type_ticket_2,
		],
			tribe_tickets()->where( 'event', $post_id )->where( 'type', 'default' )->get_ids()
		);
		$this->assertEqualSets( [
			$default_ticket_1,
			$default_ticket_2,
			$no_type_ticket_1,
			$no_type_ticket_2,
		],
			tribe_tickets()->where( 'event', $post_id )->where( 'type__not_in', [ 'pass', 'member' ] )->get_ids()
		);

		// Fetch only passes.
		$this->assertEqualSets(
			[ $pass_ticket_1, $pass_ticket_2 ],
			tribe_tickets()->where( 'event', $post_id )->where( 'type', 'pass' )->get_ids()
		);
		$this->assertEqualSets(
			[ $pass_ticket_1, $pass_ticket_2 ],
			tribe_tickets()->where( 'event', $post_id )->where( 'type__not_in', [ 'default', 'member' ] )->get_ids()
		);

		// Fetch passes and member tickets.
		$this->assertEqualSets(
			[ $pass_ticket_1, $pass_ticket_2, $member_type_ticket_1, $member_type_ticket_2 ],
			tribe_tickets()->where( 'event', $post_id )->where( 'type', [
				'pass',
				'member'
			] )->get_ids()
		);
		$this->assertEqualSets(
			[ $pass_ticket_1, $pass_ticket_2, $member_type_ticket_1, $member_type_ticket_2 ],
			tribe_tickets()->where( 'event', $post_id )->where( 'type__not_in', 'default' )->get_ids()
		);

		// Fetch member and default type.
		$this->assertEqualSets(
			[
				$default_ticket_1,
				$default_ticket_2,
				$no_type_ticket_1,
				$no_type_ticket_2,
				$member_type_ticket_1,
				$member_type_ticket_2
			],
			tribe_tickets()->where( 'event', $post_id )->where( 'type', [
				'default',
				'member'
			] )->get_ids()
		);
		$this->assertEqualSets(
			[
				$default_ticket_1,
				$default_ticket_2,
				$no_type_ticket_1,
				$no_type_ticket_2,
				$member_type_ticket_1,
				$member_type_ticket_2
			],
			tribe_tickets()->where( 'event', $post_id )->where( 'type__not_in', 'pass' )->get_ids()
		);
	}
}
