<?php

namespace TEC\Tickets\Commerce\Gateways\Square\REST;

use Closure;
use Codeception\TestCase\WPTestCase;
use Faker\Factory as Faker_Factory;
use Faker\Generator as Faker_Generator;
use Generator;
use TEC\Tickets\Commerce\Gateways\Square\Webhooks;
use WP_REST_Request;
use WP_REST_Server;

class Webhook_Endpoint_Test extends WPTestCase {
	/**
	 * The faker instance.
	 *
	 * @var Faker_Generator
	 */
	private Faker_Generator $faker;

	/**
	 * The REST server as it was before the test, which `rest_do_request()` would otherwise leave built.
	 *
	 * @var WP_REST_Server|null
	 */
	private ?WP_REST_Server $rest_server_backup;

	/**
	 * Every `tribe_log` call made during the test, JSON encoded.
	 *
	 * @var string[]
	 */
	private array $logged = [];

	/**
	 * @before
	 */
	public function set_up_faker_and_logging(): void {
		$this->faker              = Faker_Factory::create();
		$this->rest_server_backup = $GLOBALS['wp_rest_server'] ?? null;

		/*
		 * The suite bootstrap turns any error log into an exception, and a refused webhook logs one on
		 * purpose. Lifted by name so the rest of $wp_filter stays untouched.
		 */
		remove_action( 'tribe_log', $GLOBALS['tec_tickets_square_log_guard'], 10 );

		$this->logged = [];
		add_action( 'tribe_log', [ $this, 'record_log' ], 10, 3 );
	}

	/**
	 * @after
	 */
	public function restore_state(): void {
		$GLOBALS['wp_rest_server'] = $this->rest_server_backup;

		remove_action( 'tribe_log', [ $this, 'record_log' ], 10 );
		add_action( 'tribe_log', $GLOBALS['tec_tickets_square_log_guard'], 10, 3 );
		delete_transient( Webhooks::OPTION_WEBHOOK_SECRET );
	}

	/**
	 * Records a log call.
	 *
	 * @param string $level   The log level.
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 */
	public function record_log( $level, $message, $context = [] ): void {
		$this->logged[] = wp_json_encode( [ $level, $message, $context ], JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Requests that must be refused, each built from the site's valid hashed key.
	 *
	 * @return Generator<string, array{0: Closure}>
	 */
	public function unauthenticated_request_provider(): Generator {
		yield 'no key, no hash' => [
			fn( string $valid_key, Faker_Generator $faker ): array => [ [], [] ],
		];

		yield 'no key, with hash' => [
			fn( string $valid_key, Faker_Generator $faker ): array => [ [], [ 'X-WhoDat-Hash' => $faker->md5() ] ],
		];

		yield 'empty key' => [
			fn( string $valid_key, Faker_Generator $faker ): array => [
				[ Webhooks::PARAM_WEBHOOK_KEY => '' ],
				[ 'X-WhoDat-Hash' => $faker->md5() ],
			],
		];

		yield 'key sent as an array' => [
			fn( string $valid_key, Faker_Generator $faker ): array => [
				[ Webhooks::PARAM_WEBHOOK_KEY => [ $valid_key ] ],
				[ 'X-WhoDat-Hash' => $faker->md5() ],
			],
		];

		yield 'valid key, no hash' => [
			fn( string $valid_key, Faker_Generator $faker ): array => [ [ Webhooks::PARAM_WEBHOOK_KEY => $valid_key ], [] ],
		];

		yield 'wrong key' => [
			fn( string $valid_key, Faker_Generator $faker ): array => [
				[ Webhooks::PARAM_WEBHOOK_KEY => $faker->password( 64, 64 ) ],
				[ 'X-WhoDat-Hash' => $faker->md5() ],
			],
		];
	}

	/**
	 * @test
	 * @dataProvider unauthenticated_request_provider
	 */
	public function it_should_refuse_a_request_without_valid_credentials( Closure $fixture ): void {
		[ $query, $headers ] = $fixture( $this->generate_valid_key(), $this->faker );

		$response = rest_do_request( $this->build_request( $query, $headers ) );

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'invalid_signature', $response->as_error()->get_error_code() );
	}

	/**
	 * @test
	 */
	public function it_should_accept_a_request_with_a_valid_key_and_hash(): void {
		$valid_key        = $this->generate_valid_key();
		$payload          = $this->build_payload();
		$notification_url = add_query_arg(
			Webhooks::PARAM_WEBHOOK_KEY,
			$valid_key,
			untrailingslashit( tribe( Webhooks::class )->get_webhook_endpoint_url() )
		);
		$whodat_signature = tec_tickets_tests_get_fake_merchant_data()['whodat_signature'];

		$request = $this->build_request(
			[ Webhooks::PARAM_WEBHOOK_KEY => $valid_key ],
			[ 'X-WhoDat-Hash' => md5( "{$notification_url}.{$payload}.{$whodat_signature}" ) ]
		);

		$this->assertTrue( tribe( Webhook_Endpoint::class )->has_permission( $request ) );
	}

	/**
	 * @test
	 * @dataProvider unauthenticated_request_provider
	 */
	public function it_should_not_log_the_credentials_or_body_of_a_refused_request( Closure $fixture ): void {
		$valid_key           = $this->generate_valid_key();
		[ $query, $headers ] = $fixture( $valid_key, $this->faker );
		$buyer_email         = $this->faker->unique()->safeEmail();

		$response = rest_do_request( $this->build_request( $query, $headers, $this->build_payload( $buyer_email ) ) );

		$this->assertSame( 401, $response->get_status() );
		$this->assertNotEmpty( $this->logged, 'The refusal should still be logged.' );

		$log = implode( "\n", $this->logged );
		$this->assertStringNotContainsString( $valid_key, $log );
		$this->assertStringNotContainsString( $buyer_email, $log );

		if ( isset( $headers['X-WhoDat-Hash'] ) ) {
			$this->assertStringNotContainsString( $headers['X-WhoDat-Hash'], $log );
		}
	}

	/**
	 * @test
	 */
	public function it_should_log_an_accepted_webhook_by_type_and_id_only(): void {
		$valid_key        = $this->generate_valid_key();
		$buyer_email      = $this->faker->unique()->safeEmail();
		$event_id         = $this->faker->uuid();
		$payload          = wp_json_encode(
			[
				'type'     => 'payment.updated',
				'event_id' => $event_id,
				'env'      => 'production',
				'data'     => [ 'object' => [ 'payment' => [ 'buyer_email_address' => $buyer_email ] ] ],
			]
		);
		$notification_url = add_query_arg(
			Webhooks::PARAM_WEBHOOK_KEY,
			$valid_key,
			untrailingslashit( tribe( Webhooks::class )->get_webhook_endpoint_url() )
		);
		$whodat_signature = tec_tickets_tests_get_fake_merchant_data()['whodat_signature'];

		$response = rest_do_request(
			$this->build_request(
				[ Webhooks::PARAM_WEBHOOK_KEY => $valid_key ],
				[ 'X-WhoDat-Hash' => md5( "{$notification_url}.{$payload}.{$whodat_signature}" ) ],
				$payload
			)
		);

		$this->assertSame( 200, $response->get_status() );

		$log = implode( "\n", $this->logged );
		$this->assertStringContainsString( $event_id, $log );
		$this->assertStringNotContainsString( $buyer_email, $log );
	}

	/**
	 * Webhook events that reach a warning, each carrying the buyer's email.
	 *
	 * @return Generator<string, array{0: Closure}>
	 */
	public function warning_event_provider(): Generator {
		yield 'unsupported event type' => [
			fn( string $email ): array => [
				'type'   => 'customer.updated',
				'object' => [ 'customer' => [ 'email_address' => $email ] ],
			],
		];

		yield 'order update for an unknown order' => [
			fn( string $email ): array => [
				'type'   => Events::ORDER_UPDATED,
				'data'   => [
					'type'   => 'order_updated',
					'object' => [
						'order_updated' => [
							'order_id'    => 'unknown-order',
							'location_id' => 'li-8PoFNX4o9XOz9vMYOrZ6vA',
							'state'       => 'OPEN',
							'note'        => $email,
						],
					],
				],
			],
		];

		yield 'refund for an unknown order' => [
			fn( string $email ): array => [
				'type' => Events::REFUND_UPDATED,
				'data' => [
					'object' => [
						'refund' => [
							'id'         => 'refund-id',
							'order_id'   => 'unknown-order',
							'payment_id' => 'unknown-payment',
							'status'     => 'COMPLETED',
							'reason'     => $email,
						],
					],
				],
			],
		];

		yield 'payment with no order' => [
			fn( string $email ): array => [
				'type' => Events::PAYMENT_UPDATED,
				'data' => [ 'object' => [ 'payment' => [ 'buyer_email_address' => $email ] ] ],
			],
		];
	}

	/**
	 * @test
	 * @dataProvider warning_event_provider
	 */
	public function it_should_not_log_the_event_data_of_a_webhook_it_cannot_match( Closure $fixture ): void {
		$buyer_email = $this->faker->unique()->safeEmail();
		$event_data  = array_merge(
			[
				'event_id' => $this->faker->uuid(),
				'env'      => 'production',
			],
			$fixture( $buyer_email )
		);

		tribe( Webhook_Endpoint::class )->process_webhook_event( $event_data );

		$this->assertNotEmpty( $this->logged, 'The unmatched event should still be logged.' );
		$this->assertStringNotContainsString( $buyer_email, implode( "\n", $this->logged ) );
	}

	/**
	 * Generates the site's webhook secret and returns its hash, the key Square sends back.
	 *
	 * @return string The hashed webhook key.
	 */
	private function generate_valid_key(): string {
		$webhooks = tribe( Webhooks::class );
		$webhooks->get_webhook_secret( false, true );

		return $webhooks->get_webhook_secret();
	}

	/**
	 * Builds the JSON body of a Square `payment.updated` notification.
	 *
	 * @param string $buyer_email The buyer's email carried by the payment, if any.
	 *
	 * @return string The JSON payload.
	 */
	private function build_payload( string $buyer_email = '' ): string {
		$payload = [ 'type' => 'payment.updated' ];

		if ( $buyer_email ) {
			$payload['data']['object']['payment']['buyer_email_address'] = $buyer_email;
		}

		return wp_json_encode( $payload );
	}

	/**
	 * Builds a POST request to the Square webhook route.
	 *
	 * @param array<string, string|string[]> $query   The query parameters.
	 * @param array<string, string>          $headers The request headers.
	 * @param string|null                    $body    The request body; a bare notification when null.
	 *
	 * @return WP_REST_Request The request.
	 */
	private function build_request( array $query, array $headers, ?string $body = null ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/commerce/square/webhooks' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_query_params( $query );
		$request->set_body( $body ?? $this->build_payload() );

		foreach ( $headers as $name => $value ) {
			$request->set_header( $name, $value );
		}

		return $request;
	}
}
