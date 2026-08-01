<?php

namespace Tribe\Tickets\Promoter\Triggers;

use TEC\Common\Firebase\JWT\JWT as TEC_JWT;
use TEC\Common\Firebase\JWT\Key as TEC_JWT_Key;
use Tribe__Promoter__Connector;
use WP_Post;

class DispatcherTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @test
	 */
	public function should_send_domain_in_trigger_payload(): void {
		$secret_key  = 'bob';
		$license_key = 'jan';

		// Read by Tribe__Promoter__Connector::get_secret_key() when the Dispatcher is built.
		update_option( 'tribe_promoter_auth_key', $secret_key );
		// Read by Tribe__Promoter__PUE::get_license_info() when the Dispatcher is built.
		update_option( 'pue_install_key_promoter', $license_key );

		$connector  = tribe( Tribe__Promoter__Connector::class );
		$pue        = tribe( 'promoter.pue' );
		$dispatcher = new Dispatcher( $connector, $pue );

		$event_id = self::factory()->post->create( [ 'post_type' => 'tribe_events' ] );

		$trigger = new Stub_Triggered( get_post( $event_id ), 'ticket_purchased' );

		$payload = [];

		add_filter( 'pre_http_request', function ( $response, $parsed_args, $url ) use ( $secret_key, &$payload ) {
			$token   = $parsed_args['body']['token'];
			$key     = new TEC_JWT_Key( $secret_key, 'HS256' );
			$payload = (array) TEC_JWT::decode( $token, $key );

			return [ 'headers' => '', 'body' => 'Hello World', 'response' => '', 'cookies' => '', 'filename' => '' ];
		}, 99, 3 );

		$dispatcher->trigger( $trigger );

		$this->assertArrayHasKey( 'domain', $payload );
		$this->assertEquals( 'wordpress.test', $payload['domain'] );
		$this->assertEquals( $license_key, $payload['license'] );
		$this->assertEquals( 'ticket_purchased', $payload['type'] );
		$this->assertEquals( $event_id, $payload['event']->id );
	}
}

/**
 * Minimal stand-in for a real trigger (e.g. Attendee_Trigger), used so this test does not have to
 * build a full ticket/attendee pipeline just to exercise Dispatcher::get_payload().
 */
class Stub_Triggered implements Contracts\Triggered {

	/**
	 * @var WP_Post
	 */
	private $post;

	/**
	 * @var string
	 */
	private $type;

	public function __construct( WP_Post $post, $type ) {
		$this->post = $post;
		$this->type = $type;
	}

	public function post() {
		return $this->post;
	}

	public function type() {
		return $this->type;
	}

	public function build() {
	}

	public function ticket() {
		$ticket       = new \stdClass();
		$ticket->ID   = 123;
		$ticket->name = 'I am going!';

		return $ticket;
	}

	public function attendee() {
		return new Stub_Attendee();
	}
}

class Stub_Attendee implements Contracts\Attendee_Model {

	public function build() {
	}

	public function required_fields() {
		return [];
	}

	public function id() {
		return 456;
	}

	public function email() {
		return 'attendee@example.com';
	}

	public function event_id() {
		return 0;
	}

	public function product_id() {
		return 0;
	}

	public function ticket_name() {
		return 'I am going!';
	}
}
