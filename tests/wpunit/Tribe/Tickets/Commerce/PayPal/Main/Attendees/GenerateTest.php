<?php

namespace Tribe\Tickets\Commerce\PayPal\Main\Attendees;

use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe__Tickets__Data_API as Data_API;
use WP_Post;

/**
 * Class GenerateTest
 *
 * @package Tribe\Tickets\Commerce\PayPal\Main\Attendees
 * @group orm-create-update
 */
class GenerateTest extends \Codeception\TestCase\WPTestCase {

	use PayPal_Ticket_Maker;

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

	/**
	 * It should allow creating an attendee without any data.
	 *
	 * @test
	 */
	public function should_allow_creating_an_attendee_without_any_data() {
		/** @var \Tribe__Tickets__Commerce__PayPal__Main $provider */
		$provider = tribe( 'tickets.commerce.paypal' );

		$user_data = [
			'first_name' => 'Firsttest',
			'last_name'  => 'Lasttest',
			'user_email' => 'test@email.com',
		];

		$post_id   = $this->factory->post->create();
		$ticket_id = $this->create_paypal_ticket( $post_id );
		$ticket    = $provider->get_ticket( $post_id, $ticket_id );
		$user_id   = $this->factory->user->create( $user_data );

		$attendee_data = [];

		/** @var \Tribe__Tickets__Commerce__PayPal__Gateway $gateway */
		$gateway = tribe( 'tickets.commerce.paypal.gateway' );

		$transaction_data = [
			'custom'      => wp_json_encode( [
				'user_id' => $user_id,
				'oo' => [
					// Optout.
					'ticket_' . $ticket_id => 1,
				],
			] ),
			'txn_id'      => 'tpp-txn-id-test',
			'first_name'  => $user_data['first_name'],
			'last_name'   => $user_data['last_name'],
			'payer_email' => $user_data['user_email'],
			'items' => [
				[
					'ticket'    => $ticket,
					'ticket_id' => $ticket_id,
					'post_id'   => $post_id,
					'quantity'  => 1,
				],
			],
		];

		$gateway->set_transaction_data( $transaction_data );

		add_filter( 'tribe_exit', static function() {
			return static function() {
				// Do nothing.
			};
		} );

		$provider->generate_tickets( 'completed', false );

		$attendees = tribe_attendees( 'tribe-commerce' );

		$new_attendee = $attendees->first();

		$this->assertInstanceOf( WP_Post::class, $new_attendee );

		$meta = get_post_meta( $new_attendee->ID );

		$this->assertEquals( $transaction_data['first_name'] . ' ' . $transaction_data['last_name'] . ' | 1', $new_attendee->post_title );
		$this->assertEquals( $provider::ATTENDEE_OBJECT, $new_attendee->post_type );
		$this->assertEquals( 'publish', $new_attendee->post_status );
		$this->assertEquals( 0, $new_attendee->post_parent );

		// Confirm the attendee meta was set as intended.
		$this->assertEquals( $transaction_data['first_name'] . ' ' . $transaction_data['last_name'], get_post_meta( $new_attendee->ID, $provider->full_name, true ) );
		$this->assertEquals( $transaction_data['payer_email'], get_post_meta( $new_attendee->ID, $provider->email, true ) );
		$this->assertEquals( $ticket_id, get_post_meta( $new_attendee->ID, $provider::ATTENDEE_PRODUCT_KEY, true ) );
		$this->assertEquals( $post_id, get_post_meta( $new_attendee->ID, $provider::ATTENDEE_EVENT_KEY, true ) );
		$this->assertEquals( 'completed', get_post_meta( $new_attendee->ID, $provider->attendee_tpp_key, true ) );
		$this->assertEquals( 1, (int) get_post_meta( $new_attendee->ID, $provider->attendee_optout_key, true ) );
		$this->assertEquals( $transaction_data['txn_id'], get_post_meta( $new_attendee->ID, $provider->order_key, true ) );
		$this->assertEquals( $ticket->price, (float) get_post_meta( $new_attendee->ID, '_paid_price', true ) );
		$this->assertEquals( '$', get_post_meta( $new_attendee->ID, '_price_currency_symbol', true ) );
		$this->assertEquals( 1, (int) get_post_meta( $new_attendee->ID, $provider->attendee_ticket_sent, true ) );
		$this->assertEquals( $user_id, (int) get_post_meta( $new_attendee->ID, $provider->attendee_user_id, true ) );
		$this->assertNotEmpty( get_post_meta( $new_attendee->ID, $provider->security_code, true ) );
		$this->assertNotEmpty( get_post_meta( $new_attendee->ID, $provider->attendee_activity_log, true ) );
		$this->assertCount( 13, $meta, 'There appears to be untested meta on this attendee, please add them to the test: ' . var_export( $meta, true ) );

		// Confirm the ticket sales/stock is updated.
		$this->assertEquals( 1, (int) get_post_meta( $ticket_id, 'total_sales', true ) );
	}
}
